$(document).ready(function() {
	var ENCRYPTION = ENCRYPTION || {};
	ENCRYPTION.supported = Boolean(window.Promise);
	ENCRYPTION.feedback = $(document.queryCommandSupported('copy') ? '#copied' : '#copythis');
	ENCRYPTION.button = $('#encrypt');
	ENCRYPTION.area = $('#message');
	ENCRYPTION.loadEvents = {
		button: 'click focus mouseover',
		area: 'click focus'
	};
	ENCRYPTION.reset = function() {
		ENCRYPTION.button.text(ENCRYPTION.button.data('encrypt'));
		ENCRYPTION.button.off('click').on('click', ENCRYPTION.handler);
		ENCRYPTION.feedback.fadeOut('fast');
	};
	ENCRYPTION.load = function(e) {
		ENCRYPTION.button.attr('title', ENCRYPTION.button.data('loading'));
		document.getElementsByTagName('head')[0].appendChild(
			$(document.createElement('script'))
				.prop('async', true)
				.attr('integrity', ENCRYPTION.button.data('integrity'))
				.attr('src', ENCRYPTION.button.data('lib'))
				.attr('crossorigin', 'anonymous')
				.on('load', function() {
					ENCRYPTION.area.off(ENCRYPTION.loadEvents.area);
					ENCRYPTION.button
						.off(ENCRYPTION.loadEvents.button)
						.one('click', ENCRYPTION.handler)
						.removeAttr('title');
					if (e.target === ENCRYPTION.button[0] && e.type === 'click') {
						ENCRYPTION.handler();
					}
				})[0]
		);
	};
	if (ENCRYPTION.supported) {
		ENCRYPTION.handler = function() {
			openpgp.key.readArmored($('#pubkey').text()).then(function(result) {
				openpgp.config.commentstring = location.href;
				options = {
					message: openpgp.message.fromText(ENCRYPTION.area.val()),
					publicKeys: result.keys,
				};
				openpgp.encrypt(options).then(function(ciphertext) {
					ENCRYPTION.area.val(ciphertext.data);
					ENCRYPTION.button.text(ENCRYPTION.button.data('copy'));
					ENCRYPTION.button.off('click').on('click', function() {
						ENCRYPTION.area.select();
						if (document.queryCommandSupported('copy')) {
							document.execCommand('copy');
						}
						ENCRYPTION.feedback.fadeIn('fast');
					});
				});
			});
		};
	}
	$('#copied .button, #copythis .button').on('click', function() {
		ENCRYPTION.area.val('');
		ENCRYPTION.reset();
	});
	if (ENCRYPTION.supported) {
		ENCRYPTION.area.on('input', function(e) {
			if (e.target.value === '') {
				ENCRYPTION.reset();
			}
		});
		ENCRYPTION.area.on(ENCRYPTION.loadEvents.area, ENCRYPTION.load);
		ENCRYPTION.button
			.on(ENCRYPTION.loadEvents.button, ENCRYPTION.load)
			.removeAttr('title')
			.prop('disabled', false);
	} else {
		ENCRYPTION.area.val(ENCRYPTION.button.data('unsupported')).prop('disabled', true);
	}
});
