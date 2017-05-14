$(document).ready(function() {
	$('.open-container').click(function(event) {
		event.preventDefault();
		$('body').find($(this).attr('href') + '-container').slideToggle('fast');
	});

	var FormFields = {};
	FormFields.reindex = function(element, index, formName, oldName, newName) {
		if (element.attr('name') !== undefined) {
			element.attr('name', function(i, value) {
				var re = new RegExp('^' + oldName + '\\[\\d+\\]')
				return value.replace(re, newName + '[' + index + ']');
			});
		}
		if (element.attr('id') !== undefined) {
			element.attr('id', function(i, value) {
				var re = new RegExp('^' + formName + '-' + oldName + '-\\d+-')
				return value.replace(re, formName + '-' + newName + '-' + index + '-');
			});
		}
		if (element.attr('data-nette-rules') !== undefined) {
			var re = new RegExp('"' + oldName + '\\[\\d+\\]')
			element.attr('data-nette-rules', element.attr('data-nette-rules').replace(re, '"' + newName + '[' + index + ']'));
		}
	};
	$('#pridat-ucastniky .add').click(function() {
		$('#pridat-ucastniky .delete').show();
		var index = 0;
		tr = $(this).parent().parent();
		tr.after(tr.clone(true));
		tr.parent().children('tr').each(function() {
			$(this).find('input').each(function() {
				FormFields.reindex($(this), index, 'frm-applications', 'applications', 'applications');
			});
			index++;
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
				$(this).find('input').each(function() {
					FormFields.reindex($(this), index, 'frm-applications', 'applications', 'applications');
				});
				index++;
			});
			if (tbody.children('tr').length == 1) {
				$('#pridat-ucastniky .delete').hide();
			}
		} else {
			tr.removeClass('highlight');
		}
	});

	$('#statuses td[data-date]')
		.click(function() {
			$('#statuses').find('#date-' + $(this).data('date')).toggle();
			return false;
		})
		.css('cursor', 'pointer');

	$('#statusesShow').click(function() {
		$('#statuses td[data-date]').parent().next().show();
	});

	$('#statusesHide').click(function() {
		$('#statuses td[data-date]').parent().next().hide();
	});

	$('#statusesShow, #statusesHide')
		.click(function() {
			$('#statuses-links').find('span').toggle();
		})
		.css('cursor', 'pointer');

	$('.preset').click(function(event) {
		event.preventDefault();
		var preset = $(this).data('preset');
		$('#frm-statuses-date').val($(this).data('start'));
		$('#applications .status option').each(function() {
			if (this.value == preset) {
				$(this).parent().val(preset);
				return;
			}
		});
	});

	$('#emails tbody .button').click(function() {
		$(this).closest('tr').nextUntil('.row', '.expand-container').toggle();
	});

	$('#emails #checkAll').click(function(event) {
		event.preventDefault();
		$('#emails .row .send').prop('checked', true).attr('checked', true);
	});

	$('#emails #uncheckAll').click(function(event) {
		event.preventDefault();
		$('#emails .row .send').prop('checked', false).attr('checked', false);
	});

	$('a[href*="#new"]').click(function(event) {
		event.preventDefault();
		var container = $('#pridat-storage').find($(this).attr('href'));
		container.toggle();
		if (!$('#pridat-storage').find($(this).data('parent')).toggleClass('transparent').hasClass('transparent')) {
			container.find(':input').val('');
			container.find(':checkbox').prop('checked', false);
		}
		$(this).children('span').toggle();
	});

	$('#certificatesShow, #certificatesHide')
		.click(function(event) {
			event.preventDefault();
			$('#certificates').toggle();
			$('#certificates-toggle span').toggle();
		})
		.css('cursor', 'pointer');

	var FORMATTEXY = FORMATTEXY || {};
	FORMATTEXY.loadData = function(event) {
		var p = $(this);
		var alt = p.data('alt');
		var form = $('body').find(event.data.form);
		p.data('alt', p.val());
		p.val(alt);
		var disabled = form.find('input:hidden, input:button, input:submit').prop('disabled', true);
		var data = form.serializeArray();
		data.push({name: 'postId', value: form.data('post-id')});
		var load = $.post({
			url: $(this).data('url'),
			data: data,
		});
		load.done(function(data) {
			$('#preview-target').show().html(data.formatted);
		});
		load.always(function(data) {
			var alt = p.data('alt');
			p.data('alt', p.val());
			p.val(alt);
			disabled.prop('disabled', false);
		});
	};
	$('#frm-addPost #preview').click({form: '#frm-addPost'}, FORMATTEXY.loadData);
	$('#frm-editPost #preview').click({form: '#frm-editPost'}, FORMATTEXY.loadData);

	$('#frm-slides .add-after').click(function() {
		var tbody = $(this).parent().parent().parent();
		var slide = tbody.clone(true);
		var index = 0;
		slide.addClass('new-slide changed').find(':input:not(.slide-nr)').val('');
		slide.find('img').hide().removeAttr('src').removeAttr('alt').removeAttr('title').removeAttr('width').removeAttr('height');
		slide.find('.transparent').removeClass('transparent').prop('readonly', false);
		tbody.after(slide);
		tbody.nextAll().find('.slide-nr').val(function(index, value) {
			return ++value;
		});
		tbody.parent().find('.new-slide').each(function() {
			$(this).find(':input').each(function() {
				FormFields.reindex($(this), index, 'frm-slides', '(slides|new)', 'new');
			});
			index++;
		});
	});

	$('#frm-slides input:file').change(function() {
		var files = 0;
		$('#frm-slides input:file').each(function() {
			if ($(this).val()) {
				files++;
			}
		});
		$('#frm-slides input:file').each(function() {
			if (!$(this).val()) {
				$(this).prop('disabled', (files >= $('#frm-slides').data('uploads')));
			}
		});
		$('#uploading').text(files);
		var tr = $(this).parent().parent();
		var fields = tr.find('.slide-filename');
		var preview = tr.nextAll('tr.image-previews').find('img.type-' + $(this).data('type')).removeAttr('width').removeAttr('height');
		if ($(this).val()) {
			fields.addClass('transparent').prop('readonly', true);
			var reader = new FileReader();
			reader.onload = function(event) {
				preview.on('load', function() {
					tr.find('.slide-width').val($(this).width())
					tr.find('.slide-height').val($(this).height())
				});
				preview.attr('src', event.target.result).show();
			};
			reader.readAsDataURL(event.target.files[0]);
		} else {
			fields.removeClass('transparent').prop('readonly', false);
			preview.removeAttr('src');
		}
	});

	$('#frm-slides').find('input:input, textarea').change(function() {
		$(this).closest('tbody').addClass('changed');
	});
});
