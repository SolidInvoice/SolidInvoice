/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

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