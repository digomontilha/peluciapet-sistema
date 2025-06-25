<?php
/**
 * Classe de Autenticação para Sistema PelúciaPet
 * Gerencia login, logout e proteção de páginas administrativas
 */

class Auth {
    private static $instance = null;
    private $sessionName = 'peluciapet_admin';
    private $loginAttempts = [];
    private $maxAttempts = 5;
    private $lockoutTime = 900; // 15 minutos
    
    // Credenciais padrão (em produção, mover para banco de dados)
    private $credentials = [
        'admin' => [
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'name' => 'Administrador PelúciaPet',
            'role' => 'admin'
        ],
        'peluciapet' => [
            'password' => '$2y$10$8K1p0SH8qWJ4lE6FNu4Pu.H8UlaWQVKvOE8FjKr1b6EuBSqHguLGe', // peluciapet123
            'name' => 'Gerente PelúciaPet',
            'role' => 'manager'
        ]
    ];
    
    private function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerar ID da sessão periodicamente para segurança
        if (!isset($_SESSION['last_regeneration'])) {
            $this->regenerateSession();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutos
            $this->regenerateSession();
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Verifica se o usuário está autenticado
     */
    public function isAuthenticated() {
        return isset($_SESSION[$this->sessionName]) && 
               isset($_SESSION[$this->sessionName]['authenticated']) &&
               $_SESSION[$this->sessionName]['authenticated'] === true &&
               $this->isSessionValid();
    }
    
    /**
     * Verifica se a sessão é válida
     */
    private function isSessionValid() {
        // Verificar timeout da sessão (2 horas)
        if (isset($_SESSION[$this->sessionName]['last_activity'])) {
            if (time() - $_SESSION[$this->sessionName]['last_activity'] > 7200) {
                $this->logout();
                return false;
            }
        }
        
        // Atualizar última atividade
        $_SESSION[$this->sessionName]['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Realiza login do usuário
     */
    public function login($username, $password, $rememberMe = false) {
        // Verificar bloqueio por tentativas excessivas
        if ($this->isBlocked($username)) {
            return [
                'success' => false,
                'message' => 'Muitas tentativas de login. Tente novamente em 15 minutos.',
                'blocked_until' => $this->getBlockedUntil($username)
            ];
        }
        
        // Validar credenciais
        if (!$this->validateCredentials($username, $password)) {
            $this->recordFailedAttempt($username);
            return [
                'success' => false,
                'message' => 'Usuário ou senha incorretos.',
                'attempts_remaining' => $this->getRemainingAttempts($username)
            ];
        }
        
        // Login bem-sucedido
        $this->createSession($username);
        $this->clearFailedAttempts($username);
        
        if ($rememberMe) {
            $this->setRememberMeCookie($username);
        }
        
        // Log de segurança
        $this->logSecurityEvent('login_success', $username);
        
        return [
            'success' => true,
            'message' => 'Login realizado com sucesso!',
            'user' => $this->getCurrentUser()
        ];
    }
    
    /**
     * Valida credenciais do usuário
     */
    private function validateCredentials($username, $password) {
        if (!isset($this->credentials[$username])) {
            return false;
        }
        
        return password_verify($password, $this->credentials[$username]['password']);
    }
    
    /**
     * Cria sessão do usuário
     */
    private function createSession($username) {
        $this->regenerateSession();
        
        $_SESSION[$this->sessionName] = [
            'authenticated' => true,
            'username' => $username,
            'name' => $this->credentials[$username]['name'],
            'role' => $this->credentials[$username]['role'],
            'login_time' => time(),
            'last_activity' => time(),
            'ip_address' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
    }
    
    /**
     * Realiza logout do usuário
     */
    public function logout() {
        if (isset($_SESSION[$this->sessionName]['username'])) {
            $this->logSecurityEvent('logout', $_SESSION[$this->sessionName]['username']);
        }
        
        // Limpar sessão
        unset($_SESSION[$this->sessionName]);
        
        // Limpar cookie remember me
        if (isset($_COOKIE['peluciapet_remember'])) {
            setcookie('peluciapet_remember', '', time() - 3600, '/', '', true, true);
        }
        
        // Regenerar ID da sessão
        $this->regenerateSession();
        
        return ['success' => true, 'message' => 'Logout realizado com sucesso!'];
    }
    
    /**
     * Obtém dados do usuário atual
     */
    public function getCurrentUser() {
        if (!$this->isAuthenticated()) {
            return null;
        }
        
        $session = $_SESSION[$this->sessionName];
        return [
            'username' => $session['username'],
            'name' => $session['name'],
            'role' => $session['role'],
            'login_time' => $session['login_time'],
            'last_activity' => $session['last_activity']
        ];
    }
    
    /**
     * Verifica se usuário tem permissão específica
     */
    public function hasPermission($permission) {
        if (!$this->isAuthenticated()) {
            return false;
        }
        
        $role = $_SESSION[$this->sessionName]['role'];
        
        // Admin tem todas as permissões
        if ($role === 'admin') {
            return true;
        }
        
        // Definir permissões por role
        $permissions = [
            'manager' => ['view_products', 'create_products', 'edit_products', 'view_stats'],
            'editor' => ['view_products', 'create_products', 'edit_products'],
            'viewer' => ['view_products', 'view_stats']
        ];
        
        return isset($permissions[$role]) && in_array($permission, $permissions[$role]);
    }
    
    /**
     * Middleware para proteger páginas
     */
    public function requireAuth($redirectTo = '/admin/auth/login.php') {
        if (!$this->isAuthenticated()) {
            // Se for requisição AJAX, retornar JSON
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['error' => 'Não autenticado', 'redirect' => $redirectTo]);
                exit;
            }
            
            // Redirecionar para login
            header('Location: ' . $redirectTo);
            exit;
        }
    }
    
    /**
     * Middleware para verificar permissões
     */
    public function requirePermission($permission, $redirectTo = '/admin/auth/access-denied.php') {
        $this->requireAuth();
        
        if (!$this->hasPermission($permission)) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                http_response_code(403);
                echo json_encode(['error' => 'Permissão negada']);
                exit;
            }
            
            header('Location: ' . $redirectTo);
            exit;
        }
    }
    
    /**
     * Controle de tentativas de login
     */
    private function recordFailedAttempt($username) {
        $ip = $this->getClientIP();
        $key = $username . '_' . $ip;
        
        if (!isset($this->loginAttempts[$key])) {
            $this->loginAttempts[$key] = [];
        }
        
        $this->loginAttempts[$key][] = time();
        
        // Manter apenas tentativas recentes
        $this->loginAttempts[$key] = array_filter(
            $this->loginAttempts[$key],
            function($time) {
                return (time() - $time) < $this->lockoutTime;
            }
        );
        
        $this->logSecurityEvent('login_failed', $username, ['ip' => $ip]);
    }
    
    private function isBlocked($username) {
        $ip = $this->getClientIP();
        $key = $username . '_' . $ip;
        
        if (!isset($this->loginAttempts[$key])) {
            return false;
        }
        
        $recentAttempts = array_filter(
            $this->loginAttempts[$key],
            function($time) {
                return (time() - $time) < $this->lockoutTime;
            }
        );
        
        return count($recentAttempts) >= $this->maxAttempts;
    }
    
    private function getRemainingAttempts($username) {
        $ip = $this->getClientIP();
        $key = $username . '_' . $ip;
        
        if (!isset($this->loginAttempts[$key])) {
            return $this->maxAttempts;
        }
        
        $recentAttempts = array_filter(
            $this->loginAttempts[$key],
            function($time) {
                return (time() - $time) < $this->lockoutTime;
            }
        );
        
        return max(0, $this->maxAttempts - count($recentAttempts));
    }
    
    private function getBlockedUntil($username) {
        $ip = $this->getClientIP();
        $key = $username . '_' . $ip;
        
        if (!isset($this->loginAttempts[$key])) {
            return null;
        }
        
        $lastAttempt = max($this->loginAttempts[$key]);
        return $lastAttempt + $this->lockoutTime;
    }
    
    private function clearFailedAttempts($username) {
        $ip = $this->getClientIP();
        $key = $username . '_' . $ip;
        unset($this->loginAttempts[$key]);
    }
    
    /**
     * Utilitários
     */
    private function regenerateSession() {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
    
    private function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                return trim($ips[0]);
            }
        }
        
        return 'unknown';
    }
    
    private function setRememberMeCookie($username) {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (30 * 24 * 60 * 60); // 30 dias
        
        setcookie(
            'peluciapet_remember',
            $username . ':' . $token,
            $expiry,
            '/',
            '',
            true, // HTTPS only
            true  // HTTP only
        );
    }
    
    private function logSecurityEvent($event, $username, $extra = []) {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'username' => $username,
            'ip' => $this->getClientIP(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'extra' => $extra
        ];
        
        error_log('PelúciaPet Security: ' . json_encode($logData));
    }
    
    /**
     * Gerar hash de senha (para criar novas senhas)
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    /**
     * Verificar força da senha
     */
    public static function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Senha deve ter pelo menos 8 caracteres';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Senha deve conter pelo menos uma letra maiúscula';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Senha deve conter pelo menos uma letra minúscula';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Senha deve conter pelo menos um número';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}

