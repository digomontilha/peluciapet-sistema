<?php
/**
 * API de Autenticação - Sistema PelúciaPet
 * Processa login, logout e verificação de sessão
 */

// Headers CORS e segurança
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Responder a requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir classe de autenticação
require_once '../classes/Auth.php';

try {
    // Inicializar autenticação
    $auth = Auth::getInstance();
    
    // Determinar ação
    $action = $_GET['action'] ?? $_POST['action'] ?? 'check';
    
    // Processar requisição baseada na ação
    switch ($action) {
        case 'login':
            handleLogin($auth);
            break;
            
        case 'logout':
            handleLogout($auth);
            break;
            
        case 'check':
            handleCheck($auth);
            break;
            
        case 'user':
            handleGetUser($auth);
            break;
            
        case 'change_password':
            handleChangePassword($auth);
            break;
            
        default:
            throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

/**
 * Processar login
 */
function handleLogin($auth) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    // Obter dados do POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $input = $_POST;
    }
    
    $username = trim($input['username'] ?? '');
    $password = $input['password'] ?? '';
    $rememberMe = (bool)($input['remember_me'] ?? false);
    
    // Validar entrada
    if (empty($username) || empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Usuário e senha são obrigatórios'
        ]);
        return;
    }
    
    // Validar comprimento
    if (strlen($username) > 50 || strlen($password) > 100) {
        echo json_encode([
            'success' => false,
            'message' => 'Dados inválidos'
        ]);
        return;
    }
    
    // Tentar fazer login
    $result = $auth->login($username, $password, $rememberMe);
    
    // Log de tentativa de login
    logAuthEvent('login_attempt', $username, $result['success']);
    
    echo json_encode($result);
}

/**
 * Processar logout
 */
function handleLogout($auth) {
    $result = $auth->logout();
    
    // Log de logout
    logAuthEvent('logout', 'user', true);
    
    echo json_encode($result);
}

/**
 * Verificar status de autenticação
 */
function handleCheck($auth) {
    $isAuthenticated = $auth->isAuthenticated();
    $user = $isAuthenticated ? $auth->getCurrentUser() : null;
    
    echo json_encode([
        'authenticated' => $isAuthenticated,
        'user' => $user,
        'timestamp' => time()
    ]);
}

/**
 * Obter dados do usuário atual
 */
function handleGetUser($auth) {
    if (!$auth->isAuthenticated()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Não autenticado'
        ]);
        return;
    }
    
    $user = $auth->getCurrentUser();
    
    echo json_encode([
        'success' => true,
        'user' => $user
    ]);
}

/**
 * Alterar senha (funcionalidade futura)
 */
function handleChangePassword($auth) {
    if (!$auth->isAuthenticated()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Não autenticado'
        ]);
        return;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    $currentPassword = $input['current_password'] ?? '';
    $newPassword = $input['new_password'] ?? '';
    $confirmPassword = $input['confirm_password'] ?? '';
    
    // Validações básicas
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        echo json_encode([
            'success' => false,
            'message' => 'Todos os campos são obrigatórios'
        ]);
        return;
    }
    
    if ($newPassword !== $confirmPassword) {
        echo json_encode([
            'success' => false,
            'message' => 'Nova senha e confirmação não coincidem'
        ]);
        return;
    }
    
    // Validar força da senha
    $passwordValidation = Auth::validatePasswordStrength($newPassword);
    if (!$passwordValidation['valid']) {
        echo json_encode([
            'success' => false,
            'message' => 'Senha não atende aos critérios de segurança',
            'errors' => $passwordValidation['errors']
        ]);
        return;
    }
    
    // TODO: Implementar alteração de senha no banco de dados
    echo json_encode([
        'success' => false,
        'message' => 'Funcionalidade em desenvolvimento'
    ]);
}

/**
 * Log de eventos de autenticação
 */
function logAuthEvent($event, $username, $success) {
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'event' => $event,
        'username' => $username,
        'success' => $success,
        'ip' => getClientIP(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    error_log('PelúciaPet Auth: ' . json_encode($logData));
}

/**
 * Obter IP do cliente
 */
function getClientIP() {
    $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
    
    foreach ($ipKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $ips = explode(',', $_SERVER[$key]);
            return trim($ips[0]);
        }
    }
    
    return 'unknown';
}

/**
 * Validar entrada para prevenir ataques
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Rate limiting básico (em produção, usar Redis ou banco)
 */
function checkRateLimit($identifier, $maxRequests = 10, $timeWindow = 300) {
    $cacheFile = sys_get_temp_dir() . '/peluciapet_rate_' . md5($identifier);
    
    $requests = [];
    if (file_exists($cacheFile)) {
        $requests = json_decode(file_get_contents($cacheFile), true) ?: [];
    }
    
    // Remover requisições antigas
    $now = time();
    $requests = array_filter($requests, function($timestamp) use ($now, $timeWindow) {
        return ($now - $timestamp) < $timeWindow;
    });
    
    // Verificar limite
    if (count($requests) >= $maxRequests) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'error' => 'Muitas tentativas. Tente novamente em alguns minutos.',
            'retry_after' => $timeWindow
        ]);
        exit;
    }
    
    // Adicionar requisição atual
    $requests[] = $now;
    file_put_contents($cacheFile, json_encode($requests));
}

// Aplicar rate limiting para tentativas de login
if (($action ?? '') === 'login') {
    $identifier = getClientIP();
    checkRateLimit($identifier, 15, 300); // 15 tentativas por 5 minutos
}
?>

