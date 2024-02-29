const App = {};
App.ready = function (element, handler) {
	if (document.readyState !== 'loading') {
		handler();
	} else {
		element.addEventListener('DOMContentLoaded', handler);
	}
}
App.storeEventListener = function (element, type, listener) {
	if (!Array.isArray(element.eventListeners)) {
		element.eventListeners = [];
	}
	if (!Array.isArray(element.eventListeners[type])) {
		element.eventListeners[type] = [];
	}
	element.eventListeners[type].push(listener);
}
App.on = function (type, selector, listener) {
	document.querySelectorAll(selector).forEach(function (item) {
		item.addEventListener(type, listener);
		App.storeEventListener(item, type, listener);
	});
}
App.onClick = function (selector, listener) {
	App.on('click', selector, listener);
}
App.onChange = function (selector, listener) {
	App.on('change', selector, listener);
}
App.onLoad = function (selector, listener) {
	App.on('load', selector, listener);
}
App.clone = function (element) {
	const cloned = element.cloneNode(true);
	const sourceElements = element.getElementsByTagName('*');
	const clonedElements = cloned.getElementsByTagName('*');
	for (let i = 0; i < sourceElements.length; i++) {
		for (const type in sourceElements[i].eventListeners) {
			for (const listener of sourceElements[i].eventListeners[type]) {
				clonedElements[i].addEventListener(type, listener)
				App.storeEventListener(clonedElements[i], type, listener);
			}
		}
	}
	return cloned;
}
App.nextElementSiblings = function (element, selector) {
	const elements = [];
	let sibling = element.nextElementSibling;
	while (sibling) {
		if (sibling.matches(selector)) {
			elements.push(sibling);
		}
		sibling = sibling.nextElementSibling;
	}
	return elements;
}
