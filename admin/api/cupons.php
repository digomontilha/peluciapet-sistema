<?php
/**
 * API de Cupons - Sistema PelúciaPet v2.1
 */

require_once '../classes/Auth.php';
require_once '../classes/Database.php';
require_once '../classes/Cupom.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$method = $_SERVER['REQUEST_METHOD'];
$cupom = new Cupom();

try {
    switch ($method) {
        case 'GET':
            handleGet($cupom);
            break;
            
        case 'POST':
            handlePost($cupom);
            break;
            
        case 'PUT':
            handlePut($cupom);
            break;
            
        case 'DELETE':
            handleDelete($cupom);
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
 * Consultas GET
 */
function handleGet($cupom) {
    $action = $_GET['action'] ?? 'list';
    
    switch ($action) {
        case 'list':
            // Verificar autenticação para listagem administrativa
            if (isset($_GET['admin']) && $_GET['admin'] === '1') {
                $auth = new Auth();
                if (!$auth->isLoggedIn()) {
                    http_response_code(401);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Acesso negado'
                    ]);
                    return;
                }
            }
            
            $filtros = [];
            
            // Aplicar filtros
            if (isset($_GET['ativo'])) {
                $filtros['ativo'] = (int)$_GET['ativo'];
            }
            
            if (!empty($_GET['tipo_desconto'])) {
                $filtros['tipo_desconto'] = $_GET['tipo_desconto'];
            }
            
            if (isset($_GET['validos_apenas'])) {
                $filtros['validos_apenas'] = (bool)$_GET['validos_apenas'];
            }
            
            if (!empty($_GET['busca'])) {
                $filtros['busca'] = $_GET['busca'];
            }
            
            if (!empty($_GET['order_by'])) {
                $filtros['order_by'] = $_GET['order_by'];
            }
            
            $cupons = $cupom->listar($filtros);
            
            echo json_encode([
                'success' => true,
                'message' => 'Cupons carregados com sucesso',
                'data' => $cupons
            ]);
            break;
            
        case 'get':
            if (isset($_GET['id'])) {
                $cup = $cupom->buscarPorId((int)$_GET['id']);
            } elseif (isset($_GET['codigo'])) {
                $cup = $cupom->buscarPorCodigo($_GET['codigo']);
            } else {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID ou código do cupom é obrigatório'
                ]);
                return;
            }
            
            if (!$cup) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Cupom não encontrado'
                ]);
                return;
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Cupom encontrado',
                'data' => $cup
            ]);
            break;
            
        case 'validar':
            if (empty($_GET['codigo'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Código do cupom é obrigatório'
                ]);
                return;
            }
            
            $codigo = $_GET['codigo'];
            $clienteId = isset($_GET['cliente_id']) ? (int)$_GET['cliente_id'] : null;
            $valorPedido = (float)($_GET['valor_pedido'] ?? 0);
            $itens = isset($_GET['itens']) ? json_decode($_GET['itens'], true) : [];
            
            $result = $cupom->validar($codigo, $clienteId, $valorPedido, $itens);
            
            if ($result['success']) {
                http_response_code(200);
            } else {
                http_response_code(400);
            }
            
            echo json_encode($result);
            break;
            
        case 'calcular':
            if (empty($_GET['codigo'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Código do cupom é obrigatório'
                ]);
                return;
            }
            
            $codigo = $_GET['codigo'];
            $valorPedido = (float)($_GET['valor_pedido'] ?? 0);
            $valorFrete = (float)($_GET['valor_frete'] ?? 0);
            $itens = isset($_GET['itens']) ? json_decode($_GET['itens'], true) : [];
            
            $result = $cupom->calcularDesconto($codigo, $valorPedido, $valorFrete, $itens);
            
            if ($result['success']) {
                http_response_code(200);
            } else {
                http_response_code(400);
            }
            
            echo json_encode($result);
            break;
            
        case 'relatorio':
            // Verificar autenticação
            $auth = new Auth();
            if (!$auth->isLoggedIn()) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Acesso negado'
                ]);
                return;
            }
            
            $periodo = $_GET['periodo'] ?? '30d';
            $result = $cupom->obterRelatorio($periodo);
            
            if ($result['success']) {
                http_response_code(200);
            } else {
                http_response_code(400);
            }
            
            echo json_encode($result);
            break;
            
        case 'gerar_codigo':
            // Verificar autenticação
            $auth = new Auth();
            if (!$auth->isLoggedIn()) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Acesso negado'
                ]);
                return;
            }
            
            $prefixo = $_GET['prefixo'] ?? 'PELUCIA';
            $tamanho = (int)($_GET['tamanho'] ?? 8);
            
            $codigo = $cupom->gerarCodigo($prefixo, $tamanho);
            
            echo json_encode([
                'success' => true,
                'message' => 'Código gerado com sucesso',
                'data' => ['codigo' => $codigo]
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
 * Criar cupom
 */
function handlePost($cupom) {
    // Verificar autenticação
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Acesso negado. Faça login primeiro.'
        ]);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Dados inválidos'
        ]);
        return;
    }
    
    $action = $input['action'] ?? 'criar';
    
    switch ($action) {
        case 'criar':
            $result = $cupom->criar($input);
            break;
            
        case 'aplicar':
            if (!isset($input['cupom_id']) || !isset($input['pedido_id']) || !isset($input['valor_desconto'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Dados incompletos para aplicar cupom'
                ]);
                return;
            }
            
            $result = $cupom->aplicar(
                (int)$input['cupom_id'],
                (int)$input['pedido_id'],
                (float)$input['valor_desconto'],
                isset($input['cliente_id']) ? (int)$input['cliente_id'] : null
            );
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Ação não reconhecida'
            ]);
            return;
    }
    
    if ($result['success']) {
        http_response_code(201);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * Atualizar cupom
 */
function handlePut($cupom) {
    // Verificar autenticação
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Acesso negado. Faça login primeiro.'
        ]);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID do cupom é obrigatório'
        ]);
        return;
    }
    
    $id = (int)$input['id'];
    unset($input['id']);
    
    $result = $cupom->atualizar($id, $input);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * Excluir cupom
 */
function handleDelete($cupom) {
    // Verificar autenticação
    $auth = new Auth();
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Acesso negado. Faça login primeiro.'
        ]);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID do cupom é obrigatório'
        ]);
        return;
    }
    
    $result = $cupom->excluir((int)$input['id']);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}
?>

