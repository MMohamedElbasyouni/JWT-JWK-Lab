<?php
require_once __DIR__ . '/jwt.php';

if(isset($_POST['uname']) && isset($_POST['pwd'])){

	$uname = $_POST['uname'];
	$pwd = $_POST['pwd'];

	if($uname === 'mohamed' && $pwd === 'mohamed@123') {
		$payload = [
			'sub' => $uname,
			'iat' => time(),
			'exp' => time() + 3600
		];
		$token = jwt_sign($payload, __DIR__ . '/keys/private.pem');
		setcookie('token', $token, time() + 3600, '/');
		header("Location: profile.php");
	}else{
		echo "<script>alert('Username/Password is invalid.')</script>";
	}
}
?>
<html>
  <head>
    <title>Books Library</title>
    <link rel="stylesheet" type="text/css" href="../style/css.css">
  </head>
  <body id="bodyId">
    <div class="header">
      <div class="header-right">
        <a href="profile.php">Profile</a>
        <a class="active" href="login.php">Login</a>
      </div>
    </div>
    <div><br><br>
    	<center>
    	<form method="POST">
    		<input type="text" id="uname" name="uname" placeholder="Username" autocomplete="off"><br>
    		<input type="password" id="pwd" name="pwd" placeholder="Password" autocomplete="off"><br>
    		<input type="submit" value="Login" style="background-color: dodgerblue;">
    	</form>
    	</center>
    </div>
  </body>
</html>
