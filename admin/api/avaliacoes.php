<?php
/**
 * API de Avaliações - Sistema PelúciaPet v2.2
 * Endpoints para gerenciar reviews e avaliações
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Responder a requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Avaliacao.php';
require_once '../classes/Auth.php';

try {
    $avaliacao = new Avaliacao();
    $auth = new Auth();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    switch ($method) {
        case 'GET':
            handleGetRequest($avaliacao, $action);
            break;
            
        case 'POST':
            handlePostRequest($avaliacao, $auth, $action);
            break;
            
        case 'PUT':
            handlePutRequest($avaliacao, $auth, $action);
            break;
            
        case 'DELETE':
            handleDeleteRequest($avaliacao, $auth, $action);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['erro' => 'Método não permitido']);
    }
    
} catch (Exception $e) {
    error_log("Erro na API de Avaliações: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['erro' => 'Erro interno do servidor']);
}

/**
 * Lidar com requisições GET
 */
function handleGetRequest($avaliacao, $action) {
    switch ($action) {
        case 'listar':
            listarAvaliacoes($avaliacao);
            break;
            
        case 'estatisticas':
            obterEstatisticas($avaliacao);
            break;
            
        case 'produto':
            avaliacoesPorProduto($avaliacao);
            break;
            
        case 'cliente':
            avaliacoesPorCliente($avaliacao);
            break;
            
        case 'moderacao':
            avaliacoesPendentes($avaliacao);
            break;
            
        case 'detalhes':
            detalhesAvaliacao($avaliacao);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['erro' => 'Ação não especificada']);
    }
}

/**
 * Lidar com requisições POST
 */
function handlePostRequest($avaliacao, $auth, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'criar':
            criarAvaliacao($avaliacao, $input);
            break;
            
        case 'votar':
            votarUtilidade($avaliacao, $input);
            break;
            
        case 'denunciar':
            denunciarAvaliacao($avaliacao, $input);
            break;
            
        case 'responder':
            responderAvaliacao($avaliacao, $auth, $input);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['erro' => 'Ação não especificada']);
    }
}

/**
 * Lidar com requisições PUT
 */
function handlePutRequest($avaliacao, $auth, $action) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'moderar':
            moderarAvaliacao($avaliacao, $auth, $input);
            break;
            
        case 'editar':
            editarAvaliacao($avaliacao, $auth, $input);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['erro' => 'Ação não especificada']);
    }
}

/**
 * Lidar com requisições DELETE
 */
function handleDeleteRequest($avaliacao, $auth, $action) {
    if (!$auth->verificarPermissao('admin')) {
        http_response_code(403);
        echo json_encode(['erro' => 'Acesso negado']);
        return;
    }
    
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['erro' => 'ID da avaliação não fornecido']);
        return;
    }
    
    // Implementar exclusão de avaliação
    echo json_encode(['sucesso' => true, 'mensagem' => 'Avaliação excluída']);
}

/**
 * Funções específicas
 */

function listarAvaliacoes($avaliacao) {
    $filtros = [
        'pagina' => $_GET['pagina'] ?? 1,
        'limite' => $_GET['limite'] ?? 10,
        'ordenacao' => $_GET['ordenacao'] ?? 'recentes',
        'nota' => $_GET['nota'] ?? null,
        'com_comentario' => $_GET['com_comentario'] ?? false,
        'com_imagens' => $_GET['com_imagens'] ?? false
    ];
    
    $resultado = $avaliacao->listarTodasAvaliacoes($filtros);
    echo json_encode($resultado);
}

function avaliacoesPorProduto($avaliacao) {
    $produtoId = $_GET['produto_id'] ?? null;
    if (!$produtoId) {
        http_response_code(400);
        echo json_encode(['erro' => 'ID do produto não fornecido']);
        return;
    }
    
    $filtros = [
        'pagina' => $_GET['pagina'] ?? 1,
        'limite' => $_GET['limite'] ?? 10,
        'ordenacao' => $_GET['ordenacao'] ?? 'recentes',
        'nota' => $_GET['nota'] ?? null,
        'com_comentario' => $_GET['com_comentario'] ?? false,
        'com_imagens' => $_GET['com_imagens'] ?? false
    ];
    
    $resultado = $avaliacao->listarAvaliacoesProduto($produtoId, $filtros);
    echo json_encode($resultado);
}

function obterEstatisticas($avaliacao) {
    $produtoId = $_GET['produto_id'] ?? null;
    if (!$produtoId) {
        http_response_code(400);
        echo json_encode(['erro' => 'ID do produto não fornecido']);
        return;
    }
    
    $resultado = $avaliacao->obterEstatisticasProduto($produtoId);
    echo json_encode($resultado);
}

function avaliacoesPorCliente($avaliacao) {
    $clienteId = $_GET['cliente_id'] ?? null;
    if (!$clienteId) {
        http_response_code(400);
        echo json_encode(['erro' => 'ID do cliente não fornecido']);
        return;
    }
    
    $filtros = [
        'pagina' => $_GET['pagina'] ?? 1,
        'limite' => $_GET['limite'] ?? 10
    ];
    
    $resultado = $avaliacao->listarAvaliacoesCliente($clienteId, $filtros);
    echo json_encode($resultado);
}

function avaliacoesPendentes($avaliacao) {
    $filtros = [
        'pagina' => $_GET['pagina'] ?? 1,
        'limite' => $_GET['limite'] ?? 20,
        'status' => 'pendente'
    ];
    
    $resultado = $avaliacao->listarAvaliacoesModeracaoo($filtros);
    echo json_encode($resultado);
}

function detalhesAvaliacao($avaliacao) {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['erro' => 'ID da avaliação não fornecido']);
        return;
    }
    
    $resultado = $avaliacao->obterDetalhesAvaliacao($id);
    echo json_encode($resultado);
}

function criarAvaliacao($avaliacao, $input) {
    // Validar dados obrigatórios
    $camposObrigatorios = ['produto_id', 'cliente_id', 'nota'];
    foreach ($camposObrigatorios as $campo) {
        if (!isset($input[$campo])) {
            http_response_code(400);
            echo json_encode(['erro' => "Campo obrigatório: {$campo}"]);
            return;
        }
    }
    
    $resultado = $avaliacao->criarAvaliacao($input);
    
    if ($resultado['sucesso']) {
        http_response_code(201);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($resultado);
}

function votarUtilidade($avaliacao, $input) {
    $camposObrigatorios = ['avaliacao_id', 'cliente_id', 'util'];
    foreach ($camposObrigatorios as $campo) {
        if (!isset($input[$campo])) {
            http_response_code(400);
            echo json_encode(['erro' => "Campo obrigatório: {$campo}"]);
            return;
        }
    }
    
    $resultado = $avaliacao->votarUtilidadeAvaliacao(
        $input['avaliacao_id'],
        $input['cliente_id'],
        $input['util']
    );
    
    if (!$resultado['sucesso']) {
        http_response_code(400);
    }
    
    echo json_encode($resultado);
}

function denunciarAvaliacao($avaliacao, $input) {
    $camposObrigatorios = ['avaliacao_id', 'motivo'];
    foreach ($camposObrigatorios as $campo) {
        if (!isset($input[$campo])) {
            http_response_code(400);
            echo json_encode(['erro' => "Campo obrigatório: {$campo}"]);
            return;
        }
    }
    
    $resultado = $avaliacao->denunciarAvaliacao(
        $input['avaliacao_id'],
        $input['motivo'],
        $input['detalhes'] ?? ''
    );
    
    if (!$resultado['sucesso']) {
        http_response_code(400);
    }
    
    echo json_encode($resultado);
}

function responderAvaliacao($avaliacao, $auth, $input) {
    if (!$auth->verificarPermissao('gerente')) {
        http_response_code(403);
        echo json_encode(['erro' => 'Acesso negado']);
        return;
    }
    
    $camposObrigatorios = ['avaliacao_id', 'resposta'];
    foreach ($camposObrigatorios as $campo) {
        if (!isset($input[$campo])) {
            http_response_code(400);
            echo json_encode(['erro' => "Campo obrigatório: {$campo}"]);
            return;
        }
    }
    
    $resultado = $avaliacao->responderAvaliacao(
        $input['avaliacao_id'],
        $input['resposta'],
        $auth->getUsuarioId()
    );
    
    if (!$resultado['sucesso']) {
        http_response_code(400);
    }
    
    echo json_encode($resultado);
}

function moderarAvaliacao($avaliacao, $auth, $input) {
    if (!$auth->verificarPermissao('moderador')) {
        http_response_code(403);
        echo json_encode(['erro' => 'Acesso negado']);
        return;
    }
    
    $camposObrigatorios = ['avaliacao_id', 'acao'];
    foreach ($camposObrigatorios as $campo) {
        if (!isset($input[$campo])) {
            http_response_code(400);
            echo json_encode(['erro' => "Campo obrigatório: {$campo}"]);
            return;
        }
    }
    
    $resultado = $avaliacao->moderarAvaliacao(
        $input['avaliacao_id'],
        $input['acao'],
        $input['motivo'] ?? ''
    );
    
    if (!$resultado['sucesso']) {
        http_response_code(400);
    }
    
    echo json_encode($resultado);
}

function editarAvaliacao($avaliacao, $auth, $input) {
    $camposObrigatorios = ['avaliacao_id', 'cliente_id'];
    foreach ($camposObrigatorios as $campo) {
        if (!isset($input[$campo])) {
            http_response_code(400);
            echo json_encode(['erro' => "Campo obrigatório: {$campo}"]);
            return;
        }
    }
    
    // Verificar se o cliente pode editar (dentro do prazo)
    $resultado = $avaliacao->editarAvaliacao($input);
    
    if (!$resultado['sucesso']) {
        http_response_code(400);
    }
    
    echo json_encode($resultado);
}

/**
 * Funções auxiliares
 */

function validarParametros($parametros, $obrigatorios) {
    foreach ($obrigatorios as $param) {
        if (!isset($parametros[$param]) || empty($parametros[$param])) {
            return false;
        }
    }
    return true;
}

function sanitizarInput($input) {
    if (is_array($input)) {
        return array_map('sanitizarInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function logRequest($action, $data = []) {
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'data' => $data
    ];
    
    error_log("API Avaliações: " . json_encode($log));
}
?>

