$(function() {
    var chat;
    
    // Chat functionality
    var Chat = {
        login: function(username, password) {
            var conn = new WebSocket('ws://localhost:8080');
            conn.onopen = function(e) {
                console.log("Connection established!\nLogging in... ");
                conn.send(JSON.stringify({type: 'login', message: {username: username, password: password}}));
            };

            conn.onmessage = this.onMessage;
            
            return conn;
        },
        
        quit: function(quitmsg) {
            conn.send(JSON.stringify({type: 'quit', message: quitmsg}));
            chat.close();
        },
        
        onMessage: function(e) {
            var data = JSON.parse(e.data);
            if (data.type == "rlogin") {
                if (data.success == true) {
                    console.log("Success!\n");
                    $.each(data.message.channels, function(key, val) {
                        Chat.addChannel(key);
                        Chat.setChannelTopic(key, val.topic);
                        Chat.setChannelModes(key, val.modes);
                        Chat.setUserList(key, val.users);
                    });
                    $('#channel-list button').first().click();
                }
                else {
                    console.log("Failed: " + data.message + ".\n");
                }
            }
            else if (data.type == "message") {
                Chat.addMessage(data.to, data.from, data.message);
            }
            console.log(e.data);
        },
        
        addChannel: function(name) {
            if ($('#channel-list > button[data-channel="' + name + '"]').length)
                return;
            // Add to channel list
            var added = false;
            var btn = $('<button type="button" class="btn btn-primary" data-channel="' + name + '">' + name + '<span class="badge"></span></button>');
            $('#channel-list > button').each(function(i) {
                if (name.toUpperCase() < $(this).text().toUpperCase()) {
                    btn.insertBefore($(this));
                    added = true;
                    return false;
                }
            });
            if (!added)
                btn.appendTo('#channel-list');
            
            // Clone new div from template
            var clone = $('.channel-item[data-channel=""]').clone(true).attr('data-channel', name);
            $('span[data-type="name"]', clone).text(name);
            $('#channel-container').append(clone);
        },
        
        setChannelTopic: function(chan, topic) {
            $('.channel-item[data-channel="' + chan + '"] span[data-type="topic"]').text(topic);
        },
        
        setChannelModes: function(chan, modes) {
            $('.channel-item[data-channel="' + chan + '"] span[data-type="modes"]').text(modes);
        },
        
        setUserList: function(chan, userlist) {
            $('.channel-item[data-channel="' + chan + '"]').attr('data-users', JSON.stringify(userlist));
        },
        
        addUser: function(chan, name, active) {
            active = typeof active !== 'undefined' ? active : false;
        },
        
        addMessage: function(chan, user, message) {
            var chandiv = $('.channel-item[data-channel="' + chan + '"]');
            var chanlistitem = $('#channel-list > button[data-channel="'+chan+'"]');
            if (chandiv.hasClass('hidden')) {
                $('span', chanlistitem).text(parseInt(($('span', chanlistitem).text()) || 0) + 1);
            }
            var msg = $('<div class="row"><strong class="col-md-2"><a href="" class="text-danger">' + user + '</a></strong><span class="col-md-10">' + message + '</span></div>');
            msg.appendTo($('div[data-type="chat"]', chandiv));
            $('.channel-item:not(.hidden) div.chat').scrollTop($('.channel-item:not(.hidden)')[0].scrollHeight);
        }
    };
    
    // Show alert (alert-id, alert-text-id, message)
    var showAlert = function(id, txtid, msg) {
        $(id).removeClass('hidden');
        $(txtid).text(msg);
    };
    
    $('#login-form').submit(function(event) {
        event.preventDefault();
        
        chat = Chat.login($('#login-username').val(), $('#login-password').val());
    });
    
    // Change channel
    $('#channel-list').on('click', 'button', function() {
        // Strip badge text
        var channel = $(this).clone().children().remove().end().text();
        $('.channel-item:not(.hidden)').addClass('hidden');
        $('.channel-item[data-channel="' + channel + '"').removeClass('hidden');
        $('#user-list').text('');
        $.each(JSON.parse($('.channel-item[data-channel="' + channel + '"]').attr('data-users')), function(name, active) {
            // TODO: color from permission? text-danger, text-success, text-muted etc...
            var color = '';
            if (!active)
                color = 'text-muted';
            $('#user-list').append('<li role="presentation"><a href="#" class="'+ color +'">' + name + '</a></li>');
        });
    });
    
    // Alert close button
    $('.alertclose').click(function() {
        $(this).parent().addClass('hidden');
    });
    
    // Send message
    var sendMessage = function() {
        var activechan = $('.channel-item:not(.hidden)').attr('data-channel');
        var msg = $('#chat-input').val();
        chat.send(JSON.stringify({type: 'message', to: activechan, message: msg}));
        $('#chat-input').val('');
    };
    $('#basic-addon2').on('click', function() {
        sendMessage();
    });
    $('#chat-input')
    .autoresize()
    .keypress(function (e) {
        if (e.which == 13) {
            e.preventDefault();
            sendMessage();
        }
    });
    
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
