jQuery( document ).ready( function($) {
	var myheader = $("#masthead");
	$(document).on('scroll', function(e) {
		if ($(this).scrollTop() > 100) {
			myheader.addClass('gpc-fix-mastheasd');
		} else {
		    myheader.removeClass('gpc-fix-mastheasd');
		}
	});
});

