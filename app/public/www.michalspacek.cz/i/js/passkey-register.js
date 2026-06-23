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

	function decodeRegistrationOptions(options) {
		options.challenge = App.base64urlToBuffer(options.challenge);
		options.user.id = App.base64urlToBuffer(options.user.id);
		if (options.excludeCredentials) {
			options.excludeCredentials = options.excludeCredentials.map(function (c) {
				return Object.assign({}, c, { id: App.base64urlToBuffer(c.id) });
			});
		}
		return options;
	}

	function addRegisterSubmitHandler(form, options) {
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
			const options = decodeRegistrationOptions(await optionsResponse.json());
			submitButton.removeAttribute('title');
			submitButton.removeAttribute('disabled');
			addRegisterSubmitHandler(form, options);
		} catch (err) {
			console.error('Passkey registration options fetch error:', err);
			submitButton.removeAttribute('title');
			optionsError.classList.remove('hidden');
		}
	}

	App.onClick('#passkeyRetry', loadOptions);
	loadOptions();
});
