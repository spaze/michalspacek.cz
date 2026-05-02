App.ready(document, async function () {
	const form = document.getElementById('passkeyAuthenticate');
	if (!form) {
		return;
	}
	if (!window.PublicKeyCredential) {
		console.log('Passkeys not supported');
		return;
	}
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
		if (e.name === 'NotAllowedError') {
			document.location = form.dataset.canceledUrl;
		} else {
			console.error('Passkey authentication error:', e);
			document.location = form.dataset.errorUrl;
		}
	}
});
