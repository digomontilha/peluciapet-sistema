<?php
/**
 * API de Frete - Sistema PelúciaPet v2.1
 * Integração com Correios para cálculo de frete
 */

require_once '../classes/Database.php';
require_once '../classes/CorreiosAPI.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

$method = $_SERVER['REQUEST_METHOD'];
$correios = new CorreiosAPI();

try {
    switch ($method) {
        case 'GET':
            handleGet($correios);
            break;
            
        case 'POST':
            handlePost($correios);
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
function handleGet($correios) {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'consultar_cep':
            if (empty($_GET['cep'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'CEP é obrigatório'
                ]);
                return;
            }
            
            $result = $correios->consultarCep($_GET['cep']);
            break;
            
        case 'rastrear':
            if (empty($_GET['codigo'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Código de rastreamento é obrigatório'
                ]);
                return;
            }
            
            $result = $correios->rastrearEncomenda($_GET['codigo']);
            break;
            
        case 'configuracoes':
            $result = [
                'success' => true,
                'data' => $correios->obterConfiguracoes()
            ];
            break;
            
        case 'validar':
            $result = $correios->validarConfiguracao();
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
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * Cálculos POST
 */
function handlePost($correios) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Dados inválidos'
        ]);
        return;
    }
    
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'calcular_produto':
            $result = handleCalcularProduto($correios, $input);
            break;
            
        case 'calcular_carrinho':
            $result = handleCalcularCarrinho($correios, $input);
            break;
            
        case 'configurar':
            $result = handleConfigurar($correios, $input);
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
        http_response_code(200);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($result);
}

/**
 * Calcular frete para um produto
 */
function handleCalcularProduto($correios, $input) {
    // Validar dados obrigatórios
    $required = ['cep_destino', 'peso', 'comprimento', 'altura', 'largura'];
    foreach ($required as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            return [
                'success' => false,
                'message' => "Campo '{$field}' é obrigatório"
            ];
        }
    }
    
    $cepDestino = $input['cep_destino'];
    $peso = (float)$input['peso'];
    $comprimento = (float)$input['comprimento'];
    $altura = (float)$input['altura'];
    $largura = (float)$input['largura'];
    $valorDeclarado = (float)($input['valor_declarado'] ?? 0);
    $servicos = $input['servicos'] ?? null;
    
    // Validar dimensões mínimas
    if ($peso <= 0) {
        return [
            'success' => false,
            'message' => 'Peso deve ser maior que zero'
        ];
    }
    
    if ($comprimento < 16 || $altura < 2 || $largura < 11) {
        return [
            'success' => false,
            'message' => 'Dimensões mínimas: 16x2x11 cm'
        ];
    }
    
    return $correios->calcularFrete(
        $cepDestino,
        $peso,
        $comprimento,
        $altura,
        $largura,
        $valorDeclarado,
        $servicos
    );
}

/**
 * Calcular frete para carrinho
 */
function handleCalcularCarrinho($correios, $input) {
    if (!isset($input['cep_destino']) || empty($input['cep_destino'])) {
        return [
            'success' => false,
            'message' => 'CEP de destino é obrigatório'
        ];
    }
    
    if (!isset($input['itens']) || !is_array($input['itens']) || empty($input['itens'])) {
        return [
            'success' => false,
            'message' => 'Lista de itens é obrigatória'
        ];
    }
    
    $cepDestino = $input['cep_destino'];
    $itens = $input['itens'];
    $servicos = $input['servicos'] ?? null;
    
    // Buscar dados dos produtos no banco
    $itensCompletos = [];
    $db = Database::getInstance();
    
    foreach ($itens as $item) {
        if (!isset($item['produto_id']) || !isset($item['quantidade'])) {
            return [
                'success' => false,
                'message' => 'Cada item deve ter produto_id e quantidade'
            ];
        }
        
        $produtoId = (int)$item['produto_id'];
        $quantidade = (int)$item['quantidade'];
        
        if ($quantidade <= 0) {
            continue;
        }
        
        // Buscar dados do produto
        $produto = $db->fetch(
            "SELECT nome, preco, peso, comprimento, altura, largura FROM produtos WHERE id = ? AND ativo = 1",
            [$produtoId]
        );
        
        if (!$produto) {
            return [
                'success' => false,
                'message' => "Produto ID {$produtoId} não encontrado"
            ];
        }
        
        // Verificar se produto tem dimensões
        if (empty($produto['peso']) || empty($produto['comprimento']) || 
            empty($produto['altura']) || empty($produto['largura'])) {
            return [
                'success' => false,
                'message' => "Produto '{$produto['nome']}' não tem dimensões configuradas"
            ];
        }
        
        $itensCompletos[] = [
            'produto_id' => $produtoId,
            'nome' => $produto['nome'],
            'quantidade' => $quantidade,
            'peso' => (float)$produto['peso'],
            'comprimento' => (float)$produto['comprimento'],
            'altura' => (float)$produto['altura'],
            'largura' => (float)$produto['largura'],
            'valor' => (float)$produto['preco']
        ];
    }
    
    if (empty($itensCompletos)) {
        return [
            'success' => false,
            'message' => 'Nenhum item válido encontrado'
        ];
    }
    
    return $correios->calcularFreteCarrinho($cepDestino, $itensCompletos, $servicos);
}

/**
 * Configurar credenciais dos Correios
 */
function handleConfigurar($correios, $input) {
    // Verificar autenticação admin
    require_once '../classes/Auth.php';
    $auth = new Auth();
    
    if (!$auth->isLoggedIn() || !$auth->hasPermission('admin')) {
        return [
            'success' => false,
            'message' => 'Acesso negado. Apenas administradores podem configurar.'
        ];
    }
    
    $usuario = $input['usuario'] ?? '';
    $senha = $input['senha'] ?? '';
    $codigoEmpresa = $input['codigo_empresa'] ?? '';
    $cepOrigem = $input['cep_origem'] ?? '';
    
    if (empty($usuario) || empty($senha)) {
        return [
            'success' => false,
            'message' => 'Usuário e senha são obrigatórios'
        ];
    }
    
    // Configurar e testar
    $correios->configurar($usuario, $senha, $codigoEmpresa, $cepOrigem);
    
    // Validar configuração
    $teste = $correios->validarConfiguracao();
    
    if (!$teste['success']) {
        return [
            'success' => false,
            'message' => 'Configuração inválida: ' . $teste['message']
        ];
    }
    
    // Salvar configurações no banco (implementar conforme necessário)
    try {
        $db = Database::getInstance();
        
        // Verificar se já existe configuração
        $config = $db->fetch("SELECT id FROM configuracoes WHERE chave = 'correios_config'");
        
        $dadosConfig = json_encode([
            'usuario' => $usuario,
            'senha' => $senha,
            'codigo_empresa' => $codigoEmpresa,
            'cep_origem' => $cepOrigem,
            'data_atualizacao' => date('Y-m-d H:i:s')
        ]);
        
        if ($config) {
            $db->execute(
                "UPDATE configuracoes SET valor = ?, data_atualizacao = ? WHERE chave = 'correios_config'",
                [$dadosConfig, date('Y-m-d H:i:s')]
            );
        } else {
            $db->execute(
                "INSERT INTO configuracoes (chave, valor, data_criacao, data_atualizacao) VALUES (?, ?, ?, ?)",
                ['correios_config', $dadosConfig, date('Y-m-d H:i:s'), date('Y-m-d H:i:s')]
            );
        }
        
        return [
            'success' => true,
            'message' => 'Configuração salva com sucesso'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Erro ao salvar configuração: ' . $e->getMessage()
        ];
    }
}
?>

