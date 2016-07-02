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
					$('#frm-application-' + value).val(data[value]);
				});
			} else if (data.status == 400) {
				$('#loadDataNotFound').show();
			} else {
				$('#loadDataError').show();
			}
		});
		load.fail(function() {
			$('#loadDataControls span').hide();
			$('#loadDataAgain').show();
			$('#loadDataError').show();
		});
	};
	$('#loadData a').click(APPLICATION.loadData);
	$('#loadDataAgain a').click(APPLICATION.loadData);
	$('#loadData').show();

	var ENCRYPTION = ENCRYPTION || {};
	ENCRYPTION.feedback = $(document.queryCommandSupported('copy') ? '#copied' : '#copythis');
	ENCRYPTION.reset = function() {
		$('#encrypt').text($('#encrypt').data('encrypt'));
		$('#encrypt').off('click').click(ENCRYPTION.handler);
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
			$('#encrypt').text($('#encrypt').data('copy'));
			$('#encrypt').off('click').click(function() {
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

	if ($('#openpgp-preload').length) {
		document.getElementsByTagName('head')[0].appendChild(
			$(document.createElement('script'))
				.prop('async', true)
				.attr('integrity', $('#openpgp-preload').attr('integrity'))
				.attr('src', $('#openpgp-preload').attr('href'))
				.on('load', function(){
					$('#encrypt')
						.one('click', ENCRYPTION.handler)
						.removeAttr('title')
						.prop('disabled', false);
				})[0]
		);
	}
});
