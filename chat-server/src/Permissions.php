<?php
namespace IRCClone;

abstract class Permissions
{
    // ServerPermissions
    const SERVER_OPERATOR   = 1 << 0;
    const SERVER_BANNED     = 1 << 1;   // User banned from server
    const SERVER_LAST = SERVER_BANNED;
    
    // ChannelModes
    const MODE_PRIVATE      = 1 << 0;   // Not listed in public channels list
    const MODE_MODERATED    = 1 << 1;   // CHANNEL_OPERATOR or CHANNEL_VOICE required to speak
    const MODE_LAST = MODE_MODERATED;
    
    // ChannelPermissions
    const CHANNEL_VOICE     = 1 << 0;
    const CHANNEL_OPERATOR  = 1 << 1;
    const CHANNEL_BANNED    = 1 << 2;   // User banned from channel
    const CHANNEL_LAST = CHANNEL_BANNED;
}