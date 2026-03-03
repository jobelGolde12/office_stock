<?php
ob_start();

class User extends Objects {
    protected $pdo;
    private $loginLogFile = __DIR__ . '/../logs/login.log';

    private $authTableName = null;
    private $authColumns = null;

    private $seededCredentials = null;

    function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function login($identifier, $pass) {
        $identifier = is_string($identifier) ? trim($identifier) : '';
        $pass = is_string($pass) ? trim($pass) : '';

        if ($identifier === '' || $pass === '') {
            $this->setLoginFailure('missing_credentials', 'Auth debug: missing_credentials', $identifier);
            return false;
        }

        try {
            $user = $this->findUserByIdentifier($identifier);
        } catch (Exception $e) {
            $this->setLoginFailure(
                'lookup_exception',
                'Auth debug: lookup_exception - ' . $e->getMessage(),
                $identifier,
                ['error' => $e->getMessage()]
            );
            return false;
        }

        if (!$user) {
            $seedUser = $this->findSeededCredential($identifier, $pass);
            if ($seedUser) {
                try {
                    $provisioned = $this->provisionSeedUser($seedUser, $pass);
                    if ($provisioned) {
                        $user = $provisioned;
                        $this->logLoginEvent('LOGIN_INFO', [
                            'reason' => 'seed_user_provisioned',
                            'identifier' => $this->maskIdentifier($identifier),
                            'table' => $this->getAuthTableName()
                        ]);
                    }
                } catch (Exception $e) {
                    $this->setLoginFailure(
                        'seed_user_provision_exception',
                        'Auth debug: seed_user_provision_exception - ' . $e->getMessage(),
                        $identifier,
                        ['error' => $e->getMessage()]
                    );
                    return false;
                }
            }
        }

        if (!$user) {
            $this->setLoginFailure('user_not_found', 'Auth debug: user_not_found', $identifier, [
                'table' => $this->getAuthTableName(),
                'columns' => $this->getAuthColumns(),
            ]);
            return false;
        }

        $storedPassword = isset($user->password) ? (string) $user->password : '';
        $passwordValid = false;
        $rehash = false;

        if ($storedPassword !== '') {
            if (strlen($storedPassword) === 32 && md5($pass) === $storedPassword) {
                $passwordValid = true;
                $rehash = true;
            } elseif (password_verify($pass, $storedPassword)) {
                $passwordValid = true;
                if (password_needs_rehash($storedPassword, PASSWORD_DEFAULT)) {
                    $rehash = true;
                }
            } elseif (hash_equals($storedPassword, $pass)) {
                $passwordValid = true;
                $rehash = true;
            }
        }

        if (!$passwordValid) {
            $this->setLoginFailure('password_mismatch', 'Auth debug: password_mismatch', $identifier, [
                'user_id' => $user->id ?? null,
                'table' => $this->getAuthTableName()
            ]);
            return false;
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $userId = $user->id ?? null;
        $userRole = $user->role ?? ($user->user_role ?? 'staff');
        $userName = $user->full_name ?? ($user->username ?? ($user->name ?? ($user->email ?? 'User')));

        $_SESSION['user_id'] = $userId;
        $_SESSION['user_role'] = $userRole;
        $_SESSION['user_name'] = $userName;
        unset($_SESSION['login_error'], $_SESSION['login_debug_error']);

        if ($rehash && $userId !== null) {
            try {
                $this->updatePasswordHashById($userId, password_hash($pass, PASSWORD_DEFAULT));
            } catch (Exception $e) {
                $this->logLoginEvent('LOGIN_INFO', [
                    'reason' => 'rehash_update_failed',
                    'user_id' => $userId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        $this->logLoginEvent('LOGIN_SUCCESS', [
            'user_id' => $userId,
            'identifier' => $this->maskIdentifier($identifier),
            'table' => $this->getAuthTableName()
        ]);

        redirect('index.php?page=dashboard');
        return true;
    }

    private function setLoginFailure($reason, $debugMessage, $identifier, array $extra = []) {
        $_SESSION['login_error'] = 'Invalid Email or Password';
        $_SESSION['login_debug_error'] = $debugMessage;

        $context = array_merge($extra, [
            'reason' => $reason,
            'identifier' => $this->maskIdentifier($identifier),
            'table' => $this->getAuthTableName()
        ]);
        $this->logLoginEvent('LOGIN_FAILED', $context);

        redirect('login.php');
    }

    private function getAuthTableName() {
        if ($this->authTableName !== null) {
            return $this->authTableName;
        }

        try {
            $usersCols = $this->readTableColumns('users');
            if (!empty($usersCols)) {
                $this->authTableName = 'users';
                $this->authColumns = $usersCols;
                return $this->authTableName;
            }

            $userCols = $this->readTableColumns('user');
            if (!empty($userCols)) {
                $this->authTableName = 'user';
                $this->authColumns = $userCols;
                return $this->authTableName;
            }
        } catch (Exception $e) {
            $this->logLoginEvent('LOGIN_INFO', [
                'reason' => 'table_discovery_error',
                'error' => $e->getMessage()
            ]);
        }

        $this->authTableName = 'users';
        $this->authColumns = ['id', 'full_name', 'email', 'password', 'role'];
        return $this->authTableName;
    }

    private function getAuthColumns() {
        if (!is_array($this->authColumns)) {
            $this->getAuthTableName();
        }
        return $this->authColumns;
    }

    private function readTableColumns($table) {
        $stmt = $this->pdo->query("PRAGMA table_info($table)");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $cols = [];
        foreach ($rows as $row) {
            if (!empty($row['name'])) {
                $cols[] = strtolower((string) $row['name']);
            }
        }
        return $cols;
    }

    private function findUserByIdentifier($identifier) {
        $table = $this->getAuthTableName();
        $columns = $this->getAuthColumns();

        $conditions = [];
        $params = [];

        if (in_array('email', $columns, true)) {
            $conditions[] = 'LOWER(email) = LOWER(:identifier_email)';
            $params[':identifier_email'] = $identifier;
        }

        if (in_array('username', $columns, true)) {
            $conditions[] = 'LOWER(username) = LOWER(:identifier_username)';
            $params[':identifier_username'] = $identifier;
        }

        if (empty($conditions)) {
            throw new Exception('No identifier column found (email/username)');
        }

        $sql = "SELECT * FROM $table WHERE " . implode(' OR ', $conditions) . ' LIMIT 1';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    private function updatePasswordHashById($id, $hash) {
        $table = $this->getAuthTableName();
        $sql = "UPDATE $table SET password = :pass WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':pass', $hash, PDO::PARAM_STR);
        $stmt->bindValue(':id', (int) $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    private function getSeededCredentials() {
        if (is_array($this->seededCredentials)) {
            return $this->seededCredentials;
        }

        $this->seededCredentials = [
            'default_password' => 'password',
            'users' => []
        ];

        $filePath = dirname(__DIR__, 2) . '/users_credentials.md';
        if (!is_file($filePath)) {
            return $this->seededCredentials;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return $this->seededCredentials;
        }

        if (preg_match('/same password:\s*`([^`]+)`/i', $content, $match)) {
            $this->seededCredentials['default_password'] = trim($match[1]);
        }

        $lines = preg_split("/\r\n|\n|\r/", $content);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] !== '|') {
                continue;
            }
            if (strpos($line, '---') !== false || stripos($line, 'Full Name') !== false) {
                continue;
            }

            $parts = array_map('trim', explode('|', trim($line, '|')));
            if (count($parts) < 4) {
                continue;
            }

            $email = strtolower($parts[2]);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $this->seededCredentials['users'][$email] = [
                'name' => $parts[1],
                'email' => $email,
                'role' => strtolower($parts[3]) ?: 'staff'
            ];
        }

        return $this->seededCredentials;
    }

    private function findSeededCredential($identifier, $password) {
        $data = $this->getSeededCredentials();
        $email = strtolower(trim($identifier));

        if (!isset($data['users'][$email])) {
            return null;
        }

        if (!hash_equals((string) $data['default_password'], (string) $password)) {
            return null;
        }

        return $data['users'][$email];
    }

    private function provisionSeedUser(array $seedUser, $plainPassword) {
        $table = $this->getAuthTableName();
        $columns = $this->getAuthColumns();

        $insertCols = [];
        $params = [];

        if (in_array('full_name', $columns, true)) {
            $insertCols[] = 'full_name';
            $params[':full_name'] = $seedUser['name'];
        }
        if (in_array('username', $columns, true)) {
            $insertCols[] = 'username';
            $params[':username'] = $seedUser['email'];
        }
        if (in_array('email', $columns, true)) {
            $insertCols[] = 'email';
            $params[':email'] = $seedUser['email'];
        }
        if (in_array('password', $columns, true)) {
            $insertCols[] = 'password';
            $params[':password'] = password_hash($plainPassword, PASSWORD_DEFAULT);
        }
        if (in_array('role', $columns, true)) {
            $insertCols[] = 'role';
            $params[':role'] = $seedUser['role'];
        }
        if (in_array('user_role', $columns, true)) {
            $insertCols[] = 'user_role';
            $params[':user_role'] = $seedUser['role'];
        }
        if (in_array('created_at', $columns, true)) {
            $insertCols[] = 'created_at';
            $params[':created_at'] = date('Y-m-d H:i:s');
        }
        if (in_array('updated_at', $columns, true)) {
            $insertCols[] = 'updated_at';
            $params[':updated_at'] = date('Y-m-d H:i:s');
        }

        if (!in_array('password', $insertCols, true) || empty($insertCols)) {
            throw new Exception('Cannot provision user: password column missing');
        }

        $placeholderList = [];
        foreach ($insertCols as $col) {
            $placeholderList[] = ':' . $col;
        }

        $sql = "INSERT INTO $table (" . implode(', ', $insertCols) . ") VALUES (" . implode(', ', $placeholderList) . ')';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_STR);
        }
        $stmt->execute();

        return $this->findUserByIdentifier($seedUser['email']);
    }

    private function logLoginEvent($event, array $context = []) {
        $logDir = dirname($this->loginLogFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }

        $context['ip'] = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        $context['ua'] = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $line = sprintf(
            "[%s] %s %s\n",
            date('Y-m-d H:i:s'),
            $event,
            json_encode($context, JSON_UNESCAPED_SLASHES)
        );

        error_log($line, 3, $this->loginLogFile);
    }

    private function maskIdentifier($identifier) {
        if (!is_string($identifier) || $identifier === '') {
            return '';
        }

        $identifier = strtolower($identifier);
        if (strpos($identifier, '@') === false) {
            return substr($identifier, 0, 2) . str_repeat('*', max(strlen($identifier) - 2, 0));
        }

        [$name, $domain] = explode('@', $identifier, 2);
        $maskedName = substr($name, 0, 2) . str_repeat('*', max(strlen($name) - 2, 0));
        return $maskedName . '@' . $domain;
    }

    public function is_admin(){
        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
            return true;
        } else {
            return false;
        }
    }

    public function redirect_unauth_users($page){
        if ($this->is_admin()) {
            return true;
        } else {
            redirect($page);
        }
    }

    public function is_login() {
        return !empty($_SESSION['user_id']);
    }

    public function logOut() {
        unset($_SESSION['user_id']);
        unset($_SESSION['user_role']);
        unset($_SESSION['user_name']);
        $_SESSION = array();
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        redirect('login.php');
    }

    public function checkUser($username) {
        return (bool) $this->findUserByIdentifier($username);
    }

    public function checkEmail($email) {
        return $this->checkUser($email);
    }

    public function userLog(){
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM logs ORDER BY id DESC LIMIT 5');
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (Exception $e) {
            return [];
        }
    }
}

ob_end_clean();
?>
