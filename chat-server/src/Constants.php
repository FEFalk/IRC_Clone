<?php
namespace IRCClone

abstract class ServerPermissions
{
    const SERVER_OPERATOR = 1;
}

abstract class ChannelModes
{
    const MODE_PRIVATE = 1;
    const MODE_MODERATED = 2;
}

abstract class ChannelPermissions
{
    const CHANNEL_OPERATOR = 1;
    const CHANNEL_VOICE = 2;
}