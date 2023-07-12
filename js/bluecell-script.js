jQuery(document).ready(function($) {
	$('#bluecell-form').submit(function(e) {
		e.preventDefault();

		var name = $('#bluecell-name').val();
		var email = $('#bluecell-email').val();
		var phone = $('#bluecell-phone').val();
		var message = $('#bluecell-message').val();
		var subject = $('#bluecell-subject').val();
		var accept = $('#bluecell-accept').is(':checked');

		console.log(message)

		var data = {
			action: 'bluecell_process_form',
			security: bluecell_ajax.nonce,
			name: name,
			email: email,
			phone: phone,
			message: message,
			subject: subject,
			accept: accept
		};

		$.ajax({
			type: 'POST',
			url: bluecell_ajax.ajax_url,
			data: data,
			dataType: 'json',
			success: function(response) {
				alert(response.data);
			},
			error: function(error) {
				console.log(error);
			}
		});
	});
});
