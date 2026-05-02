App.ready(document, async function () {
	const form = document.getElementById('passkeyReset');
	if (!form) {
		return;
	}

	if (!window.PublicKeyCredential) {
		document.location = form.dataset.notSupportedUrl;
		return;
	}
	const token = window.location.hash.slice(1);
	if (!token) {
		document.location = form.dataset.errorUrl;
		return;
	}

	form.querySelector('#passkeyResetToken').value = token;

	const submitButton = document.getElementById('passkeyRegisterButton');
	const optionsError = document.getElementById('passkeyOptionsError');

	async function loadOptions() {
		optionsError.hidden = true;
		submitButton.title = submitButton.dataset.loading;
		try {
			const optionsResponse = await fetch('passkey-reset-options', {
				method: 'POST',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
				body: new URLSearchParams({ token }),
			});
			if (!optionsResponse.ok) {
				console.error('Passkey reset options error:', optionsResponse.status, await optionsResponse.text());
				document.location = form.dataset.errorUrl;
				return;
			}
			const options = App.Passkey.decodeRegistrationOptions(await optionsResponse.json());
			submitButton.removeAttribute('title');
			submitButton.removeAttribute('disabled');
			App.Passkey.addRegisterSubmitHandler(form, options);
		} catch (err) {
			console.error('Passkey reset options fetch error:', err);
			submitButton.removeAttribute('title');
			optionsError.hidden = false;
		}
	}

	document.getElementById('passkeyRetry').addEventListener('click', loadOptions);
	loadOptions();
});
