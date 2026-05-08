App.ready(document, function () {
	const form = document.getElementById('passkeyRegister');
	if (!form) {
		return;
	}
	if (!window.PublicKeyCredential) {
		document.location = form.dataset.notSupportedUrl;
		return;
	}
	const options = App.Passkey.decodeRegistrationOptions(JSON.parse(form.dataset.options));
	App.Passkey.addRegisterSubmitHandler(form, options);
});
