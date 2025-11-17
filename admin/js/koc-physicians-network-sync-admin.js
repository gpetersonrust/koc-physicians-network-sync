(function( $ ) {
	'use strict';

	$(function() {
		$('#koc-pns-copy-password').on('click', function() {
			var passwordField = document.getElementById('koc_pns_application_password');
			var copyButton = $(this);

			passwordField.select();
			document.execCommand('copy');

			copyButton.text('Copied!');
			setTimeout(function() {
				copyButton.text('Copy');
			}, 2000);
		});
	});

})( jQuery );
