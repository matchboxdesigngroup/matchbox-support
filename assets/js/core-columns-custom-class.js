/**
 * Adds a class to the columns block based on the number of columns.
 *
 * @since 1.0.0
 *
 * @return void
 */
function columnClasses() {
	const columnBlocks = document.querySelectorAll(".wp-block-columns");

	columnBlocks.forEach((block) => {
		const columns = block.querySelectorAll(".wp-block-column");
		const columnCount = columns.length;

		if (columnCount > 0) {
			const newClass = `has-${columnCount}-columns`;

			if (!block.classList.contains(newClass)) {
				block.classList.add(newClass);
			}
		}
	});
}

// Example: Run after DOM is fully loaded
document.addEventListener("DOMContentLoaded", columnClasses);
