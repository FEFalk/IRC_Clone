<?php
namespace IRCClone;

abstract class Permissions
{
    // ServerPermissions
    const SERVER_OPERATOR   = 1 << 0;
    
    // ChannelModes
    const MODE_PRIVATE      = 1 << 0;   // Not listed in public channels list
    const MODE_MODERATED    = 1 << 1;   // CHANNEL_OPERATOR or CHANNEL_VOICE required to speak
    
    // ChannelPermissions
    const CHANNEL_VOICE     = 1 << 0;
    const CHANNEL_OPERATOR  = 1 << 1;
}