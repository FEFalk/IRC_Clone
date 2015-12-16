$(function() {
    // Show alert (alert-id, alert-text-id, message)
    var showAlert = function(id, txtid, msg) {
        $(id).removeClass('hidden');
        $(txtid).text(msg);
    };
    
    // Alert close button
    $('.alertclose').click(function() {
        $(this).parent().addClass('hidden');
    });
    
    $('#chat-input').autoresize();
    
    // Register form
    $('#register-form-submit').click(function(event) {
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
        .done(function(response) {
            inputs.prop('disabled', false);
            if (response.success) {
                $('registerModal').modal('hide');
            }
            else {
                showAlert('#reg-alert', '#reg-txt-alert', 'Unable to register: ' + response.message);
            }
        })
        .fail(function(msg) {
            inputs.prop('disabled', false);
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