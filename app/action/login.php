<?php 
	require_once '../init.php';

	if (isset($_POST['admin_login'])) {
		$username = $_POST['username'];
		$password = $_POST['password'];

		$Ouser->login($username, $password);
	}
 ?>