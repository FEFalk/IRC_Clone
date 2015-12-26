$(function() {
    var chat;
    
    // Constants
    var Permissions = {};
    Object.defineProperty(Permissions, 'CHANNEL_VOICE',      withValue(1 << 0));
    Object.defineProperty(Permissions, 'CHANNEL_OPERATOR',   withValue(1 << 1));
    Object.defineProperty(Permissions, 'CHANNEL_BANNED',     withValue(1 << 2));
    
    
    // Chat functionality
    var Chat = {
        login: function(username, password) {
            var conn = new WebSocket('ws://' + window.location.hostname + ':8080');

            conn.onopen = function(e) {
                console.log("Connection established!\nLogging in... ");
                conn.send(JSON.stringify({type: 'login', message: {username: username, password: password}}));
            };

            conn.onmessage = this.onMessage;
            
            conn.onclose = function(e) {
                Chat.addMessage('', 'SERVER', 'Connection to server closed!');
            };
            
            return conn;
        },
        
        quit: function(quitmsg) {
            conn.send(JSON.stringify({type: 'quit', message: quitmsg}));
            chat.close();
        },
        
        onMessage: function(e) {
            var data = JSON.parse(e.data);
            if (data.success != null && data.success == false) {
                console.log("Failed: " + e.data);
                return;
            }
            // Response messages
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
            else if (data.type == "rjoin") {
                Chat.addChannel(data.message.name);
                Chat.setChannelTopic(data.message.name, data.message.topic);
                Chat.setChannelModes(data.message.name, data.message.modes);
                Chat.setUserList(data.message.name, data.message.users);
                $('#channel-list button[data-channel="' + data.message.name + '"]').click();
            }
            
            // Event messages
            else if (data.type == "message") {
                Chat.addMessage(data.to, data.from, data.message);
            }
            else if (data.type == "topic") {
                Chat.setChannelTopic(data.to, data.message);
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
            $('.channel-item[data-channel="' + chan + '"]').data('users', userlist);
        },
        
        addUser: function(chan, name, permissions, active) {
            permissions = typeof permissions !== 'undefined' ? permissions : 0;
            active = typeof active !== 'undefined' ? active : false;
            $('.channel-item[data-channel="' + chan + '"]').data('users')[name] = {active: active, permissions: permissions};
        },
        
        addMessage: function(chan, user, message) {
            var chandiv, doscroll = false;
            if (chan == '')
                chandiv = $('.channel-item:not(.hidden)');
            else
                chandiv = $('.channel-item[data-channel="' + chan + '"]');
            var chanlistitem = $('#channel-list > button[data-channel="'+chan+'"]');
            var chatitem = $('div.chat', chandiv);
            if (chatitem[0].scrollHeight - chatitem.scrollTop() == chatitem.outerHeight())
                doscroll = true;
           
            /*Parse it!*/
            var msg = parseMessage(message);

            var msg = $('<div class="row"><strong class="col-md-2"><a href="" class="text-danger">' + user + '</a></strong><span class="col-md-10">' + msg + '</span></div>');
            msg.appendTo(chatitem);
           
            if (chandiv.hasClass('hidden')) {
                $('span', chanlistitem).text(parseInt(($('span', chanlistitem).text()) || 0) + 1);
            }
            else if (doscroll) {
                chatitem.scrollTop(chatitem[0].scrollHeight);
            }
        }
    };
    
    /*Create a parseMessage object with all the functions in it?*/
    var parseMessage = function(text) {
        return checkForlinks(text);  
    }

    /*adds href tags to links, check for http:// https:// or www*/
    var checkForlinks= function(text) {
        var exp = /(\b(((https?|ftp|file|):\/\/)|www[.])[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig;
        var temp = text.replace(exp,"<a href=\"$1\">$1</a>");
        var result = "";
        while (temp.length > 0) {
            var pos = temp.indexOf("href=\"");
            if (pos == -1) {
                result += temp;
                break;
            }
            result += temp.substring(0, pos + 6);
            temp = temp.substring(pos + 6, temp.length);
            if ((temp.indexOf("://") > 8) || (temp.indexOf("://") == -1)) {
                result += "http://";
            }
        }
        return result;
    }
    
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
        updateActiveUserlist();
        $('#channel-list > button[data-channel="'+ channel +'"] span').text("");
        $('#channel-list button[data-channel="' + channel + '"]').addClass('active').siblings().removeClass('active');
    });
    
    function updateActiveUserlist() {
        var users = $('.channel-item:not(.hidden)').data('users'),
            keys = Object.keys(users),
            i, k, len, color;
        
        // Sort keys based on active status, permissions and name
        keys.sort(function(a, b){
            var usera = users[a], userb = users[b];
            if (!usera.active ^ !userb.active)
                return (usera.active ? -1 : 1);
            if (!(usera.permissions & Permissions.CHANNEL_OPERATOR) ^ !(userb.permissions & Permissions.CHANNEL_OPERATOR))
                return usera.permissions & Permissions.CHANNEL_OPERATOR ? -1 : 1;
            if (!(usera.permissions & Permissions.CHANNEL_VOICE) ^ !(userb.permissions & Permissions.CHANNEL_VOICE))
                return usera.permissions & Permissions.CHANNEL_VOICE ? -1 : 1;
            console.log(a + ":" + b + " - " + a.localeCompare(b));
            return a.localeCompare(b);
        });
        len = keys.length;
        $('#user-list').text('');
        for(i = 0; i < len; ++i) {
            k = keys[i];
            color = 'text-info';
            if (!users[k].active)
                color = 'text-muted';
            else if (users[k].permissions & Permissions.CHANNEL_OPERATOR)
                color = 'text-danger';
            else if (users[k].permissions & Permissions.CHANNEL_VOICE)
                color = 'text-warning';
            $('#user-list').append('<li role="presentation"><a href="#" class="'+ color +'">' + k + '</a></li>');
        };
    }
    
    // Alert close button
    $('.alertclose').click(function() {
        $(this).parent().addClass('hidden');
    });
    
    // Send message
    var sendMessage = function() {
        var msg = $('#chat-input').val();
        var activechan = $('.channel-item:not(.hidden)').attr('data-channel');

        // If message is a command
        if (msg.charAt(0) == '/') {
            var split = msg.split(' ');
            var cmd = split[0].substr(1),
                arg = split.length > 1 ? split[1] : ''
                msg = split.length > 2 ? split.splice(2).join(' ') : '';
            
            console.log('/' + cmd + ': ' + arg + ' - ' + msg);
            
            if (cmd == 'join') {
                chat.send(JSON.stringify({type: 'join', message: {chan: arg, password: msg}}));
            } else if (cmd == 'quit') {
                chat.send(JSON.stringify({type: 'quit', message: arg}));
                chat.close();
            } else if (cmd == 'topic') {
                //makes it possible to have space:s in the topic message
                var topic = arg.concat(" ").concat(msg);
                chat.send(JSON.stringify({type: 'topic', to: activechan, message: topic}));
            }
        }
        else {
            chat.send(JSON.stringify({type: 'message', to: activechan, message: msg}));
        }
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
                console.log(response);
                $('#registerModal').modal('hide');
            }
            else {
                showAlert('#reg-alert', '#reg-txt-alert', 'Unable to register: ' + response.message);
            }
        })
        .fail(function(msg) {
            inputs.prop('disabled', false);
        });
    });
    $('#registerModal').on('hidden.bs.modal', function() { 
        $('#register-form').trigger("reset");
    });
    
    // Support functions
    function withValue(value) {
        var d = withValue.d || (
            withValue.d = {
                enumerable: false,
                writable: false,
                configurable: false,
                value: null
            }
        );
        d.value = value;
        return d;
    }
    
    // Show alert (alert-id, alert-text-id, message)
    function showAlert(id, txtid, msg) {
        $(id).removeClass('hidden');
        $(txtid).text(msg);
    };
});
