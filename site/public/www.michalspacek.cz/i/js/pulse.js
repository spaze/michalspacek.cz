App.ready(document, function () {
	App.onClick('.open-button', function (event) {
		event.preventDefault();
		let elements;
		if (this.dataset.open) {
			const element = document.querySelector(this.dataset.open);
			if (element) {
				elements = [element];
			}
		} else {
			elements = App.nextElementSiblings(this.parentElement, '.expandable');
		}
		elements.forEach(function (item) {
			item.classList.toggle('hidden');
		});
		this.classList.toggle('open');
	});
	App.onChange('#frm-searchSort select', function () {
		this.form.submit();
	});
});
