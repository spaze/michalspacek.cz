var param = 'fbclid';
if (location.search.indexOf(param + '=') !== -1) {
	var replace = '';
	try {
		var url = new URL(location);
		url.searchParams.delete(param);
		replace = url.pathname + url.search + url.hash;
	} catch (ex) {
		var regExp = new RegExp('[?&]' + param + '=.*$');
		replace = location.search.replace(regExp, '');
		replace = location.pathname + replace + location.hash;
	}
	history.replaceState(null, '', replace);
}
