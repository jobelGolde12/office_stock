<?php 
	require_once '../init.php';

	if (isset($_POST['admin_login'])) {
		$username = $_POST['username'];
		$password = $_POST['password'];

		$result = $Ouser->login($username , $password);

		if ($result) {
			header("location:../index.php");
			exit();
		}else{
			header("location:../login.php?error=1");
			exit();
		}
	}
 ?>