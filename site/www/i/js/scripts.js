$(document).ready(function() {
	$('#termin a[href="#prihlaska"]').click(function() {
		$('#frmapplication-trainingId').val($(this).data('id'));
	});
	$('#uskutecnene-terminy a[href="#uskutecnene-terminy"]').click(function() {
		$('#uskutecnene-terminy-container').fadeToggle('fast');
		return false;
	});
	$('#uskutecnene-terminy-container').hide();
});
