<!DOCTYPE html>
<html>
<head>
    <title>Wow! So cool!</title>
    <script type="text/javascript" src="js/jquery-1.11.3.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="js/autoresize.js"></script>
    <script type="text/javascript" src="js/chat.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Open+Sans:400,300,700,800'>
</head>
<body>
    <nav class="navbar navbar-inverse navbar-static-top">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                </button>
                <a class="navbar-brand" href="#">Project #IRC sexy</a>
            </div>
            <div id="navbar" class="collapse navbar-collapse">
                <ul class="nav navbar-nav">
                    <li class="active"><a href="#">Home</a></li>
                    <li><a href="#about">About</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
                <form id="login-form" class="navbar-form navbar-right">
                    <div class="form-group">
                        <input type="text" placeholder="Username or Email" class="form-control">
                    </div>
                    <div class="form-group">
                        <input type="password" placeholder="Password" class="form-control">
                    </div>
                    <div class="form-group hidden"></div>
                    <button type="submit" class="btn btn-success">Sign in</button>
                    <button id="btnRegister" type="button" class="btn" data-toggle="modal" data-target="#registerModal">Register</button>
                </form>
            </div>
        </div>
    </nav>
    
    
    <div class="top-buffer container-fluid row-fluid row-no-padding" id="content">
            <div class="alert alert-danger alert-dismissible hidden" id="alert" role="alert"><button type="button" class="close alertclose" aria-label="Close"><span aria-hidden="true">&times;</span></button><span id="txtAlert">Wow big alert warning warning hello</span></div>
            <div class="col-md-2 panel btn-group-vertical nav" role="group" aria-label="channels" id="channel-list">
                <button type="button" class="btn btn-primary active">#Default<span class="badge">4</span></button>
                <button type="button" class="btn btn-primary">#CoolChan<span class="badge"></span></button>
            </div>
            <div class="col-md-8 panel panel-default" style="background-color: #FFFFFF;" id="chat-frame">     
                <div class="panel-heading">
                    <span class="label label-primary">#Default</span>
                    <span class="label label-danger">[10/10]</span>
                    <abbr title="[m]oderated [p]rivate" class="label label-warning">[+mp]</abbr>
                    <span class="label label-info">This is a cool topic. I like cool topics. Do you like cool topics?</span>
                </div>
                <div class="panel-body container-fluid" id="chat">
                    <div class="row"><strong class="col-md-2"><a href="" class="text-danger">@Coolest|Person</a></strong><span class="col-md-10">There are many chats. But this one is mine. Such cool chat, now meme free! Try it out!</span></div>
                    <div class="row"><strong class="col-md-2"><a href="" class="text-success">+CoolPerson</a></strong><span class="col-md-10">There once was a man named Sean, he loved trees a lot.</span></div>
                    <div class="row"><strong class="col-md-2"><a href="" class="text-danger">@Coolest|Person</a></strong><span class="col-md-10">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vivamus eu massa dictum orci vehicula dapibus ut vel lectus. Morbi suscipit ipsum at metus rutrum cursus. Etiam interdum ex at risus pretium, sit amet vehicula sapien faucibus. Suspendisse eget sed.</span></div>
                    <div class="row"><strong class="col-md-2"><a href="">UncoolPerson</a></strong><span class="col-md-10">Shut up :(</span></div>
                </div>
                <div class="panel-footer input-group">
                    <span class="input-group-addon" id="basic-addon1">></span>
                    <textarea maxlength="255" rows="1" class="form-control" id="chat-input" type="text" placeholder="" autocomplete="off"></textarea>
                    <span class="input-group-addon btn btn-success" id="basic-addon2">Send</span>
                </div>
            </div>
            <ul class="col-md-2 panel nav nav-pulls nav-stacked" role="tablist" id="user-list">
                <li role="presentation"><a href="#" class="text-danger">@Coolest|Person</a></li>
                <li role="presentation"><a href="#" class="text-danger">@CoolPerson</a></li>
                <li role="presentation"><a href="#" class="text-success">+CoolPerson</a></li>
                <li role="presentation"><a href="#">UncoolPerson</a></li>
                <li></li>
                <li role="presentation"><a href="#" class="text-muted">OfflineUser</a></li>
            </ul>
    </div>
    
    <!-- Registration -->
    <div class="modal fade" id="registerModal" tabindex="-1" role="dialog" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="registerModalLabel">Register</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger alert-dismissible hidden" id="reg-alert" role="alert"><button type="button" class="close alertclose" aria-label="Close"><span aria-hidden="true">&times;</span></button><span id="reg-txt-alert"></span></div>
                    <form id="register-form">
                        <div class="form-group">
                            <input name="username" type="text" placeholder="Username" class="form-control">
                        </div>
                        <div class="form-group">
                            <input name="password" type="password" placeholder="Password" class="form-control">
                        </div>
                        <div class="form-group">
                            <input name="email" type="text" placeholder="Email" class="form-control">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="register-form-submit">Register</button>
                </div>
            </div>
        </div>
    </div>
    
</body>
</html>
