<?php
namespace IRCClone

abstract class ServerPermissions
{
    const SERVER_OPERATOR   = 1 << 0;
}

abstract class ChannelModes
{
    const MODE_PRIVATE      = 1 << 0;   // Not listed in public channels list
    const MODE_MODERATED    = 1 << 1;   // CHANNEL_OPERATOR or CHANNEL_VOICE required to speak
}

abstract class ChannelPermissions
{
    const CHANNEL_OPERATOR  = 1 << 0;
    const CHANNEL_VOICE     = 1 << 1;
}

abstract class ErrorCodes
{
    const UNKNOWN_ERROR             = 1;
    const LOGIN_INCORRECT           = 2;
    
    const CHANNEL_NOT_EXIST         = 3;
    const USER_NOT_EXIST            = 4;
    const USER_NOT_IN_CHANNEL       = 5;
    
    const CHANNEL_PASSWORD_MISMATCH = 6;
    const CHANNEL_USERLIMIT_REACHED = 7;
}