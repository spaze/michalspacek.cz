const App = {};
App.onLoad = function (element, handler) {
	if (document.readyState !== 'loading') {
		handler();
	} else {
		element.addEventListener('DOMContentLoaded', handler);
	}
}
App.on = function (type, selector, listener) {
	document.querySelectorAll(selector).forEach(function (item) {
		item.addEventListener(type, listener);
	});
}
App.onClick = function (selector, listener) {
	App.on('click', selector, listener);
}
App.onChange = function (selector, listener) {
	App.on('change', selector, listener);
}
