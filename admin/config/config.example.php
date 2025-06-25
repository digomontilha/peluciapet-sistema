<?php
/**
 * Configurações do Sistema PelúciaPet
 * Arquivo de configuração principal
 */

// Configurações do Banco de Dados MySQL
define('DB_HOST', 'SEU_HOST_MYSQL');
define('DB_PORT', '3306');
define('DB_NAME', 'peluciapet');
define('DB_USER', 'peluciapet');
define('DB_PASS', 'SUA_SENHA_AQUI');
define('DB_CHARSET', 'utf8mb4');

// Configurações da Aplicação
define('APP_NAME', 'PelúciaPet Admin');
define('APP_VERSION', '2.0.0');
define('APP_ENV', 'production'); // development, staging, production

// URLs Base
define('BASE_URL', 'https://peluciapet.com.br');
define('ADMIN_URL', BASE_URL . '/admin');
define('API_URL', ADMIN_URL . '/api');

// Configurações de Segurança
define('SECRET_KEY', 'peluciapet_secret_key_2024_' . md5(DB_NAME . DB_USER));
define('SESSION_LIFETIME', 7200); // 2 horas em segundos
define('REMEMBER_ME_LIFETIME', 2592000); // 30 dias em segundos

// Configurações de Upload
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Configurações de Email (para futuras implementações)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'contato@peluciapet.com.br');
define('SMTP_PASS', ''); // Configurar quando necessário
define('SMTP_FROM_NAME', 'PelúciaPet');

// Configurações de Cache
define('CACHE_ENABLED', true);
define('CACHE_LIFETIME', 3600); // 1 hora

// Configurações de Log
define('LOG_ENABLED', true);
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_FILE', $_SERVER['DOCUMENT_ROOT'] . '/logs/peluciapet.log');

// Configurações de Backup
define('BACKUP_DIR', $_SERVER['DOCUMENT_ROOT'] . '/backups');
define('BACKUP_RETENTION_DAYS', 30);

// Configurações do WhatsApp
define('WHATSAPP_NUMBER', '5511999999999'); // Atualizar com número real
define('WHATSAPP_MESSAGE_TEMPLATE', 'Olá! Tenho interesse em um produto da PelúciaPet:');

// Configurações de SEO
define('SITE_TITLE', 'PelúciaPet - Caminhas e Roupinhas para Pets');
define('SITE_DESCRIPTION', 'Loja especializada em caminhas confortáveis e roupinhas estilosas para cães e gatos. Produtos de alta qualidade com entrega para todo o Brasil.');
define('SITE_KEYWORDS', 'caminhas para pets, roupinhas para cães, acessórios para gatos, pet shop online');

// Configurações de Timezone
date_default_timezone_set('America/Sao_Paulo');

// Configurações de Erro (apenas em desenvolvimento)
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
}

// Configurações de Sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Função para verificar se estamos em HTTPS
function isHTTPS() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
           $_SERVER['SERVER_PORT'] == 443 ||
           (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
}

// Forçar HTTPS em produção
if (APP_ENV === 'production' && !isHTTPS() && php_sapi_name() !== 'cli') {
    $redirectURL = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $redirectURL");
    exit();
}

// Configurações específicas por ambiente
switch (APP_ENV) {
    case 'development':
        define('DEBUG_MODE', true);
        define('CACHE_ENABLED', false);
        break;
        
    case 'staging':
        define('DEBUG_MODE', true);
        define('CACHE_ENABLED', true);
        break;
        
    case 'production':
    default:
        define('DEBUG_MODE', false);
        define('CACHE_ENABLED', true);
        break;
}

// Autoloader simples para classes
spl_autoload_register(function ($className) {
    $classFile = __DIR__ . '/../classes/' . $className . '.php';
    if (file_exists($classFile)) {
        require_once $classFile;
    }
});

// Função para log de eventos
function logEvent($level, $message, $context = []) {
    if (!LOG_ENABLED) return;
    
    $logLevels = ['DEBUG' => 0, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3];
    $currentLevel = $logLevels[LOG_LEVEL] ?? 1;
    $messageLevel = $logLevels[$level] ?? 1;
    
    if ($messageLevel < $currentLevel) return;
    
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
    $logMessage = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
    
    // Criar diretório de logs se não existir
    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents(LOG_FILE, $logMessage, FILE_APPEND | LOCK_EX);
}

// Função para obter configuração
function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

// Função para verificar se estamos no ambiente de desenvolvimento
function isDevelopment() {
    return APP_ENV === 'development';
}

// Função para verificar se estamos em produção
function isProduction() {
    return APP_ENV === 'production';
}

// Headers de segurança
if (!isDevelopment()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    if (isHTTPS()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

// Configurações específicas do MySQL
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
}

// Verificar extensões PHP necessárias
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'curl'];
$missingExtensions = [];

foreach ($requiredExtensions as $extension) {
    if (!extension_loaded($extension)) {
        $missingExtensions[] = $extension;
    }
}

if (!empty($missingExtensions)) {
    $message = 'Extensões PHP necessárias não encontradas: ' . implode(', ', $missingExtensions);
    logEvent('ERROR', $message);
    
    if (isDevelopment()) {
        die($message);
    }
}

// Configuração concluída
logEvent('INFO', 'Sistema PelúciaPet inicializado', [
    'version' => APP_VERSION,
    'environment' => APP_ENV,
    'php_version' => PHP_VERSION,
    'database' => DB_NAME
]);
?>

