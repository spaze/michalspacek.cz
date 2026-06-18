App.ready(document, async function () {
	const form = document.getElementById('passkeyRegistration');
	if (!form) {
		return;
	}

	if (!window.PublicKeyCredential) {
		document.location = form.dataset.notSupportedUrl;
		return;
	}
	const token = window.location.hash.slice(1);
	if (!token) {
		const reopen = document.getElementById('passkeyReopenMessage');
		if (reopen) {
			reopen.classList.remove('hidden');
			form.classList.add('hidden');
			return;
		}
		document.location = form.dataset.errorUrl;
		return;
	}

	form.elements.token.value = token;

	const submitButton = document.getElementById('passkeyRegisterButton');
	const optionsError = document.getElementById('passkeyOptionsError');

	async function loadOptions() {
		optionsError.classList.add('hidden');
		submitButton.title = submitButton.dataset.loading;
		try {
			const optionsResponse = await fetch(form.dataset.optionsUrl, {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({ token }),
			});
			if (!optionsResponse.ok) {
				console.error('Passkey registration options error:', optionsResponse.status, await optionsResponse.text());
				document.location = form.dataset.errorUrl;
				return;
			}
			const options = App.Passkey.decodeRegistrationOptions(await optionsResponse.json());
			submitButton.removeAttribute('title');
			submitButton.removeAttribute('disabled');
			App.Passkey.addRegisterSubmitHandler(form, options);
		} catch (err) {
			console.error('Passkey registration options fetch error:', err);
			submitButton.removeAttribute('title');
			optionsError.classList.remove('hidden');
		}
	}

	App.onClick('#passkeyRetry', loadOptions);
	loadOptions();
});
