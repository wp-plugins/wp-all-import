/**
 * plugin admin area javascript
 */
(function($){$(function () {
	if ( ! $('body.pmxi_plugin').length) return; // do not execute any code if we are not on plugin page

	// fix layout position
	setTimeout(function () {
		$('table.layout').length && $('table.layout td.left h2:first-child').css('margin-top',  $('.wrap').offset().top - $('table.layout').offset().top);
	}, 10);
	
	
	// help icons
	$('a.help').tipsy({
		gravity: function() {
			var ver = 'n';
			if ($(document).scrollTop() < $(this).offset().top - $('.tipsy').height() - 2) {
				ver = 's';
			}
			var hor = '';
			if ($(this).offset().left + $('.tipsy').width() < $(window).width() + $(document).scrollLeft()) {
				hor = 'w';
			} else if ($(this).offset().left - $('.tipsy').width() > $(document).scrollLeft()) {
				hor = 'e';
			}
	        return ver + hor;
	    },
		live: true,
		html: true,
		opacity: 1
	}).live('click', function () {
		return false;
	}).each(function () { // fix tipsy title for IE
		$(this).attr('original-title', $(this).attr('title'));
		$(this).removeAttr('title');
	});
	
	// swither show/hide logic
	$('input.switcher').change(function (e) {
		if ($(this).is(':radio:checked')) {
			$(this).parents('form').find('input.switcher:radio[name="' + $(this).attr('name') + '"]').not(this).change();
		}
		var $targets = $('.switcher-target-' + $(this).attr('id'));
		var is_show = $(this).is(':checked'); if ($(this).is('.switcher-reversed')) is_show = ! is_show;
		if (is_show) {
			$targets.fadeIn();
		} else {
			$targets.hide().find('.clear-on-switch').add($targets.filter('.clear-on-switch')).val('');
		}
	}).change();
	
	// autoselect input content on click
	$('input.selectable').live('click', function () {
		$(this).select();
	});
	
	// input tags with title
	$('input[title]').each(function () {
		var $this = $(this);
		$this.bind('focus', function () {
			if ('' == $(this).val() || $(this).val() == $(this).attr('title')) {
				$(this).removeClass('note').val('');
			}
		}).bind('blur', function () {
			if ('' == $(this).val() || $(this).val() == $(this).attr('title')) {
				$(this).addClass('note').val($(this).attr('title'));
			}
		}).blur();
		$this.parents('form').bind('submit', function () {
			if ($this.val() == $this.attr('title')) {
				$this.val('');
			}
		});
	});

	// datepicker
	$('input.datepicker').datepicker({
		dateFormat: 'yy-mm-dd',
		showOn: 'button',
		buttonText: '',
		constrainInput: false,
		showAnim: 'fadeIn',
		showOptions: 'fast'
	}).bind('change', function () {
		var selectedDate = $(this).val();
		var instance = $(this).data('datepicker');
		var date = null;
		if ('' != selectedDate) {
			try {
				date = $.datepicker.parseDate(instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings);
			} catch (e) {
				date = null;
			}
		}
		if ($(this).hasClass('range-from')) {
			$(this).parent().find('.datepicker.range-to').datepicker("option", "minDate", date);
		}
		if ($(this).hasClass('range-to')) {
			$(this).parent().find('.datepicker.range-from').datepicker("option", "maxDate", date);
		}
	}).change();
	$('.ui-datepicker').hide(); // fix: make sure datepicker doesn't break wordpress layout upon initialization 
	
	// no-enter-submit forms
	$('form.no-enter-submit').find('input,select,textarea').not('*[type="submit"]').keydown(function (e) {
		if (13 == e.keyCode) e.preventDefault();
	});
	
	// choose file form: option selection dynamic
	// options form: highlight options of selected post type
	$('form.choose-file input[name="type"]').click(function() {
		var $container = $(this).parents('.file-type-container');		
		$('.file-type-container').not($container).removeClass('selected').find('.file-type-options').hide();
		$container.addClass('selected').find('.file-type-options').show();
	}).filter(':checked').click();
	
	// template form: auto submit when `load template` list value is picked
	$('form.template').find('select[name="load_template"]').change(function () {
		$(this).parents('form').submit();
	});	
	// template form: preview button
	$('form.template').each(function () {
		var $form = $(this);
		var $modal = $('<div></div>').dialog({
			autoOpen: false,
			modal: true,
			title: 'Preview Post',
			width: 760,
			maxHeight: 600,
			open: function(event, ui) {
				$(this).dialog('option', 'height', 'auto').css({'max-height': $(this).dialog('option', 'maxHeight') - $(this).prev().height() - 24, 'overflow-y': 'auto'}); 
	    	}
		});
		$form.find('.preview').click(function () {
			$modal.addClass('loading').empty().dialog('open').dialog('option', 'position', 'center');
			tinyMCE.triggerSave(false, false);
			$.post('admin.php?page=pmxi-admin-import&action=preview', $form.serialize(), function (response) {
				$modal.removeClass('loading').html(response).dialog('option', 'position', 'center');
			});
			return false;
		});
	});
	
	// options form: highlight options of selected post type
	$('form.options input[name="type"]').click(function() {
		var $container = $(this).parents('.post-type-container');
		$('.post-type-container').not($container).removeClass('selected').find('.post-type-options').hide();
		$container.addClass('selected').find('.post-type-options').show();
	}).filter(':checked').click();
	// options form: add / remove custom params
	$('.form-table a.action[href="#add"]').live('click', function () {
		var $template = $(this).parents('table').first().find('tr.template');
		$template.clone(true).insertBefore($template).css('display', 'none').removeClass('template').fadeIn();
		return false;
	});
	// options form: auto submit when `load options` checkbox is checked
	$('form.options').find('input[name="load_options"]').click(function () {		
		if ($(this).is(':checked')) $(this).parents('form').submit();
	});
	// options form: auto submit when `reset options` checkbox is checked
	$('form.options').find('input[name="reset_options"]').click(function () {		
		if ($(this).is(':checked')) $(this).parents('form').submit();
	});
	$('.form-table .action.remove a').live('click', function () {
		$(this).parents('tr').first().remove();
		return false;
	});
	
	var dblclickbuf = {
		'selected':false,
		'value':''
	};

	function insertxpath(){
		if (dblclickbuf.selected)
		{
			$(this).val($(this).val() + dblclickbuf.value);
			$('.xml-element[title*="/'+dblclickbuf.value.replace('{','').replace('}','')+'"]').removeClass('selected');
			dblclickbuf.value = '';
			dblclickbuf.selected = false;					
		}
	}

	// [xml representation dynamic]
	$.fn.xml = function (opt) {
		if ( ! this.length) return this;
		
		var $self = this;
		var opt = opt || {};
		var action = {};
		if ('object' == typeof opt) {
			action = opt;
		} else {
			action[opt] = true;
		}
		action = $.extend({init: ! this.data('initialized')}, action);
		
		if (action.init) {
			this.data('initialized', true);
			// add expander
			this.find('.xml-expander').click(function () {
				var method;
				if ('-' == $(this).text()) {
					$(this).text('+');
					method = 'addClass';
				} else {
					$(this).text('-');
					method = 'removeClass';
				}
				// for nested representation based on div
				$(this).parent().find('> .xml-content')[method]('collapsed');
				// for nested representation based on tr
				var $tr = $(this).parent().parent().filter('tr.xml-element').next()[method]('collapsed');
			});
		}
		if (action.dragable) { // drag & drop
			var _w; var _dbl = 0;
			var $drag = $('__drag'); $drag.length || ($drag = $('<input type="text" id="__drag" readonly="readonly" />'));

			$drag.css({
				position: 'absolute',
				background: 'transparent',
				top: -50,
				left: 0,
				margin: 0,
				border: 'none',
				lineHeight: 1,
				opacity: 0,
				cursor: 'pointer',
				borderRadius: 0
			}).appendTo(document.body).mousedown(function (e) {
				if (_dbl) return;
				var _x = e.pageX - $drag.offset().left;
				var _y = e.pageY - $drag.offset().top;
				if (_x < 4 || _y < 4 || $drag.width() - _x < 0 || $drag.height() - _y < 0) {
					return;
				}
				$drag.width($(document.body).width() - $drag.offset().left - 5).css('opacity', 1);
				$drag.select();
				_dbl = true; setTimeout(function () {_dbl = false;}, 400);
			}).mouseup(function () {
				$drag.css('opacity', 0).css('width', _w);
				$drag.blur();
			}).dblclick(function(){
				if (dblclickbuf.selected)
				{
					$('.xml-element[title*="/'+dblclickbuf.value.replace('{','').replace('}','')+'"]').removeClass('selected');

					if ($(this).val() == dblclickbuf.value)
					{
						dblclickbuf.value = '';
						dblclickbuf.selected = false;
					}
					else
					{
						dblclickbuf.selected = true;
						dblclickbuf.value = $(this).val();
						$('.xml-element[title*="/'+$(this).val().replace('{','').replace('}','')+'"]').addClass('selected');
					}
				}
				else
				{
					dblclickbuf.selected = true;
					dblclickbuf.value = $(this).val();
					$('.xml-element[title*="/'+$(this).val().replace('{','').replace('}','')+'"]').addClass('selected');
				}
			});
			
			$('#title, #content, .widefat, input[name^=custom_name], textarea[name^=custom_value], input[name^=featured_image], input[name^=unique_key]').bind('focus', insertxpath );
			
			$(document).mousemove(function () {
				if (parseInt($drag.css('opacity')) != 0) {
					setTimeout(function () {
						$drag.css('opacity', 0);
					}, 50);
					setTimeout(function () {
						$drag.css('width', _w);
					}, 500);
				}
			});
			
			if ($('#content').length) tinymce.dom.Event.add('wp-content-editor-container', 'click', function(e) {
				if (dblclickbuf.selected)
				{
					tinyMCE.activeEditor.selection.setContent(dblclickbuf.value);
					$('.xml-element[title*="'+dblclickbuf.value.replace('{','').replace('}','')+'"]').removeClass('selected');
					dblclickbuf.value = '';
					dblclickbuf.selected = false;					
				}				
			});

			this.find('.xml-tag.opening > .xml-tag-name, .xml-attr-name').each(function () {
				var $this = $(this);
				var xpath = '{' + (($this.is('.xml-attr-name') ? $this.parent() : $this.parent().parent()).attr('title').replace(/^\/[^\/]+\/?/, '') || '.') + '}';

				$this.mouseover(function (e) {
					$drag.val(xpath).offset({left: $this.offset().left - 2, top: $this.offset().top - 2}).width(_w = $this.width() + 4).height($this.height() + 4);
				});
			}).eq(0).mouseover();
		}
		return this;
	};

	// selection logic
	$('form.choose-elements').each(function () {
		var $form = $(this);
		$form.find('.xml').xml();
		var $input = $form.find('input[name="xpath"]');
		$form.find('.xml-tag.opening').bind('mousedown', function () {return false;}).dblclick(function () {
			if ($form.hasClass('loading')) return; // do nothing if selecting operation is currently under way
			$input.val($(this).parents('.xml-element').first().attr('title').replace(/\[\d+\]$/, '')).change();
		});
		var xpathChanged = function () {
			if ($input.val() == $input.data('checkedValue')) return;
			$form.addClass('loading');
			$form.find('.xml-element.selected').removeClass('selected'); // clear current selection
			// request server to return elements which correspond to xpath entered
			$input.attr('readonly', true).unbind('change', xpathChanged).data('checkedValue', $input.val());
			$('.ajax-console').load('admin.php?page=pmxi-admin-import&action=evaluate', {xpath: $input.val()}, function () {
				$input.attr('readonly', false).change(xpathChanged);
				$form.removeClass('loading');
			});
		};
		$input.change(xpathChanged).change();
		$input.keyup(function (e) {
			if (13 == e.keyCode) $(this).change();
		});
	});
	
	// tag preview
	$.fn.tag = function () {
		this.each(function () {
			var $tag = $(this);
			$tag.xml('dragable');
			var tagno = parseInt($tag.find('input[name="tagno"]').val());
			$tag.find('.navigation a').click(function () {
				tagno += '#prev' == $(this).attr('href') ? -1 : 1;
				$tag.addClass('loading').css('opacity', 0.7);
				$.post('admin.php?page=pmxi-admin-import&action=tag', {tagno: tagno}, function (data) {
					var $indicator = $('<span />').insertBefore($tag);
					$tag.replaceWith(data);
					$indicator.next().tag().prevObject.remove();
				}, 'html');
				return false;
			});
		});
		return this;
	};
	$('.tag').tag();
	// [/xml representation dynamic]
	
	$('input.autocomplete').each(function () {
		$(this).autocomplete({
			source: eval($(this).attr('id')),
			minLength: 0
		}).click(function () {
			$(this).autocomplete('search', '');
		});
	});

	/* Categories hierarchy */

	$('.sortable').nestedSortable({
        handle: 'div',
        items: 'li',
        toleranceElement: '> div',
        update: function () {	        
	       $(this).parents('td:first').find('.hierarhy-output').val(window.JSON.stringify($(this).nestedSortable('toArray', {startDepthCount: 0})));
	       if ($('.drag-element:first').find('input').val() == '') $(this).parents('td:first').find('.hierarhy-output').val('');
	    }
    });

    $('.drag-element').find('input').live('blur', function(){    	
    	$(this).parents('td:first').find('.hierarhy-output').val(window.JSON.stringify($(this).parents('.sortable:first').nestedSortable('toArray', {startDepthCount: 0})));
    	if ($('.drag-element:first').find('input').val() == '') $(this).parents('td:first').find('.hierarhy-output').val('');
    });

	$('.sortable').find('.remove-ico').live('click', function(){
	 	
	 	var parent_td = $(this).parents('td:first');
	 
		$(this).parents('li:first').remove(); 			
		parent_td.find('ol.sortable:first').find('li').each(function(i, e){
			$(this).attr({'id':'item_'+ (i+1)});
		});
		parent_td.find('.hierarhy-output').val(window.JSON.stringify(parent_td.find('.sortable:first').nestedSortable('toArray', {startDepthCount: 0})));	         	
	 	if ($('.drag-element:first').find('input').val() == '') parent_td.find('.hierarhy-output').val('');	 			 	
	});

	$('.add-new-ico').click(function(){		
		var count = $(this).parents('tr:first').find('ol.sortable').find('li').length + 1;
		$(this).parents('tr:first').find('ol.sortable').append('<li id="item_'+count+'"><div class="drag-element"><input type="text" value="" class="widefat"></div><a class="icon-item remove-ico" href="javascript:void(0);"></a></li>');
		$('.widefat').bind('focus', insertxpath );
	});

	/* END Categories hierarchy */



});})(jQuery);