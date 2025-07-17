/**
 * Adds a toggle button to the FSE toolbar to show/hide Userback.
 */
import { ToolbarButton } from "@wordpress/components";
import { render, useState, useEffect, useRef } from "@wordpress/element";
import { getPlugin, registerPlugin } from "@wordpress/plugins";
import { __ } from "@wordpress/i18n";

export default function ToolbarToggle() {
	function Toolbar() {
		const [isVisible, setIsVisible] = useState(true);
		const originalDisplay = useRef(null);
		const wrapperRef = useRef(null);

		// Handle container visibility.
		useEffect(() => {
			const container = document.getElementById("userback_button_container");
			if (!container) return;

			if (isVisible) {
				// Restore original display if available, otherwise use 'block'.
				container.style.display = originalDisplay.current || "block";
			} else {
				// Capture and save original display value.
				originalDisplay.current = getComputedStyle(container).display;
				container.style.display = "none";
			}
		}, [isVisible]);

		// Attach/detach toolbar button.
		useEffect(() => {
			const toolbar = document.querySelector(
				".edit-post-header-toolbar, .edit-site-header__toolbar",
			);
			if (!toolbar || wrapperRef.current) return;

			const wrapper = document.createElement("div");
			wrapper.classList.add("matchbox-userback-toggle-wrapper");
			toolbar.appendChild(wrapper);
			wrapperRef.current = wrapper;

			// Initial button render.
			renderButton();

			return () => {
				if (wrapperRef.current) {
					render(null, wrapperRef.current);
					wrapperRef.current.remove();
					wrapperRef.current = null;
				}
			};
		}, []);

		// Re-render button when visibility changes.
		useEffect(() => {
			if (wrapperRef.current) renderButton();
		}, [isVisible]);

		function renderButton() {
			render(
				<ToolbarButton
					icon={
						<svg
							width="30"
							height="24"
							viewBox="0 0 30 24"
							xmlns="http://www.w3.org/2000/svg"
						>
							<path d="M5.25 0.875H8.5V2.5H11.75V5.75H18.25V2.5H21.5V0.875H24.75V4.125H21.5V5.75V7.375H24.75V10.625H26.375V5.75H29.625V13.875H26.375V18.75H23.125V23.625H19.875H16.625V20.375H19.875V18.75H10.125V20.375H13.375V23.625H10.125H6.875V18.75H3.625V13.875H0.375V5.75H3.625V10.625H5.25V7.375H8.5V5.75V4.125H5.25V0.875ZM8.5 15.5H11.75V10.625H8.5V15.5ZM18.25 15.5H21.5V10.625H18.25V15.5Z"></path>
						</svg>
					}
					label={
						isVisible
							? __("Hide Userback Widget", "matchbox-support")
							: __("Show Userback Widget", "matchbox-support")
					}
					className={`matchbox-userback-toolbar-toggle ${
						isVisible ? "is-active" : "is-inactive"
					}`}
					isPressed={isVisible}
					onClick={() => setIsVisible(!isVisible)}
				/>,
				wrapperRef.current,
			);
		}

		return null;
	}

	if (!getPlugin("matchbox-userback-toolbar-plugin")) {
		registerPlugin("matchbox-userback-toolbar-plugin", {
			render: Toolbar,
		});
	}
}
