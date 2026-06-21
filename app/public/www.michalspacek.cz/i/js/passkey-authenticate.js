App.ready(document, function () {
	if (!window.PublicKeyCredential) {
		console.log('Passkeys not supported');
		return;
	}
	document.querySelectorAll('form[data-options]').forEach(function (form) {
		if (isDedicatedCeremonyPage(form)) {
			runCeremony(form);
		} else {
			form.addEventListener('submit', function (event) {
				event.preventDefault();
				if (Nette.validateForm(form)) {
					runCeremony(form);
				}
			});
		}
	});

	function errorElement(form) {
		return form.dataset.errorElement ? document.getElementById(form.dataset.errorElement) : null;
	}

	async function runCeremony(form) {
		errorElement(form)?.classList.add('hidden');
		const options = JSON.parse(form.dataset.options);
		options.challenge = App.base64urlToBuffer(options.challenge);
		try {
			const credential = await navigator.credentials.get({ publicKey: options });
			form.querySelector('#passkeyCredential').value = JSON.stringify({
				id: credential.id,
				rawId: App.bufferToBase64url(credential.rawId),
				type: credential.type,
				response: {
					authenticatorData: App.bufferToBase64url(credential.response.authenticatorData),
					clientDataJSON: App.bufferToBase64url(credential.response.clientDataJSON),
					signature: App.bufferToBase64url(credential.response.signature),
					userHandle: credential.response.userHandle ? App.bufferToBase64url(credential.response.userHandle) : null,
				},
			});
			form.submit();
		} catch (e) {
			if (isDedicatedCeremonyPage(form)) {
				if (e.name === 'NotAllowedError') {
					document.location = form.dataset.canceledUrl;
				} else {
					console.error('Passkey authentication error:', e);
					document.location = form.dataset.errorUrl;
				}
			} else {
				console.error('Passkey authentication error:', e);
				errorElement(form)?.classList.remove('hidden');
			}
		}
	}

	function isDedicatedCeremonyPage(form) {
		return form.dataset.canceledUrl !== undefined;
	}
});
