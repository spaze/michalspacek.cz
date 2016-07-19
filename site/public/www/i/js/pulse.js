$(function() {
	$('.open-button').click(function(event) {
		event.preventDefault();
		$(this).parent().next().slideToggle('fast');
		$(this).toggleClass('arrow-down');
	});
});