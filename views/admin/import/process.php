<div class="inner-content">
	<h2><?php _e('Import XML - <span id="status">Processing...</span>', 'pmxi_plugin') ?></h2>
	<hr />
	
	<p><?php _e('Importing may take some time. Please do not close browser or refresh the page untill process is complete.', 'pmxi_plugin') ?></p>
</div>

<script type="text/javascript">
//<![CDATA[
(function($){
	$('#status').each(function () {
		var $this = $(this);
		if ($this.html().match(/\.{3}$/)) {
			var dots = 0;
			var status = $this.html().replace(/\.{3}$/, '');
			var interval ;
			interval = setInterval(function () {
				if ($this.html().match(new RegExp(status + '\\.{1,3}$', ''))) {
					$this.html(status + '...'.substr(0, dots++ % 3 + 1));
				} else {
					clearInterval(interval);
				}
			}, 1000);
		}
	});
	window.onbeforeunload = function () {
		return 'WARNING:\nImport process in under way, leaving the page will interrupt\nthe operation and most likely to cause leftovers in posts.';
	};
})(jQuery);
//]]>
</script>