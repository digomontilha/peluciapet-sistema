<?php
/**
 * API de Upload de Imagens - Sistema PelúciaPet v2.1
 */

require_once '../classes/Auth.php';
require_once '../classes/Database.php';
require_once '../classes/ImageUpload.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Verificar autenticação
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Acesso negado. Faça login primeiro.'
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$imageUpload = new ImageUpload();

try {
    switch ($method) {
        case 'POST':
            handleUpload($imageUpload);
            break;
            
        case 'GET':
            handleGetImages($imageUpload);
            break;
            
        case 'DELETE':
            handleDeleteImage($imageUpload);
            break;
            
        case 'PUT':
            handleReorderImages($imageUpload);
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
 * Fazer upload de imagens
 */
function handleUpload($imageUpload) {
    if (!isset($_FILES['images']) || !isset($_POST['produto_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Dados incompletos. Envie as imagens e o ID do produto.'
        ]);
        return;
    }
    
    $produtoId = (int)$_POST['produto_id'];
    
    if ($produtoId <= 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID do produto inválido'
        ]);
        return;
    }
    
    // Verificar se o produto existe
    $db = Database::getInstance();
    $produto = $db->fetch("SELECT id FROM produtos WHERE id = ?", [$produtoId]);
    
    if (!$produto) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Produto não encontrado'
        ]);
        return;
    }
    
    // Fazer upload
    $result = $imageUpload->uploadMultiple($_FILES['images'], $produtoId);
    
    if ($result['success']) {
        http_response_code(201);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * Listar imagens de um produto
 */
function handleGetImages($imageUpload) {
    if (!isset($_GET['produto_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID do produto é obrigatório'
        ]);
        return;
    }
    
    $produtoId = (int)$_GET['produto_id'];
    $images = $imageUpload->getProductImages($produtoId);
    
    echo json_encode([
        'success' => true,
        'message' => 'Imagens carregadas com sucesso',
        'data' => $images
    ]);
}

/**
 * Excluir imagem
 */
function handleDeleteImage($imageUpload) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['image_id'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID da imagem é obrigatório'
        ]);
        return;
    }
    
    $imageId = (int)$input['image_id'];
    $result = $imageUpload->deleteImage($imageId);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * Reordenar imagens
 */
function handleReorderImages($imageUpload) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['produto_id']) || !isset($input['orders'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Dados incompletos para reordenação'
        ]);
        return;
    }
    
    $produtoId = (int)$input['produto_id'];
    $orders = $input['orders'];
    
    $result = $imageUpload->reorderImages($produtoId, $orders);
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}
?>

