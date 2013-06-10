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
		if ($(this).val() == 'upload' || $(this).val() == 'file' || $(this).val() == 'reimport' || $(this).val() == 'url') $('#large_import').slideDown(); else { $('#large_import').slideUp(); $('#large_import_toggle').removeAttr('checked'); $('#large_import_xpath').slideUp();}
		var $container = $(this).parents('.file-type-container');		
		$('.file-type-container').not($container).removeClass('selected').find('.file-type-options').hide();
		$container.addClass('selected').find('.file-type-options').show();
	}).filter(':checked').click();
	
	// template form: auto submit when `load template` list value is picked
	$('form.template, form.options').find('select[name="load_template"]').change(function () {
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
	$('input[name="load_options"]').click(function () {		
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
			this.find('.xml-expander').live('click', function () {
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
				borderRadius: 0,
				zIndex:99
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
			
			if ($('#content').length && window.tinymce != undefined) tinymce.dom.Event.add('wp-content-editor-container', 'click', function(e) {
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
				var xpath = '.';
				if ($this.is('.xml-attr-name'))
					xpath = '{' + ($this.parents('.xml-element:first').attr('title').replace(/^\/[^\/]+\/?/, '') || '.') + '/@' + $this.html().trim() + '}';
				else
					xpath = '{' + ($this.parent().parent().attr('title').replace(/^\/[^\/]+\/?/, '') || '.') + '}';

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
		var $next_element = $form.find('#next_element');
		var $prev_element = $form.find('#prev_element');		
		var $goto_element =  $form.find('#goto_element');
		var $get_default_xpath = $form.find('#get_default_xpath');	
		var $root_element = $form.find('#root_element');		

		var $xml = $('.xml');
		$form.find('.xml-tag.opening').live('mousedown', function () {return false;}).live('dblclick', function () {
			if ($form.hasClass('loading')) return; // do nothing if selecting operation is currently under way
			$input.val($(this).parents('.xml-element').first().attr('title').replace(/\[\d+\]$/, '')).change();
		});
		var xpathChanged = function () {
			if ($input.val() == $input.data('checkedValue')) return;			
			var xpath_elements = $input.val().split('[');			
			var xpath_parts = xpath_elements[0].split('/');
			xpath_elements[0] = '';			
			$input.val('/' + xpath_parts[xpath_parts.length - 1] + ((xpath_elements.length) ? xpath_elements.join('[') : ''));
			$form.addClass('loading');			
			$form.find('.xml-element.selected').removeClass('selected'); // clear current selection
			// request server to return elements which correspond to xpath entered
			$input.attr('readonly', true).unbind('change', xpathChanged).data('checkedValue', $input.val());
			$xml.css({'visibility':'hidden'});
			$xml.parents('fieldset:first').addClass('preload');
			$('.ajax-console').load('admin.php?page=pmxi-admin-import&action=evaluate', {xpath: $input.val(), show_element: $goto_element.val(), root_element:$root_element.val()}, function () {
				$input.attr('readonly', false).change(function(){$goto_element.val(1); xpathChanged();});
				$form.removeClass('loading');
				$xml.parents('fieldset:first').removeClass('preload');
			});
		};
		$next_element.click(function(){
			var show_element = Math.min((parseInt($goto_element.val()) + 1), parseInt($('.matches_count').html()));
			$goto_element.val(show_element).html( show_element ); $input.data('checkedValue', ''); xpathChanged();
		});
		$prev_element.click(function(){
			var show_element = Math.max((parseInt($goto_element.val()) - 1), 1);
			$goto_element.val(show_element).html( show_element ); $input.data('checkedValue', ''); xpathChanged();
		});
		$goto_element.change(function(){
			var show_element = Math.max(Math.min(parseInt($goto_element.val()), parseInt($('.matches_count').html())), 1);
			$goto_element.val(show_element); $input.data('checkedValue', ''); xpathChanged();			
		});
		$get_default_xpath.click(function(){$root_element.val($(this).attr('root')); $goto_element.val(1); $input.val($(this).attr('rel')); xpathChanged();});
		$('.change_root_element').click(function(){
			$root_element.val($(this).attr('rel')); $goto_element.val(1); $input.val('/' + $(this).attr('rel')); xpathChanged();
		});
		$input.change(function(){$goto_element.val(1); xpathChanged();}).change();
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
					if ($('#variations_xpath').length){
						$('#variations_xpath').data('checkedValue', '').change();
					}
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
	       if ($(this).parents('td:first').find('input:first').val() == '') $(this).parents('td:first').find('.hierarhy-output').val('');
	    }
    });

    $('.drag-element').find('input').live('blur', function(){    	
    	$(this).parents('td:first').find('.hierarhy-output').val(window.JSON.stringify($(this).parents('.sortable:first').nestedSortable('toArray', {startDepthCount: 0})));
    	if ($(this).parents('td:first').find('input:first').val() == '') $(this).parents('td:first').find('.hierarhy-output').val('');
    });

    $('.drag-element').find('input').live('change', function(){    	
    	$(this).parents('td:first').find('.hierarhy-output').val(window.JSON.stringify($(this).parents('.sortable:first').nestedSortable('toArray', {startDepthCount: 0})));
    	if ($(this).parents('td:first').find('input:first').val() == '') $(this).parents('td:first').find('.hierarhy-output').val('');
    });

    $('.drag-element').find('input').live('hover', function(){},function(){    	
    	$(this).parents('td:first').find('.hierarhy-output').val(window.JSON.stringify($(this).parents('.sortable:first').nestedSortable('toArray', {startDepthCount: 0})));
    	if ($(this).parents('td:first').find('input:first').val() == '') $(this).parents('td:first').find('.hierarhy-output').val('');
    });

    $('.taxonomy_auto_nested').live('click', function(){
    	$(this).parents('td:first').find('.hierarhy-output').val(window.JSON.stringify($(this).parents('td:first').find('.sortable:first').nestedSortable('toArray', {startDepthCount: 0})));
    	if ($(this).parents('td:first').find('input:first').val() == '') $(this).parents('td:first').find('.hierarhy-output').val('');
    });

	$('.sortable').find('.remove-ico').live('click', function(){
	 	
	 	var parent_td = $(this).parents('td:first');
	 
		$(this).parents('li:first').remove(); 			
		parent_td.find('ol.sortable:first').find('li').each(function(i, e){
			$(this).attr({'id':'item_'+ (i+1)});
		});
		parent_td.find('.hierarhy-output').val(window.JSON.stringify(parent_td.find('.sortable:first').nestedSortable('toArray', {startDepthCount: 0})));	         	
	 	if (parent_td.find('input:first').val() == '') parent_td.find('.hierarhy-output').val('');	 			 	
	});

	$('.add-new-ico').click(function(){		
		var count = $(this).parents('tr:first').find('ol.sortable').find('li').length + 1;
		$(this).parents('tr:first').find('ol.sortable').append('<li id="item_'+count+'"><div class="drag-element"><input type="checkbox" class="assign_post" checked="checked"/><input type="text" value="" class="widefat"></div><a class="icon-item remove-ico" href="javascript:void(0);"></a></li>');
		$(this).parents('td:first').find('.hierarhy-output').val(window.JSON.stringify($(this).parents('.sortable:first').nestedSortable('toArray', {startDepthCount: 0})));
    	if ($(this).parents('td:first').find('input:first').val() == '') $(this).parents('td:first').find('.hierarhy-output').val('');
		$('.widefat').bind('focus', insertxpath );
	});
	
	$('form.options').find('input[type=submit]').click(function(e){
		e.preventDefault();
		
		$('.hierarhy-output').each(function(){			
			$(this).val(window.JSON.stringify($(this).parents('td:first').find('.sortable:first').nestedSortable('toArray', {startDepthCount: 0})));			
			if ($(this).parents('td:first').find('input:first').val() == '') $(this).val('');
		});
		if ($(this).attr('name') == 'btn_save_only') $('.save_only').val('1');

		$('input[name^=in_variations], input[name^=is_visible], input[name^=is_taxonomy], input[name^=create_taxonomy_in_not_exists], input[name^=variable_create_taxonomy_in_not_exists], input[name^=variable_in_variations], input[name^=variable_is_visible], input[name^=variable_is_taxonomy]').each(function(){
	    	if ( ! $(this).is(':checked') && ! $(this).parents('.form-field:first').hasClass('template')){	    		
	    		$(this).val('0').attr('checked','checked');
	    	}
	    });

		$(this).parents('form:first').submit();
	});

	/* END Categories hierarchy */	

	// manage screen: cron url
	$('.get_cron_url').each(function () {
		var $form = $(this);
		var $modal = $('<div></div>').dialog({
			autoOpen: false,
			modal: true,
			title: 'Cron URLs',
			width: 760,
			maxHeight: 600,
			open: function(event, ui) {
				$(this).dialog('option', 'height', 'auto').css({'max-height': $(this).dialog('option', 'maxHeight') - $(this).prev().height() - 24, 'overflow-y': 'auto'}); 
	    	}
		});	
		$form.find('a').click(function () {
			$modal.addClass('loading').empty().dialog('open').dialog('option', 'position', 'center');									
			$modal.removeClass('loading').html('<textarea style="width:100%; height:100%;">' + $form.find('a').attr('rel') + '</textarea>').dialog('option', 'position', 'center');
		});
	});
	
	// chunk files upload
	if ($('#plupload-ui').length)
	{
		$('#plupload-ui').show();
		$('#html-upload-ui').hide();	

		wplupload = $('#select-files').wplupload({
			runtimes : 'gears,browserplus,html5,flash,silverlight,html4',
			url : 'admin.php?page=pmxi-admin-settings&action=upload',
			container: 'plupload-ui',
			browse_button : 'select-files',
			file_data_name : 'async-upload',
			flash_swf_url : plugin_url + '/static/js/plupload/plupload.flash.swf',
			silverlight_xap_url : plugin_url + '/static/js/plupload/plupload.silverlight.xap',
		
			multipart: false,
			max_file_size: '1000mb',
			chunk_size: '1mb',			
			drop_element: 'plupload-ui'
		});
	}	

	/* END plupload scripts */

	if ($('#large_import_toggle').is(':checked')) $('#large_import_xpath').slideToggle();

	$('#large_import_toggle').click(function(){
		$('#large_import_xpath').slideToggle();
	});

	// Step 4 - custom meta keys helper	

	if ($('#pmxi_tabs').length){ 		
		if ($('form.options').length){
			if ($('#selected_post_type').val() != ''){
				var post_type_founded = false;
				$('input[name=custom_type]').each(function(i){					
					if ($(this).val() == $('#selected_post_type').val()) { $('#pmxi_tabs').tabs({ selected:i }).show(); post_type_founded = true; }
				});
				if ( ! post_type_founded){
					$('#pmxi_tabs').tabs({ selected: ($('#selected_type').val() == 'post') ? 0 : 1 }).show();					
				}
			}
			else if ($('#selected_type').val() != ''){
				$('#pmxi_tabs').tabs({ selected: ($('#selected_type').val() == 'post') ? 0 : 1 }).show();				
			}
		}
		else
			$('#pmxi_tabs').tabs().show();
	}

	if ($('#upload_process').length){ 
		$('#upload_process').progressbar({ value: (($('#progressbar').html() != '') ? 100 : 0) });
		if ($('#progressbar').html() != '')
			$('.submit-buttons').show();
	}

	$('#view_log').live('click', function(){
		$('#import_finished').css({'visibility':'hidden'});
		$('#logwrapper').slideToggle(100, function(){
			$('#import_finished').css({'visibility':'visible'});
		});
	});			

    $(document).scroll(function() {    	    	
        if ($(document).scrollTop() > 135)
            $('.tag').css({'top':'30px'});        
        else
        	$('.tag').css({'top':''});
    });           

});})(jQuery);
