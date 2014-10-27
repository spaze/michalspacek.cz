$(document).ready(function() {
	$('#tags').addClass('form-control');
	$('#search').addClass('form-control');

	var allTagsText = document.getElementById('all-tags');
	var allTags = JSON.parse(allTagsText.textContent || allTagsText.innerText);

	$('#search').select2({
		tags: allTags,
		separator: ' ',
		tokenSeparators: [' ', '+'],
		openOnEnter: false
	});
	$('#tags').select2({
		tags: allTags,
		separator: ' ',
		tokenSeparators: [',', ' ', '+'],
		openOnEnter: false
	});
	$('#add').draggable({
		handle: '.modal-header'
	});
	$('#add [title]').tooltip();
});
