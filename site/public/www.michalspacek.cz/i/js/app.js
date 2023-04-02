const App = {};
App.onLoad = function (element, handler) {
	if (document.readyState !== 'loading') {
		handler();
	} else {
		element.addEventListener('DOMContentLoaded', handler);
	}
}
