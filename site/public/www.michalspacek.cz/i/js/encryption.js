App.onLoad(document, function () {
	const supported = Boolean(window.Promise);
	let encrypted = false;
	const feedback = document.querySelector(document.queryCommandSupported('copy') ? '#copied' : '#copythis');
	const button = document.querySelector('#encrypt');
	const area = document.querySelector('#message');
	const reset = function () {
		encrypted = false;
		button.innerText = button.dataset.encrypt;
		button.removeEventListener('click', copy);
		button.addEventListener('click', handler);
		feedback.style.opacity = '0';
	};
	const handler = function () {
		if (encrypted || !supported) {
			return;
		}
		openpgp.readKey({armoredKey: document.querySelector('#pubkey').innerText}).then(function (key) {
			openpgp.config.commentString = location.href;
			openpgp.config.showComment = true;
			openpgp.createMessage({text: area.value}).then(function (message) {
				openpgp.encrypt({message, encryptionKeys: key}).then(function (ciphertext) {
					encrypted = true;
					area.value = ciphertext;
					button.innerText = button.dataset.copy;
					button.removeEventListener('click', handler);
					button.addEventListener('click', copy);
				});
			});
		});
	};
	const copy = function () {
		area.select();
		if (document.queryCommandSupported('copy')) {
			document.execCommand('copy');
		}
		feedback.style.opacity = '1';
	};
	feedback.style.transition = 'opacity 0.2s';
	feedback.querySelector('.button').addEventListener('click', function () {
		area.value = '';
		reset();
	});
	button.title = button.dataset.loading;
	App.onLoad(document.getElementById('encryption-js'), function () {
		button.addEventListener('click', handler)
		button.removeAttribute('title');
	});
	if (supported) {
		area.addEventListener('input', function (e) {
			if (e.target.value === '') {
				reset();
			}
		});
		button.removeAttribute('title');
		button.removeAttribute('disabled');
	} else {
		area.value = button.dataset.unsupported;
		area.disabled = true;
	}
});
