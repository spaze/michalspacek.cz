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
	$('#filterType, #filterPrefix').change(function() {
		var filterType = $('#filterType').val();
		var filterPrefix = $('#filterPrefix').val();
		$('#result tbody tr').show();
		if (filterType) {
			$('#result tbody tr').not('.' + filterType).hide();
		}
		if (filterPrefix) {
			$('#result tbody tr').not('.' + filterPrefix).hide();
		}
        $('#result tbody tr:visible:even').addClass('dark');
        $('#result tbody tr:visible:odd').removeClass('dark');
        
        var i = 1;
        $('#result tbody td.nr:visible code').text(function() {
        	return i++ + '.';
        });
	});
});
