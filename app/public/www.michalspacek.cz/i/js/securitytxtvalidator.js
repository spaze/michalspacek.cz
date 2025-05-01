App.ready(document, function () {
	App.on('submit', 'form.submit-once', function (event) {
		event.target.querySelectorAll('input[type=submit]').forEach(function (item) {
			item.disabled = true;
			setTimeout(function () {
				item.disabled = false;
			}, 5000);
		});
	});
});
