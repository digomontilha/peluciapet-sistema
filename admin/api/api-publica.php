<?php
/**
 * API Pública - Sistema PelúciaPet
 * Endpoints públicos para consumo do frontend do site
 */

// Headers CORS e segurança
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Cache-Control: public, max-age=300'); // Cache de 5 minutos

// Responder a requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Apenas métodos GET são permitidos na API pública
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Método não permitido. Apenas GET é aceito.'
    ]);
    exit;
}

// Incluir dependências
require_once '../config/config.php';
require_once '../classes/Database.php';
require_once '../classes/Produto.php';

try {
    // Inicializar componentes
    $produto = new Produto();
    
    // Determinar ação
    $action = $_GET['action'] ?? 'produtos';
    
    // Rate limiting para API pública
    checkPublicRateLimit();
    
    // Processar requisição baseada na ação
    switch ($action) {
        case 'produtos':
            handleListarProdutos($produto);
            break;
            
        case 'produto':
            handleObterProduto($produto);
            break;
            
        case 'categorias':
            handleCategorias();
            break;
            
        case 'destaque':
            handleProdutosDestaque($produto);
            break;
            
        case 'buscar':
            handleBuscarProdutos($produto);
            break;
            
        case 'estatisticas':
            handleEstatisticasPublicas($produto);
            break;
            
        case 'status':
            handleStatus();
            break;
            
        default:
            throw new Exception('Ação não reconhecida: ' . $action);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor',
        'message' => isDevelopment() ? $e->getMessage() : 'Erro temporário. Tente novamente.',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    // Log do erro
    logEvent('ERROR', 'Public API Error: ' . $e->getMessage(), [
        'action' => $action ?? 'unknown',
        'ip' => getClientIP(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);
}

/**
 * Listar produtos para o frontend
 */
function handleListarProdutos($produto) {
    try {
        $filtros = [
            'categoria_id' => $_GET['categoria_id'] ?? null,
            'destaque' => isset($_GET['destaque']) ? (bool)$_GET['destaque'] : null,
            'limite' => min((int)($_GET['limite'] ?? 20), 50), // Máximo 50 produtos
            'offset' => max(0, (int)($_GET['offset'] ?? 0)),
            'ordem' => $_GET['ordem'] ?? 'destaque'
        ];
        
        $produtos = $produto->listar($filtros);
        
        // Formatar dados para o frontend
        $produtosFormatados = array_map('formatarProdutoParaFrontend', $produtos);
        
        echo json_encode([
            'success' => true,
            'produtos' => $produtosFormatados,
            'total' => count($produtosFormatados),
            'filtros' => array_filter($filtros),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao carregar produtos',
            'error' => isDevelopment() ? $e->getMessage() : null
        ]);
    }
}

/**
 * Obter produto específico para o frontend
 */
function handleObterProduto($produto) {
    try {
        $id = $_GET['id'] ?? null;
        $slug = $_GET['slug'] ?? null;
        
        if (!$id && !$slug) {
            throw new Exception('ID ou slug do produto é obrigatório');
        }
        
        if ($slug) {
            // Buscar por slug
            $db = Database::getInstance();
            $produtoData = $db->fetch("SELECT * FROM produtos WHERE slug = ? AND ativo = 1", [$slug]);
            if ($produtoData) {
                $id = $produtoData['id'];
            }
        }
        
        if (!$id) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Produto não encontrado'
            ]);
            return;
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
        
        // Incrementar visualizações
        incrementarVisualizacoes($id);
        
        // Formatar dados para o frontend
        $produtoFormatado = formatarProdutoParaFrontend($produtoData, true);
        
        echo json_encode([
            'success' => true,
            'produto' => $produtoFormatado
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao carregar produto',
            'error' => isDevelopment() ? $e->getMessage() : null
        ]);
    }
}

/**
 * Listar categorias para o frontend
 */
function handleCategorias() {
    try {
        $db = Database::getInstance();
        $categorias = $db->fetchAll("
            SELECT 
                c.*,
                COUNT(p.id) as total_produtos
            FROM categorias c
            LEFT JOIN produtos p ON c.id = p.categoria_id AND p.ativo = 1
            WHERE c.ativo = 1
            GROUP BY c.id
            ORDER BY c.ordem ASC, c.nome ASC
        ");
        
        echo json_encode([
            'success' => true,
            'categorias' => $categorias
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao carregar categorias',
            'error' => isDevelopment() ? $e->getMessage() : null
        ]);
    }
}

/**
 * Listar produtos em destaque
 */
function handleProdutosDestaque($produto) {
    try {
        $limite = min((int)($_GET['limite'] ?? 8), 20);
        
        $filtros = [
            'destaque' => true,
            'limite' => $limite,
            'ordem' => 'created_at_desc'
        ];
        
        $produtos = $produto->listar($filtros);
        $produtosFormatados = array_map('formatarProdutoParaFrontend', $produtos);
        
        echo json_encode([
            'success' => true,
            'produtos' => $produtosFormatados,
            'total' => count($produtosFormatados)
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao carregar produtos em destaque',
            'error' => isDevelopment() ? $e->getMessage() : null
        ]);
    }
}

/**
 * Buscar produtos
 */
function handleBuscarProdutos($produto) {
    try {
        $busca = trim($_GET['q'] ?? $_GET['busca'] ?? '');
        
        if (strlen($busca) < 2) {
            throw new Exception('Termo de busca deve ter pelo menos 2 caracteres');
        }
        
        $filtros = [
            'busca' => $busca,
            'limite' => min((int)($_GET['limite'] ?? 20), 50),
            'offset' => max(0, (int)($_GET['offset'] ?? 0)),
            'categoria_id' => $_GET['categoria_id'] ?? null
        ];
        
        $produtos = $produto->listar($filtros);
        $produtosFormatados = array_map('formatarProdutoParaFrontend', $produtos);
        
        echo json_encode([
            'success' => true,
            'produtos' => $produtosFormatados,
            'total' => count($produtosFormatados),
            'termo_busca' => $busca
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro na busca',
            'error' => isDevelopment() ? $e->getMessage() : null
        ]);
    }
}

/**
 * Estatísticas públicas (limitadas)
 */
function handleEstatisticasPublicas($produto) {
    try {
        $db = Database::getInstance();
        
        $stats = [
            'total_produtos' => $db->countRecords('produtos', 'ativo = 1'),
            'total_categorias' => $db->countRecords('categorias', 'ativo = 1'),
            'produtos_destaque' => $db->countRecords('produtos', 'ativo = 1 AND destaque = 1'),
            'ultima_atualizacao' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode([
            'success' => true,
            'estatisticas' => $stats
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erro ao carregar estatísticas',
            'error' => isDevelopment() ? $e->getMessage() : null
        ]);
    }
}

/**
 * Status da API
 */
function handleStatus() {
    try {
        $db = Database::getInstance();
        $isConnected = $db->isConnected();
        
        echo json_encode([
            'success' => true,
            'status' => 'online',
            'database' => $isConnected ? 'connected' : 'disconnected',
            'version' => APP_VERSION,
            'timestamp' => date('Y-m-d H:i:s'),
            'uptime' => getUptime()
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'status' => 'error',
            'message' => 'Erro no status da API'
        ]);
    }
}

/**
 * Formatar produto para o frontend
 */
function formatarProdutoParaFrontend($produto, $incluirDetalhes = false) {
    $produtoFormatado = [
        'id' => (int)$produto['id'],
        'nome' => $produto['nome'],
        'slug' => $produto['slug'],
        'descricao' => $produto['descricao'],
        'categoria' => $produto['categoria_nome'] ?? null,
        'categoria_slug' => $produto['categoria_slug'] ?? null,
        'preco_base' => (float)$produto['preco_base'],
        'preco_promocional' => $produto['preco_promocional'] ? (float)$produto['preco_promocional'] : null,
        'material' => $produto['material'],
        'destaque' => (bool)$produto['destaque'],
        'imagem_principal' => $produto['imagem_principal'],
        'total_variacoes' => (int)($produto['total_variacoes'] ?? 0),
        'estoque_total' => (int)($produto['estoque_total'] ?? 0),
        'disponivel' => (int)($produto['estoque_total'] ?? 0) > 0,
        'url_whatsapp' => gerarUrlWhatsApp($produto)
    ];
    
    // Incluir detalhes completos se solicitado
    if ($incluirDetalhes) {
        $produtoFormatado = array_merge($produtoFormatado, [
            'peso' => $produto['peso'],
            'dimensoes' => $produto['dimensoes'],
            'cuidados' => $produto['cuidados'],
            'views' => (int)($produto['views'] ?? 0),
            'variacoes' => $produto['variacoes'] ?? [],
            'imagens' => $produto['imagens'] ?? [],
            'meta_title' => $produto['meta_title'],
            'meta_description' => $produto['meta_description']
        ]);
        
        // Formatar variações
        if (!empty($produto['variacoes'])) {
            $produtoFormatado['variacoes'] = array_map(function($variacao) {
                return [
                    'id' => (int)$variacao['id'],
                    'tamanho' => [
                        'id' => (int)$variacao['tamanho_id'],
                        'nome' => $variacao['tamanho_nome'],
                        'dimensoes' => $variacao['tamanho_dimensoes'],
                        'peso_recomendado' => $variacao['tamanho_peso']
                    ],
                    'cor' => [
                        'id' => (int)$variacao['cor_id'],
                        'nome' => $variacao['cor_nome'],
                        'codigo' => $variacao['cor_codigo']
                    ],
                    'sku' => $variacao['sku'],
                    'preco_adicional' => (float)$variacao['preco_adicional'],
                    'estoque_atual' => (int)$variacao['estoque_atual'],
                    'disponivel' => (int)$variacao['estoque_atual'] > 0
                ];
            }, $produto['variacoes']);
        }
    }
    
    return $produtoFormatado;
}

/**
 * Gerar URL do WhatsApp para o produto
 */
function gerarUrlWhatsApp($produto) {
    $numero = WHATSAPP_NUMBER;
    $mensagem = WHATSAPP_MESSAGE_TEMPLATE . "\n\n";
    $mensagem .= "🐾 *" . $produto['nome'] . "*\n";
    $mensagem .= "💰 Preço: R$ " . number_format($produto['preco_base'], 2, ',', '.') . "\n";
    $mensagem .= "🔗 " . BASE_URL . "/produto/" . $produto['slug'];
    
    return "https://wa.me/" . $numero . "?text=" . urlencode($mensagem);
}

/**
 * Incrementar visualizações do produto
 */
function incrementarVisualizacoes($produtoId) {
    try {
        $db = Database::getInstance();
        $db->execute("UPDATE produtos SET views = views + 1 WHERE id = ?", [$produtoId]);
    } catch (Exception $e) {
        // Ignorar erro silenciosamente
    }
}

/**
 * Rate limiting para API pública
 */
function checkPublicRateLimit() {
    $ip = getClientIP();
    $cacheFile = sys_get_temp_dir() . '/peluciapet_public_rate_' . md5($ip);
    
    $requests = [];
    if (file_exists($cacheFile)) {
        $requests = json_decode(file_get_contents($cacheFile), true) ?: [];
    }
    
    // Remover requisições antigas (últimos 5 minutos)
    $now = time();
    $requests = array_filter($requests, function($timestamp) use ($now) {
        return ($now - $timestamp) < 300;
    });
    
    // Verificar limite (60 requisições por 5 minutos para IP público)
    if (count($requests) >= 60) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'error' => 'Muitas requisições. Tente novamente em alguns minutos.',
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

/**
 * Obter uptime do servidor (aproximado)
 */
function getUptime() {
    if (function_exists('sys_getloadavg')) {
        $load = sys_getloadavg();
        return [
            'load_average' => $load,
            'timestamp' => time()
        ];
    }
    
    return null;
}
?>

