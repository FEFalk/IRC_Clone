<?php
namespace IRCClone;

abstract class ErrorCodes
{
    const UNKNOWN_ERROR             = 1;
    
    const LOGIN_INCORRECT           = 2;
    
    const USER_NOT_EXIST            = 3;
    const USER_NOT_IN_CHANNEL       = 4;
    
    const CHANNEL_NOT_EXIST         = 5;
    const CHANNEL_PASSWORD_MISMATCH = 6;
    const CHANNEL_USERLIMIT_REACHED = 7;
    const CHANNEL_MODERATED         = 8;
}