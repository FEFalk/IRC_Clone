<?php
namespace IRCClone;

abstract class ErrorCodes
{
    const UNKNOWN_ERROR             = 1;
    
    const LOGIN_INCORRECT           = 2;
    const LOGIN_ALREADY_LOGGEDIN    = 3;
    const USER_NOT_EXIST            = 4;
    const USER_NOT_IN_CHANNEL       = 5;
    const USER_BANNED               = 6;
    
    const CHANNEL_NOT_EXIST         = 7;
    const CHANNEL_PASSWORD_MISMATCH = 8;
    const CHANNEL_USERLIMIT_REACHED = 9;
    const CHANNEL_MODERATED         = 10;
    
    const BAD_FORMAT                = 11;
    const INSUFFICIENT_PERMISSION   = 12;
    const NAME_IN_USE               = 13;
}