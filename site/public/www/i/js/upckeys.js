$(document).ready(function() {
	var submitted = false;
	$('#frm-ssid').submit(function() {
		if (submitted) {
			return false;
		}
		submitted = true;
		var s = $('#submit');
		var alt = s.data('alt');
		s.data('alt', s.val());
		s.val(alt).prop('disabled', true);
		setTimeout(function() {
			var alt = s.val();
			s.val(s.data('alt')).prop('disabled', false);
			s.data('alt', alt);
			submitted = false;
		}, 5000);
	});
});