<?php
/**
 * Classe Cupom - Sistema PelúciaPet v2.1
 * Sistema completo de cupons de desconto
 */

class Cupom {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Criar novo cupom
     */
    public function criar($dados) {
        try {
            // Validar dados obrigatórios
            $required = ['codigo', 'tipo_desconto', 'valor_desconto'];
            foreach ($required as $field) {
                if (empty($dados[$field])) {
                    return [
                        'success' => false,
                        'message' => "Campo '{$field}' é obrigatório"
                    ];
                }
            }
            
            // Validar código único
            if ($this->codigoExists($dados['codigo'])) {
                return [
                    'success' => false,
                    'message' => 'Código do cupom já existe'
                ];
            }
            
            // Validar tipo de desconto
            if (!in_array($dados['tipo_desconto'], ['percentual', 'valor_fixo', 'frete_gratis'])) {
                return [
                    'success' => false,
                    'message' => 'Tipo de desconto inválido'
                ];
            }
            
            // Validar valor do desconto
            $valorDesconto = (float)$dados['valor_desconto'];
            if ($dados['tipo_desconto'] === 'percentual' && ($valorDesconto <= 0 || $valorDesconto > 100)) {
                return [
                    'success' => false,
                    'message' => 'Desconto percentual deve estar entre 0 e 100'
                ];
            }
            
            if ($dados['tipo_desconto'] === 'valor_fixo' && $valorDesconto <= 0) {
                return [
                    'success' => false,
                    'message' => 'Valor do desconto deve ser maior que zero'
                ];
            }
            
            // Validar datas
            $dataInicio = $dados['data_inicio'] ?? date('Y-m-d H:i:s');
            $dataFim = $dados['data_fim'] ?? null;
            
            if ($dataFim && strtotime($dataFim) <= strtotime($dataInicio)) {
                return [
                    'success' => false,
                    'message' => 'Data de fim deve ser posterior à data de início'
                ];
            }
            
            // Preparar dados para inserção
            $dadosInsercao = [
                'codigo' => strtoupper(trim($dados['codigo'])),
                'nome' => trim($dados['nome'] ?? ''),
                'descricao' => trim($dados['descricao'] ?? ''),
                'tipo_desconto' => $dados['tipo_desconto'],
                'valor_desconto' => $valorDesconto,
                'valor_minimo_pedido' => (float)($dados['valor_minimo_pedido'] ?? 0),
                'valor_maximo_desconto' => (float)($dados['valor_maximo_desconto'] ?? 0),
                'limite_uso_total' => (int)($dados['limite_uso_total'] ?? 0),
                'limite_uso_cliente' => (int)($dados['limite_uso_cliente'] ?? 1),
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'ativo' => isset($dados['ativo']) ? (int)$dados['ativo'] : 1,
                'primeira_compra_apenas' => isset($dados['primeira_compra_apenas']) ? (int)$dados['primeira_compra_apenas'] : 0,
                'categorias_permitidas' => !empty($dados['categorias_permitidas']) ? json_encode($dados['categorias_permitidas']) : null,
                'produtos_permitidos' => !empty($dados['produtos_permitidos']) ? json_encode($dados['produtos_permitidos']) : null,
                'clientes_permitidos' => !empty($dados['clientes_permitidos']) ? json_encode($dados['clientes_permitidos']) : null,
                'data_criacao' => date('Y-m-d H:i:s'),
                'data_atualizacao' => date('Y-m-d H:i:s')
            ];
            
            $sql = "INSERT INTO cupons (
                codigo, nome, descricao, tipo_desconto, valor_desconto, 
                valor_minimo_pedido, valor_maximo_desconto, limite_uso_total, 
                limite_uso_cliente, data_inicio, data_fim, ativo, 
                primeira_compra_apenas, categorias_permitidas, produtos_permitidos, 
                clientes_permitidos, data_criacao, data_atualizacao
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $this->db->execute($sql, array_values($dadosInsercao));
            $cupomId = $this->db->lastInsertId();
            
            return [
                'success' => true,
                'message' => 'Cupom criado com sucesso',
                'data' => ['id' => $cupomId]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao criar cupom: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validar cupom para uso
     */
    public function validar($codigo, $clienteId = null, $valorPedido = 0, $itens = []) {
        try {
            $cupom = $this->buscarPorCodigo($codigo);
            
            if (!$cupom) {
                return [
                    'success' => false,
                    'message' => 'Cupom não encontrado'
                ];
            }
            
            // Verificar se está ativo
            if (!$cupom['ativo']) {
                return [
                    'success' => false,
                    'message' => 'Cupom inativo'
                ];
            }
            
            // Verificar período de validade
            $agora = date('Y-m-d H:i:s');
            
            if ($cupom['data_inicio'] && $agora < $cupom['data_inicio']) {
                return [
                    'success' => false,
                    'message' => 'Cupom ainda não está válido'
                ];
            }
            
            if ($cupom['data_fim'] && $agora > $cupom['data_fim']) {
                return [
                    'success' => false,
                    'message' => 'Cupom expirado'
                ];
            }
            
            // Verificar limite de uso total
            if ($cupom['limite_uso_total'] > 0 && $cupom['total_usos'] >= $cupom['limite_uso_total']) {
                return [
                    'success' => false,
                    'message' => 'Cupom esgotado'
                ];
            }
            
            // Verificar limite de uso por cliente
            if ($clienteId && $cupom['limite_uso_cliente'] > 0) {
                $usosCliente = $this->contarUsosCliente($cupom['id'], $clienteId);
                if ($usosCliente >= $cupom['limite_uso_cliente']) {
                    return [
                        'success' => false,
                        'message' => 'Limite de uso do cupom atingido para este cliente'
                    ];
                }
            }
            
            // Verificar valor mínimo do pedido
            if ($cupom['valor_minimo_pedido'] > 0 && $valorPedido < $cupom['valor_minimo_pedido']) {
                return [
                    'success' => false,
                    'message' => 'Valor mínimo do pedido não atingido: R$ ' . number_format($cupom['valor_minimo_pedido'], 2, ',', '.')
                ];
            }
            
            // Verificar primeira compra
            if ($cupom['primeira_compra_apenas'] && $clienteId) {
                $pedidosAnteriores = $this->db->fetch(
                    "SELECT COUNT(*) as total FROM pedidos WHERE cliente_id = ? AND status NOT IN ('cancelado', 'devolvido')",
                    [$clienteId]
                );
                
                if ($pedidosAnteriores['total'] > 0) {
                    return [
                        'success' => false,
                        'message' => 'Cupom válido apenas para primeira compra'
                    ];
                }
            }
            
            // Verificar restrições de categorias
            if ($cupom['categorias_permitidas'] && !empty($itens)) {
                $categoriasPermitidas = json_decode($cupom['categorias_permitidas'], true);
                $temItemValido = false;
                
                foreach ($itens as $item) {
                    if (in_array($item['categoria_id'], $categoriasPermitidas)) {
                        $temItemValido = true;
                        break;
                    }
                }
                
                if (!$temItemValido) {
                    return [
                        'success' => false,
                        'message' => 'Cupom não válido para os produtos selecionados'
                    ];
                }
            }
            
            // Verificar restrições de produtos
            if ($cupom['produtos_permitidos'] && !empty($itens)) {
                $produtosPermitidos = json_decode($cupom['produtos_permitidos'], true);
                $temItemValido = false;
                
                foreach ($itens as $item) {
                    if (in_array($item['produto_id'], $produtosPermitidos)) {
                        $temItemValido = true;
                        break;
                    }
                }
                
                if (!$temItemValido) {
                    return [
                        'success' => false,
                        'message' => 'Cupom não válido para os produtos selecionados'
                    ];
                }
            }
            
            // Verificar restrições de clientes
            if ($cupom['clientes_permitidos'] && $clienteId) {
                $clientesPermitidos = json_decode($cupom['clientes_permitidos'], true);
                
                if (!in_array($clienteId, $clientesPermitidos)) {
                    return [
                        'success' => false,
                        'message' => 'Cupom não válido para este cliente'
                    ];
                }
            }
            
            return [
                'success' => true,
                'message' => 'Cupom válido',
                'data' => $cupom
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao validar cupom: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Calcular desconto do cupom
     */
    public function calcularDesconto($codigo, $valorPedido, $valorFrete = 0, $itens = []) {
        try {
            $validacao = $this->validar($codigo, null, $valorPedido, $itens);
            
            if (!$validacao['success']) {
                return $validacao;
            }
            
            $cupom = $validacao['data'];
            $desconto = 0;
            $freteGratis = false;
            
            switch ($cupom['tipo_desconto']) {
                case 'percentual':
                    $desconto = ($valorPedido * $cupom['valor_desconto']) / 100;
                    
                    // Aplicar limite máximo se definido
                    if ($cupom['valor_maximo_desconto'] > 0) {
                        $desconto = min($desconto, $cupom['valor_maximo_desconto']);
                    }
                    break;
                    
                case 'valor_fixo':
                    $desconto = min($cupom['valor_desconto'], $valorPedido);
                    break;
                    
                case 'frete_gratis':
                    $freteGratis = true;
                    $desconto = $valorFrete;
                    break;
            }
            
            // Garantir que o desconto não seja maior que o valor do pedido
            $desconto = min($desconto, $valorPedido);
            
            return [
                'success' => true,
                'data' => [
                    'cupom_id' => $cupom['id'],
                    'codigo' => $cupom['codigo'],
                    'tipo_desconto' => $cupom['tipo_desconto'],
                    'valor_desconto' => $desconto,
                    'frete_gratis' => $freteGratis,
                    'valor_original' => $valorPedido,
                    'valor_final' => $valorPedido - $desconto,
                    'economia' => $desconto
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao calcular desconto: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Aplicar cupom em um pedido
     */
    public function aplicar($cupomId, $pedidoId, $valorDesconto, $clienteId = null) {
        try {
            // Registrar uso do cupom
            $sql = "INSERT INTO cupom_usos (
                cupom_id, pedido_id, cliente_id, valor_desconto, data_uso
            ) VALUES (?, ?, ?, ?, ?)";
            
            $this->db->execute($sql, [
                $cupomId,
                $pedidoId,
                $clienteId,
                $valorDesconto,
                date('Y-m-d H:i:s')
            ]);
            
            return [
                'success' => true,
                'message' => 'Cupom aplicado com sucesso'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao aplicar cupom: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Listar cupons
     */
    public function listar($filtros = []) {
        try {
            $where = ['1=1'];
            $params = [];
            
            // Filtro por status
            if (isset($filtros['ativo'])) {
                $where[] = "c.ativo = ?";
                $params[] = $filtros['ativo'];
            }
            
            // Filtro por tipo
            if (!empty($filtros['tipo_desconto'])) {
                $where[] = "c.tipo_desconto = ?";
                $params[] = $filtros['tipo_desconto'];
            }
            
            // Filtro por validade
            if (isset($filtros['validos_apenas']) && $filtros['validos_apenas']) {
                $agora = date('Y-m-d H:i:s');
                $where[] = "(c.data_inicio IS NULL OR c.data_inicio <= ?)";
                $where[] = "(c.data_fim IS NULL OR c.data_fim >= ?)";
                $params[] = $agora;
                $params[] = $agora;
            }
            
            // Filtro por busca
            if (!empty($filtros['busca'])) {
                $where[] = "(c.codigo LIKE ? OR c.nome LIKE ? OR c.descricao LIKE ?)";
                $busca = '%' . $filtros['busca'] . '%';
                $params[] = $busca;
                $params[] = $busca;
                $params[] = $busca;
            }
            
            $orderBy = $filtros['order_by'] ?? 'c.data_criacao DESC';
            
            $sql = "SELECT c.*, 
                           COALESCE(usos.total_usos, 0) as total_usos,
                           COALESCE(usos.valor_total_descontos, 0) as valor_total_descontos
                    FROM cupons c
                    LEFT JOIN (
                        SELECT cupom_id, 
                               COUNT(*) as total_usos,
                               SUM(valor_desconto) as valor_total_descontos
                        FROM cupom_usos 
                        GROUP BY cupom_id
                    ) usos ON c.id = usos.cupom_id
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY {$orderBy}";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Buscar cupom por ID
     */
    public function buscarPorId($id) {
        try {
            $sql = "SELECT c.*, 
                           COALESCE(usos.total_usos, 0) as total_usos,
                           COALESCE(usos.valor_total_descontos, 0) as valor_total_descontos
                    FROM cupons c
                    LEFT JOIN (
                        SELECT cupom_id, 
                               COUNT(*) as total_usos,
                               SUM(valor_desconto) as valor_total_descontos
                        FROM cupom_usos 
                        GROUP BY cupom_id
                    ) usos ON c.id = usos.cupom_id
                    WHERE c.id = ?";
            
            return $this->db->fetch($sql, [$id]);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Buscar cupom por código
     */
    public function buscarPorCodigo($codigo) {
        try {
            $sql = "SELECT c.*, 
                           COALESCE(usos.total_usos, 0) as total_usos,
                           COALESCE(usos.valor_total_descontos, 0) as valor_total_descontos
                    FROM cupons c
                    LEFT JOIN (
                        SELECT cupom_id, 
                               COUNT(*) as total_usos,
                               SUM(valor_desconto) as valor_total_descontos
                        FROM cupom_usos 
                        GROUP BY cupom_id
                    ) usos ON c.id = usos.cupom_id
                    WHERE c.codigo = ?";
            
            return $this->db->fetch($sql, [strtoupper($codigo)]);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Atualizar cupom
     */
    public function atualizar($id, $dados) {
        try {
            $cupom = $this->buscarPorId($id);
            if (!$cupom) {
                return [
                    'success' => false,
                    'message' => 'Cupom não encontrado'
                ];
            }
            
            // Verificar se código já existe (exceto para o próprio cupom)
            if (!empty($dados['codigo']) && strtoupper($dados['codigo']) !== $cupom['codigo']) {
                if ($this->codigoExists($dados['codigo'], $id)) {
                    return [
                        'success' => false,
                        'message' => 'Código do cupom já existe'
                    ];
                }
            }
            
            // Preparar dados para atualização
            $campos = [];
            $valores = [];
            
            $camposPermitidos = [
                'codigo', 'nome', 'descricao', 'tipo_desconto', 'valor_desconto',
                'valor_minimo_pedido', 'valor_maximo_desconto', 'limite_uso_total',
                'limite_uso_cliente', 'data_inicio', 'data_fim', 'ativo',
                'primeira_compra_apenas', 'categorias_permitidas', 'produtos_permitidos',
                'clientes_permitidos'
            ];
            
            foreach ($camposPermitidos as $campo) {
                if (isset($dados[$campo])) {
                    if ($campo === 'codigo') {
                        $campos[] = "{$campo} = ?";
                        $valores[] = strtoupper(trim($dados[$campo]));
                    } elseif (in_array($campo, ['categorias_permitidas', 'produtos_permitidos', 'clientes_permitidos'])) {
                        $campos[] = "{$campo} = ?";
                        $valores[] = !empty($dados[$campo]) ? json_encode($dados[$campo]) : null;
                    } else {
                        $campos[] = "{$campo} = ?";
                        $valores[] = $dados[$campo];
                    }
                }
            }
            
            if (empty($campos)) {
                return [
                    'success' => false,
                    'message' => 'Nenhum campo para atualizar'
                ];
            }
            
            // Adicionar data de atualização
            $campos[] = "data_atualizacao = ?";
            $valores[] = date('Y-m-d H:i:s');
            $valores[] = $id;
            
            $sql = "UPDATE cupons SET " . implode(', ', $campos) . " WHERE id = ?";
            $this->db->execute($sql, $valores);
            
            return [
                'success' => true,
                'message' => 'Cupom atualizado com sucesso'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao atualizar cupom: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Excluir cupom
     */
    public function excluir($id) {
        try {
            $cupom = $this->buscarPorId($id);
            if (!$cupom) {
                return [
                    'success' => false,
                    'message' => 'Cupom não encontrado'
                ];
            }
            
            // Verificar se tem usos
            if ($cupom['total_usos'] > 0) {
                return [
                    'success' => false,
                    'message' => 'Não é possível excluir cupom que já foi usado'
                ];
            }
            
            $this->db->execute("DELETE FROM cupons WHERE id = ?", [$id]);
            
            return [
                'success' => true,
                'message' => 'Cupom excluído com sucesso'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao excluir cupom: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obter relatório de cupons
     */
    public function obterRelatorio($periodo = '30d') {
        try {
            $dataInicio = $this->calcularDataInicio($periodo);
            $dataFim = date('Y-m-d 23:59:59');
            
            // Cupons mais usados
            $maisUsados = $this->db->fetchAll("
                SELECT c.codigo, c.nome, c.tipo_desconto,
                       COUNT(cu.id) as total_usos,
                       SUM(cu.valor_desconto) as valor_total_descontos
                FROM cupons c
                INNER JOIN cupom_usos cu ON c.id = cu.cupom_id
                WHERE cu.data_uso BETWEEN ? AND ?
                GROUP BY c.id, c.codigo, c.nome, c.tipo_desconto
                ORDER BY total_usos DESC
                LIMIT 10
            ", [$dataInicio, $dataFim]);
            
            // Estatísticas gerais
            $stats = $this->db->fetch("
                SELECT 
                    COUNT(DISTINCT c.id) as total_cupons_ativos,
                    COUNT(cu.id) as total_usos,
                    SUM(cu.valor_desconto) as valor_total_descontos,
                    AVG(cu.valor_desconto) as desconto_medio
                FROM cupons c
                LEFT JOIN cupom_usos cu ON c.id = cu.cupom_id 
                    AND cu.data_uso BETWEEN ? AND ?
                WHERE c.ativo = 1
            ", [$dataInicio, $dataFim]);
            
            // Cupons por tipo
            $porTipo = $this->db->fetchAll("
                SELECT 
                    c.tipo_desconto,
                    COUNT(DISTINCT c.id) as total_cupons,
                    COUNT(cu.id) as total_usos,
                    SUM(cu.valor_desconto) as valor_descontos
                FROM cupons c
                LEFT JOIN cupom_usos cu ON c.id = cu.cupom_id 
                    AND cu.data_uso BETWEEN ? AND ?
                WHERE c.ativo = 1
                GROUP BY c.tipo_desconto
                ORDER BY total_usos DESC
            ", [$dataInicio, $dataFim]);
            
            return [
                'success' => true,
                'data' => [
                    'periodo' => $periodo,
                    'data_inicio' => $dataInicio,
                    'data_fim' => $dataFim,
                    'estatisticas' => $stats,
                    'mais_usados' => $maisUsados,
                    'por_tipo' => $porTipo
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao gerar relatório: ' . $e->getMessage()
            ];
        }
    }
    
    // Métodos auxiliares
    
    private function codigoExists($codigo, $excludeId = null) {
        $sql = "SELECT id FROM cupons WHERE codigo = ?";
        $params = [strtoupper($codigo)];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return !empty($result);
    }
    
    private function contarUsosCliente($cupomId, $clienteId) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as total FROM cupom_usos WHERE cupom_id = ? AND cliente_id = ?",
            [$cupomId, $clienteId]
        );
        
        return (int)($result['total'] ?? 0);
    }
    
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
    
    /**
     * Gerar código aleatório para cupom
     */
    public function gerarCodigo($prefixo = 'PELUCIA', $tamanho = 8) {
        $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $codigo = $prefixo;
        
        for ($i = 0; $i < $tamanho; $i++) {
            $codigo .= $caracteres[rand(0, strlen($caracteres) - 1)];
        }
        
        // Verificar se já existe
        if ($this->codigoExists($codigo)) {
            return $this->gerarCodigo($prefixo, $tamanho);
        }
        
        return $codigo;
    }
}
?>

