$(function() { 
	$('#loggedIn-form').hide();
	$('#btnSignIn').on('click', function() {
		$('#loggedIn-form').toggle();
		$('#login-form').toggle();
		$('#loggedInName').html($('#login-username:text').val());
		
	});
	$('#btnLogout').on('click', function (){
		$('#loggedIn-form').toggle();
		$('#login-form').toggle();
	});
});
