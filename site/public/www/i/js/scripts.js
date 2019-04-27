$(document).ready(function() {
	$('#termin a[href="#prihlaska"]').click(function() {
		$('#frm-application-trainingId').val($(this).data('id'));
	});

	var APPLICATION = APPLICATION || {};
	APPLICATION.loadData = function(event) {
		event.preventDefault();
		if ($('#frm-application-country').val() == '' || $('#frm-application-companyId').val().replace(/ /g, '') == '') {
			alert($('#errorCountryCompanyMissing').text());
			return;
		}
		$('#loadDataControls span').hide();
		$('#loadDataWait').show();
		var load = $.ajax({
			url: $('#loadData').data('url'),
			data: {
				country: $('#frm-application-country').val(),
				companyId: $('#frm-application-companyId').val().replace(/ /g, ''),
			},
			timeout: 10000
		});
		load.done(function(data) {
			$('#loadDataControls span').hide();
			$('#loadDataAgain').show();
			if (data.status == 200) {
				$.each(['companyId', 'companyTaxId', 'company', 'street', 'city', 'zip', 'country'], function(key, value) {
					$('#company').find('#frm-application-' + value).val(data[value]);
				});
			} else if (data.status == 400) {
				$('#loadDataNotFound').show();
			} else {
				$('#loadDataError').show();
			}
		});
		load.fail(function() {
			$('#loadDataControls span').hide();
			$('#loadDataAgain, #loadDataError').show();
		});
	};
	$('#loadData a, #loadDataAgain a').click(APPLICATION.loadData);
	$('#loadDataDisabled').hide();
	$('#loadData').show();
	APPLICATION.changeLabels = function() {
		$('#frm-application').find('label').each(function() {
			var label = $(this).data('label-' + $('#frm-application-country').val());
			if (label) {
				$(this).text(label);
			}
		});
	};
	$('#frm-application-country').change(APPLICATION.changeLabels);
	APPLICATION.changeLabels();

	var ENCRYPTION = ENCRYPTION || {};
	ENCRYPTION.feedback = $(document.queryCommandSupported('copy') ? '#copied' : '#copythis');
	ENCRYPTION.button = $('#encrypt');
	ENCRYPTION.area = $('#message');
	ENCRYPTION.loadEvents = {
		button: 'click focus mouseover',
		area: 'click focus'
	};
	ENCRYPTION.reset = function() {
		ENCRYPTION.button.text(ENCRYPTION.button.data('encrypt'));
		ENCRYPTION.button.off('click').click(ENCRYPTION.handler);
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
	ENCRYPTION.handler = async function() {
		openpgp.config.commentstring = location.href;
		options = {
			message: openpgp.message.fromText(ENCRYPTION.area.val()),
			publicKeys: (await openpgp.key.readArmored($('#pubkey').text())).keys,
		};
		openpgp.encrypt(options).then(function(ciphertext) {
			ENCRYPTION.area.val(ciphertext.data);
			ENCRYPTION.button.text(ENCRYPTION.button.data('copy'));
			ENCRYPTION.button.off('click').click(function() {
				ENCRYPTION.area.select();
				if (document.queryCommandSupported('copy')) {
					document.execCommand('copy');
				}
				ENCRYPTION.feedback.fadeIn('fast');
			});
		});
	};
	$('#copied .button, #copythis .button').click(function() {
		ENCRYPTION.area.val('');
		ENCRYPTION.reset();
	});
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

	if ($('#slides-container .highlight').length) {
		$('html, body').animate({scrollTop: $('#slides-container .highlight').offset().top - 10});
	}

	var para = $('.column-content').find(window.location.hash)
	if (para.length) {
		para.addClass('highlight');
		$('html, body').animate({scrollTop: para.offset().top - 10});
	}
});
