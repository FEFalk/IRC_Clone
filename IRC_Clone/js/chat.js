/* global Notification */
/* global chat */
$(function() {
    window.chat = null;
    var chatuser = '';
    
    // Constants
    var Permissions = {};
    Object.defineProperty(Permissions, 'MODE_PRIVATE',      withValue(1 << 0));
    Object.defineProperty(Permissions, 'MODE_MODERATED',   withValue(1 << 1));
    
    Object.defineProperty(Permissions, 'CHANNEL_VOICE',      withValue(1 << 0));
    Object.defineProperty(Permissions, 'CHANNEL_OPERATOR',   withValue(1 << 1));
    Object.defineProperty(Permissions, 'CHANNEL_BANNED',     withValue(1 << 2));
    
    // Chat functionality
    var Chat = {
        login: function(username, password) {
            if (chat)
                chat.close();
            chat = new WebSocket('ws://' + window.location.hostname + ':8080');

            chat.onopen = function(e) {
                console.log("Connection established!\nLogging in... ");
                chat.send(JSON.stringify({type: 'login', message: {username: username, password: password}}));
            };

            chat.onmessage = this.onMessage;
            
            chat.onclose = function(e) {
                Chat.addMessage('', 'SERVER', 'Connection to server closed!');
            };
            
            chat.onerror = function(e) {
                Chat.addMessage('', 'SERVER', 'Connection error!');
            }
            
            return chat;
        },
        
        quit: function(quitmsg) {
            chat.send(JSON.stringify({type: 'quit', message: quitmsg}));
            chat.close();
        },
        
        onMessage: function(e) {
            var data = JSON.parse(e.data);
            
            console.log(e.data);
            
            // Event messages
            if (data.type === 'message') {
                Chat.addMessage(data.to, data.from, data.message, data.date);
            }
            else if (data.type === 'online') {
                if (data.from === chatuser) return;
                $('.channel-item[data-channel="' + data.to + '"]').data('users')[data.from].active = true;
                Chat.addMessage(data.to, 'ONLINE', data.from + ' has come online!', data.date);
                if (data.to === $('.channel-item:not(.hidden)').attr('data-channel'))
                    updateActiveUserlist();
            }
            else if (data.type === 'offline') {
                if (data.from === chatuser) return;
                $('.channel-item[data-channel="' + data.to + '"]').data('users')[data.from].active = false;
                Chat.addMessage(data.to, 'OFFLINE', data.from + ' has gone offline!', data.date);
                if (data.to === $('.channel-item:not(.hidden)').attr('data-channel'))
                    updateActiveUserlist();
            }
            else if (data.type === 'join') {
                Chat.addUser(data.to, data.from, data.message, true);
                Chat.addMessage(data.to, 'JOIN', data.from + ' has joined the channel!', data.date);
                if (data.to === $('.channel-item:not(.hidden)').attr('data-channel'))
                    updateActiveUserlist();
            }
            else if (data.type === 'part') {
                Chat.removeUser(data.to, data.from);
                Chat.addMessage(data.to, 'PART', data.from + ' has left the channel!', data.date);
                if (data.to === $('.channel-item:not(.hidden)').attr('data-channel'))
                    updateActiveUserlist();
            }
            else if (data.type === 'kick') {
                Chat.removeUser(data.to, data.message);
                Chat.addMessage(data.to, 'KICK', data.message + ' was kicked from the channel by ' + data.from + '!', data.date);
                if (data.to === $('.channel-item:not(.hidden)').attr('data-channel'))
                    updateActiveUserlist();
            }
            else if (data.type === 'umode') {
                var mode = parseInt(data.message.mode) || 0;
                if (mode & Permissions.CHANNEL_BANNED)
                    Chat.removeUser(data.to, data.from);
                else
                    $('.channel-item[data-channel="' + data.to + '"]').data('users')[data.message.user].permissions = mode;
                Chat.addMessage(data.to, 'USERMODE', data.from + ' changed usermode of ' + data.message.user + ' to ' + data.message.mode + '!', data.date);
                if (data.to === $('.channel-item:not(.hidden)').attr('data-channel'))
                    updateActiveUserlist();
            }
            else if (data.type === 'name') {
                var info = $('.channel-item[data-channel="' + data.to + '"]').data('users')[data.message];
                Chat.removeUser(data.to, data.message);
                Chat.addUser(data.to, data.from, info.permissions, info.active);
                Chat.addMessage(data.to, 'NAME', data.message + ' changed name to ' + data.from + '!', data.date);
                if (data.to === $('.channel-item:not(.hidden)').attr('data-channel'))
                    updateActiveUserlist();
            }
            else if (data.type === 'topic') {
                Chat.setChannelTopic(data.to, data.message);
                Chat.addMessage(data.to, 'TOPIC', data.from + ' changed the channel topic to ' + data.message + '!', data.date);
            }
            else if (data.type === 'mode') {
                Chat.setChannelModes(data.to, data.message);
                Chat.addMessage(data.to, 'MODE', data.from + ' changed channel mode to ' + data.message + '!', data.date);
            }
            else if (data.type === 'loginmsgs') {
                $.each(data.message, function(name, arr) {
                    $.each(arr, function(key, val) {
                        Chat.addMessage(name, val.from, val.message, val.date);
                    });
                });
            }
            
            // Response messages
            else if (data.type === "rlogin") {
                if (data.success === false) {
                    showAlert('alert', 'txtAlert', 'Login error: ' + data.message);
                    return;
                }
                chatuser = data.message.name;
                $("#footerContainer").hide();
                $('#loggedInName').html(chatuser);
                $("#titleContainer").slideUp(400);
                $("#loginContainer").slideUp(400);
                $("#registerContainer").slideUp(400);
                $("#mainNavbar").delay(400).slideDown(700);
                $("#chat-content").delay(400).slideDown(700);
                
                $.each(data.message.channels, function(key, val) {
                    Chat.addChannel(key);
                    Chat.setChannelTopic(key, val.topic);
                    Chat.setChannelModes(key, val.modes);
                    Chat.setUserList(key, val.users);
                });
                $('#channel-list button:first').click();
                
                // Ask for notification permission
                if (window.Notification && Notification.permission !== "denied") {
                    Notification.requestPermission();
                }
            }
            else if (data.type === "rjoin") {
                if (data.success === false) {
                    showAlert('alert', 'txtAlert', 'Join error: ' + data.message);
                    return;
                }
                
                Chat.addChannel(data.message.name);
                Chat.setChannelTopic(data.message.name, data.message.topic);
                Chat.setChannelModes(data.message.name, data.message.modes);
                Chat.setUserList(data.message.name, data.message.users);
                $('#channel-list > span[data-channel="' + data.message.name + '"] > button').click();
            }
            else if (data.type === 'rname') {
                if (data.success === false) {
                    showAlert('alert', 'txtAlert', 'Name change error: ' + data.message);
                    return;
                }
                chatuser = data.from;
                $('#loggedInName').html(chatuser);
            }
            else if (data.type === 'rclist') {
                $('#searchWord').popover('destroy');
                var content = '<table class="table" id="searchrlist"><thead><tr><td>Channel</td><td>Topic</td></tr></thead><tbody>';
                if (data.message.length == 0)
                    content += '<tr><td>No channels found.</td><td>Press ENTER to create!</td></tr>';
                else
                    $.each(data.message, function(name, arr){
                        if (!$('#channel-list > span[data-channel="' + name + '"]').length)
                            content += '<tr class="searchitem '+ (arr.count == 0 ? 'active' : 'success') +'"><td>' + name + '</td><td>' + arr.topic + '</td></tr>';
                    });
                content += '</tbody></table>'
                $('#searchWord').popover({
                    placement: 'right',
                    container: 'body',
                    trigger: 'manual',
                    html: true,
                    content: content
                }).popover('show');
                $('#searchrlist').on('mouseleave', function(){
                    $('#searchWord').popover('destroy');
                });
                $('.searchitem').on('click', function(){
                    chat.send(JSON.stringify({type: 'join', to: $(this).find('td:first').html(), message: ''}));
                    $('#searchWord').popover('destroy');
                });
            }
        },
        
        addChannel: function(name) {
            if ($('#channel-list > span[data-channel="' + name + '"]').length)
                return;
            // Add to channel list
            var added = false;
            var btn = $('<span class="input-group" data-channel="' + name + '"><span class="input-group-addon">&times;</span><button type="button" class="btn btn-primary">' + name + '</button><span class="badge"></span></span>');
            $('#channel-list button').each(function(i) {
                if (name.toUpperCase() < $(this).text().toUpperCase()) { 
                    btn.insertBefore($(this).parent());
                    added = true;
                    return false;
                }
            });
            if (!added)
                btn.appendTo('#channel-list');
            
            $('span:first', btn).on('click', function(e){
                var to = $(this).next().text();
                chat.send(JSON.stringify({type: 'part', to: to, message: ''}));
                $(this).parent().remove();
                $('.channel-item[data-channel="' + to + '"]').remove();
                $('#channel-list button:first').click();
            });
            
            // Clone new div from template
            var clone = $('.channel-item[data-channel=""]').clone(true).attr('data-channel', name);
            $('span[data-type="name"]', clone).text(name);
            $('#channel-container').append(clone);
        },
        
        setChannelTopic: function(chan, topic) {
            $('.channel-item[data-channel="' + chan + '"] span[data-type="topic"]').text(topic);
        },
        
        setChannelModes: function(chan, modes) {
            var mode = parseInt(modes) || 0;
            var abbr = '', txt = '';
            if (mode & Permissions.MODE_PRIVATE) {
                abbr += '[p]rivate ';
                txt += 'p';
            }
            if (mode & Permissions.MODE_MODERATED) {
                abbr += '[m]oderated ';
                txt += 'm';
            }
            
            if (txt.length > 0)
                txt = '+' + txt;
            
            $('.channel-item[data-channel="' + chan + '"] abbr[data-type="modes"]').attr('title', abbr).text(txt);
        },
        
        setUserList: function(chan, userlist) {
            $('.channel-item[data-channel="' + chan + '"]').data('users', userlist);
        },
        
        addUser: function(chan, name, permissions, active) {
            permissions = typeof permissions !== 'undefined' ? permissions : 0;
            active = typeof active !== 'undefined' ? active : false;
            $('.channel-item[data-channel="' + chan + '"]').data('users')[name] = {active: active, permissions: permissions};
        },
        
        removeUser: function(chan, name) {
            if (name === chatuser) {
                $('#channel-list > span[data-channel="' + chan + '"]').remove();
                if (!$('.channel-item:not(.hidden)').length)
                    $('#channel-list button:first').click();
                return;
            }
            delete $('.channel-item[data-channel="' + chan + '"]').data('users')[name];
        },
        
        addMessage: function(chan, user, message, date) {
            if (!chan) return;
            date = typeof date !== 'undefined' ? date : Date.now() / 1000 | 0;
            if (!$('#channel-list > span[data-channel="' + chan + '"]').length)
                Chat.addChannel(chan);
            var chandiv, doscroll = false;
            if (chan == '')
                chandiv = $('.channel-item:not(.hidden)');
            else
                chandiv = $('.channel-item[data-channel="' + chan + '"]');
            var chanlistitem = $('#channel-list > span[data-channel="'+chan+'"]');
            var chatitem = $('div.chat', chandiv);
            if (chatitem[0].scrollHeight - chatitem.scrollTop() == chatitem.outerHeight() || chatitem[0].scrollHeight - chatitem.scrollTop() == chatitem.outerHeight() - 1)
                doscroll = true;
           
            /*Parse it!*/
            var msg = parseMessage(message);
            var dateobj = new Date(date*1000);

            var msgobj = $('<div class="row"><strong class="col-md-2"><span class="timestamp">[' + ("0" + dateobj.getHours()).slice(-2) + ":" + ("0" + dateobj.getMinutes()).slice(-2) + ":" + ("0" + dateobj.getSeconds()).slice(-2)  + ']</span><a class="text-danger">' + user + '</a></strong><span class="col-md-10">' + msg + '</span></div>');
            msgobj.appendTo(chatitem);
            msgobj.smilify();
           
            if (chandiv.hasClass('hidden')) {
                $('span:last', chanlistitem).text(parseInt(($('span:last', chanlistitem).text()) || "0") + 1);
            }
            else if (doscroll) {
                chatitem.scrollTop(chatitem[0].scrollHeight);
            }
            sendNotification(chan + ": New Message from " + user + "\n" + msg);
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
        
        Chat.login($('#login-username').val(), $('#login-password').val());
    });
    $('#homeLoginForm').submit(function (event) {
        event.preventDefault();

        Chat.login($('#homeLogin-username').val(), $('#homeLogin-password').val());
    });
    
    
    // Change channel
    $('#channel-list').on('click', 'button', function() {
        // Strip badge text
        //var channel = $(this).clone().children().remove().end().text();
        var channel = $(this).text();
        $('.channel-item:not(.hidden)').addClass('hidden');
        $('.channel-item[data-channel="' + channel + '"').removeClass('hidden');
        updateActiveUserlist();
        $('#channel-list > span[data-channel="'+ channel +'"] > span:last').text("");
        $('#channel-list > span[data-channel="' + channel + '"]').addClass('active').siblings().removeClass('active');
    });
    
    function updateActiveUserlist() {
        var users = $('.channel-item:not(.hidden)').data('users');
        var keys = Object.keys(users),
            i, k, len, color;
        
        // Sort keys based on active status, permissions and name
        keys.sort(function(a, b){
            var usera = users[a], userb = users[b];
            if (usera.active !== userb.active)
                return (usera.active ? -1 : 1);
            if (!(usera.permissions & Permissions.CHANNEL_OPERATOR) !== !(userb.permissions & Permissions.CHANNEL_OPERATOR))
                return usera.permissions & Permissions.CHANNEL_OPERATOR ? -1 : 1;
            if (!(usera.permissions & Permissions.CHANNEL_VOICE) !== !(userb.permissions & Permissions.CHANNEL_VOICE))
                return usera.permissions & Permissions.CHANNEL_VOICE ? -1 : 1;
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
            $('#user-list').append('<li role="presentation"><a class="'+ color +'">' + k + '</a></li>');
        };
    }
    
    // Alert close button
    $('.alertclose').click(function() {
        $(this).parent().addClass('hidden');
    });
    
    var chanHasUser = function(chan, user) {
        if (!$('.channel-item[data-channel="' + chan + '"]').data('users').length)
            return false;
        $('.channel-item[data-channel="' + chan + '"]').data('users').each(function(name, arr) {
            if (name.toUpperCase() === user.toUpperCase())
                return true;
        });
        return false;
    }
    
    // Send message
    var sendMessage = function() {
        var msg = $('#chat-input').val();
        var activechan = $('.channel-item:not(.hidden)').attr('data-channel');
        
        // If message is a command
        if (msg.charAt(0) === '/') {
            var split = msg.split(' ');
            var to = activechan,
                cmd = split[0].substr(1),
                arg = split.length > 1 ? split[1] : '';
            msg = split.length > 2 ? split.splice(2).join(' ') : '';
            
            if (arg[0] === '#' || chanHasUser(activechan, arg)) {
                to = arg;
            }
            else {
                msg = arg.concat(' ').concat(msg).trim();
            }
            
            console.log('cmd: ' + cmd + '| to: ' + to + '| arg: ' + arg + '| msg: ' + msg);
            
            if (cmd === 'part') {
                chat.send(JSON.stringify({type: 'part', to: to, message: msg}));
                $('#channel-list > span[data-channel="' + to + '"]').remove();
                $('.channel-item[data-channel="' + to + '"]').remove();
                $('#channel-list button:first').click();
            }
            else if (cmd === 'join' || cmd === 'topic' || cmd === 'mode' || cmd === 'kick'
                    || cmd === 'name')
            {
                chat.send(JSON.stringify({type: cmd, to: to, message: msg}));
            }
            else if (cmd === 'umode') {
                if (to[0] === '#') {
                    var msgsplit = msg.split(' ');
                    chat.send(JSON.stringify({type: 'umode', to: to, message: { user: msgsplit[0], mode: msgsplit[1]}}));
                }
                else {
                    chat.send(JSON.stringify({type: 'umode', to: activechan, message: { user: to, mode: msg}}));
                }
            }
            else if (cmd === 'quit') {
                chat.send(JSON.stringify({type: 'quit', message: msg}));
                chat.close();
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
    
    var searchtimer;
    function doSearch() {
        var msg = ($('#searchWord').val()).trim();
        if (msg.length == 0)
            return;
        chat.send(JSON.stringify({type: 'clist', message: msg}));
    }
    
    $('#searchWord').on('keyup', function(e) {
        if (e.which !== 13) {
            clearTimeout(searchtimer);
            searchtimer = setTimeout(doSearch, 1000);
        }
    });
    $('#searchWord').on('keydown', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            var msg = ($('#searchWord').val()).trim();
            if (msg.length < 2)
                return;
            chat.send(JSON.stringify({type: 'join', to: msg, message: ''}));
            $('#searchWord').popover('destroy');
        }
        clearTimeout(searchtimer);
    });

    // Show alert (alert-id, alert-text-id, message)
    function showAlert(id, txtid, msg) {
        $(id).removeClass('hidden');
        $(txtid).text(msg);
    };
    
    /**
     * Support functions
     */
    
    var vis = (function(){
        var stateKey, eventKey, keys = {
            hidden: "visibilitychange",
            webkitHidden: "webkitvisibilitychange",
            mozHidden: "mozvisibilitychange",
            msHidden: "msvisibilitychange"
        };
        for (stateKey in keys) {
            if (stateKey in document) {
                eventKey = keys[stateKey];
                break;
            }
        }
        return function(c) {
            if (c) document.addEventListener(eventKey, c);
            return !document[stateKey];
        }
    })();
    
    // Add constant properties to an object
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
    
    function sendNotification(text) {
        if (vis())
            return;
        if (!("Notification" in window)) {
            console.log("This browser does not support system notifications");
        }
        else if (Notification.permission === "granted") {
            var notification = new Notification("Project Chat", {body: text, icon: "images/chat_logo4_black.png"});
        }
        else if (Notification.permission !== 'denied') {
            Notification.requestPermission(function (permission) {
                if (permission === "granted") {
                    var notification = new Notification("Project Chat", {body: text, icon: "images/chat_logo4_black.png"});
                }
            });
        }
    }
});
