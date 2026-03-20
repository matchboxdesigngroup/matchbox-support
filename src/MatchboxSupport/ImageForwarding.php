<?php
/**
 * Rewrites attachment image URLs to a forward domain (e.g. production) on non-production sites.
 *
 * @package MatchboxSupport
 */

namespace MatchboxSupport;

defined( 'ABSPATH' ) || exit;

/**
 * Image URL forwarding for local/staging workflows.
 *
 * @since TBD
 */
class ImageForwarding {

	/**
	 * Singleton instance.
	 *
	 * @var ?ImageForwarding
	 */
	private static ?ImageForwarding $instance = null;

	/**
	 * Cached forward base (trailingslashit URL or empty).
	 *
	 * @var ?string
	 */
	private ?string $forward_base_cache = null;

	/**
	 * Cached local URL prefixes for replacement.
	 *
	 * @var list<string>|null
	 */
	private ?array $local_prefixes_cache = null;

	/**
	 * Get singleton instance.
	 *
	 * @return ImageForwarding
	 */
	public static function instance(): ImageForwarding {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Register WordPress filters.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		add_filter( 'wp_get_attachment_image_src', [ $this, 'filter_attachment_image_src' ], 10, 4 );
		add_filter( 'wp_calculate_image_srcset', [ $this, 'filter_image_srcset' ], 10, 5 );
		add_filter( 'wp_get_attachment_url', [ $this, 'filter_attachment_url' ], 10, 2 );
		add_filter( 'wp_prepare_attachment_for_js', [ $this, 'filter_prepare_attachment_for_js' ], 10, 3 );
		add_filter( 'rest_prepare_attachment', [ $this, 'filter_rest_prepare_attachment' ], 10, 3 );
		add_filter( 'wp_get_attachment_image_attributes', [ $this, 'filter_attachment_image_attributes' ], 10, 3 );
	}

	/**
	 * Whether forwarding should run for this request.
	 *
	 * @return bool
	 */
	private function is_active(): bool {
		if ( ! apply_filters( 'matchbox_image_forward_allow', true ) ) {
			return false;
		}

		$scope = get_option( 'matchbox_image_forward_scope', 'non_production' );
		if ( 'off' === $scope ) {
			return false;
		}

		if ( '' === $this->get_forward_base() ) {
			return false;
		}

		if ( 'non_production' === $scope ) {
			$env = wp_get_environment_type();
			if ( 'production' === $env ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Resolved forward base URL, trailing slash, or empty if disabled.
	 *
	 * @return string
	 */
	private function get_forward_base(): string {
		if ( null !== $this->forward_base_cache ) {
			return $this->forward_base_cache;
		}

		if ( defined( 'MATCHBOX_IMAGE_FORWARD_URL' ) && is_string( MATCHBOX_IMAGE_FORWARD_URL ) && '' !== trim( MATCHBOX_IMAGE_FORWARD_URL ) ) {
			$this->forward_base_cache = trailingslashit( esc_url_raw( trim( MATCHBOX_IMAGE_FORWARD_URL ) ) );
			return $this->forward_base_cache;
		}

		$opt = get_option( 'matchbox_image_forward_base_url', '' );
		if ( ! is_string( $opt ) || '' === trim( $opt ) ) {
			$this->forward_base_cache = '';
			return $this->forward_base_cache;
		}

		$this->forward_base_cache = trailingslashit( esc_url_raw( trim( $opt ) ) );
		return $this->forward_base_cache;
	}

	/**
	 * Local site URL prefixes to replace (home and site when they differ).
	 *
	 * @return list<string>
	 */
	private function get_local_prefixes(): array {
		if ( null !== $this->local_prefixes_cache ) {
			return $this->local_prefixes_cache;
		}

		$home = untrailingslashit( home_url() );
		$site = untrailingslashit( site_url() );
		$this->local_prefixes_cache = array_values( array_unique( array_filter( [ $home, $site ] ) ) );

		return $this->local_prefixes_cache;
	}

	/**
	 * Replace local URL prefix with forward base.
	 *
	 * @param string $url Full URL.
	 * @return string
	 */
	private function replace_forward_url( string $url ): string {
		if ( '' === $url || ! $this->is_active() ) {
			return $url;
		}

		$forward = untrailingslashit( $this->get_forward_base() );
		if ( '' === $forward ) {
			return $url;
		}

		foreach ( $this->get_local_prefixes() as $local ) {
			if ( str_starts_with( $url, $local ) ) {
				return $forward . substr( $url, strlen( $local ) );
			}
		}

		return $url;
	}

	/**
	 * Replace URLs in a srcset attribute value.
	 *
	 * @param string $srcset srcset attribute string.
	 * @return string
	 */
	private function replace_srcset_string( string $srcset ): string {
		if ( '' === $srcset || ! $this->is_active() ) {
			return $srcset;
		}

		$parts = array_map( 'trim', explode( ',', $srcset ) );
		$out   = [];

		foreach ( $parts as $part ) {
			if ( '' === $part ) {
				continue;
			}
			$pieces = preg_split( '/\s+/', $part, 2 );
			if ( ! empty( $pieces[0] ) ) {
				$pieces[0] = $this->replace_forward_url( $pieces[0] );
				$out[]     = isset( $pieces[1] ) ? $pieces[0] . ' ' . $pieces[1] : $pieces[0];
			}
		}

		return implode( ', ', $out );
	}

	/**
	 * Deep-replace URL-like keys in nested arrays (REST / media).
	 *
	 * @param mixed $data Response fragment.
	 * @return mixed
	 */
	private function deep_replace_url_keys( $data ) {
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				if ( is_string( $value ) && ( 'url' === $key || 'source_url' === $key ) ) {
					$data[ $key ] = $this->replace_forward_url( $value );
				} elseif ( is_array( $value ) ) {
					$data[ $key ] = $this->deep_replace_url_keys( $value );
				}
			}
		}

		return $data;
	}

	/**
	 * Filter: wp_get_attachment_image_src.
	 *
	 * @param array|false  $image         Image data or false.
	 * @param int          $attachment_id Attachment ID.
	 * @param string|array $size          Image size.
	 * @param bool         $icon          Icon flag.
	 *
	 * @return array|false
	 */
	public function filter_attachment_image_src( $image, $attachment_id, $size, $icon ) {
		unset( $attachment_id, $size, $icon );

		if ( ! $this->is_active() || ! is_array( $image ) || ! isset( $image[0] ) || ! is_string( $image[0] ) ) {
			return $image;
		}

		$image[0] = $this->replace_forward_url( $image[0] );

		return $image;
	}

	/**
	 * Filter: wp_calculate_image_srcset.
	 *
	 * @param array  $sources       Srcset sources.
	 * @param array  $size_array    Size array.
	 * @param string $image_src     Image src.
	 * @param array  $image_meta    Image meta.
	 * @param int    $attachment_id Attachment ID.
	 *
	 * @return array
	 */
	public function filter_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		unset( $size_array, $image_src, $image_meta, $attachment_id );

		if ( ! $this->is_active() || ! is_array( $sources ) ) {
			return $sources;
		}

		foreach ( $sources as $w => $source ) {
			if ( isset( $source['url'] ) && is_string( $source['url'] ) ) {
				$sources[ $w ]['url'] = $this->replace_forward_url( $source['url'] );
			}
		}

		return $sources;
	}

	/**
	 * Filter: wp_get_attachment_url.
	 *
	 * @param string $url            Attachment URL.
	 * @param int    $attachment_id Attachment post ID.
	 * @return string
	 */
	public function filter_attachment_url( $url, $attachment_id ) {
		unset( $attachment_id );

		if ( ! is_string( $url ) || '' === $url ) {
			return $url;
		}

		return $this->replace_forward_url( $url );
	}

	/**
	 * Filter: wp_prepare_attachment_for_js.
	 *
	 * @param array      $response   Attachment data for JS.
	 * @param \WP_Post   $attachment Attachment post object.
	 * @param array|bool $meta       Meta.
	 * @return array
	 */
	public function filter_prepare_attachment_for_js( $response, $attachment, $meta ) {
		unset( $attachment, $meta );

		if ( ! $this->is_active() || ! is_array( $response ) ) {
			return $response;
		}

		if ( isset( $response['url'] ) && is_string( $response['url'] ) ) {
			$response['url'] = $this->replace_forward_url( $response['url'] );
		}

		if ( ! empty( $response['sizes'] ) && is_array( $response['sizes'] ) ) {
			foreach ( $response['sizes'] as $size => $size_data ) {
				if ( isset( $size_data['url'] ) && is_string( $size_data['url'] ) ) {
					$response['sizes'][ $size ]['url'] = $this->replace_forward_url( $size_data['url'] );
				}
			}
		}

		return $response;
	}

	/**
	 * Filter: rest_prepare_attachment.
	 *
	 * @param \WP_REST_Response $response Response object.
	 * @param \WP_Post          $post     Attachment post.
	 * @param \WP_REST_Request  $request Request.
	 * @return \WP_REST_Response
	 */
	public function filter_rest_prepare_attachment( $response, $post, $request ) {
		unset( $post, $request );

		if ( ! $this->is_active() || ! $response instanceof \WP_REST_Response ) {
			return $response;
		}

		$data = $response->get_data();
		if ( ! is_array( $data ) ) {
			return $response;
		}

		$response->set_data( $this->deep_replace_url_keys( $data ) );

		return $response;
	}

	/**
	 * Filter: wp_get_attachment_image_attributes.
	 *
	 * @param array        $attr       Img attributes.
	 * @param \WP_Post     $attachment Attachment post.
	 * @param string|array $size       Image size.
	 * @return array
	 */
	public function filter_attachment_image_attributes( $attr, $attachment, $size ) {
		unset( $attachment, $size );

		if ( ! $this->is_active() || ! is_array( $attr ) ) {
			return $attr;
		}

		if ( isset( $attr['src'] ) && is_string( $attr['src'] ) ) {
			$attr['src'] = $this->replace_forward_url( $attr['src'] );
		}

		if ( isset( $attr['srcset'] ) && is_string( $attr['srcset'] ) ) {
			$attr['srcset'] = $this->replace_srcset_string( $attr['srcset'] );
		}

		return $attr;
	}
}
