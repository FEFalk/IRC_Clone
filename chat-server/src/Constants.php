<?php
namespace IRCClone

abstract class ServerPermissions
{
    const OPERATOR = 1;
}

abstract class ChannelPermissions
{
    const PRIVATE = 1;
    const MODERATED = 2;
}

abstract class ChannelUserPermissions
{
    const OPERATOR = 1;
    const VOICE = 2;
}