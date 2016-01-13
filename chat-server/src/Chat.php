<?php
namespace IRCClone;

use IRCClone\Connection\ChatConnection;

use SplObjectStorage;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface
{
    private $clients;
    private $channels;
    private $defaultchan;
    public $db;

    public function __construct($db)
    {
        $this->clients = new \SplObjectStorage;
        $this->channels = new \SplObjectStorage;
        $this->db = $db;
        
        // Initialise the default channel
        $ch = $this->db->getChannelInfo('#Default');
        if (!$ch) {
            echo "Unable to create default channel #Default!\n";
            return;
        }
        $this->defaultchan = new Channel($ch['name'], $this);
        $this->defaultchan->setTopic($ch['topic']);
        $this->defaultchan->addMode($ch['modes']);
        $this->channels->attach($this->defaultchan);
        echo "Created default channel {$this->defaultchan->getName()}\n";
    }

    public function onOpen(ConnectionInterface $conn)
    {
        echo "New connection! ({$conn->resourceId})\n";
        $this->clients->attach(new ChatConnection($conn, $this));
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $client = $this->getClient($from);
        if (!$client) {
            $from->close();
            return;
        }
        
        $obj = json_decode($msg);
        print_r($obj);
        if (!$obj)
            return;
        
        // Login
        if ($obj->type === 'login' && !$client->getUser()) {
            if ($this->parseLogin($client, $obj))
                echo "User login: Logged in!\n";
            else
                echo "User login failed\n";
            return;
        }
        
        // Other events require a logged in user
        if (!$client->getUser())
            return;
        
        /**
         * Handle events
         */
         
        // Messages
        if ($obj->type === 'message') {
            if ($this->parseMessage($client, $obj))
                echo "{$client->getUser()->getName()} -> {$obj->to} ({$obj->message})\n";
            else
                echo "Message failed\n";
        }
        
        // Server operations
        else if ($obj->type === 'quit') {
            if ($this->parseQuit($client, $obj))
                echo "{$client->getUser()->getName()} quit\n";
        }
        
        // Channel operations
        else if ($obj->type === 'join') {
            if ($this->parseJoin($client, $obj))
                echo "{$client->getUser()->getName()} joined channel {$obj->to}\n";
            else
                echo "{$client->getUser()->getName()} unable to join channel {$obj->to}\n";
        }
        else if ($obj->type === 'part') {
            if ($this->parsePart($client, $obj))
                echo "{$client->getUser()->getName()} part channel {$obj->message->chan}\n";
            else
                echo "{$client->getUser()->getName()} unable to part channel {$obj->message->chan}\n";
        }
        else if ($obj->type === 'topic') {
            if($this->parseTopic($client, $obj))
                echo "{$client->getUser()->getName()} changed topic in {$obj->to} to {$obj->message}\n";
            else
                echo "{$client->getUser()->getName()} unable to change topic in {$obj->to}\n";
        }
        else if ($obj->type === 'mode') {
            if ($this->parseMode($client, $obj))
                echo "{$client->getUser()->getName()} changed modes in {$obj->to} to {$obj->message}\n";
            else
                echo "{$client->getUser()->getName()} unable to change modes in {$obj->to}\n";
        }
        else if ($obj->type === 'kick') {
            if ($this->parseKick($client, $obj))
                echo "{$client->getUser()->getName()} kicked {$obj->message} from {$obj->to}\n";
            else
                echo "{$client->getUser()->getName()} unable to kick {$obj->message} from {$obj->to}\n";
        }
        else if ($obj->type === 'umode') {
            if ($this->parseUserMode($client, $obj))
                echo "{$client->getUser()->getName()} changed usermode of {$obj->message->user} in {$obj->to} to {$obj->message->mode}\n";
            else
                echo "{$client->getUser()->getName()} unable to change usermode of {$obj->message->user} in {$obj->to}\n";
        }
        
        // User operations
        else if ($obj->type === 'name') {
            echo "{$client->getUser()->getName()} ";
            if ($this->parseNameChange($client, $obj))
                echo "changed name to {$client->getUser()->getName()}\n";
            else
                echo "unable to change name to {$obj->message}\n";
        }
        
        // TODO: More events?
    }
    
    public function parseLogin($client, $obj)
    {
        // If client is already logged in. Might want to allow multiple logins?
        if ($this->getClientByName($obj->message->username))
            $error = ErrorCodes::LOGIN_ALREADY_LOGGEDIN;
        else {
            $user = $client->login($obj->message->username, $obj->message->password);
            if (!$user)
                $error = ErrorCodes::LOGIN_INCORRECT;
        }
        
        if (isset($error)) {
            $client->send([
                'type' => 'rlogin',
                'success' => false,
                'message' => $error
            ]);
            return false;
        }
        
        $chans = $client->getUser()->getChannels();
        foreach($chans as $channame => $arr) {
            // Get all users from database
            $chans[$channame]['users'] = $this->db->getChannelUsers($channame);
            // Get active users
            foreach($arr['chan']->getUsers() as $u => $p) {
                $chans[$channame]['users'][$u]['active'] = true;
            }
            unset($chans[$channame]['chan']);
        }
        
        // Send success string to client
        $client->send([
            'type' => 'rlogin',
            'success' => true,
            'message' => [
                'name' => $client->getUser()->getName(),
                'permissions' => $client->getUser()->getPermissions(),
                'channels' => $chans
            ]
        ]);
        
        // Feed offline messages
        $offlinemsgs = $this->db->getOfflineMessages($client->getUser());
        $client->send([
            'type' => 'loginmsgs',
            'message' => $offlinemsgs
        ]);
        
        // Broadcast login to the users channels
        $client->getUser()->broadcast([
            'type' => 'online',
            'message' => null
        ]);
        return true;
    }

    public function parseMessage($client, $obj)
    {
        if (!$obj->to)
            return;
        $message = trim(htmlspecialchars($obj->message));
        if (strlen($message) == 0)
            return false;
        
        // Sent to a channel
        if ($obj->to[0] == '#') {
            $chan = $this->getChannelByName($obj->to);
            if (!$chan)
                $error = ErrorCodes::CHANNEL_NOT_EXIST;
            else if (!$chan->hasUser($client->getUser()))
                $error = ErrorCodes::USER_NOT_IN_CHANNEL;
            else if ($chan->hasMode(Permissions::MODE_MODERATED)
                    && !$chan->userHasPermissions($client->getUser(), Permissions::CHANNEL_OPERATOR | Permissions::CHANNEL_VOICE)
                    && !$client->getUser()->hasPermission(Permissions::SERVER_OPERATOR))
                $error = ErrorCodes::CHANNEL_MODERATED;
            
            if (isset($error)) {
                $client->send([
                    'type' => 'rmessage',
                    'success' => false,
                    'message' => $error
                ]);
                return false;
            }
            
            $to = $chan->getName();
            
            // Send message to channel
            $chan->send([
                'type' => 'message',
                'from' => $client->getUser()->getName(),
                'date' => time(),
                'message' => $message
            ]);
        }
        // User
        else {
            $receiver = $this->getClientByName($obj->to);
            if ($receiver) {
                $to = $receiver->getUser()->getName();
                // Send message to receiver
                $receiver->send([
                    'type' => 'message',
                    'from' => $client->getUser()->getName(),
                    'to' => $receiver->getUser()->getName(),
                    'date' => time(),
                    'message' => $message
                ]);
            }
            else {
                // User may be offline
                $u = $this->getUserInfo($obj->to);
                if (!$u) {
                    $client->send([
                        'type' => 'rmessage',
                        'success' => false,
                        'message' => ErrorCodes::USER_NOT_EXIST
                    ]);
                    return false;
                }
                $to = $u['name'];
            }
        }
        
        // Success string to client, do we need this?
        $client->send([
            'type' => 'rmessage',
            'to' => $to,
            'success' => true,
            'message' => $message
        ]);
        
        // Add event to database
        $this->db->addEvent($client->getUser()->getUserId(), $to, 'message', $obj->message);
        return true;
    }
    
    public function parseQuit($client, $obj)
    {
        $client->logout($obj->message);
        $this->onClose($client->getConnection());
        return true;
    }
    
    public function parseJoin($client, $obj)
    {
        $chan = null;
        // Check channel format
        if (!preg_match('/^([#][^\x07\x2C\s]{0,16})$/', $obj->to)) {
            $error = ErrorCodes::BAD_FORMAT;
        }
        else {
            $chan = $this->getChannelOrCreate($obj->to);
            if (!$chan)
                $error = ErrorCodes::UNKNOWN_ERROR;
        }
        
        // Check password, permissions and user limit
        if ($chan) {
            $permissions = $this->db->getUserChannelPermissions($client->getUser()->getUserId(), $chan->getName());
            if (!$client->getUser()->hasPermission(Permissions::SERVER_OPERATOR)) {
                if ($chan->hasPassword() && $chan->getPassword() != $obj->message)
                    $error = ErrorCodes::CHANNEL_PASSWORD_MISMATCH;
                else if ($chan->getUserCount() < $chan->getUserLimit())
                    $error = ErrorCodes::CHANNEL_USERLIMIT_REACHED;
                else if ($permissions & Permissions::CHANNEL_BANNED)
                    $error = ErrorCodes::USER_BANNED;
            }
        }
        
        if (isset($error)) {
            $client->send([
                'type' => 'rjoin',
                'success' => false,
                'message' => $error
            ]);
            return false;
        }
        
        // We have a channel and the user has provided the correct password/userlimit not reached
        $chan->addUser($client->getUser(), $permissions);
        
        // Add user to channel database
        $this->db->addUserToChannel($client->getUser(), $chan->getName());
        
        // Populate user list
        $users = $this->db->getChannelUsers($chan->getName());
        foreach($chan->getUsers() as $u => $p) {
            $users[$u]['active'] = true;
        }

        // Send success string
        $client->send([
            'type' => 'rjoin',
            'success' => true,
            'message' => [
                'name' => $chan->getName(),
                'topic' => $chan->getTopic(),
                'modes' => $chan->getModes(),
                'userlimit' => $chan->getUserLimit(),
                'permissions' => $permissions,
                'users' => $users
            ]
        ]);
        
        // Send join event to users in channel
        $chan->send([
            'type' => 'join',
            'from' => $client->getUser()->getName(),
            'date' => time(),
            'message' => $permissions
        ]);
        
        // Add event to database
        $this->db->addEvent($client->getUser()->getUserId(), $chan->getName(), 'join', $client->getUser()->getName());
        return true;
    }
    
    public function parsePart($client, $obj)
    {
        $chan = $this->getChannelByName($obj->to);
        if (!$chan)
            $error = ErrorCodes::CHANNEL_NOT_EXIST;
        else if (!$chan->removeUser($client->getUser()))
            $error = ErrorCodes::USER_NOT_IN_CHANNEL;
        
        if (isset($error)) {
            $client->send([
                'type' => 'rpart',
                'success' => false,
                'message' => $error
            ]);
            return false;
        }
        
        // Broadcast to channel
        $chan->send([
            'type' => 'part',
            'from' => $client->getUser()->getName(),
            'date' => time(),
            'message' => null
        ]);
        
        // Success string to client, do we need this?
        $client->send([
            'type' => 'rpart',
            'success' => true,
            'to' => $obj->to,
            'message' => null
        ]);
        
        $this->db->removeUserFromChannel($user->getUser(), $chan->getName());
        
        // Add event to database
        $this->db->addEvent($client->getUser()->getUserId(), $chan->getName(), 'part', $client->getUser()->getName());
        return true;
    }
    
    public function parseTopic($client, $obj) 
    {
        $chan = $this->getChannelByName($obj->to);
        if (!$chan)
            $error = ErrorCodes::CHANNEL_NOT_EXIST;
        else if (!$chan->userHasPermissions($client->getUser(), Permissions::CHANNEL_OPERATOR)
                && !$client->getUser()->hasPermission(Permissions::SERVER_OPERATOR))
            $error = ErrorCodes::INSUFFICIENT_PERMISSION;
        
        if (isset($error)) {
            $client->send([
                'type' => 'rtopic',
                'success' => false,
                'message' => $error
            ]);
            return false;
        }
        
        $message = htmlspecialchars($obj->message);
        $chan->setTopic($message);
        
        // Broadcast to channel
        $chan->send([
            'type' => 'topic',
            'from' => $client->getUser()->getName(),
            'date' => time(),
            'message' => $message
        ]);
        
        // Success string to client, do we need this?
        $client->send([
            'type' => 'rtopic',
            'success' => true,
            'to' => $obj->to,
            'message' => $message
        ]);
        
        // Update channel in database
        $this->db->setChannelInfo($chan->getName(), ['topic' => $message]);
        
        // Add event to database
        $this->db->addEvent($client->getUser()->getUserId(), $chan->getName(), 'topic', $obj->message);
        return true;
    }
    
    public function parseMode($client, $obj)
    {
        $chan = $this->getChannelByName($obj->to);
        if (!$chan)
            $error = ErrorCodes::CHANNEL_NOT_EXIST;
        else if (!$chan->userHasPermissions($client->getUser(), Permissions::CHANNEL_OPERATOR)
                && !$client->getUser()->hasPermission(Permissions::SERVER_OPERATOR))
            $error = ErrorCodes::INSUFFICIENT_PERMISSION;
        
        $mode = intval($obj->message);
        if ($mode >= Permissions::MODE_LAST << 1)
            $error = ErrorCodes::BAD_FORMAT;
            
        if (isset($error)) {
            $client->send([
                'type' => 'rmode',
                'success' => false,
                'message' => $error
            ]);
            return false;
        }
        
        $chan->setModes($mode);
        
        // Broadcast to channel
        $chan->send([
            'type' => 'mode',
            'from' => $client->getUser()->getName(),
            'date' => time(),
            'message' => $mode
        ]);
        
        // Success string to client, do we need this?
        $client->send([
            'type' => 'rmode',
            'success' => true,
            'to' => $obj->to,
            'message' => $mode
        ]);
        
        // Update channel in database
        $this->db->setChannelInfo($chan->getName(), ['modes' => $mode]);
        
        // Add event to database
        $this->db->addEvent($client->getUser()->getUserId(), $chan->getName(), 'mode', $mode);
        return true;
    }
    
    public function parseKick($client, $obj)
    {
        // TODO: Do we want kick messages?
        $chan = $this->getChannelByName($obj->to);
        $user = $this->getClientByName($obj->message);
        if (!$chan)
            $error = ErrorCodes::CHANNEL_NOT_EXIST;
        else if (!$user)
            $error = ErrorCodes::USER_NOT_EXIST;
        else if (!$chan->userHasPermissions($client->getUser(), Permissions::CHANNEL_OPERATOR)
                && !$client->getUser()->hasPermission(Permissions::SERVER_OPERATOR))
            $error = ErrorCodes::INSUFFICIENT_PERMISSION;
            
        if (isset($error)) {
            $client->send([
                'type' => 'rkick',
                'success' => false,
                'message' => $error
            ]);
            return false;
        }
        
        // Broadcast to channel
        $chan->send([
            'type' => 'kick',
            'from' => $client->getUser()->getName(),
            'date' => time(),
            'message' => $user->getUser()->getName()
        ]);
        
        $chan->removeUser($user->getUser());
        $this->db->removeUserFromChannel($user->getUser(), $chan->getName());
        
        // Success string to client, do we need this?
        $client->send([
            'type' => 'rkick',
            'success' => true,
            'to' => $chan->getName(),
            'message' => $user->getUser()->getName()
        ]);
        
        // Add event to database
        $this->db->addEvent($client->getUser()->getUserId(), $chan->getName(), 'kick', $user->getUser()->getName());
        return true;
    }
    
    public function parseUserMode($client, $obj)
    {
        $chan = $this->getChannelByName($obj->to);
        $user = $this->getClientByName($obj->message->user);
        if (!$chan)
            $error = ErrorCodes::CHANNEL_NOT_EXIST;
        else if (!$user)
            $error = ErrorCodes::USER_NOT_EXIST;
        else if (!$chan->hasUser($user->getUser()))
            $error = ErrorCodes::USER_NOT_IN_CHANNEL;
        else if (!$chan->userHasPermissions($client->getUser(), Permissions::CHANNEL_OPERATOR)
                && !$client->getUser()->hasPermission(Permissions::SERVER_OPERATOR))
            $error = ErrorCodes::INSUFFICIENT_PERMISSION;
        
        $mode = intval($obj->message->mode);
        if ($mode >= Permissions::CHANNEL_LAST << 1)
            $error = ErrorCodes::BAD_FORMAT;
            
        if (isset($error)) {
            $client->send([
                'type' => 'rumode',
                'success' => false,
                'message' => $error
            ]);
            return false;
        }
        
        $chan->setUserPermissions($user->getUser(), $mode);
        $this->db->setUserChannelPermissions($chan->getName(), $user->getUser()->getName(), $mode);
        
        // Broadcast to channel
        $chan->send([
            'type' => 'umode',
            'from' => $client->getUser()->getName(),
            'date' => time(),
            'message' => [
                'user' => $user->getUser()->getName(),
                'mode' => $mode
            ]
        ]);
        
        // Success string to client, do we need this?
        $client->send([
            'type' => 'rumode',
            'success' => true,
            'to' => $chan->getName(),
            'message' => [
                'user' => $user->getUser()->getName(),
                'mode' => $mode
            ]
        ]);
        
        // Kick user from channel if banned
        if ($mode & Permissions::CHANNEL_BANNED)
            $chan->removeUser($user);
        
        // Add event to database
        $this->db->addEvent($client->getUser()->getUserId(), $chan->getName(), 'umode', $user->getUser()->getName() + ':' + $mode);
    }
    
    public function parseNameChange($client, $obj)
    {
        $user = $this->db->getUserInfo($obj->message);
        if ($user)
            $error = ErrorCodes::NAME_IN_USE;
        else if (!preg_match('/\A[a-z_\-\[\]\\^{}|`][a-z0-9_\-\[\]\\^{}|`]{2,15}\z/i', $obj->message))
            $error = ErrorCodes::BAD_FORMAT;
        
        if (isset($error)) {
            $client->send([
                'type' => 'rname',
                'success' => false,
                'message' => $error
            ]);
            return false;
        }
        
        // Change name
        $oldname = $client->getUser()->getName();
        if (!$this->db->changeUserName($client->getUser(), $obj->message)) {
            echo " Error while changing name to {$obj->message}!\n";
            return false;
        }
        $client->getUser()->setName($obj->message);
        
        // Success string to client
        $client->send([
            'type' => 'rname',
            'success' => true,
            'message' => $obj->message
        ]);
        
        // Broadcast to user channels
        $client->getUser()->broadcast([
            'type' => 'name',
            'date' => time(),
            'message' => $oldname
        ]);
        
        // Add event to database
        foreach($client->getUser()->getChannels(true) as $channame => $arr) {
            $this->db->addEvent($client->getUser()->getUserId(), $channame, 'name', $oldname + ':' + $client->getUser()->getName());
        }
        return true;
    }
    
    public function onClose(ConnectionInterface $conn) {
        echo "Connection {$conn->resourceId} has disconnected\n";
        
        $client = $this->getClient($conn);
        if ($client) {
            $client->logout('User disconnected');
        }
        
        foreach($this->clients as $client) {
            if ($client->getConnection() === $conn)
                $this->clients->detach($client);
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        
        $conn->close();
    }
    
    // Get client by Connection
    public function getClient(ConnectionInterface $conn)
    {
        foreach($this->clients as $client) {
            if ($client->getConnection() === $conn) {
                return $client;
            }
        }
        return null;
    }
    
    public function getClientByName($name)
    {
        foreach($this->clients as $client) {
            if ($client->getUser() && strtolower($client->getUser()->getName()) === strtolower($name)) {
                return $client;
            }
        }
        return null;
    }
    
    public function getChannelByName($name)
    {
        foreach($this->channels as $chan) {
            if (strtolower($chan->getName()) === strtolower($name)) {
                return $chan;
            }
        }
        return null;
    }

    public function getChannelOrCreate($name)
    {
        $chan = $this->getChannelByName($name);
        if (!$chan) {
            // Channel does not exist or has currently no active users (destructed)
            $ch = $this->db->getChannelInfo($name);
            if (!$ch) {
                // Channel does not exist, create it
                if (!$this->db->createChannel($name))
                    return null;
                $chan = new Channel($name, $this);
            }
            else {
                // Channel exists but is destructed
                $chan = new Channel($ch['name'], $this);
                $chan->setTopic($ch['topic']);
                $chan->addMode($ch['modes']);
                $chan->setUserLimit($ch['userlimit']);
                $chan->setPassword($ch['password']);
            }
            
            $this->channels->attach($chan);
        }
        return $chan;
    }
    
    public function getDefaultChannel()
    {
        return $this->defaultchan;
    }
}