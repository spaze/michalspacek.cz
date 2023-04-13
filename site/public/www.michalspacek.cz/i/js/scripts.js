App.onLoad(document, function () {
	const dateElement = document.querySelector('#termin a[href="#prihlaska"]');
	if (dateElement) {
		dateElement.addEventListener('click', function () {
			document.querySelector('#frm-application-trainingId').value = this.dataset.id;
		});
	}

	const countryElement = document.querySelector('#frm-application-country');
	const companyIdElement = document.querySelector('#frm-application-companyId');
	App.hideLoadControls = function () {
		for (const item of document.querySelectorAll('#loadDataControls span')) {
			item.classList.add('hidden');
		}
	};
	App.showLoadControls = function (selector) {
		for (const item of document.querySelectorAll(selector)) {
			item.classList.remove('hidden');
		}
	};
	App.loadData = function (event) {
		event.preventDefault();
		if (!countryElement || countryElement.value === '' || companyIdElement.value.replace(/ /g, '') === '') {
			alert(document.querySelector('#errorCountryCompanyMissing').innerText);
			return;
		}
		App.hideLoadControls();
		App.showLoadControls('#loadDataWait');
		const loadDataElement = document.querySelector('#loadData')
		if (!loadDataElement) {
			return;
		}

		const url = new URL(loadDataElement.dataset.url);
		url.searchParams.set('country', countryElement ? countryElement.value : '');
		url.searchParams.set('companyId', companyIdElement ? companyIdElement.value.replace(/ /g, '') : '');
		const controller = new AbortController();
		const timeoutId = setTimeout(() => controller.abort(), 10000);
		fetch(url.toString(), {signal: controller.signal})
			.then((response) => {
				clearTimeout(timeoutId);
				if (!response.ok) {
					throw new Error('Network response not ok');
				}
				return response.json()
			})
			.then((data) => {
				App.hideLoadControls();
				App.showLoadControls('#loadDataAgain');
				if (data.status === 200) {
					['companyId', 'companyTaxId', 'company', 'street', 'city', 'zip', 'country'].forEach(function (value) {
						const companyElement = document.querySelector('#company');
						if (companyElement) {
							companyElement.querySelector('#frm-application-' + value).value = data[value];
						}
					});
				} else if (data.status === 400) {
					App.showLoadControls('#loadDataNotFound');
				} else {
					App.showLoadControls('#loadDataError');
				}
			})
			.catch((error) => {
				App.hideLoadControls();
				App.showLoadControls('#loadDataAgain, #loadDataError');
				console.error('🐶⚾ fetch error:', error);
			});
	};
	App.onClick('#loadData a, #loadDataAgain a', App.loadData);
	if (companyIdElement) {
		companyIdElement.addEventListener('keypress', function (e) {
			if (e.which === 13) {
				App.loadData(e);
			}
		});
	}
	const loadDataDisabledElement = document.querySelector('#loadDataDisabled');
	if (loadDataDisabledElement) {
		loadDataDisabledElement.classList.add('hidden');
	}
	App.hideLoadControls();
	App.showLoadControls('#loadDataControls, #loadData');
	App.changeLabels = function () {
		for (const item of document.querySelectorAll('#frm-application label')) {
			if (countryElement) {
				let label = item.dataset[countryElement.value];
				if (label) {
					item.innerText = label;
				}
			}
		}
	};
	if (countryElement) {
		countryElement.addEventListener('change', App.changeLabels);
	}
	App.changeLabels();

	const columnContentElement = document.querySelector('.column-content');
	if (columnContentElement && window.location.hash) {
		const columnContentHighlightElement = columnContentElement.querySelector(window.location.hash);
		if (columnContentHighlightElement) {
			columnContentHighlightElement.classList.add('highlight');
		}
	}

	const highlightElement = document.querySelector('.column-content .highlight');
	if (highlightElement) {
		highlightElement.scrollIntoView({
			'behavior': 'smooth',
		});
	}
});
