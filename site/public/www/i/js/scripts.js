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
	ENCRYPTION.reset = function() {
		ENCRYPTION.button.text(ENCRYPTION.button.data('encrypt'));
		ENCRYPTION.button.off('click').click(ENCRYPTION.handler);
		ENCRYPTION.feedback.fadeOut('fast');
	};
	ENCRYPTION.handler = function() {
		openpgp.config.commentstring = location.href;
		options = {
			data: $('#message').val(),
			publicKeys: openpgp.key.readArmored($('#pubkey').text()).keys,
		};
		openpgp.encrypt(options).then(function(ciphertext) {
			$('#message').val(ciphertext.data);
			ENCRYPTION.button.text(ENCRYPTION.button.data('copy'));
			ENCRYPTION.button.off('click').click(function() {
				$('#message').select();
				if (document.queryCommandSupported('copy')) {
					document.execCommand('copy');
				}
				ENCRYPTION.feedback.fadeIn('fast');
			});
		});
	};
	$('#copied .button, #copythis .button').click(function() {
		$('#message').val('');
		ENCRYPTION.reset();
	});
	$('#message').on('input', function(e) {
		if (e.target.value === '') {
			ENCRYPTION.reset();
		}
	});

	if (ENCRYPTION.button.length) {
		document.getElementsByTagName('head')[0].appendChild(
			$(document.createElement('script'))
				.prop('async', true)
				.attr('integrity', ENCRYPTION.button.data('integrity'))
				.attr('src', ENCRYPTION.button.data('lib'))
				.on('load', function(){
					ENCRYPTION.button
						.one('click', ENCRYPTION.handler)
						.removeAttr('title')
						.prop('disabled', false);
				})[0]
		);
	}

	if ($('#slides-container .highlight').length) {
		$('html, body').animate({scrollTop: $('#slides-container .highlight').offset().top - 10});
	}

	var para = $('.column-content').find(window.location.hash)
	if (para.length) {
		para.addClass('highlight');
		$('html, body').animate({scrollTop: para.offset().top - 10});
	}
});
