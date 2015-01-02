$(document).ready(function() {
	$('.selectAll').focus(function(e){
		e.target.select()
	});

	$('#pridat-ucastniky a[href="#pridat-ucastniky"]').click(function() {
		$('#pridat-ucastniky-container').slideToggle('fast');
		return false;
	});
	$('#pridat-ucastniky-container').hide();

	var Applications = {};
	Applications.reindex = function(element, index) {
		element.find('input').each(function() {
			$(this).attr('name', function(i, value) {
				return value.replace(/^applications\[\d+\]/, 'applications[' + index + ']');
			});
			$(this).attr('id', function(i, value) {
				return value.replace(/^frm-applications-applications-\d+-/, 'frm-applications-applications-' + index + '-');
			});
			$(this).attr('data-nette-rules', $(this).attr('data-nette-rules').replace(/'applications\[\d+\]/, "'applications[" + index + ']'));
		});
	};
	$('#pridat-ucastniky .add').click(function() {
		$('#pridat-ucastniky .delete').show();
		var index = 0;
		tr = $(this).parent().parent();
		tr.after(tr.clone(true));
		tr.parent().children('tr').each(function() {
			Applications.reindex($(this), index++);
		});
	});
	$('#pridat-ucastniky .delete').click(function() {
		var index = 0;
		tr = $(this).parent().parent();
		tr.addClass('highlight');
		if (confirm('Odebrat účastníka?')) {
			tbody = tr.parent();
			tr.remove();
			tbody.children('tr').each(function() {
				Applications.reindex($(this), index++);
			});
			if (tbody.children('tr').length == 1) {
				$('#pridat-ucastniky .delete').hide();
			}
		} else {
			tr.removeClass('highlight');
		}
	});
	$('#pridat-ucastniky .delete').hide();

	$('#pridat-soubor a[href="#pridat-soubor"]').click(function() {
		$('#pridat-soubor-container').slideToggle('fast');
		return false;
	});
	$('#pridat-soubor-container').hide();

	$('#upravit-termin a[href="#upravit-termin"]').click(function() {
		$('#upravit-termin-container').slideToggle('fast');
		return false;
	});
	$('#upravit-termin-container').hide();

	$('#statuses td[data-date]')
		.click(function() {
			$('#date-' + $(this).data('date')).slideToggle('fast');
			return false;
		})
		.css('cursor', 'pointer')
		.parent().next().hide();
	$('<small>')
		.append($('<a>', {
				text: 'Zobrazit všechny',
				href: '#statuses',
				id: 'statusesShow',
				click: function(event) {
					event.preventDefault();
					$('#statuses td[data-date]').parent().next().show();
					$(this).hide();
					$('#statusesHide').show();
				}
			}))
		.append($('<a>', {
				text: 'Skrýt všechny',
				href: '#statuses',
				id: 'statusesHide',
				click: function(event) {
					event.preventDefault();
					$('#statuses td[data-date]').parent().next().hide();
					$(this).hide();
					$('#statusesShow').show();
				}
			}).hide())
		.appendTo('#statuses-links');
});
