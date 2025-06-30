<?php
/**
 * Classe Marketplace - Integração com Marketplaces v2.2
 * Gerencia integração com Mercado Livre, Amazon, Shopee, etc.
 */

class Marketplace {
    private $db;
    private $config;
    private $apis;
    
    public function __construct() {
        $this->db = new Database();
        $this->config = [
            'mercadolivre' => [
                'app_id' => '',
                'client_secret' => '',
                'redirect_uri' => '',
                'sandbox' => true,
                'base_url' => 'https://api.mercadolibre.com'
            ],
            'amazon' => [
                'access_key' => '',
                'secret_key' => '',
                'marketplace_id' => '',
                'region' => 'us-east-1',
                'base_url' => 'https://sellingpartnerapi-na.amazon.com'
            ],
            'shopee' => [
                'partner_id' => '',
                'partner_key' => '',
                'shop_id' => '',
                'base_url' => 'https://partner.shopeemobile.com'
            ]
        ];
        
        $this->apis = [
            'mercadolivre' => new MercadoLivreAPI($this->config['mercadolivre']),
            'amazon' => new AmazonAPI($this->config['amazon']),
            'shopee' => new ShopeeAPI($this->config['shopee'])
        ];
    }
    
    /**
     * Sincronizar produto com marketplaces
     */
    public function sincronizarProduto($produtoId, $marketplaces = []) {
        try {
            $produto = $this->buscarProduto($produtoId);
            if (!$produto) {
                return ['sucesso' => false, 'erro' => 'Produto não encontrado'];
            }
            
            $resultados = [];
            
            foreach ($marketplaces as $marketplace) {
                if (!isset($this->apis[$marketplace])) {
                    $resultados[$marketplace] = ['sucesso' => false, 'erro' => 'Marketplace não suportado'];
                    continue;
                }
                
                $api = $this->apis[$marketplace];
                
                // Verificar se produto já existe no marketplace
                $produtoMarketplace = $this->buscarProdutoMarketplace($produtoId, $marketplace);
                
                if ($produtoMarketplace) {
                    // Atualizar produto existente
                    $resultado = $this->atualizarProdutoMarketplace($api, $produto, $produtoMarketplace);
                } else {
                    // Criar novo produto
                    $resultado = $this->criarProdutoMarketplace($api, $produto, $marketplace);
                }
                
                $resultados[$marketplace] = $resultado;
                
                // Salvar resultado no banco
                $this->salvarResultadoSincronizacao($produtoId, $marketplace, $resultado);
            }
            
            return [
                'sucesso' => true,
                'resultados' => $resultados
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao sincronizar produto: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro interno do servidor'];
        }
    }
    
    /**
     * Sincronizar estoque
     */
    public function sincronizarEstoque($produtoId, $novoEstoque) {
        try {
            $produtosMarketplace = $this->buscarProdutosMarketplace($produtoId);
            $resultados = [];
            
            foreach ($produtosMarketplace as $produtoMp) {
                $api = $this->apis[$produtoMp['marketplace']];
                $resultado = $api->atualizarEstoque($produtoMp['id_externo'], $novoEstoque);
                
                $resultados[$produtoMp['marketplace']] = $resultado;
                
                if ($resultado['sucesso']) {
                    // Atualizar estoque local
                    $this->atualizarEstoqueLocal($produtoMp['id'], $novoEstoque);
                }
            }
            
            return [
                'sucesso' => true,
                'resultados' => $resultados
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao sincronizar estoque: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro ao sincronizar estoque'];
        }
    }
    
    /**
     * Sincronizar preços
     */
    public function sincronizarPrecos($produtoId, $novoPreco) {
        try {
            $produtosMarketplace = $this->buscarProdutosMarketplace($produtoId);
            $resultados = [];
            
            foreach ($produtosMarketplace as $produtoMp) {
                $api = $this->apis[$produtoMp['marketplace']];
                
                // Aplicar margem específica do marketplace se configurada
                $precoFinal = $this->calcularPrecoMarketplace($novoPreco, $produtoMp['marketplace']);
                
                $resultado = $api->atualizarPreco($produtoMp['id_externo'], $precoFinal);
                $resultados[$produtoMp['marketplace']] = $resultado;
                
                if ($resultado['sucesso']) {
                    // Atualizar preço local
                    $this->atualizarPrecoLocal($produtoMp['id'], $precoFinal);
                }
            }
            
            return [
                'sucesso' => true,
                'resultados' => $resultados
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao sincronizar preços: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro ao sincronizar preços'];
        }
    }
    
    /**
     * Importar pedidos dos marketplaces
     */
    public function importarPedidos($marketplace = null, $dataInicio = null) {
        try {
            $marketplaces = $marketplace ? [$marketplace] : array_keys($this->apis);
            $pedidosImportados = [];
            
            foreach ($marketplaces as $mp) {
                $api = $this->apis[$mp];
                $pedidos = $api->buscarPedidos($dataInicio);
                
                foreach ($pedidos as $pedidoExterno) {
                    // Verificar se pedido já foi importado
                    if ($this->pedidoJaImportado($pedidoExterno['id'], $mp)) {
                        continue;
                    }
                    
                    // Converter pedido para formato interno
                    $pedidoInterno = $this->converterPedidoMarketplace($pedidoExterno, $mp);
                    
                    // Criar pedido no sistema
                    $pedidoId = $this->criarPedidoInterno($pedidoInterno);
                    
                    if ($pedidoId) {
                        // Salvar referência do marketplace
                        $this->salvarReferenciaPedido($pedidoId, $pedidoExterno['id'], $mp);
                        $pedidosImportados[] = $pedidoId;
                    }
                }
            }
            
            return [
                'sucesso' => true,
                'pedidos_importados' => count($pedidosImportados),
                'pedidos' => $pedidosImportados
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao importar pedidos: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro ao importar pedidos'];
        }
    }
    
    /**
     * Atualizar status de pedido no marketplace
     */
    public function atualizarStatusPedido($pedidoId, $novoStatus) {
        try {
            $referenciasMarketplace = $this->buscarReferenciasMarketplace($pedidoId);
            $resultados = [];
            
            foreach ($referenciasMarketplace as $ref) {
                $api = $this->apis[$ref['marketplace']];
                $statusExterno = $this->converterStatusParaMarketplace($novoStatus, $ref['marketplace']);
                
                $resultado = $api->atualizarStatusPedido($ref['id_externo'], $statusExterno);
                $resultados[$ref['marketplace']] = $resultado;
            }
            
            return [
                'sucesso' => true,
                'resultados' => $resultados
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao atualizar status: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro ao atualizar status'];
        }
    }
    
    /**
     * Obter relatório de vendas por marketplace
     */
    public function obterRelatorioVendas($periodo = '30d') {
        try {
            $conn = $this->db->getConnection();
            
            $dataInicio = date('Y-m-d', strtotime("-{$periodo}"));
            
            $sql = "SELECT 
                pm.marketplace,
                COUNT(DISTINCT p.id) as total_pedidos,
                SUM(p.valor_total) as valor_total,
                AVG(p.valor_total) as ticket_medio,
                COUNT(DISTINCT pi.produto_id) as produtos_vendidos,
                SUM(pi.quantidade) as quantidade_total
            FROM pedidos p
            JOIN pedido_marketplace pm ON p.id = pm.pedido_id
            JOIN pedido_itens pi ON p.id = pi.pedido_id
            WHERE p.criado_em >= ?
            GROUP BY pm.marketplace
            ORDER BY valor_total DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$dataInicio]);
            $relatorio = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Adicionar dados de performance
            foreach ($relatorio as &$marketplace) {
                $marketplace['crescimento'] = $this->calcularCrescimento($marketplace['marketplace'], $periodo);
                $marketplace['produtos_mais_vendidos'] = $this->obterProdutosMaisVendidos($marketplace['marketplace'], 5);
            }
            
            return [
                'sucesso' => true,
                'periodo' => $periodo,
                'relatorio' => $relatorio
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao gerar relatório: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro ao gerar relatório'];
        }
    }
    
    /**
     * Configurar marketplace
     */
    public function configurarMarketplace($marketplace, $configuracoes) {
        try {
            $conn = $this->db->getConnection();
            
            // Validar configurações
            $validacao = $this->validarConfiguracoes($marketplace, $configuracoes);
            if (!$validacao['valido']) {
                return ['sucesso' => false, 'erro' => $validacao['erro']];
            }
            
            // Testar conexão com API
            $testeConexao = $this->testarConexaoAPI($marketplace, $configuracoes);
            if (!$testeConexao['sucesso']) {
                return ['sucesso' => false, 'erro' => 'Falha na conexão: ' . $testeConexao['erro']];
            }
            
            // Salvar configurações
            $sql = "INSERT INTO marketplace_config (marketplace, configuracoes, ativo) 
                    VALUES (?, ?, 1)
                    ON DUPLICATE KEY UPDATE 
                    configuracoes = VALUES(configuracoes),
                    ativo = VALUES(ativo),
                    atualizado_em = NOW()";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$marketplace, json_encode($configuracoes)]);
            
            // Atualizar configurações em memória
            $this->config[$marketplace] = array_merge($this->config[$marketplace], $configuracoes);
            
            return [
                'sucesso' => true,
                'mensagem' => 'Marketplace configurado com sucesso'
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao configurar marketplace: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro ao salvar configurações'];
        }
    }
    
    /**
     * Métodos auxiliares
     */
    
    private function buscarProduto($produtoId) {
        $conn = $this->db->getConnection();
        $sql = "SELECT * FROM produtos WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$produtoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function buscarProdutoMarketplace($produtoId, $marketplace) {
        $conn = $this->db->getConnection();
        $sql = "SELECT * FROM produto_marketplace WHERE produto_id = ? AND marketplace = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$produtoId, $marketplace]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function buscarProdutosMarketplace($produtoId) {
        $conn = $this->db->getConnection();
        $sql = "SELECT * FROM produto_marketplace WHERE produto_id = ? AND ativo = 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$produtoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function criarProdutoMarketplace($api, $produto, $marketplace) {
        // Preparar dados do produto para o marketplace
        $dadosProduto = $this->prepararDadosProduto($produto, $marketplace);
        
        // Criar produto via API
        $resultado = $api->criarProduto($dadosProduto);
        
        if ($resultado['sucesso']) {
            // Salvar referência no banco
            $this->salvarProdutoMarketplace($produto['id'], $marketplace, $resultado['id_externo']);
        }
        
        return $resultado;
    }
    
    private function atualizarProdutoMarketplace($api, $produto, $produtoMarketplace) {
        // Preparar dados atualizados
        $dadosProduto = $this->prepararDadosProduto($produto, $produtoMarketplace['marketplace']);
        
        // Atualizar via API
        return $api->atualizarProduto($produtoMarketplace['id_externo'], $dadosProduto);
    }
    
    private function prepararDadosProduto($produto, $marketplace) {
        $dados = [
            'titulo' => $produto['nome'],
            'descricao' => $produto['descricao'],
            'preco' => $this->calcularPrecoMarketplace($produto['preco'], $marketplace),
            'estoque' => $produto['estoque'],
            'categoria' => $this->mapearCategoria($produto['categoria_id'], $marketplace),
            'imagens' => $this->buscarImagensProduto($produto['id']),
            'atributos' => $this->prepararAtributos($produto, $marketplace)
        ];
        
        return $dados;
    }
    
    private function calcularPrecoMarketplace($precoBase, $marketplace) {
        // Aplicar margem específica do marketplace
        $margens = [
            'mercadolivre' => 1.15, // 15% de margem
            'amazon' => 1.20,       // 20% de margem
            'shopee' => 1.10        // 10% de margem
        ];
        
        $margem = $margens[$marketplace] ?? 1.0;
        return round($precoBase * $margem, 2);
    }
    
    private function mapearCategoria($categoriaId, $marketplace) {
        // Mapear categoria interna para categoria do marketplace
        $conn = $this->db->getConnection();
        $sql = "SELECT categoria_externa FROM categoria_marketplace 
                WHERE categoria_id = ? AND marketplace = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$categoriaId, $marketplace]);
        $resultado = $stmt->fetchColumn();
        
        return $resultado ?: 'MLB1071'; // Categoria padrão
    }
    
    private function buscarImagensProduto($produtoId) {
        $conn = $this->db->getConnection();
        $sql = "SELECT caminho FROM produto_imagens WHERE produto_id = ? ORDER BY ordem";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$produtoId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    private function prepararAtributos($produto, $marketplace) {
        // Preparar atributos específicos do marketplace
        $atributos = [];
        
        if (!empty($produto['peso'])) {
            $atributos['peso'] = $produto['peso'];
        }
        
        if (!empty($produto['comprimento'])) {
            $atributos['comprimento'] = $produto['comprimento'];
        }
        
        // Adicionar atributos específicos do marketplace
        switch ($marketplace) {
            case 'mercadolivre':
                $atributos['BRAND'] = 'PelúciaPet';
                $atributos['MODEL'] = $produto['sku'];
                break;
                
            case 'amazon':
                $atributos['brand'] = 'PelúciaPet';
                $atributos['manufacturer'] = 'PelúciaPet';
                break;
        }
        
        return $atributos;
    }
    
    private function salvarProdutoMarketplace($produtoId, $marketplace, $idExterno) {
        $conn = $this->db->getConnection();
        $sql = "INSERT INTO produto_marketplace (produto_id, marketplace, id_externo, ativo) 
                VALUES (?, ?, ?, 1)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$produtoId, $marketplace, $idExterno]);
    }
    
    private function salvarResultadoSincronizacao($produtoId, $marketplace, $resultado) {
        $conn = $this->db->getConnection();
        $sql = "INSERT INTO sincronizacao_log (produto_id, marketplace, tipo, resultado, detalhes) 
                VALUES (?, ?, 'produto', ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $produtoId,
            $marketplace,
            $resultado['sucesso'] ? 'sucesso' : 'erro',
            json_encode($resultado)
        ]);
    }
    
    private function pedidoJaImportado($idExterno, $marketplace) {
        $conn = $this->db->getConnection();
        $sql = "SELECT COUNT(*) FROM pedido_marketplace WHERE id_externo = ? AND marketplace = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$idExterno, $marketplace]);
        return $stmt->fetchColumn() > 0;
    }
    
    private function converterPedidoMarketplace($pedidoExterno, $marketplace) {
        // Converter estrutura do pedido do marketplace para formato interno
        return [
            'cliente_email' => $pedidoExterno['buyer']['email'] ?? '',
            'cliente_nome' => $pedidoExterno['buyer']['name'] ?? '',
            'valor_total' => $pedidoExterno['total_amount'] ?? 0,
            'status' => $this->converterStatusDoMarketplace($pedidoExterno['status'], $marketplace),
            'itens' => $this->converterItensMarketplace($pedidoExterno['items'] ?? []),
            'endereco_entrega' => $this->converterEnderecoMarketplace($pedidoExterno['shipping'] ?? [])
        ];
    }
    
    private function converterStatusDoMarketplace($statusExterno, $marketplace) {
        $mapeamentos = [
            'mercadolivre' => [
                'confirmed' => 'confirmado',
                'payment_required' => 'aguardando_pagamento',
                'payment_in_process' => 'processando_pagamento',
                'paid' => 'pago',
                'shipped' => 'enviado',
                'delivered' => 'entregue',
                'cancelled' => 'cancelado'
            ],
            'amazon' => [
                'Pending' => 'aguardando_pagamento',
                'Unshipped' => 'confirmado',
                'PartiallyShipped' => 'enviado_parcial',
                'Shipped' => 'enviado',
                'Cancelled' => 'cancelado'
            ]
        ];
        
        return $mapeamentos[$marketplace][$statusExterno] ?? 'pendente';
    }
    
    private function converterStatusParaMarketplace($statusInterno, $marketplace) {
        $mapeamentos = [
            'mercadolivre' => [
                'confirmado' => 'confirmed',
                'enviado' => 'shipped',
                'entregue' => 'delivered',
                'cancelado' => 'cancelled'
            ],
            'amazon' => [
                'confirmado' => 'Unshipped',
                'enviado' => 'Shipped',
                'cancelado' => 'Cancelled'
            ]
        ];
        
        return $mapeamentos[$marketplace][$statusInterno] ?? $statusInterno;
    }
    
    private function validarConfiguracoes($marketplace, $configuracoes) {
        $camposObrigatorios = [
            'mercadolivre' => ['app_id', 'client_secret'],
            'amazon' => ['access_key', 'secret_key', 'marketplace_id'],
            'shopee' => ['partner_id', 'partner_key', 'shop_id']
        ];
        
        if (!isset($camposObrigatorios[$marketplace])) {
            return ['valido' => false, 'erro' => 'Marketplace não suportado'];
        }
        
        foreach ($camposObrigatorios[$marketplace] as $campo) {
            if (empty($configuracoes[$campo])) {
                return ['valido' => false, 'erro' => "Campo obrigatório: {$campo}"];
            }
        }
        
        return ['valido' => true];
    }
    
    private function testarConexaoAPI($marketplace, $configuracoes) {
        try {
            $api = new $this->apis[$marketplace]($configuracoes);
            return $api->testarConexao();
        } catch (Exception $e) {
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
}

/**
 * Classes específicas das APIs dos marketplaces
 */

class MercadoLivreAPI {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    public function criarProduto($dados) {
        // Implementar criação de produto no Mercado Livre
        return ['sucesso' => true, 'id_externo' => 'MLB123456789'];
    }
    
    public function atualizarProduto($id, $dados) {
        // Implementar atualização de produto
        return ['sucesso' => true];
    }
    
    public function atualizarEstoque($id, $estoque) {
        // Implementar atualização de estoque
        return ['sucesso' => true];
    }
    
    public function atualizarPreco($id, $preco) {
        // Implementar atualização de preço
        return ['sucesso' => true];
    }
    
    public function buscarPedidos($dataInicio = null) {
        // Implementar busca de pedidos
        return [];
    }
    
    public function atualizarStatusPedido($id, $status) {
        // Implementar atualização de status
        return ['sucesso' => true];
    }
    
    public function testarConexao() {
        // Implementar teste de conexão
        return ['sucesso' => true];
    }
}

class AmazonAPI {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    // Métodos similares ao MercadoLivreAPI
    public function criarProduto($dados) { return ['sucesso' => true, 'id_externo' => 'ASIN123']; }
    public function atualizarProduto($id, $dados) { return ['sucesso' => true]; }
    public function atualizarEstoque($id, $estoque) { return ['sucesso' => true]; }
    public function atualizarPreco($id, $preco) { return ['sucesso' => true]; }
    public function buscarPedidos($dataInicio = null) { return []; }
    public function atualizarStatusPedido($id, $status) { return ['sucesso' => true]; }
    public function testarConexao() { return ['sucesso' => true]; }
}

class ShopeeAPI {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    // Métodos similares aos outros marketplaces
    public function criarProduto($dados) { return ['sucesso' => true, 'id_externo' => 'SHOPEE123']; }
    public function atualizarProduto($id, $dados) { return ['sucesso' => true]; }
    public function atualizarEstoque($id, $estoque) { return ['sucesso' => true]; }
    public function atualizarPreco($id, $preco) { return ['sucesso' => true]; }
    public function buscarPedidos($dataInicio = null) { return []; }
    public function atualizarStatusPedido($id, $status) { return ['sucesso' => true]; }
    public function testarConexao() { return ['sucesso' => true]; }
}
?>

