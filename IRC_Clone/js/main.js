$(function () {
    $("#mainNavbar").hide();
    $("#chat-content").hide();
    $("#register-form").hide();
    $("#regErrorMsg").hide();
    // Register form
    $('#register-form-submit').click(function (event) {
        event.preventDefault();
        var inputs = $(this).find("input, select");
        
        inputs.prop('disabled', true);

        var formdata = $('#register-form').serialize();
        // TODO: Validate formdata

        $.ajax({
            method: 'POST',
            url: 'register.php',
            dataType: 'json',
            data: formdata
        })
        .done(function (response) {
            inputs.prop('disabled', false);
            if (response.success) {
                console.log(response);
                
                $('#registerModal').modal('hide');
                $('#btnNotRegistered').toggle();
                $('#notRegText').toggle();
                $('#title').html('User Login')
                $('#homeLoginForm').slideToggle(200);
                $('#register-form').slideToggle(200);
            }
            else {
                showAlert('#reg-alert', '#reg-txt-alert', 'Unable to register: ' + response.message);
            }
        })
        .fail(function (msg) {
            inputs.prop('disabled', false);
        });
    });

    $('#searchWord').keypress(function (event) {

        if (event.which == 13) {
            event.preventDefault();

            var inputs = $(this).find("input, select");
            inputs.prop('disabled', true);

            var formdata = $('#search-form').serialize();
            console.log(formdata);
            // TODO: Validate formdata

            $.ajax({
                method: 'POST',
                url: 'search.php',
                dataType: 'json',
                data: formdata
            })
		    .done(function (response) {
		        inputs.prop('disabled', false);
		        if (response.success) {
		            console.log(response.message.name);
		        }
		        else {
		            showAlert('#search-alert', '#search-txt-alert', 'Error: ' + response.message);
		        }

		    })
            .fail(function (msg) {
                inputs.prop('disabled', false);
            });
        }
    });


    $('#registerModal').on('hidden.bs.modal', function () {
        $('#register-form').trigger("reset");
    });

    $('#btnNotRegistered').on('click', function () {
        
        $('#btnNotRegistered').toggle();
        $('#notRegText').toggle();
        $('#title').html('Create Account')
        $('#homeLoginForm').slideToggle(1000);
        $('#register-form').slideToggle(1200);
            
       
    });
    $('#register-form-cancel').on('click', function () {

        $('#btnNotRegistered').toggle();
        $('#notRegText').toggle();
        $('#title').html('User Login')
        $('#homeLoginForm').slideToggle(200);
        $('#register-form').slideToggle(200);


    });
    $('#btnLogin').on('click', function () {
        $("#mainNavbar").show();
        $("#chat-content").show();
        $("#titleContainer").hide(500);
        $("#loginContainer").hide(500);
        $("#registerContainer").hide(500);
    });

    $('#btnLogout').on('click', function () {
        $("#mainNavbar").hide();
        $("#chat-content").hide();
        $("#titleContainer").show(500);
        $("#loginContainer").show(500);
        $("#registerContainer").show(500);
    });

    // Show alert (alert-id, alert-text-id, message)
    function showAlert(id, txtid, msg) {
        $(id).removeClass('hidden');
        $(txtid).text(msg);
    };

});

