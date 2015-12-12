<!DOCTYPE html>
<html>
<head>
    <title>Wow! So cool!</title>
    <script type="text/javascript" src="js/jquery-1.11.3.min.js"></script>
    <script type="text/javascript" src="js/chat.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />
    <link rel="stylesheet" href="css/style.css" />
    <link href='https://fonts.googleapis.com/css?family=Open+Sans:400,300,700,800' rel='stylesheet' type='text/css'>
</head>
<body>
        <nav class="navbar navbar-inverse navbar-fixed-top">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="#">Project #IRC sexy</a>
                </div>
                <div id="navbar" class="collapse navbar-collapse">
                    <ul class="nav navbar-nav">
                        <li class="active"><a href="#">Home</a></li>
                        <li><a href="#about">About</a></li>
                        <li><a href="#contact">Contact</a></li>
                    </ul>
                </div><!--/.nav-collapse -->
            </div>
        </nav>
        <div class="container top-buffer" id="content">
            <div class="row">        
                <div class="col-md-5 text-center col-md-offset-3" id="grey-text">
                        <h1>This is our chat</h1>
                        <p class="lead">There are many chats<br> But this one is mine. Such cool chat, <br> now meme free! Try it out!</p>
                </div>
            <div class="col-md-2 col-md-offset-1" id="grey-text">        
                    <form method="post" class="form-horizontal" id="login-form">
                        <div class="form-group">
                            <label for="username_input" id="username-label"> Username </label>
                            <br>
                            <input class="form-control" type="text" name="username" id="username">
                        </div>
                        <div class="form-group">
                            <label for="password_input" id="password-label"> Password </label>
                            <br>
                            <input class="form-control" type="password" name="password" id="password">
                        </div>
                        <div class="form-group" id="submit-group">
                            <button type="submit" class="btn btn-default" id="submit">Submit</button>
                            <span><strong  class="leader" id="register">Register</strong></span>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </body>
</html>
