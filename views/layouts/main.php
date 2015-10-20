<?php
use base\Application;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Auth</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/site.css">
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
            <a class="navbar-brand" href="<?=Application::$app->base_uri?>">Auth</a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
            <div class="navbar-form navbar-right">
                <?php if(Application::$app->getUser()->getIsGuest()):?>
                    <a class="btn btn-success" href="/index.php?r=user/login">Log in</a>
                    <a class="btn btn-warning" href="/index.php?r=user/register">Register</a>
                <?php else: ?>
                    <a class="btn btn-danger" href="/index.php?r=user/logout">Logout</a>
                <?php endif; ?>
            </div>

        </div><!--/.navbar-collapse -->
    </div>
</nav>

<div class="container maincontainer">
    <!-- Alerts -->
    <?php foreach (Application::$app->getSession()->getAllFlashes(true) as $key=>$message):?>
        <?php $alertClass = substr($key,strpos($key,'-')+1); ?>
        <div class="alert alert-dismissible alert-<?=$alertClass?>" role="alert">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            <p><?=$message?></p>
        </div>
    <?php endforeach ?>

    <?= $content ?>

    <hr>
    <footer class="footer">
        <div class="container" align="center">Copyright &copy; <?= date('Y') ?> - <a href="/">Auth</a>
        </div>
    </footer>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>
</body>
</html>