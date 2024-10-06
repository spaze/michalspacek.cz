(function () {
	const param = 'fbclid';
	if (location.search.indexOf(param + '=') !== -1) {
		let replace;
		try {
			const url = new URL(location);
			url.searchParams.delete(param);
			replace = url.href;
		} catch (ex) {
			const regExp = new RegExp('[?&]' + param + '=.*$');
			replace = location.search.replace(regExp, '');
			replace = location.pathname + replace + location.hash;
		}
		history.replaceState(null, '', replace);
	}
})();
