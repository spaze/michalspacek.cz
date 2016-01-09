$(document).ready(function() {
	$('#frm-ssid').submit(function() {
		var s = $('#submit');
		var alt = s.data('alt');
		s.data('alt', s.val());
		s.val(alt).prop('disabled', true);
		setTimeout(function() {
			var alt = s.val();
			s.val(s.data('alt')).prop('disabled', false);
			s.data('alt', alt);
		}, 5000);
	});
});