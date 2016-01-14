$(document).ready(function() {
	var submitted = false;
	var timer;
	var orig;
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
	$('#filterType, #filterPrefix, #filterKey').change(function() {
		var filterType = $('#filterType').val();
		var filterPrefix = $('#filterPrefix').val();
		var filterKey = $('#filterKey').val();
		re = new RegExp('^[0-9a-z]*$', 'i');
		if (!re.test(filterType) || !re.test(filterPrefix) || !re.test(filterKey)) {
			return false;
		}
		tr = $('#result tbody tr');
		tr.show();
		if (filterType) {
			tr.not('.' + filterType).hide();
		}
		if (filterPrefix) {
			tr.not('.' + filterPrefix).hide();
		}
		if (filterKey) {
			tr.filter(function() {
				return !(new RegExp(filterKey, 'i')).test($(this).find('td.key code').text());
			}).hide();
		}
        $('#result tbody tr:visible:even').addClass('dark');
        $('#result tbody tr:visible:odd').removeClass('dark');
    	$('#footer').toggle(tr.siblings(':visible').length === 0);
        var i = 1;
        $('#result tbody td.nr:visible code').text(function() {
        	return i++ + '.';
        });
	});
	$('#filterKey').keyup(function() {
		clearTimeout(timer);
		timer = setTimeout(function() {
			if (orig === $('#filterKey').val()) {
				return false;
			}
			orig = $('#filterKey').val();
			$('#filterKey').change();
		}, 100);
	});
});
