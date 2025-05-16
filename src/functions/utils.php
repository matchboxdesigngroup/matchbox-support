<?php

if ( ! function_exists( 'dd' ) ) {
	/**
	 * Dump and die function for debugging.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $data Data to dump.
	 *
	 * @return void
	 */
	function dd( $data ) {
		ini_set( 'highlight.comment', '#969896; font-style: italic' );
		ini_set( 'highlight.default', '#FFFFFF' );
		ini_set( 'highlight.html', '#D16568' );
		ini_set( 'highlight.keyword', '#7FA3BC; font-weight: bold' );
		ini_set( 'highlight.string', '#F2C47E' );
		$output = highlight_string( "<?php\n\n" . var_export( $data, true ), true );
		echo "<div style=\"background-color: #1C1E21; padding: 1rem\">{$output}</div>";
		die();
	}
}

if ( ! function_exists( 'format_phone_for_tel_link' ) ) {
	/**
	 * Format a phone number for a tel link.
	 *
	 * Example: format_phone_for_tel_link("(555) 123-4567")
	 * Returns: tel:+15551234567
	 *
	 * @since 1.0.0
	 *
	 * @param string $phone Phone number to format.
	 *
	 * @return string Formatted phone number for tel link.
	 */
	function format_phone_for_tel_link( $phone ) {
		// Remove all non-numeric characters.
		$phone = preg_replace( '/[^0-9]/', '', $phone );

		// Check if phone number length is valid (10 digits for US numbers).
		if ( strlen( $phone ) === 10 ) {
			// Format the number as (XXX) XXX-XXXX.
			$formatted = 'tel:+1' . $phone;
			return $formatted;
		} elseif ( strlen( $phone ) === 11 && substr( $phone, 0, 1 ) === '1' ) {
			// If the number is 11 digits and starts with '1', assume it's a US number with country code.
			$formatted = 'tel:+' . $phone;
			return $formatted;
		} else {
			// Return the original phone number if it doesn't match expected lengths.
			return $phone;
		}
	}
}
