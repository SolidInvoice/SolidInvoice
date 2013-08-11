(function($){
	$('#grid-advanced-search').on('click', function(evt){
		evt.preventDefault();
		$('.form-search').hide();
		$('.grid-search').slideDown();
	});
	
	$('#grid-simple-search').on('click', function(evt){
		evt.preventDefault();
		$('.grid-search').slideUp(function(){
			$('.form-search').show();
		});
	});
})(jQuery);