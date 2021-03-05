// The toggle
(function($){

	$(function(){
		$('#grid').on('click', function() {
			$(this).addClass('active');
			$('#list').removeClass('active');
			$.cookie('gridcookie','grid', { path: '/' });
			$('ul.products').fadeOut(300, function() {
				$(this).addClass('grid').removeClass('list').fadeIn(300);
			});
			return false;
		});
	
		$('#list').on('click', function() {
	
			$(this).addClass('active');
			$('#grid').removeClass('active');
			$.cookie('gridcookie','list', { path: '/' });
			$('ul.products').fadeOut(300, function() {
				$(this).removeClass('grid').addClass('list').fadeIn(300);
			});
			return false;
		});
	
		if ($.cookie('gridcookie')) {
			$('ul.products, #gridlist-toggle').addClass($.cookie('gridcookie'));
		}
	
		if ($.cookie('gridcookie') == 'grid') {
			$('.gridlist-toggle #grid').addClass('active');
			$('.gridlist-toggle #list').removeClass('active');
		}
	
		if ($.cookie('gridcookie') == 'list') {
			$('.gridlist-toggle #list').addClass('active');
			$('.gridlist-toggle #grid').removeClass('active');
		}
	
		$('#gridlist-toggle a').on('click', function(event) {
			event.preventDefault();
		});
	});

})(jQuery);​
