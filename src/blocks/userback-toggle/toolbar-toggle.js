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
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
							<path d="M256 0c53 0 96 43 96 96l0 3.6c0 15.7-12.7 28.4-28.4 28.4l-135.1 0c-15.7 0-28.4-12.7-28.4-28.4l0-3.6c0-53 43-96 96-96zM41.4 105.4c12.5-12.5 32.8-12.5 45.3 0l64 64c.7 .7 1.3 1.4 1.9 2.1c14.2-7.3 30.4-11.4 47.5-11.4l112 0c17.1 0 33.2 4.1 47.5 11.4c.6-.7 1.2-1.4 1.9-2.1l64-64c12.5-12.5 32.8-12.5 45.3 0s12.5 32.8 0 45.3l-64 64c-.7 .7-1.4 1.3-2.1 1.9c6.2 12 10.1 25.3 11.1 39.5l64.3 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c0 24.6-5.5 47.8-15.4 68.6c2.2 1.3 4.2 2.9 6 4.8l64 64c12.5 12.5 12.5 32.8 0 45.3s-32.8 12.5-45.3 0l-63.1-63.1c-24.5 21.8-55.8 36.2-90.3 39.6L272 240c0-8.8-7.2-16-16-16s-16 7.2-16 16l0 239.2c-34.5-3.4-65.8-17.8-90.3-39.6L86.6 502.6c-12.5 12.5-32.8 12.5-45.3 0s-12.5-32.8 0-45.3l64-64c1.9-1.9 3.9-3.4 6-4.8C101.5 367.8 96 344.6 96 320l-64 0c-17.7 0-32-14.3-32-32s14.3-32 32-32l64.3 0c1.1-14.1 5-27.5 11.1-39.5c-.7-.6-1.4-1.2-2.1-1.9l-64-64c-12.5-12.5-12.5-32.8 0-45.3z" />
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
