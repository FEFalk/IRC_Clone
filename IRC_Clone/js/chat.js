$(function() {

    /*Makes the Register/login in form Toggable*/
	$('#register').clickToggle(function() {
			$('<div class="form-group" id="email-group">'+
                '<label for="username_input" id="email-label"> Email </label>'+
                '<br><input class="form-control" type="text" name="email" id="email" placeholder="optional">'+
            '</div>').insertBefore($('#submit-group'));
            $('#register').text('Back to login');
		}, function() {
            $('#email-group').remove();
            $('#register').text('Register');
    });

    $('#login-form').submit(function(event) {
        var data = {
            'username' : $('#username').val(),
            'password' : $('#password').val(),
            'email' : $('#email').val()
        };

        var form = $(this);
        var inputs = form.find("input, select");

        //seralize:s it, which will make it easier for the PHP code
        var serializedData = form.serialize();

        //disable inputs
        inputs.prop('disabled',true);

        $.ajax({
            method: 'POST',
            url: 'register.php',
            dataType: 'json',
            data: serializedData
        })
        .done(function(response) {
            console.log(response);
        })
        .fail(function(msg) {
            console.log(msg);
        });
    });

});

/*Creates the clickToggle function*/
/*Toggles between two Function that you pass in*/
(function($) {
    $.fn.clickToggle = function(func1, func2) {
        var funcs = [func1, func2];
        this.data('toggleclicked', 0);
        this.click(function() {
            var data = $(this).data();
            var tc = data.toggleclicked;
            $.proxy(funcs[tc], this)();
            data.toggleclicked = (tc + 1) % 2;
        });
        return this;
    };
}(jQuery));