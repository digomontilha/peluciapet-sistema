<?php
/**
 * API de Categorias - Sistema PelúciaPet v2.1
 */

require_once '../classes/Auth.php';
require_once '../classes/Database.php';
require_once '../classes/Categoria.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Verificar autenticação para operações de escrita
$method = $_SERVER['REQUEST_METHOD'];
$auth = new Auth();

if (in_array($method, ['POST', 'PUT', 'DELETE']) && !$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Acesso negado. Faça login primeiro.'
    ]);
    exit;
}

$categoria = new Categoria();

try {
    switch ($method) {
        case 'GET':
            handleGet($categoria);
            break;
            
        case 'POST':
            handlePost($categoria);
            break;
            
        case 'PUT':
            handlePut($categoria);
            break;
            
        case 'DELETE':
            handleDelete($categoria);
            break;
            
        case 'OPTIONS':
            http_response_code(200);
            exit;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método não permitido'
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno: ' . $e->getMessage()
    ]);
}

/**
 * Listar categorias
 */
function handleGet($categoria) {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            $filtros = [];
            
            // Aplicar filtros
            if (isset($_GET['categoria_pai_id'])) {
                $filtros['categoria_pai_id'] = $_GET['categoria_pai_id'] === 'null' ? null : (int)$_GET['categoria_pai_id'];
            }
            
            if (isset($_GET['nivel'])) {
                $filtros['nivel'] = (int)$_GET['nivel'];
            }
            
            if (isset($_GET['ativo'])) {
                $filtros['ativo'] = (int)$_GET['ativo'];
            }
            
            if (isset($_GET['mostrar_home'])) {
                $filtros['mostrar_home'] = (int)$_GET['mostrar_home'];
            }
            
            if (!empty($_GET['busca'])) {
                $filtros['busca'] = $_GET['busca'];
            }
            
            if (!empty($_GET['order_by'])) {
                $filtros['order_by'] = $_GET['order_by'];
            }
            
            $categorias = $categoria->listar($filtros);
            
            echo json_encode([
                'success' => true,
                'message' => 'Categorias carregadas com sucesso',
                'data' => $categorias
            ]);
            break;
            
        case 'tree':
            $apenasAtivas = isset($_GET['apenas_ativas']) ? (bool)$_GET['apenas_ativas'] : true;
            $arvore = $categoria->obterArvore($apenasAtivas);
            
            echo json_encode([
                'success' => true,
                'message' => 'Árvore de categorias carregada',
                'data' => $arvore
            ]);
            break;
            
        case 'breadcrumb':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID da categoria é obrigatório'
                ]);
                return;
            }
            
            $breadcrumb = $categoria->obterBreadcrumb((int)$_GET['id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Breadcrumb carregado',
                'data' => $breadcrumb
            ]);
            break;
            
        case 'stats':
            $stats = $categoria->obterEstatisticas();
            
            echo json_encode([
                'success' => true,
                'message' => 'Estatísticas carregadas',
                'data' => $stats
            ]);
            break;
            
        case 'get':
            if (isset($_GET['id'])) {
                $cat = $categoria->buscarPorId((int)$_GET['id']);
            } elseif (isset($_GET['slug'])) {
                $cat = $categoria->buscarPorSlug($_GET['slug']);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID ou slug da categoria é obrigatório'
                ]);
                return;
            }
            
            if (!$cat) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Categoria não encontrada'
                ]);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Categoria encontrada',
                'data' => $cat
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Ação não reconhecida'
            ]);
    }
}

/**
 * Criar categoria
 */
function handlePost($categoria) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Dados inválidos'
        ]);
        return;
    }
    
    $result = $categoria->criar($input);
    
    if ($result['success']) {
        http_response_code(201);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * Atualizar categoria
 */
function handlePut($categoria) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID da categoria é obrigatório'
        ]);
        return;
    }
    
    $id = (int)$input['id'];
    unset($input['id']);
    
    // Verificar se é operação de mover
    if (isset($input['action']) && $input['action'] === 'move') {
        $result = $categoria->mover(
            $id,
            $input['nova_categoria_pai_id'] ?? null,
            $input['nova_ordem'] ?? null
        );
    } else {
        $result = $categoria->atualizar($id, $input);
    }
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * Excluir categoria
 */
function handleDelete($categoria) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID da categoria é obrigatório'
        ]);
        return;
    }
    
    $result = $categoria->excluir((int)$input['id']);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}
?>

