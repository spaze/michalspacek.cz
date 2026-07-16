(function () {
	// Reload if a page restored from the back/forward cache uses an old theme
	const theme = document.documentElement.dataset.theme;
	if (theme === undefined) {
		return;
	}
	try {
		localStorage.setItem('theme', theme);
	} catch (e) {
		return; // Prevent reloads on every restore when localStorage is read-only
	}
	window.addEventListener('pageshow', function (event) {
		try {
			if (event.persisted && localStorage.getItem('theme') !== theme) {
				location.reload();
			}
		} catch (e) {
		}
	});
})();
