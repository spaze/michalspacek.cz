$(function() {
	$('.open-button').click(function(event) {
		event.preventDefault();
		$(this).parent().nextAll('.hidden').slideToggle('fast');
		$(this).toggleClass('arrow-down');
	});
});
