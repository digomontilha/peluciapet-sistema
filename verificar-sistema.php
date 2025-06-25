<?php
/**
 * Verificador de Sistema - PelúciaPet
 * Script para diagnóstico completo pós-instalação
 */

// Headers para exibição web
header('Content-Type: text/html; charset=utf-8');

// Configurações
$config = [
    'db_host' => 'peluciapet.mysql.dbaas.com.br',
    'db_name' => 'peluciapet',
    'db_user' => 'peluciapet',
    'db_pass' => 'Ogid@102290',
    'base_url' => 'https://peluciapet.com.br',
    'admin_path' => '/admin',
    'required_php_version' => '7.4.0',
    'required_extensions' => ['pdo', 'pdo_mysql', 'json', 'mbstring', 'curl', 'openssl', 'session'],
    'required_tables' => ['categorias', 'tamanhos', 'cores', 'produtos', 'produto_variacoes', 'produto_imagens'],
    'critical_files' => [
        'admin/config/config.php',
        'admin/classes/Database.php',
        'admin/classes/Produto.php',
        'admin/classes/Auth.php',
        'admin/api/produtos.php',
        'admin/api/api-publica.php',
        'admin/api/auth.php',
        'admin/auth/login.php',
        'admin/public/index.html',
        'admin/public/cadastro-produto.html'
    ]
];

// Resultados dos testes
$results = [
    'overall_status' => 'unknown',
    'tests' => [],
    'summary' => [],
    'recommendations' => []
];

/**
 * Executar teste e registrar resultado
 */
function runTest($name, $description, $callback) {
    global $results;
    
    $start_time = microtime(true);
    
    try {
        $result = $callback();
        $status = $result['status'] ?? 'unknown';
        $message = $result['message'] ?? '';
        $details = $result['details'] ?? [];
        $error = null;
    } catch (Exception $e) {
        $status = 'error';
        $message = $e->getMessage();
        $details = [];
        $error = $e->getTraceAsString();
    }
    
    $execution_time = round((microtime(true) - $start_time) * 1000, 2);
    
    $results['tests'][$name] = [
        'name' => $name,
        'description' => $description,
        'status' => $status,
        'message' => $message,
        'details' => $details,
        'execution_time' => $execution_time,
        'error' => $error
    ];
    
    return $status === 'success';
}

/**
 * Teste de versão do PHP
 */
function testPHPVersion() {
    global $config;
    
    $current_version = PHP_VERSION;
    $required_version = $config['required_php_version'];
    
    if (version_compare($current_version, $required_version, '>=')) {
        return [
            'status' => 'success',
            'message' => "PHP $current_version (>= $required_version)",
            'details' => [
                'current' => $current_version,
                'required' => $required_version,
                'sapi' => php_sapi_name()
            ]
        ];
    } else {
        return [
            'status' => 'error',
            'message' => "PHP $current_version é muito antigo (requer >= $required_version)",
            'details' => [
                'current' => $current_version,
                'required' => $required_version
            ]
        ];
    }
}

/**
 * Teste de extensões PHP
 */
function testPHPExtensions() {
    global $config;
    
    $missing = [];
    $loaded = [];
    
    foreach ($config['required_extensions'] as $extension) {
        if (extension_loaded($extension)) {
            $loaded[] = $extension;
        } else {
            $missing[] = $extension;
        }
    }
    
    if (empty($missing)) {
        return [
            'status' => 'success',
            'message' => 'Todas as extensões necessárias estão carregadas',
            'details' => [
                'loaded' => $loaded,
                'missing' => $missing
            ]
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Extensões faltando: ' . implode(', ', $missing),
            'details' => [
                'loaded' => $loaded,
                'missing' => $missing
            ]
        ];
    }
}

/**
 * Teste de conexão com banco de dados
 */
function testDatabaseConnection() {
    global $config;
    
    try {
        $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // Testar query simples
        $stmt = $pdo->query("SELECT VERSION() as version, DATABASE() as database");
        $info = $stmt->fetch();
        
        return [
            'status' => 'success',
            'message' => 'Conexão com banco de dados estabelecida',
            'details' => [
                'mysql_version' => $info['version'],
                'database' => $info['database'],
                'host' => $config['db_host'],
                'charset' => 'utf8mb4'
            ]
        ];
    } catch (PDOException $e) {
        return [
            'status' => 'error',
            'message' => 'Falha na conexão: ' . $e->getMessage(),
            'details' => [
                'host' => $config['db_host'],
                'database' => $config['db_name'],
                'user' => $config['db_user']
            ]
        ];
    }
}

/**
 * Teste de estrutura do banco
 */
function testDatabaseStructure() {
    global $config;
    
    try {
        $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        
        $existing_tables = [];
        $missing_tables = [];
        $table_stats = [];
        
        // Verificar tabelas existentes
        $stmt = $pdo->query("SHOW TABLES");
        $tables_in_db = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($config['required_tables'] as $table) {
            if (in_array($table, $tables_in_db)) {
                $existing_tables[] = $table;
                
                // Contar registros
                $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                $count = $count_stmt->fetch()['count'];
                $table_stats[$table] = $count;
            } else {
                $missing_tables[] = $table;
            }
        }
        
        if (empty($missing_tables)) {
            return [
                'status' => 'success',
                'message' => 'Todas as tabelas necessárias existem',
                'details' => [
                    'existing_tables' => $existing_tables,
                    'missing_tables' => $missing_tables,
                    'table_stats' => $table_stats,
                    'total_tables' => count($tables_in_db)
                ]
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Tabelas faltando: ' . implode(', ', $missing_tables),
                'details' => [
                    'existing_tables' => $existing_tables,
                    'missing_tables' => $missing_tables,
                    'table_stats' => $table_stats
                ]
            ];
        }
    } catch (PDOException $e) {
        return [
            'status' => 'error',
            'message' => 'Erro ao verificar estrutura: ' . $e->getMessage()
        ];
    }
}

/**
 * Teste de arquivos críticos
 */
function testCriticalFiles() {
    global $config;
    
    $existing_files = [];
    $missing_files = [];
    $file_permissions = [];
    
    $base_path = $_SERVER['DOCUMENT_ROOT'];
    
    foreach ($config['critical_files'] as $file) {
        $full_path = $base_path . '/' . $file;
        
        if (file_exists($full_path)) {
            $existing_files[] = $file;
            $file_permissions[$file] = [
                'readable' => is_readable($full_path),
                'writable' => is_writable($full_path),
                'size' => filesize($full_path),
                'modified' => date('Y-m-d H:i:s', filemtime($full_path))
            ];
        } else {
            $missing_files[] = $file;
        }
    }
    
    if (empty($missing_files)) {
        return [
            'status' => 'success',
            'message' => 'Todos os arquivos críticos existem',
            'details' => [
                'existing_files' => $existing_files,
                'missing_files' => $missing_files,
                'file_permissions' => $file_permissions
            ]
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Arquivos faltando: ' . implode(', ', $missing_files),
            'details' => [
                'existing_files' => $existing_files,
                'missing_files' => $missing_files,
                'file_permissions' => $file_permissions
            ]
        ];
    }
}

/**
 * Teste de configurações
 */
function testConfiguration() {
    $config_file = $_SERVER['DOCUMENT_ROOT'] . '/admin/config/config.php';
    
    if (!file_exists($config_file)) {
        return [
            'status' => 'error',
            'message' => 'Arquivo de configuração não encontrado'
        ];
    }
    
    // Incluir configurações
    ob_start();
    include $config_file;
    ob_end_clean();
    
    $checks = [
        'DB_HOST' => defined('DB_HOST'),
        'DB_NAME' => defined('DB_NAME'),
        'DB_USER' => defined('DB_USER'),
        'DB_PASS' => defined('DB_PASS'),
        'BASE_URL' => defined('BASE_URL'),
        'APP_VERSION' => defined('APP_VERSION')
    ];
    
    $missing_configs = [];
    $existing_configs = [];
    
    foreach ($checks as $config => $exists) {
        if ($exists) {
            $existing_configs[] = $config;
        } else {
            $missing_configs[] = $config;
        }
    }
    
    if (empty($missing_configs)) {
        return [
            'status' => 'success',
            'message' => 'Configurações básicas definidas',
            'details' => [
                'existing_configs' => $existing_configs,
                'missing_configs' => $missing_configs,
                'base_url' => defined('BASE_URL') ? BASE_URL : 'não definido',
                'app_version' => defined('APP_VERSION') ? APP_VERSION : 'não definido'
            ]
        ];
    } else {
        return [
            'status' => 'warning',
            'message' => 'Configurações faltando: ' . implode(', ', $missing_configs),
            'details' => [
                'existing_configs' => $existing_configs,
                'missing_configs' => $missing_configs
            ]
        ];
    }
}

/**
 * Teste de APIs
 */
function testAPIs() {
    global $config;
    
    $base_url = $config['base_url'] . $config['admin_path'];
    $apis_to_test = [
        'API Pública' => $base_url . '/api/api-publica.php?action=status',
        'API Admin' => $base_url . '/api/produtos.php?action=test'
    ];
    
    $api_results = [];
    $all_working = true;
    
    foreach ($apis_to_test as $name => $url) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'method' => 'GET',
                'header' => 'User-Agent: PelúciaPet System Checker'
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response !== false) {
            $json_data = json_decode($response, true);
            
            if ($json_data && isset($json_data['success'])) {
                $api_results[$name] = [
                    'status' => 'success',
                    'url' => $url,
                    'response' => $json_data
                ];
            } else {
                $api_results[$name] = [
                    'status' => 'warning',
                    'url' => $url,
                    'response' => 'Resposta inválida'
                ];
                $all_working = false;
            }
        } else {
            $api_results[$name] = [
                'status' => 'error',
                'url' => $url,
                'response' => 'Falha na conexão'
            ];
            $all_working = false;
        }
    }
    
    return [
        'status' => $all_working ? 'success' : 'warning',
        'message' => $all_working ? 'Todas as APIs responderam' : 'Algumas APIs com problemas',
        'details' => $api_results
    ];
}

/**
 * Teste de permissões de diretório
 */
function testDirectoryPermissions() {
    $directories_to_check = [
        'uploads' => $_SERVER['DOCUMENT_ROOT'] . '/uploads',
        'logs' => $_SERVER['DOCUMENT_ROOT'] . '/logs',
        'admin/config' => $_SERVER['DOCUMENT_ROOT'] . '/admin/config'
    ];
    
    $permission_results = [];
    $all_ok = true;
    
    foreach ($directories_to_check as $name => $path) {
        if (!is_dir($path)) {
            // Tentar criar diretório
            if (@mkdir($path, 0755, true)) {
                $permission_results[$name] = [
                    'exists' => true,
                    'readable' => is_readable($path),
                    'writable' => is_writable($path),
                    'created' => true
                ];
            } else {
                $permission_results[$name] = [
                    'exists' => false,
                    'readable' => false,
                    'writable' => false,
                    'created' => false
                ];
                $all_ok = false;
            }
        } else {
            $permission_results[$name] = [
                'exists' => true,
                'readable' => is_readable($path),
                'writable' => is_writable($path),
                'created' => false
            ];
            
            if (!is_readable($path) || !is_writable($path)) {
                $all_ok = false;
            }
        }
    }
    
    return [
        'status' => $all_ok ? 'success' : 'warning',
        'message' => $all_ok ? 'Permissões de diretório OK' : 'Problemas de permissão detectados',
        'details' => $permission_results
    ];
}

/**
 * Executar todos os testes
 */
function runAllTests() {
    global $results;
    
    $tests = [
        'php_version' => ['Versão do PHP', 'testPHPVersion'],
        'php_extensions' => ['Extensões PHP', 'testPHPExtensions'],
        'database_connection' => ['Conexão com Banco', 'testDatabaseConnection'],
        'database_structure' => ['Estrutura do Banco', 'testDatabaseStructure'],
        'critical_files' => ['Arquivos Críticos', 'testCriticalFiles'],
        'configuration' => ['Configurações', 'testConfiguration'],
        'apis' => ['APIs', 'testAPIs'],
        'directory_permissions' => ['Permissões de Diretório', 'testDirectoryPermissions']
    ];
    
    $success_count = 0;
    $warning_count = 0;
    $error_count = 0;
    
    foreach ($tests as $test_id => [$description, $callback]) {
        $success = runTest($test_id, $description, $callback);
        
        $status = $results['tests'][$test_id]['status'];
        
        switch ($status) {
            case 'success':
                $success_count++;
                break;
            case 'warning':
                $warning_count++;
                break;
            case 'error':
                $error_count++;
                break;
        }
    }
    
    // Determinar status geral
    if ($error_count > 0) {
        $results['overall_status'] = 'error';
    } elseif ($warning_count > 0) {
        $results['overall_status'] = 'warning';
    } else {
        $results['overall_status'] = 'success';
    }
    
    $results['summary'] = [
        'total_tests' => count($tests),
        'success_count' => $success_count,
        'warning_count' => $warning_count,
        'error_count' => $error_count,
        'overall_status' => $results['overall_status']
    ];
    
    // Gerar recomendações
    generateRecommendations();
}

/**
 * Gerar recomendações baseadas nos resultados
 */
function generateRecommendations() {
    global $results;
    
    $recommendations = [];
    
    foreach ($results['tests'] as $test) {
        switch ($test['status']) {
            case 'error':
                switch ($test['name']) {
                    case 'php_version':
                        $recommendations[] = 'Atualize o PHP para a versão mais recente';
                        break;
                    case 'php_extensions':
                        $recommendations[] = 'Instale as extensões PHP faltando: ' . implode(', ', $test['details']['missing']);
                        break;
                    case 'database_connection':
                        $recommendations[] = 'Verifique as credenciais do banco de dados em config.php';
                        break;
                    case 'database_structure':
                        $recommendations[] = 'Execute o script database/install.sql para criar as tabelas';
                        break;
                    case 'critical_files':
                        $recommendations[] = 'Faça upload dos arquivos faltando via FTP';
                        break;
                }
                break;
                
            case 'warning':
                switch ($test['name']) {
                    case 'configuration':
                        $recommendations[] = 'Complete as configurações em admin/config/config.php';
                        break;
                    case 'apis':
                        $recommendations[] = 'Verifique a configuração do servidor web e URLs';
                        break;
                    case 'directory_permissions':
                        $recommendations[] = 'Ajuste as permissões dos diretórios (chmod 755)';
                        break;
                }
                break;
        }
    }
    
    if (empty($recommendations)) {
        $recommendations[] = 'Sistema funcionando corretamente! Remova este arquivo por segurança.';
    }
    
    $results['recommendations'] = $recommendations;
}

// Executar testes
runAllTests();

// Determinar se deve retornar JSON ou HTML
$return_json = isset($_GET['format']) && $_GET['format'] === 'json';

if ($return_json) {
    header('Content-Type: application/json');
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Exibir HTML
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificador de Sistema - PelúciaPet</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #FDF6ED 0%, #F5F5F5 100%);
            color: #5C2C0D;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 2rem;
            background: linear-gradient(135deg, #5C2C0D 0%, #A0522D 100%);
            color: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: bold;
            margin: 1rem 0;
            font-size: 1.1rem;
        }
        
        .status-success { background: #d4edda; color: #155724; }
        .status-warning { background: #fff3cd; color: #856404; }
        .status-error { background: #f8d7da; color: #721c24; }
        
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .summary-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .summary-card h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .tests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .test-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .test-header {
            padding: 1rem;
            font-weight: bold;
            display: flex;
            justify-content: between;
            align-items: center;
        }
        
        .test-header.success { background: #d4edda; color: #155724; }
        .test-header.warning { background: #fff3cd; color: #856404; }
        .test-header.error { background: #f8d7da; color: #721c24; }
        
        .test-body {
            padding: 1rem;
        }
        
        .test-details {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-top: 1rem;
            font-family: monospace;
            font-size: 0.9rem;
        }
        
        .recommendations {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .recommendations h2 {
            color: #5C2C0D;
            margin-bottom: 1rem;
        }
        
        .recommendations ul {
            list-style-type: none;
        }
        
        .recommendations li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .recommendations li:before {
            content: "💡 ";
            margin-right: 0.5rem;
        }
        
        .footer {
            text-align: center;
            margin-top: 2rem;
            padding: 1rem;
            color: #666;
        }
        
        .json-link {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        
        @media (max-width: 768px) {
            .container { padding: 1rem; }
            .tests-grid { grid-template-columns: 1fr; }
            .header h1 { font-size: 2rem; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🐾 Verificador de Sistema PelúciaPet</h1>
            <p>Diagnóstico completo da instalação</p>
            <div class="status-badge status-<?php echo $results['overall_status']; ?>">
                Status: <?php echo strtoupper($results['overall_status']); ?>
            </div>
        </div>
        
        <div class="summary">
            <div class="summary-card">
                <h3><?php echo $results['summary']['total_tests']; ?></h3>
                <p>Testes Executados</p>
            </div>
            <div class="summary-card">
                <h3 style="color: #28a745;"><?php echo $results['summary']['success_count']; ?></h3>
                <p>Sucessos</p>
            </div>
            <div class="summary-card">
                <h3 style="color: #ffc107;"><?php echo $results['summary']['warning_count']; ?></h3>
                <p>Avisos</p>
            </div>
            <div class="summary-card">
                <h3 style="color: #dc3545;"><?php echo $results['summary']['error_count']; ?></h3>
                <p>Erros</p>
            </div>
        </div>
        
        <div class="tests-grid">
            <?php foreach ($results['tests'] as $test): ?>
            <div class="test-card">
                <div class="test-header <?php echo $test['status']; ?>">
                    <span><?php echo htmlspecialchars($test['description']); ?></span>
                    <span><?php echo $test['execution_time']; ?>ms</span>
                </div>
                <div class="test-body">
                    <p><strong>Status:</strong> <?php echo ucfirst($test['status']); ?></p>
                    <p><strong>Mensagem:</strong> <?php echo htmlspecialchars($test['message']); ?></p>
                    
                    <?php if (!empty($test['details'])): ?>
                    <div class="test-details">
                        <strong>Detalhes:</strong><br>
                        <?php echo htmlspecialchars(json_encode($test['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($test['error']): ?>
                    <div class="test-details" style="background: #f8d7da;">
                        <strong>Erro:</strong><br>
                        <?php echo htmlspecialchars($test['error']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="recommendations">
            <h2>📋 Recomendações</h2>
            <ul>
                <?php foreach ($results['recommendations'] as $recommendation): ?>
                <li><?php echo htmlspecialchars($recommendation); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <div class="footer">
            <p>Verificação executada em <?php echo date('Y-m-d H:i:s'); ?></p>
            <a href="?format=json" class="json-link">Ver Resultado em JSON</a>
            <p style="margin-top: 1rem; color: #dc3545;">
                <strong>⚠️ IMPORTANTE:</strong> Remova este arquivo após a verificação por motivos de segurança!
            </p>
        </div>
    </div>
</body>
</html>

