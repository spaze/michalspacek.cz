document.addEventListener('DOMContentLoaded', function() {
	const supported = Boolean(window.Promise);
	let encrypted = false;
	const feedback = document.querySelector(document.queryCommandSupported('copy') ? '#copied' : '#copythis');
	const button = document.querySelector('#encrypt');
	const area = document.querySelector('#message');
	const reset = function() {
		encrypted = false;
		button.innerText = button.dataset.encrypt;
		button.removeEventListener('click', copy);
		button.addEventListener('click', handler);
		feedback.style.opacity = '0';
	};
	const handler = function() {
		if (encrypted || !supported) {
			return;
		}
		openpgp.key.readArmored(document.querySelector('#pubkey').innerText).then(function(result) {
			openpgp.config.commentstring = location.href;
			let options = {
				message: openpgp.message.fromText(area.value),
				publicKeys: result.keys,
			};
			openpgp.encrypt(options).then(function(ciphertext) {
				encrypted = true;
				area.value = ciphertext.data;
				button.innerText = button.dataset.copy;
				button.removeEventListener('click', handler);
				button.addEventListener('click', copy);
			});
		});
	};
	const load = function(e) {
		button.title = button.dataset.loading;
		let script = document.createElement('script');
		script.async = true;
		script.integrity = button.dataset.integrity;
		script.src = button.dataset.lib;
		script.crossorigin = 'anonymous';
		script.addEventListener('load', function() {
			area.removeEventListener('click', load);
			area.removeEventListener('focus', load);
			button.removeEventListener('click', load);
			button.removeEventListener('focus', load);
			button.removeEventListener('mouseover', load);
			button.addEventListener('click', handler)
			button.removeAttribute('title');
			if (e.target === button[0] && e.type === 'click') {
				handler();
			}
		});
		document.getElementsByTagName('head')[0].appendChild(script);
	};
	const copy = function() {
		area.select();
		if (document.queryCommandSupported('copy')) {
			document.execCommand('copy');
		}
		feedback.style.opacity = '1';
	};
	document.querySelector('#copied .button, #copythis .button').addEventListener('click', function() {
		area.value = '';
		reset();
	});
	if (supported) {
		area.addEventListener('input', function(e) {
			if (e.target.value === '') {
				reset();
			}
		});
		area.addEventListener('click', load);
		area.addEventListener('focus', load);
		button.addEventListener('click', load);
		button.addEventListener('focus', load);
		button.addEventListener('mouseover', load);
		button.removeAttribute('title');
		button.removeAttribute('disabled');
	} else {
		area.value = button.dataset.unsupported;
		area.disabled = true;
	}
});
