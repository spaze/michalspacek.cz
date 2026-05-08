App.Passkey = {};
App.Passkey.decodeRegistrationOptions = function (options) {
	options.challenge = App.base64urlToBuffer(options.challenge);
	options.user.id = App.base64urlToBuffer(options.user.id);
	if (options.excludeCredentials) {
		options.excludeCredentials = options.excludeCredentials.map(function (c) {
			return Object.assign({}, c, { id: App.base64urlToBuffer(c.id) });
		});
	}
	return options;
}
App.Passkey.addRegisterSubmitHandler = function (form, options) {
	form.addEventListener('submit', async function (e) {
		e.preventDefault();

		try {
			const credential = await navigator.credentials.create({ publicKey: options });
			form.querySelector('#passkeyCredential').value = JSON.stringify({
				id: credential.id,
				rawId: App.bufferToBase64url(credential.rawId),
				type: credential.type,
				response: {
					attestationObject: App.bufferToBase64url(credential.response.attestationObject),
					clientDataJSON: App.bufferToBase64url(credential.response.clientDataJSON),
					transports: credential.response.getTransports ? credential.response.getTransports() : [],
				},
			});
			form.submit();
		} catch (err) {
			if (err.name === 'NotAllowedError') {
				document.location = form.dataset.canceledUrl;
			} else {
				console.error('Passkey registration error:', err);
				document.location = form.dataset.errorUrl;
			}
		}
	});
}
