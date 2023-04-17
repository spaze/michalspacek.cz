App.ready(document, function () {
	App.onClick('.open-button', function (event) {
		event.preventDefault();
		const element = this.dataset.open ? document.querySelector(this.dataset.open) : this.parentElement.nextElementSibling.matches('.expandable') ? this.parentElement.nextElementSibling : null;
		if (element) {
			element.classList.toggle('hidden');
		}
		this.classList.toggle('open');
	});
	App.onChange('#frm-searchSort select', function () {
		this.form.submit();
	});
});
