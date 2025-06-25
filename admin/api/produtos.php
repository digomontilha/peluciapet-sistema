<?php
/**
 * API Administrativa - Sistema PelúciaPet
 * Endpoints protegidos por autenticação para gerenciamento de produtos
 */

// Headers CORS e segurança
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Responder a requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir dependências
require_once '../config/config.php';
require_once '../classes/Auth.php';
require_once '../classes/Database.php';
require_once '../classes/Produto.php';

try {
    // Inicializar componentes
    $auth = Auth::getInstance();
    $produto = new Produto();
    
    // Determinar ação
    $action = $_GET['action'] ?? $_POST['action'] ?? 'list';
    
    // Verificar autenticação para todas as ações exceto 'test'
    if ($action !== 'test') {
        $auth->requireAuth();
    }
    
    // Processar requisição baseada na ação
    switch ($action) {
        case 'test':
            handleTest();
            break;
            
        case 'estatisticas':
            handleEstatisticas($produto);
            break;
            
        case 'categorias':
            handleCategorias();
            break;
            
        case 'tamanhos':
            handleTamanhos();
            break;
            
        case 'cores':
            handleCores();
            break;
            
        case 'produtos':
        case 'list':
            handleListarProdutos($produto);
            break;
            
        case 'produto':
            handleObterProduto($produto);
            break;
            
        case 'criar':
            $auth->requirePermission('create_products');
            handleCriarProduto($produto);
            break;
            
        case 'atualizar':
            $auth->requirePermission('edit_products');
            handleAtualizarProduto($produto);
            break;
            
        case 'excluir':
            $auth->requirePermission('delete_products');
            handleExcluirProduto($produto);
            break;
            
        case 'upload':
            $auth->requirePermission('create_products');
            handleUploadImagem();
            break;
            
        default:
            throw new Exception('Ação não reconhecida: ' . $action);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    // Log do erro
    logEvent('ERROR', 'API Error: ' . $e->getMessage(), [
        'action' => $action ?? 'unknown',
        'method' => $_SERVER['REQUEST_METHOD'],
        'user' => $auth->getCurrentUser()['username'] ?? 'anonymous'
    ]);
}

/**
 * Teste de conectividade da API
 */
function handleTest() {
    try {
        $db = Database::getInstance();
        $testResult = $db->testConnection();
        
        echo json_encode([
            'success' => true,
            'message' => 'API funcionando corretamente',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => APP_VERSION,
            'database_test' => $testResult,
            'php_version' => PHP_VERSION,
            'mysql_version' => $db->fetch("SELECT VERSION() as version")['version'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro no teste da API',
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Obter estatísticas do sistema
 */
function handleEstatisticas($produto) {
    try {
        $stats = $produto->obterEstatisticas();
        
        echo json_encode([
            'success' => true,
            'estatisticas' => $stats,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao obter estatísticas',
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Listar categorias
 */
function handleCategorias() {
    try {
        $db = Database::getInstance();
        $categorias = $db->fetchAll("
            SELECT id, nome, slug, descricao, icone, cor_tema, ordem
            FROM categorias 
            WHERE ativo = 1 
            ORDER BY ordem ASC, nome ASC
        ");
        
        echo json_encode([
            'success' => true,
            'categorias' => $categorias
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao obter categorias',
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Listar tamanhos
 */
function handleTamanhos() {
    try {
        $db = Database::getInstance();
        $categoriaId = $_GET['categoria_id'] ?? null;
        
        $sql = "
            SELECT t.*, c.nome as categoria_nome
            FROM tamanhos t
            LEFT JOIN categorias c ON t.categoria_id = c.id
            WHERE t.ativo = 1
        ";
        
        $params = [];
        if ($categoriaId) {
            $sql .= " AND t.categoria_id = :categoria_id";
            $params['categoria_id'] = $categoriaId;
        }
        
        $sql .= " ORDER BY t.categoria_id ASC, t.ordem ASC";
        
        $tamanhos = $db->fetchAll($sql, $params);
        
        echo json_encode([
            'success' => true,
            'tamanhos' => $tamanhos
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao obter tamanhos',
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Listar cores
 */
function handleCores() {
    try {
        $db = Database::getInstance();
        $cores = $db->fetchAll("
            SELECT id, nome, codigo_hex, codigo_rgb, familia_cor, ordem
            FROM cores 
            WHERE ativo = 1 
            ORDER BY ordem ASC, nome ASC
        ");
        
        echo json_encode([
            'success' => true,
            'cores' => $cores
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao obter cores',
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Listar produtos com filtros
 */
function handleListarProdutos($produto) {
    try {
        $filtros = [
            'categoria_id' => $_GET['categoria_id'] ?? null,
            'busca' => $_GET['busca'] ?? null,
            'destaque' => isset($_GET['destaque']) ? (bool)$_GET['destaque'] : null,
            'preco_min' => $_GET['preco_min'] ?? null,
            'preco_max' => $_GET['preco_max'] ?? null,
            'ordem' => $_GET['ordem'] ?? 'created_at_desc',
            'limite' => $_GET['limite'] ?? 20,
            'offset' => $_GET['offset'] ?? 0
        ];
        
        $produtos = $produto->listar($filtros);
        
        echo json_encode([
            'success' => true,
            'produtos' => $produtos,
            'filtros_aplicados' => array_filter($filtros),
            'total' => count($produtos)
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao listar produtos',
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Obter produto específico
 */
function handleObterProduto($produto) {
    try {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            throw new Exception('ID do produto é obrigatório');
        }
        
        $produtoData = $produto->obterPorId($id);
        
        if (!$produtoData) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Produto não encontrado'
            ]);
            return;
        }
        
        echo json_encode([
            'success' => true,
            'produto' => $produtoData
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao obter produto',
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Criar novo produto
 */
function handleCriarProduto($produto) {
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception('Método não permitido');
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $input = $_POST;
        }
        
        // Validar dados obrigatórios
        $requiredFields = ['nome', 'descricao', 'categoria_id', 'preco_base'];
        foreach ($requiredFields as $field) {
            if (empty($input[$field])) {
                throw new Exception("Campo obrigatório: $field");
            }
        }
        
        $result = $produto->criar($input);
        
        // Log da ação
        logEvent('INFO', 'Produto criado', [
            'produto_id' => $result['produto_id'],
            'nome' => $input['nome'],
            'usuario' => Auth::getInstance()->getCurrentUser()['username']
        ]);
        
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao criar produto',
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Atualizar produto existente
 */
function handleAtualizarProduto($produto) {
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception('Método não permitido');
        }
        
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        
        if (!$id) {
            throw new Exception('ID do produto é obrigatório');
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = $_POST;
        }
        
        $result = $produto->atualizar($id, $input);
        
        // Log da ação
        logEvent('INFO', 'Produto atualizado', [
            'produto_id' => $id,
            'usuario' => Auth::getInstance()->getCurrentUser()['username']
        ]);
        
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao atualizar produto',
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Excluir produto (soft delete)
 */
function handleExcluirProduto($produto) {
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception('Método não permitido');
        }
        
        $id = $_GET['id'] ?? $_POST['id'] ?? null;
        
        if (!$id) {
            throw new Exception('ID do produto é obrigatório');
        }
        
        $result = $produto->excluir($id);
        
        // Log da ação
        logEvent('WARNING', 'Produto excluído', [
            'produto_id' => $id,
            'usuario' => Auth::getInstance()->getCurrentUser()['username']
        ]);
        
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao excluir produto',
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Upload de imagem (funcionalidade futura)
 */
function handleUploadImagem() {
    try {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            throw new Exception('Método não permitido');
        }
        
        if (!isset($_FILES['imagem'])) {
            throw new Exception('Nenhuma imagem foi enviada');
        }
        
        $file = $_FILES['imagem'];
        
        // Validações básicas
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro no upload da imagem');
        }
        
        if ($file['size'] > MAX_FILE_SIZE) {
            throw new Exception('Arquivo muito grande. Máximo: ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB');
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_EXTENSIONS)) {
            throw new Exception('Formato não permitido. Use: ' . implode(', ', ALLOWED_EXTENSIONS));
        }
        
        // TODO: Implementar upload real
        echo json_encode([
            'success' => false,
            'message' => 'Funcionalidade de upload em desenvolvimento',
            'file_info' => [
                'name' => $file['name'],
                'size' => $file['size'],
                'type' => $file['type']
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro no upload',
            'error' => $e->getMessage()
        ]);
    }
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
 * Rate limiting para API
 */
function checkApiRateLimit() {
    $user = Auth::getInstance()->getCurrentUser();
    $identifier = $user ? $user['username'] : getClientIP();
    
    $cacheFile = sys_get_temp_dir() . '/peluciapet_api_rate_' . md5($identifier);
    
    $requests = [];
    if (file_exists($cacheFile)) {
        $requests = json_decode(file_get_contents($cacheFile), true) ?: [];
    }
    
    // Remover requisições antigas (últimos 5 minutos)
    $now = time();
    $requests = array_filter($requests, function($timestamp) use ($now) {
        return ($now - $timestamp) < 300;
    });
    
    // Verificar limite (100 requisições por 5 minutos para usuários autenticados)
    $maxRequests = $user ? 100 : 20;
    
    if (count($requests) >= $maxRequests) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'error' => 'Limite de requisições excedido',
            'retry_after' => 300
        ]);
        exit;
    }
    
    // Adicionar requisição atual
    $requests[] = $now;
    file_put_contents($cacheFile, json_encode($requests));
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

// Aplicar rate limiting
checkApiRateLimit();
?>

