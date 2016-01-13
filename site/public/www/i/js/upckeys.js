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
	$('#filterType').change(function() {
		$('#result tbody tr').show();
		var filterClass = $(this).val();
		if (filterClass) {
			$('#result tbody tr').not('.' + filterClass).hide();
		}
        $('#result tbody tr:visible:even').addClass('dark');
        $('#result tbody tr:visible:odd').removeClass('dark');
        
        var i = 1;
        $('#result tbody td.nr:visible code').text(function() {
        	return i++ + '.';
        });
	});
});
