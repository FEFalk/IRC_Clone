<!DOCTYPE html>
<html>
<head>
    <title>Wow! So cool!</title>
    <script type="text/javascript" src="js/jquery-1.11.3.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="js/autoresize.js"></script>
    <script type="text/javascript" src="js/chat.js"></script>
	<script type="text/javascript" src="js/login.js"></script>
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
                        <input id="login-username" type="text" placeholder="Username or Email" class="form-control">
                    </div>
                    <div class="form-group">
                        <input id="login-password" type="password" placeholder="Password" class="form-control">
                    </div>
                <div class="form-group hidden"></div>
                    <button id="btnSignIn" type="submit" class="btn btn-success" >Sign in</button>
                    <button id="btnRegister" type="button" class="btn" data-toggle="modal" data-target="#registerModal">Register</button>
                </form>
				<form id="loggedIn-form" class="navbar-form navbar-right">
					<div class="form-group">
						<p class="text-info" id="loggedInText">Logged in as: <span class="text-warning" id='loggedInName'></span></p>
					</div>
					<div class="form-group hidden"></div>
					<button id="btnLogout" type="submit" class="btn btn-success">Logout</button>
				</form>
				</div>
			</div>
        </div>
    </nav>
    <div id="searchDiv">
	<div class="alert alert-danger alert-dismissible hidden" id="search-alert" role="alert"><button type="button" class="close alertclose" aria-label="Close"><span aria-hidden="true">&times;</span></button><span id="search-txt-alert"></span></div>
		<form id="search-form" class="form-horizontal">		
			<input class="form-control" type="text" name="searchWord" id="searchWord" placeholder="Search: ">
			<button type="button" class="btn btn-primary" id="search-form-submit">Submit</button>	
		</form>
	</div>
    <div class="top-buffer container-fluid row-fluid row-no-padding" id="content">
        <div class="alert alert-danger alert-dismissible hidden" id="alert" role="alert"><button type="button" class="close alertclose" aria-label="Close"><span aria-hidden="true">&times;</span></button><span id="txtAlert">Wow big alert warning warning hello</span></div>
        <div class="col-md-2 panel btn-group-vertical nav" role="group" aria-label="channels" id="channel-list">	
        </div>
        <div class="col-md-8 panel panel-default" style="background-color: #FFFFFF;" id="chat-frame">  
            <div id="channel-container">
            </div>
            <div class="panel-footer input-group">
                <span class="input-group-addon" id="basic-addon1">></span>
                <textarea maxlength="255" rows="1" class="form-control" id="chat-input" type="text" placeholder="" autocomplete="off"></textarea>
                <span class="input-group-addon btn btn-success" id="basic-addon2">Send</span>
            </div>
        </div>
        <ul class="col-md-2 panel nav nav-pulls nav-stacked" role="tablist" id="user-list">
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
    
    <!-- Chat channel template -->
    <div class="channel-item hidden" data-channel="">
        <div class="panel-heading">
            <span class="label label-primary" data-type="name"></span>
            <span class="label label-danger" data-type="users"></span>
            <abbr title="" class="label label-warning" data-type="modes"></abbr>
            <span class="label label-info" data-type="topic"></span>
        </div>
        <div class="panel-body container-fluid chat" data-type="chat">
        </div>
    </div>
</body>
</html>
