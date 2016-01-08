$(document).ready(function() {
	$('#frm-ssid').submit(function() {
		var s = $('#submit');
		var alt = s.data('alt');
		s.data('alt', s.val());
		s.val(alt).prop('disabled', true);
		setTimeout(function() {
			s.val(s.data('alt')).prop('disabled', false);
		}, 5000);
	});
});