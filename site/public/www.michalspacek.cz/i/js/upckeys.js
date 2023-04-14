App.onLoad(document, function () {
	let submitted = false;
	let timer = null;
	let orig = null;
	App.on('submit', '#frm-ssid', function () {
		if (submitted) {
			return false;
		}
		submitted = true;
		const s = document.querySelector('#submit');
		const alt = s.dataset.alt;
		s.dataset.alt = s.value;
		s.value = alt;
		s.disabled = true;
		setTimeout(function () {
			const alt = s.value;
			s.value = s.dataset.alt;
			s.disabled = false;
			s.dataset.alt = alt;
			submitted = false;
		}, 5000);
	});
	const listener = function () {
		const filterType = document.querySelector('#filterType').value;
		const filterPrefix = document.querySelector('#filterPrefix').value;
		const filterKey = document.querySelector('#filterKey').value;
		const filterMac = document.querySelector('#filterMac').value;
		const re = new RegExp('^[0-9a-z]*$', 'i');
		if (!re.test(filterType) || !re.test(filterPrefix) || !re.test(filterKey) || !re.test(filterMac)) {
			return false;
		}
		let i = 0;
		for (const tr of document.querySelectorAll('#result tbody tr')) {
			let hide = false;
			if (filterType && !tr.matches('.' + filterType)) {
				hide = true;
			}
			if (filterPrefix && !tr.matches('.' + filterPrefix)) {
				hide = true;
			}
			if (filterKey && !(new RegExp(filterKey, 'i')).test(tr.querySelector('td.key code').textContent)) {
				hide = true;
			}
			const macCode = tr.querySelector('td.mac code');
			if (filterMac && (macCode && !(new RegExp(filterMac, 'i')).test(macCode.textContent) || !macCode)) {
				hide = true;
			}
			if (hide) {
				tr.style.display = 'none';
			} else {
				tr.style.display = '';
				if (i++ % 2) {
					tr.classList.remove('dark');
				} else {
					tr.classList.add('dark');
				}
				tr.querySelector('td.nr code').textContent = i + '.'
			}
		}
		document.querySelector('#footer').style.display = i === 0 ? 'table-footer-group' : 'none';
	};
	App.onChange('#filterType, #filterPrefix', listener);
	App.on('keyup', '#filterKey, #filterMac', function () {
		const element = this;
		clearTimeout(timer);
		timer = setTimeout(function () {
			if (orig === element.value) {
				return false;
			}
			orig = element.value;
			listener();
		}, 100);
	});
	for (const item of document.querySelectorAll('#filterType, #filterPrefix, #filterKey, #filterMac')) {
		item.removeAttribute('title');
		item.disabled = false;
	}
});
