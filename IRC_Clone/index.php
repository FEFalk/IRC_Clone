<!DOCTYPE html>
<html>
<head>
    <title>Wow! So cool!</title>
    <script type="text/javascript" src="js/jquery-1.11.3.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="js/autoresize.js"></script>
    <script type="text/javascript" src="js/chat.js"></script>
    <script type="text/javascript" src="js/main.js"></script>
	<script type="text/javascript" src="js/login.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Open+Sans:400,300,700,800'>
</head>
<body>


    <!-- Navigation-Bar -->
    <nav class="navbar navbar-inverse navbar-static-top" id="mainNavbar">
        <div class="container">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                </button>
                <a class="navbar-brand" href="#">Project #IRC</a>
            </div>
            <div id="navbar" class="collapse navbar-collapse">
                <ul class="nav navbar-nav">
                    <li><a href="#about">About</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
				<form id="loggedIn-form" class="navbar-form navbar-right">
					<div class="form-group">
						<p><span id="loggedInText">Logged in as: </span><span class="text-warning" id='loggedInName'></span></p>
					</div>
					<div class="form-group hidden"></div>
					<button id="btnLogout" type="submit" class="btn btn-success">Logout</button>
				</form>
            </div>
        </div>
        </div>
    </nav>
    
    
    <!-- Title Div -->
    <div class="container" id="titleContainer">
    </div>

    <!-- Login Content -->
    <div class="container" id="loginContainer">
        <!-- Login-forms -->
        <h2 id="title">Welcome back!</h2>
        <form id="homeLoginForm">
            <div class="form-group">
                <input id="homeLogin-username" type="text" placeholder="Username/Email" class="form-control">
            </div>
            <div class="form-group">
                <input id="homeLogin-password" type="password" placeholder="Password" class="form-control">
            </div>
            <button type="submit" class="btn btn-success" id="btnLogin">Sign in</button>

        </form>
        <!-- Registration -->
        <h3 id="regErrorMsg">message</h3>
        <form id="register-form">
            <div class="form-group">
                <input id="homeLogin-username" name="username" type="text" placeholder="Username" class="form-control">
            </div>
            <div class="form-group">
                <input id="homeLogin-password" name="password" type="password" placeholder="Password" class="form-control">
            </div>
            <div class="form-group">
                <input id="register-email" name="email" type="text" placeholder="Email" class="form-control">
            </div>
           <button type="submit" class="btn btn-primary.gradient" id="register-form-submit">Register</button>
           <button type="button" class="btn btn-secondary" id="register-form-cancel">Cancel</button>
        </form>
    </div>

    <div class="container" id="registerContainer">
        <p id="notRegText">Don't have a registered account?</p>
        <button id="btnNotRegistered" type="button" class="btn">Create Account</button>
    </div>





    <!-- Chat Content -->
    <div class="top-buffer container-fluid row-fluid row-no-padding" id="chat-content">
        <div class="alert alert-danger alert-dismissible hidden" id="alert" role="alert"><button type="button" class="close alertclose" aria-label="Close"><span aria-hidden="true">&times;</span></button><span id="txtAlert">Wow big alert warning warning hello</span></div>
        
        <div class="col-md-2 panel btn-group-vertical nav" role="group" aria-label="channels" id="channel-list">
            <div id="searchDiv">
	            <div class="alert alert-danger alert-dismissible hidden" id="search-alert" role="alert"><button type="button" class="close alertclose" aria-label="Close"><span aria-hidden="true">&times;</span></button><span id="search-txt-alert"></span></div>
		        <form id="search-form" class="form-horizontal">		
			        <input type="text" name="searchWord" id="searchWord" placeholder="Search for channels...">
		        </form>
	        </div>
            <h1 id="channelsTitle">CHANNELS</h1>
        </div>
        <div class="col-md-8 panel panel-default" id="chat-frame">  
            <div id="channel-container">
            </div>
            <div class="panel-footer input-group">
                <textarea maxlength="255" rows="1" class="form-control" id="chat-input" type="text" placeholder="Enter a message..." autocomplete="off"></textarea>
                <span class="input-group-addon btn btn-success" id="basic-addon2">Send</span>
            </div>
        </div>
        <h1>USERS</h1>
        <ul class="col-md-2 panel nav nav-pulls nav-stacked" role="tablist" id="user-list">
        </ul>
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

        <!-- Footer Info -->
        <div class="container" id="footerContainer">
            <div id="textDiv">
            <p>This is a school-project for a webdevelopment-course at Mälardalens Högskola in Sweden. 
               The developers of this site are André Sääf, Alvin Samuelsson, Filiph Eriksson-Falk and Fredrik Frenning.
            </p>
            </div>
        </div>

</body>
</html>
