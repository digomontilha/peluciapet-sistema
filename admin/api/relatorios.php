<?php
/**
 * API de Relatórios - Sistema PelúciaPet v2.1
 */

require_once '../classes/Auth.php';
require_once '../classes/Database.php';
require_once '../classes/RelatorioVendas.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
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
$relatorio = new RelatorioVendas();

try {
    switch ($method) {
        case 'GET':
            handleGet($relatorio);
            break;
            
        case 'POST':
            handlePost($relatorio);
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
 * Listar relatórios
 */
function handleGet($relatorio) {
    $action = $_GET['action'] ?? 'dashboard';
    
    switch ($action) {
        case 'dashboard':
            $periodo = $_GET['periodo'] ?? '30d';
            $result = $relatorio->obterDashboard($periodo);
            break;
            
        case 'detalhado':
            $filtros = [];
            
            // Aplicar filtros
            if (!empty($_GET['data_inicio'])) {
                $filtros['data_inicio'] = $_GET['data_inicio'];
            }
            
            if (!empty($_GET['data_fim'])) {
                $filtros['data_fim'] = $_GET['data_fim'];
            }
            
            if (!empty($_GET['status'])) {
                $filtros['status'] = $_GET['status'];
            }
            
            if (!empty($_GET['categoria_id'])) {
                $filtros['categoria_id'] = (int)$_GET['categoria_id'];
            }
            
            if (!empty($_GET['produto_id'])) {
                $filtros['produto_id'] = (int)$_GET['produto_id'];
            }
            
            if (!empty($_GET['cliente_id'])) {
                $filtros['cliente_id'] = (int)$_GET['cliente_id'];
            }
            
            if (!empty($_GET['metodo_pagamento'])) {
                $filtros['metodo_pagamento'] = $_GET['metodo_pagamento'];
            }
            
            $result = $relatorio->getRelatorioDetalhado($filtros);
            break;
            
        case 'clientes':
            $periodo = $_GET['periodo'] ?? '12m';
            $result = $relatorio->getAnaliseClientes($periodo);
            break;
            
        case 'previsao':
            $meses = isset($_GET['meses']) ? (int)$_GET['meses'] : 3;
            $result = $relatorio->getPrevisaoVendas($meses);
            break;
            
        case 'export':
            handleExport($relatorio);
            return;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Ação não reconhecida'
            ]);
            return;
    }
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * Gerar relatórios personalizados
 */
function handlePost($relatorio) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['tipo'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Tipo de relatório é obrigatório'
        ]);
        return;
    }
    
    $tipo = $input['tipo'];
    $filtros = $input['filtros'] ?? [];
    
    switch ($tipo) {
        case 'dashboard_personalizado':
            $periodo = $filtros['periodo'] ?? '30d';
            $result = $relatorio->obterDashboard($periodo);
            break;
            
        case 'vendas_detalhado':
            $result = $relatorio->getRelatorioDetalhado($filtros);
            break;
            
        case 'analise_clientes':
            $periodo = $filtros['periodo'] ?? '12m';
            $result = $relatorio->getAnaliseClientes($periodo);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Tipo de relatório não suportado'
            ]);
            return;
    }
    
    if ($result['success']) {
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * Exportar relatórios
 */
function handleExport($relatorio) {
    $tipo = $_GET['tipo'] ?? '';
    $formato = $_GET['formato'] ?? 'csv';
    
    if (empty($tipo)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Tipo de exportação é obrigatório'
        ]);
        return;
    }
    
    if ($formato !== 'csv') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Formato não suportado. Use: csv'
        ]);
        return;
    }
    
    // Preparar filtros
    $filtros = [];
    if (!empty($_GET['data_inicio'])) {
        $filtros['data_inicio'] = $_GET['data_inicio'];
    }
    if (!empty($_GET['data_fim'])) {
        $filtros['data_fim'] = $_GET['data_fim'];
    }
    if (!empty($_GET['status'])) {
        $filtros['status'] = $_GET['status'];
    }
    if (!empty($_GET['categoria_id'])) {
        $filtros['categoria_id'] = (int)$_GET['categoria_id'];
    }
    
    $result = $relatorio->exportarCSV($tipo, $filtros);
    
    if (!$result['success']) {
        http_response_code(400);
        echo json_encode($result);
        return;
    }
    
    // Configurar headers para download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $result['filename'] . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Adicionar BOM para UTF-8
    echo "\xEF\xBB\xBF";
    echo $result['data'];
}
?>

