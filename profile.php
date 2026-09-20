<?php
require_once __DIR__ . '/jwt.php';

$token = $_COOKIE['token'] ?? null;
if(!$token){
    header("Location: login.php");
    die();
}

$payload = jwt_verify($token, __DIR__ . '/keys/public.pem');
if(!$payload || !isset($payload['sub'])){
    header("Location: login.php");
    die();
}

$username = $payload['sub'];
?>

<html>
  <head>
    <title>Books Library</title>
    <link rel="stylesheet" type="text/css" href="../style/css.css">
  </head>
  <body id="bodyId">
    <div class="header">
      <div class="header-right">
        <a class="active" href="profile.php">Profile</a>
        <a href="login.php">Login</a>
      </div>
    </div>
    
    <div class="container">
    <div class="be-comment-block">
        <div class="be-comment">
        <?php

        echo "<center><h1>Welcome <i>".$username."</i></h1></center>";

        ?>
        </div>
    </div>
    </div>
  </body>
</html>
