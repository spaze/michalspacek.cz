App.ready(document, function () {
	App.onClick('.open-container', function (event) {
		event.preventDefault();
		const container = document.querySelector(this.getAttribute('href') + '-container');
		if (container) {
			container.classList.toggle('hidden');
		}
		if (container.dataset.display) {
			container.style.display = container.dataset.display;
		}
	});

	const FormFields = {};
	FormFields.reindex = function (element, index, formName, oldName, newName) {
		let attr;
		attr = element.getAttribute('name');
		if (attr) {
			element.setAttribute('name', attr.replace(new RegExp('^' + oldName + '\\[\\d+\\]'), newName + '[' + index + ']'));
		}
		attr = element.getAttribute('id');
		if (attr) {
			element.setAttribute('id', attr.replace(new RegExp('^' + formName + '-' + oldName + '-\\d+-'), formName + '-' + newName + '-' + index + '-'));
		}
		attr = element.getAttribute('data-nette-rules');
		if (attr) {
			element.setAttribute('data-nette-rules', attr.replace(new RegExp('"' + oldName + '\\[\\d+\\]'), '"' + newName + '[' + index + ']'));
		}
	};

	App.onClick('#pridat-ucastniky .add', function () {
		for (const item of document.querySelectorAll('#pridat-ucastniky .delete')) {
			item.classList.remove('hidden');
		}
		const tr = this.parentElement.parentElement;
		tr.after(tr.cloneNode(true));
		let index = 0;
		for (const child of tr.parentElement.children) {
			if (child instanceof HTMLTableRowElement) {
				for (const item of child.querySelectorAll('input')) {
					FormFields.reindex(item, index, 'frm-applications', 'applications', 'applications');
				}
				index++;
			}
		}
	});
	App.onClick('#pridat-ucastniky .delete', function () {
		const tr = this.parentElement.parentElement;
		tr.classList.add('highlight');
		if (confirm('Odebrat účastníka?')) {
			const tbody = tr.parentElement;
			tr.remove();
			let index = 0;
			for (const child of tbody.children) {
				if (child instanceof HTMLTableRowElement) {
					for (const item of child.querySelectorAll('input')) {
						FormFields.reindex(item, index, 'frm-applications', 'applications', 'applications');
					}
					index++;
				}
			}
			if (index === 1) {
				for (const item of document.querySelectorAll('#pridat-ucastniky .delete')) {
					item.classList.add('hidden');
				}
			}
		} else {
			tr.classList.remove('highlight');
		}
	});

	App.onClick('#statuses td[data-date]', function () {
		for (const item of document.querySelectorAll('#statuses #date-' + this.dataset.date)) {
			item.classList.toggle('hidden');
		}
		return false;
	});

	App.onClick('#statusesShow', function () {
		for (const item of document.querySelectorAll('#statuses td[data-date]')) {
			item.parentElement.nextElementSibling.classList.remove('hidden');
		}
	});

	App.onClick('#statusesHide', function () {
		for (const item of document.querySelectorAll('#statuses td[data-date]')) {
			item.parentElement.nextElementSibling.classList.add('hidden');
		}
	});

	App.onClick('#statusesShow, #statusesHide', function () {
		for (const item of document.querySelectorAll('#statuses-links span')) {
			item.classList.toggle('hidden');
		}
	});

	App.onClick('.preset', function (event) {
		event.preventDefault();
		document.querySelector('#frm-statuses-date').value = this.dataset.start;
		for (const item of document.querySelectorAll('#applications .status option')) {
			if (item.value === this.dataset.preset) {
				item.parentElement.value = this.dataset.preset;
				return;
			}
		}
	});

	App.onClick('#emails tbody .button', function () {
		for (const item of this.parentElement.parentElement.querySelectorAll('.expand-container')) {
			item.classList.toggle('hidden');
		}
	});
	App.onClick('#emails #checkAll', function (event) {
		event.preventDefault();
		for (const item of document.querySelectorAll('#emails .row .send:enabled')) {
			item.checked = true;
		}
	});
	App.onClick('#emails #uncheckAll', function (event) {
		event.preventDefault();
		for (const item of document.querySelectorAll('#emails .row .send:enabled')) {
			item.checked = false;
		}
	});

	App.onClick('a[href*="#new"]', function (event) {
		event.preventDefault();
		const button = document.querySelector('#pridat-storage');
		const container = button.querySelector(this.getAttribute('href'));
		const parent = button.querySelector(this.dataset.parent);
		container.classList.toggle('hidden');
		parent.classList.toggle('transparent')
		if (!parent.classList.contains('transparent')) {
			for (const item of container.querySelectorAll('input')) {
				item.value = '';
				item.checked = false;
			}
		}
		for (const child of this.children) {
			if (child instanceof HTMLSpanElement) {
				child.classList.toggle('hidden');
			}
		}
	});

	App.onClick('#certificatesShow, #certificatesHide', function (event) {
		event.preventDefault();
		document.querySelector('#certificates').classList.toggle('hidden');
		for (const item of document.querySelectorAll('#certificates-toggle span')) {
			item.classList.toggle('hidden');
		}
	});

	App.onChange('#frm-addReview-application', function () {
		const dataset = this.options[this.selectedIndex].dataset;
		document.querySelector('#frm-addReview-name').value = dataset.name ?? '';
		document.querySelector('#frm-addReview-company').value = dataset.company ?? '';
	});

	App.onClick('#frm-slides .add-after', function () {
		const tbody = this.parentElement.parentElement.parentElement;
		const slide = tbody.cloneNode(true);
		let index = 0;
		slide.classList.add('new-slide', 'changed');
		for (const input of slide.querySelectorAll('input:not(.slide-nr), textarea')) {
			input.value = '';
		}
		for (const image of slide.querySelectorAll('img')) {
			SlidePreview.clearDimensions(image);
			image.classList.add('hidden');
			image.removeAttribute('src');
			image.removeAttribute('alt');
			image.removeAttribute('title');
			image.removeAttribute('width');
			image.removeAttribute('height');
		}
		for (const item of slide.querySelectorAll('.transparent')) {
			item.classList.remove('transparent');
			item.readOnly = false;
		}
		tbody.after(slide);
		let newTbody = tbody;
		while (newTbody.nextElementSibling) {
			const slideNr = newTbody.querySelector('.slide-nr');
			if (slideNr) {
				++slideNr.value;
			}
			newTbody = newTbody.nextElementSibling;
		}
		for (const slide of tbody.parentElement.querySelectorAll('.new-slide')) {
			for (const input of slide.querySelectorAll('input, textarea')) {
				FormFields.reindex(input, index, 'frm-slides', '(slides|new)', 'new');
			}
			index++;
		}
	});

	const SlidePreview = {};
	SlidePreview.getDimensions = function () {
		return JSON.parse(document.querySelector('#frm-slides').dataset.dimensions);
	};
	SlidePreview.checkDimensions = function (preview) {
		const dimensions = SlidePreview.getDimensions();
		return preview.naturalWidth / dimensions.ratio.width * dimensions.ratio.height === preview.naturalHeight && preview.naturalWidth <= dimensions.max.width && preview.naturalHeight <= dimensions.max.height;
	};
	SlidePreview.textRatio = function () {
		const dimensions = SlidePreview.getDimensions();
		return dimensions.ratio.width + ':' + dimensions.ratio.height;
	};
	SlidePreview.textMax = function () {
		const dimensions = SlidePreview.getDimensions();
		return dimensions.max.width + '×' + dimensions.max.height;
	};
	SlidePreview.selectDimensionsElement = function (element, image) {
		return element.querySelector('.dimensions.type-' + image.dataset.type);
	};
	SlidePreview.setDimensions = function (image) {
		const tbody = image.closest('tbody');
		const target = SlidePreview.selectDimensionsElement(tbody, image);
		target.classList.remove('error');
		if (image.getAttribute('src')) {
			target.innerText = image.naturalWidth + '×AAA' + image.naturalHeight;
		}
		if (!SlidePreview.checkDimensions(image)) {
			target.classList.add('error');
			setTimeout(function () {
				const slide = tbody.querySelector('.slide-nr').value;
				alert((slide ? 'Slajd ' + slide : 'Nový slajd') + ': nesprávná velikost ' + image.naturalWidth + '×' + image.naturalHeight + ', správné rozměry jsou ' + SlidePreview.textRatio() + ', max ' + SlidePreview.textMax());
			}, 100);
		}
	};
	SlidePreview.clearDimensions = function (image) {
		const item = SlidePreview.selectDimensionsElement(image.closest('tbody'), image);
		if (item) {
			item.classList.remove('error');
			item.innerText = '';
		}
	};

	const images = '#frm-slides img.type-image, #frm-slides img.type-alternative';
	for (const image of document.querySelectorAll(images)) {
		SlidePreview.setDimensions(image);
	}
	App.onLoad(images, function () {
		SlidePreview.setDimensions(this);
	});

	const inputs = '#frm-slides input[type=file]';
	App.onChange(inputs, function (event) {
		const elements = document.querySelectorAll(inputs);
		const files = Array.from(elements).filter((input) => input.value).length;
		elements.forEach(function (element) {
			if (!element.value) {
				element.disabled = files >= document.querySelector('#frm-slides').dataset.uploads;
			}
		});
		document.querySelector('#uploading').innerText = files;
		const tr = this.parentElement.parentElement;
		const field = tr.querySelector('.slide-filename');
		const preview = tr.parentElement.querySelector('tr.image-previews img.type-' + this.dataset.type);
		if (this.value) {
			const reader = new FileReader();
			reader.onload = function (event) {
				field.classList.add('transparent');
				field.readOnly = true;
				if (!preview.dataset.prev) {
					preview.dataset.prev = preview.src;
				}
				preview.src = event.target.result;
				preview.classList.remove('hidden');
			};
			reader.readAsDataURL(event.target.files[0]);
		} else {
			field.classList.remove('transparent');
			field.readOnly = false;
			if (preview.dataset.prev) {
				preview.src = preview.dataset.prev;
			} else {
				preview.removeAttribute('src');
				SlidePreview.clearDimensions(preview);
			}
		}
	});

	const blockingForm = 'form.blocking';
	const beforeUnloadListener = (e) => e.returnValue = 'ORLY?'; // The value is ignored and not displayed
	const beforeUnloadType = 'beforeunload';
	App.onChange(`${blockingForm} input:not(.non-blocking), ${blockingForm} textarea, ${blockingForm} select`, function () {
		this.closest('tbody').classList.add('changed');
		window.addEventListener(beforeUnloadType, beforeUnloadListener);
	});
	App.on('submit', blockingForm, function () {
		window.removeEventListener(beforeUnloadType, beforeUnloadListener);
	});

	App.onChange('.disableInput', function () {
		this.nextElementSibling.classList.toggle('transparent');
	});

	const change = '#change-training-date';
	const changeCancel = '#change-training-date-cancel';
	const dateForm = '#frm-applicationForm-date';
	App.onClick([change, changeCancel].join(), function (event) {
		event.preventDefault();
		for (const item of document.querySelectorAll(['#training-date', change, changeCancel, dateForm].join())) {
			item.classList.toggle('hidden');
		}
	});
	App.onClick(changeCancel, function (event) {
		event.preventDefault();
		const dateSelect = document.querySelector(dateForm);
		dateSelect.value = dateSelect.dataset.originalDateId;
	});

	App.onClick('.confirm-click', function () {
		return confirm(this.dataset.confirm);
	})

	App.onClick('#preview-button', function () {
		document.querySelector('#preview').classList.remove('hidden');
		const button = this;
		const originalValue = button.value;
		button.value = button.dataset.loadingValue;
		App.onLoad('#preview-frame', function () {
			button.value = originalValue;
		});
	});
});
