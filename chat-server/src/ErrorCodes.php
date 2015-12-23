<?php
namespace IRCClone;

abstract class ErrorCodes
{
    const UNKNOWN_ERROR             = 1;
    
    const LOGIN_INCORRECT           = 2;
    const LOGIN_ALREADY_LOGGEDIN    = 3;
    const USER_NOT_EXIST            = 4;
    const USER_NOT_IN_CHANNEL       = 5;
    
    const CHANNEL_NOT_EXIST         = 6;
    const CHANNEL_PASSWORD_MISMATCH = 7;
    const CHANNEL_USERLIMIT_REACHED = 8;
    const CHANNEL_MODERATED         = 9;
    
    const BAD_FORMAT                = 10;
    const INSUFFICIENT_PERMISSION   = 11;
}