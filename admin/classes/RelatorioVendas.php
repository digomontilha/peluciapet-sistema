<?php
/**
 * Classe RelatorioVendas - Sistema PelúciaPet v2.1
 * Geração de relatórios e estatísticas de vendas
 */

class RelatorioVendas {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Dashboard principal com métricas gerais
     */
    public function getDashboard($periodo = '30d') {
        try {
            $dataInicio = $this->calcularDataInicio($periodo);
            $dataFim = date('Y-m-d 23:59:59');
            
            $dashboard = [
                'periodo' => $periodo,
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'metricas_gerais' => $this->getMetricasGerais($dataInicio, $dataFim),
                'vendas_por_dia' => $this->getVendasPorDia($dataInicio, $dataFim),
                'produtos_mais_vendidos' => $this->getProdutosMaisVendidos($dataInicio, $dataFim),
                'categorias_performance' => $this->getCategoriasPerformance($dataInicio, $dataFim),
                'vendas_por_regiao' => $this->getVendasPorRegiao($dataInicio, $dataFim),
                'metodos_pagamento' => $this->getMetodosPagamento($dataInicio, $dataFim),
                'status_pedidos' => $this->getStatusPedidos($dataInicio, $dataFim)
            ];
            
            return [
                'success' => true,
                'data' => $dashboard
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao gerar dashboard: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Métricas gerais do período
     */
    private function getMetricasGerais($dataInicio, $dataFim) {
        // Vendas do período atual
        $vendasAtual = $this->db->fetch("
            SELECT 
                COUNT(*) as total_pedidos,
                COALESCE(SUM(valor_total), 0) as receita_total,
                COALESCE(AVG(valor_total), 0) as ticket_medio,
                COUNT(DISTINCT cliente_id) as clientes_unicos
            FROM pedidos 
            WHERE data_pedido BETWEEN ? AND ? 
            AND status NOT IN ('cancelado', 'devolvido')
        ", [$dataInicio, $dataFim]);
        
        // Período anterior para comparação
        $diasPeriodo = (strtotime($dataFim) - strtotime($dataInicio)) / (60 * 60 * 24);
        $dataInicioAnterior = date('Y-m-d H:i:s', strtotime($dataInicio . " -{$diasPeriodo} days"));
        $dataFimAnterior = date('Y-m-d H:i:s', strtotime($dataInicio . " -1 day"));
        
        $vendasAnterior = $this->db->fetch("
            SELECT 
                COUNT(*) as total_pedidos,
                COALESCE(SUM(valor_total), 0) as receita_total,
                COALESCE(AVG(valor_total), 0) as ticket_medio,
                COUNT(DISTINCT cliente_id) as clientes_unicos
            FROM pedidos 
            WHERE data_pedido BETWEEN ? AND ? 
            AND status NOT IN ('cancelado', 'devolvido')
        ", [$dataInicioAnterior, $dataFimAnterior]);
        
        // Calcular variações percentuais
        $metricas = [
            'total_pedidos' => (int)$vendasAtual['total_pedidos'],
            'receita_total' => (float)$vendasAtual['receita_total'],
            'ticket_medio' => (float)$vendasAtual['ticket_medio'],
            'clientes_unicos' => (int)$vendasAtual['clientes_unicos'],
            'variacao_pedidos' => $this->calcularVariacao($vendasAnterior['total_pedidos'], $vendasAtual['total_pedidos']),
            'variacao_receita' => $this->calcularVariacao($vendasAnterior['receita_total'], $vendasAtual['receita_total']),
            'variacao_ticket' => $this->calcularVariacao($vendasAnterior['ticket_medio'], $vendasAtual['ticket_medio']),
            'variacao_clientes' => $this->calcularVariacao($vendasAnterior['clientes_unicos'], $vendasAtual['clientes_unicos'])
        ];
        
        // Métricas adicionais
        $metricas['taxa_conversao'] = $this->getTaxaConversao($dataInicio, $dataFim);
        $metricas['produtos_vendidos'] = $this->getTotalProdutosVendidos($dataInicio, $dataFim);
        $metricas['valor_medio_item'] = $metricas['receita_total'] > 0 && $metricas['produtos_vendidos'] > 0 
            ? $metricas['receita_total'] / $metricas['produtos_vendidos'] : 0;
        
        return $metricas;
    }
    
    /**
     * Vendas por dia para gráfico temporal
     */
    private function getVendasPorDia($dataInicio, $dataFim) {
        return $this->db->fetchAll("
            SELECT 
                DATE(data_pedido) as data,
                COUNT(*) as total_pedidos,
                COALESCE(SUM(valor_total), 0) as receita,
                COUNT(DISTINCT cliente_id) as clientes_unicos
            FROM pedidos 
            WHERE data_pedido BETWEEN ? AND ? 
            AND status NOT IN ('cancelado', 'devolvido')
            GROUP BY DATE(data_pedido)
            ORDER BY data ASC
        ", [$dataInicio, $dataFim]);
    }
    
    /**
     * Produtos mais vendidos
     */
    private function getProdutosMaisVendidos($dataInicio, $dataFim, $limite = 10) {
        return $this->db->fetchAll("
            SELECT 
                p.id,
                p.nome,
                p.preco,
                SUM(pi.quantidade) as quantidade_vendida,
                SUM(pi.quantidade * pi.preco_unitario) as receita_produto,
                COUNT(DISTINCT pi.pedido_id) as pedidos_distintos,
                AVG(pi.preco_unitario) as preco_medio_venda
            FROM produtos p
            INNER JOIN pedido_itens pi ON p.id = pi.produto_id
            INNER JOIN pedidos ped ON pi.pedido_id = ped.id
            WHERE ped.data_pedido BETWEEN ? AND ?
            AND ped.status NOT IN ('cancelado', 'devolvido')
            GROUP BY p.id, p.nome, p.preco
            ORDER BY quantidade_vendida DESC
            LIMIT ?
        ", [$dataInicio, $dataFim, $limite]);
    }
    
    /**
     * Performance por categoria
     */
    private function getCategoriasPerformance($dataInicio, $dataFim) {
        return $this->db->fetchAll("
            SELECT 
                c.id,
                c.nome as categoria,
                c.cor_destaque,
                COUNT(DISTINCT p.id) as produtos_categoria,
                SUM(pi.quantidade) as quantidade_vendida,
                SUM(pi.quantidade * pi.preco_unitario) as receita_categoria,
                COUNT(DISTINCT pi.pedido_id) as pedidos_distintos,
                AVG(pi.preco_unitario) as preco_medio
            FROM categorias c
            INNER JOIN produtos p ON c.id = p.categoria_id
            INNER JOIN pedido_itens pi ON p.id = pi.produto_id
            INNER JOIN pedidos ped ON pi.pedido_id = ped.id
            WHERE ped.data_pedido BETWEEN ? AND ?
            AND ped.status NOT IN ('cancelado', 'devolvido')
            AND c.ativo = 1
            GROUP BY c.id, c.nome, c.cor_destaque
            ORDER BY receita_categoria DESC
        ", [$dataInicio, $dataFim]);
    }
    
    /**
     * Vendas por região (baseado no CEP)
     */
    private function getVendasPorRegiao($dataInicio, $dataFim) {
        return $this->db->fetchAll("
            SELECT 
                CASE 
                    WHEN SUBSTRING(cep, 1, 1) IN ('0', '1') THEN 'São Paulo'
                    WHEN SUBSTRING(cep, 1, 1) = '2' THEN 'Rio de Janeiro/Espírito Santo'
                    WHEN SUBSTRING(cep, 1, 1) = '3' THEN 'Minas Gerais'
                    WHEN SUBSTRING(cep, 1, 1) = '4' THEN 'Bahia/Sergipe'
                    WHEN SUBSTRING(cep, 1, 1) = '5' THEN 'Paraná/Santa Catarina'
                    WHEN SUBSTRING(cep, 1, 1) = '6' THEN 'Pernambuco/Alagoas/Paraíba/Rio Grande do Norte'
                    WHEN SUBSTRING(cep, 1, 1) = '7' THEN 'Ceará/Piauí'
                    WHEN SUBSTRING(cep, 1, 1) = '8' THEN 'Rio Grande do Sul'
                    WHEN SUBSTRING(cep, 1, 1) = '9' THEN 'Mato Grosso/Mato Grosso do Sul/Rondônia/Acre'
                    ELSE 'Outros'
                END as regiao,
                COUNT(*) as total_pedidos,
                SUM(valor_total) as receita_total,
                COUNT(DISTINCT cliente_id) as clientes_unicos,
                AVG(valor_total) as ticket_medio
            FROM pedidos 
            WHERE data_pedido BETWEEN ? AND ? 
            AND status NOT IN ('cancelado', 'devolvido')
            AND cep IS NOT NULL AND cep != ''
            GROUP BY regiao
            ORDER BY receita_total DESC
        ", [$dataInicio, $dataFim]);
    }
    
    /**
     * Métodos de pagamento
     */
    private function getMetodosPagamento($dataInicio, $dataFim) {
        return $this->db->fetchAll("
            SELECT 
                metodo_pagamento,
                COUNT(*) as total_pedidos,
                SUM(valor_total) as receita_total,
                AVG(valor_total) as ticket_medio,
                (COUNT(*) * 100.0 / (SELECT COUNT(*) FROM pedidos WHERE data_pedido BETWEEN ? AND ? AND status NOT IN ('cancelado', 'devolvido'))) as percentual
            FROM pedidos 
            WHERE data_pedido BETWEEN ? AND ? 
            AND status NOT IN ('cancelado', 'devolvido')
            GROUP BY metodo_pagamento
            ORDER BY receita_total DESC
        ", [$dataInicio, $dataFim, $dataInicio, $dataFim]);
    }
    
    /**
     * Status dos pedidos
     */
    private function getStatusPedidos($dataInicio, $dataFim) {
        return $this->db->fetchAll("
            SELECT 
                status,
                COUNT(*) as total_pedidos,
                SUM(valor_total) as receita_total,
                (COUNT(*) * 100.0 / (SELECT COUNT(*) FROM pedidos WHERE data_pedido BETWEEN ? AND ?)) as percentual
            FROM pedidos 
            WHERE data_pedido BETWEEN ? AND ?
            GROUP BY status
            ORDER BY total_pedidos DESC
        ", [$dataInicio, $dataFim, $dataInicio, $dataFim]);
    }
    
    /**
     * Relatório detalhado de vendas
     */
    public function getRelatorioDetalhado($filtros = []) {
        try {
            $where = ['1=1'];
            $params = [];
            
            // Filtro por período
            if (!empty($filtros['data_inicio'])) {
                $where[] = "p.data_pedido >= ?";
                $params[] = $filtros['data_inicio'] . ' 00:00:00';
            }
            
            if (!empty($filtros['data_fim'])) {
                $where[] = "p.data_pedido <= ?";
                $params[] = $filtros['data_fim'] . ' 23:59:59';
            }
            
            // Filtro por status
            if (!empty($filtros['status'])) {
                $where[] = "p.status = ?";
                $params[] = $filtros['status'];
            }
            
            // Filtro por categoria
            if (!empty($filtros['categoria_id'])) {
                $where[] = "prod.categoria_id = ?";
                $params[] = $filtros['categoria_id'];
            }
            
            // Filtro por produto
            if (!empty($filtros['produto_id'])) {
                $where[] = "pi.produto_id = ?";
                $params[] = $filtros['produto_id'];
            }
            
            // Filtro por cliente
            if (!empty($filtros['cliente_id'])) {
                $where[] = "p.cliente_id = ?";
                $params[] = $filtros['cliente_id'];
            }
            
            // Filtro por método de pagamento
            if (!empty($filtros['metodo_pagamento'])) {
                $where[] = "p.metodo_pagamento = ?";
                $params[] = $filtros['metodo_pagamento'];
            }
            
            $sql = "
                SELECT 
                    p.id as pedido_id,
                    p.data_pedido,
                    p.status,
                    p.valor_total,
                    p.metodo_pagamento,
                    p.cliente_nome,
                    p.cliente_email,
                    p.cidade,
                    p.estado,
                    pi.produto_id,
                    prod.nome as produto_nome,
                    c.nome as categoria_nome,
                    pi.quantidade,
                    pi.preco_unitario,
                    (pi.quantidade * pi.preco_unitario) as subtotal
                FROM pedidos p
                INNER JOIN pedido_itens pi ON p.id = pi.pedido_id
                INNER JOIN produtos prod ON pi.produto_id = prod.id
                LEFT JOIN categorias c ON prod.categoria_id = c.id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY p.data_pedido DESC, p.id DESC
            ";
            
            $vendas = $this->db->fetchAll($sql, $params);
            
            // Calcular totais
            $totais = [
                'total_pedidos' => 0,
                'receita_total' => 0,
                'quantidade_total' => 0,
                'ticket_medio' => 0
            ];
            
            $pedidosUnicos = [];
            foreach ($vendas as $venda) {
                if (!isset($pedidosUnicos[$venda['pedido_id']])) {
                    $pedidosUnicos[$venda['pedido_id']] = $venda['valor_total'];
                    $totais['total_pedidos']++;
                    $totais['receita_total'] += $venda['valor_total'];
                }
                $totais['quantidade_total'] += $venda['quantidade'];
            }
            
            $totais['ticket_medio'] = $totais['total_pedidos'] > 0 
                ? $totais['receita_total'] / $totais['total_pedidos'] : 0;
            
            return [
                'success' => true,
                'data' => [
                    'vendas' => $vendas,
                    'totais' => $totais,
                    'filtros_aplicados' => $filtros
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao gerar relatório: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Análise de cohort (clientes por período de primeira compra)
     */
    public function getAnaliseClientes($periodo = '12m') {
        try {
            $dataInicio = $this->calcularDataInicio($periodo);
            
            // Clientes novos vs recorrentes
            $clientesNovos = $this->db->fetchAll("
                SELECT 
                    DATE_FORMAT(primeira_compra, '%Y-%m') as mes,
                    COUNT(*) as clientes_novos
                FROM (
                    SELECT 
                        cliente_id,
                        MIN(data_pedido) as primeira_compra
                    FROM pedidos 
                    WHERE status NOT IN ('cancelado', 'devolvido')
                    GROUP BY cliente_id
                ) primeira_compras
                WHERE primeira_compra >= ?
                GROUP BY mes
                ORDER BY mes
            ", [$dataInicio]);
            
            // Taxa de retenção
            $retencao = $this->db->fetchAll("
                SELECT 
                    DATE_FORMAT(data_pedido, '%Y-%m') as mes,
                    COUNT(DISTINCT cliente_id) as clientes_ativos,
                    COUNT(DISTINCT CASE WHEN total_pedidos_cliente > 1 THEN cliente_id END) as clientes_recorrentes
                FROM (
                    SELECT 
                        p1.cliente_id,
                        p1.data_pedido,
                        COUNT(p2.id) as total_pedidos_cliente
                    FROM pedidos p1
                    LEFT JOIN pedidos p2 ON p1.cliente_id = p2.cliente_id 
                        AND p2.data_pedido <= p1.data_pedido
                        AND p2.status NOT IN ('cancelado', 'devolvido')
                    WHERE p1.data_pedido >= ?
                    AND p1.status NOT IN ('cancelado', 'devolvido')
                    GROUP BY p1.cliente_id, p1.data_pedido
                ) cliente_historico
                GROUP BY mes
                ORDER BY mes
            ", [$dataInicio]);
            
            return [
                'success' => true,
                'data' => [
                    'clientes_novos' => $clientesNovos,
                    'retencao' => $retencao
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro na análise de clientes: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Previsão de vendas baseada em dados históricos
     */
    public function getPrevisaoVendas($mesesPrevisao = 3) {
        try {
            // Dados dos últimos 12 meses para análise de tendência
            $dadosHistoricos = $this->db->fetchAll("
                SELECT 
                    DATE_FORMAT(data_pedido, '%Y-%m') as mes,
                    COUNT(*) as total_pedidos,
                    SUM(valor_total) as receita_total
                FROM pedidos 
                WHERE data_pedido >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                AND status NOT IN ('cancelado', 'devolvido')
                GROUP BY mes
                ORDER BY mes
            ");
            
            if (count($dadosHistoricos) < 3) {
                return [
                    'success' => false,
                    'message' => 'Dados insuficientes para previsão (mínimo 3 meses)'
                ];
            }
            
            // Calcular tendência linear simples
            $previsao = $this->calcularTendenciaLinear($dadosHistoricos, $mesesPrevisao);
            
            return [
                'success' => true,
                'data' => [
                    'dados_historicos' => $dadosHistoricos,
                    'previsao' => $previsao,
                    'meses_previsao' => $mesesPrevisao
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro na previsão: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Exportar relatório para CSV
     */
    public function exportarCSV($tipo, $filtros = []) {
        try {
            switch ($tipo) {
                case 'vendas_detalhado':
                    $dados = $this->getRelatorioDetalhado($filtros);
                    if (!$dados['success']) {
                        return $dados;
                    }
                    return $this->gerarCSVVendas($dados['data']['vendas']);
                    
                case 'produtos_vendidos':
                    $dataInicio = $filtros['data_inicio'] ?? date('Y-m-01');
                    $dataFim = $filtros['data_fim'] ?? date('Y-m-d');
                    $produtos = $this->getProdutosMaisVendidos($dataInicio . ' 00:00:00', $dataFim . ' 23:59:59', 1000);
                    return $this->gerarCSVProdutos($produtos);
                    
                default:
                    return [
                        'success' => false,
                        'message' => 'Tipo de relatório não suportado'
                    ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro na exportação: ' . $e->getMessage()
            ];
        }
    }
    
    // Métodos auxiliares
    
    private function calcularDataInicio($periodo) {
        switch ($periodo) {
            case '7d':
                return date('Y-m-d H:i:s', strtotime('-7 days'));
            case '30d':
                return date('Y-m-d H:i:s', strtotime('-30 days'));
            case '90d':
                return date('Y-m-d H:i:s', strtotime('-90 days'));
            case '6m':
                return date('Y-m-d H:i:s', strtotime('-6 months'));
            case '12m':
                return date('Y-m-d H:i:s', strtotime('-12 months'));
            default:
                return date('Y-m-d H:i:s', strtotime('-30 days'));
        }
    }
    
    private function calcularVariacao($valorAnterior, $valorAtual) {
        if ($valorAnterior == 0) {
            return $valorAtual > 0 ? 100 : 0;
        }
        return (($valorAtual - $valorAnterior) / $valorAnterior) * 100;
    }
    
    private function getTaxaConversao($dataInicio, $dataFim) {
        // Implementar lógica de taxa de conversão baseada em visitas vs pedidos
        // Por enquanto, retorna um valor simulado
        return 2.5;
    }
    
    private function getTotalProdutosVendidos($dataInicio, $dataFim) {
        $result = $this->db->fetch("
            SELECT SUM(pi.quantidade) as total
            FROM pedido_itens pi
            INNER JOIN pedidos p ON pi.pedido_id = p.id
            WHERE p.data_pedido BETWEEN ? AND ?
            AND p.status NOT IN ('cancelado', 'devolvido')
        ", [$dataInicio, $dataFim]);
        
        return (int)($result['total'] ?? 0);
    }
    
    private function calcularTendenciaLinear($dados, $mesesPrevisao) {
        // Implementação simples de regressão linear
        $n = count($dados);
        $somaX = 0;
        $somaY = 0;
        $somaXY = 0;
        $somaX2 = 0;
        
        foreach ($dados as $i => $dado) {
            $x = $i + 1;
            $y = (float)$dado['receita_total'];
            
            $somaX += $x;
            $somaY += $y;
            $somaXY += $x * $y;
            $somaX2 += $x * $x;
        }
        
        $a = ($n * $somaXY - $somaX * $somaY) / ($n * $somaX2 - $somaX * $somaX);
        $b = ($somaY - $a * $somaX) / $n;
        
        $previsao = [];
        for ($i = 1; $i <= $mesesPrevisao; $i++) {
            $proximoMes = date('Y-m', strtotime("+{$i} months"));
            $valorPrevisto = $a * ($n + $i) + $b;
            
            $previsao[] = [
                'mes' => $proximoMes,
                'receita_prevista' => max(0, $valorPrevisto) // Não permitir valores negativos
            ];
        }
        
        return $previsao;
    }
    
    private function gerarCSVVendas($vendas) {
        $csv = "Pedido ID,Data,Status,Cliente,Email,Produto,Categoria,Quantidade,Preço Unitário,Subtotal,Total Pedido\n";
        
        foreach ($vendas as $venda) {
            $csv .= sprintf(
                "%d,%s,%s,%s,%s,%s,%s,%d,%.2f,%.2f,%.2f\n",
                $venda['pedido_id'],
                $venda['data_pedido'],
                $venda['status'],
                $venda['cliente_nome'],
                $venda['cliente_email'],
                $venda['produto_nome'],
                $venda['categoria_nome'],
                $venda['quantidade'],
                $venda['preco_unitario'],
                $venda['subtotal'],
                $venda['valor_total']
            );
        }
        
        return [
            'success' => true,
            'data' => $csv,
            'filename' => 'vendas_' . date('Y-m-d_H-i-s') . '.csv'
        ];
    }
    
    private function gerarCSVProdutos($produtos) {
        $csv = "Produto ID,Nome,Preço,Quantidade Vendida,Receita,Pedidos Distintos,Preço Médio Venda\n";
        
        foreach ($produtos as $produto) {
            $csv .= sprintf(
                "%d,%s,%.2f,%d,%.2f,%d,%.2f\n",
                $produto['id'],
                $produto['nome'],
                $produto['preco'],
                $produto['quantidade_vendida'],
                $produto['receita_produto'],
                $produto['pedidos_distintos'],
                $produto['preco_medio_venda']
            );
        }
        
        return [
            'success' => true,
            'data' => $csv,
            'filename' => 'produtos_vendidos_' . date('Y-m-d_H-i-s') . '.csv'
        ];
    }
}
?>

