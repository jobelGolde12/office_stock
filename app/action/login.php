<?php 
	require_once '../init.php';

	if (isset($_POST['admin_login'])) {
		$username = $_POST['username'] ?? '';
		$password = $_POST['password'] ?? '';

		try {
			$Ouser->login($username, $password);
		} catch (Throwable $e) {
			$logDir = __DIR__ . '/../logs';
			if (!is_dir($logDir)) {
				mkdir($logDir, 0775, true);
			}

			$line = sprintf(
				"[%s] LOGIN_HANDLER_EXCEPTION %s\n",
				date('Y-m-d H:i:s'),
				json_encode([
					'identifier' => is_string($username) ? substr($username, 0, 2) . '***' : '',
					'error' => $e->getMessage(),
				], JSON_UNESCAPED_SLASHES)
			);
			error_log($line, 3, $logDir . '/login.log');

			$_SESSION['login_error'] = "Login failed. Please try again.";
			$_SESSION['login_debug_error'] = "Auth debug: handler_exception - " . $e->getMessage();
			header("location: ../../login.php");
			exit;
		}
	}
 ?>
