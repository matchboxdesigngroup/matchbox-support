/**
 * Registers a new block provided a unique name and an object defining its behavior.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
import { registerBlockType } from "@wordpress/blocks";

/**
 * Internal dependencies
 */
import ToolbarToggle from "./toolbar-toggle";
import Edit from "./edit";
import Save from "./save";
import metadata from "./block.json";

/**
 * Every block starts by registering a new block type definition.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
registerBlockType(metadata.name, {
	/**
	 * @see ./edit.js
	 */
	edit: Edit,

	/**
	 * @see ./save.js
	 */
	save: Save,

	/**
	 * Block Icon
	 *
	 * Font Awesome Pro 6.7.2 by @fontawesome - https://fontawesome.com
	 * License - https://fontawesome.com/license (Commercial License)
	 * Copyright 2025 Fonticons, Inc.
	 */
	icon: {
		src: (
			<svg
				width="30"
				height="24"
				viewBox="0 0 30 24"
				xmlns="http://www.w3.org/2000/svg"
			>
				<path d="M5.25 0.875H8.5V2.5H11.75V5.75H18.25V2.5H21.5V0.875H24.75V4.125H21.5V5.75V7.375H24.75V10.625H26.375V5.75H29.625V13.875H26.375V18.75H23.125V23.625H19.875H16.625V20.375H19.875V18.75H10.125V20.375H13.375V23.625H10.125H6.875V18.75H3.625V13.875H0.375V5.75H3.625V10.625H5.25V7.375H8.5V5.75V4.125H5.25V0.875ZM8.5 15.5H11.75V10.625H8.5V15.5ZM18.25 15.5H21.5V10.625H18.25V15.5Z"></path>
			</svg>
		),
	},
});

ToolbarToggle();
