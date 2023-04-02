APPLICATION.onLoad(document, function () {
	document.querySelectorAll('.open-button').forEach(function (item) {
		item.addEventListener('click', function (event) {
			event.preventDefault();
			const element = this.dataset.open ? document.querySelector(this.dataset.open) : this.parentElement.nextElementSibling.matches('.expandable') ? this.parentElement.nextElementSibling : null;
			if (element) {
				element.classList.toggle('hidden');
			}
			this.classList.toggle('open');
		});
	});
	document.querySelectorAll('#frm-searchSort select').forEach(function (item) {
		item.addEventListener('change', function () {
			this.form.submit();
		});
	});
});
