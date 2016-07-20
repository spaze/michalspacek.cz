$(function() {
	$('.open-button').click(function(event) {
		event.preventDefault();
		$(this).parent().nextAll('.expandable').slideToggle(100);
		$(this).toggleClass('open');
	});
});
