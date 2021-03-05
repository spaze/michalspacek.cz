$(document).ready(function() {
	$('.open-button').on('click', function (event) {
		event.preventDefault();
		const elements = $(this).data('open') ? $('body').find($(this).data('open')) : $(this).parent().nextAll('.expandable');
		elements.slideToggle(100);
		$(this).toggleClass('open');
	});
	$('#frm-searchSort select').on('change', function () {
		this.form.submit();
	})
});
