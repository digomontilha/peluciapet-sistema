<?php
/**
 * Classe MultiLoja - Sistema Multi-loja v2.2
 * Gerencia múltiplas lojas e franquias
 */

class MultiLoja {
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = new Database();
        $this->config = [
            'max_lojas_por_franquia' => 50,
            'tipos_loja' => ['matriz', 'filial', 'franquia', 'parceiro'],
            'permissoes_padrao' => [
                'matriz' => ['*'], // Todas as permissões
                'filial' => ['vendas', 'estoque', 'clientes', 'relatorios'],
                'franquia' => ['vendas', 'estoque', 'clientes', 'produtos_limitado'],
                'parceiro' => ['vendas', 'clientes']
            ],
            'comissoes_padrao' => [
                'franquia' => 0.15, // 15%
                'parceiro' => 0.10   // 10%
            ]
        ];
    }
    
    /**
     * Criar nova loja
     */
    public function criarLoja($dados) {
        try {
            // Validar dados
            $validacao = $this->validarDadosLoja($dados);
            if (!$validacao['valido']) {
                return ['sucesso' => false, 'erro' => $validacao['erro']];
            }
            
            $conn = $this->db->getConnection();
            
            // Verificar se CNPJ já existe
            if ($this->cnpjJaExiste($dados['cnpj'])) {
                return ['sucesso' => false, 'erro' => 'CNPJ já cadastrado'];
            }
            
            // Gerar código único da loja
            $codigoLoja = $this->gerarCodigoLoja($dados['tipo']);
            
            // Inserir loja
            $sql = "INSERT INTO lojas (
                codigo, nome, tipo, cnpj, email, telefone,
                endereco, cidade, estado, cep, pais,
                responsavel_nome, responsavel_email, responsavel_telefone,
                loja_pai_id, ativa, configuracoes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";
            
            $configuracoes = json_encode($this->getConfiguracoesPadrao($dados['tipo']));
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $codigoLoja,
                $dados['nome'],
                $dados['tipo'],
                $dados['cnpj'],
                $dados['email'],
                $dados['telefone'],
                $dados['endereco'],
                $dados['cidade'],
                $dados['estado'],
                $dados['cep'],
                $dados['pais'] ?? 'Brasil',
                $dados['responsavel_nome'],
                $dados['responsavel_email'],
                $dados['responsavel_telefone'],
                $dados['loja_pai_id'] ?? null,
                $configuracoes
            ]);
            
            $lojaId = $conn->lastInsertId();
            
            // Criar usuário administrador da loja
            $usuarioId = $this->criarUsuarioLoja($lojaId, $dados);
            
            // Configurar permissões padrão
            $this->configurarPermissoesLoja($lojaId, $dados['tipo']);
            
            // Configurar catálogo inicial
            $this->configurarCatalogoInicial($lojaId, $dados);
            
            // Configurar comissões se for franquia/parceiro
            if (in_array($dados['tipo'], ['franquia', 'parceiro'])) {
                $this->configurarComissoes($lojaId, $dados['tipo']);
            }
            
            // Log da criação
            $this->registrarLog('loja_criada', $lojaId, $dados);
            
            return [
                'sucesso' => true,
                'loja_id' => $lojaId,
                'codigo_loja' => $codigoLoja,
                'usuario_id' => $usuarioId,
                'mensagem' => 'Loja criada com sucesso'
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao criar loja: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro interno do servidor'];
        }
    }
    
    /**
     * Gerenciar estoque por loja
     */
    public function gerenciarEstoqueLoja($lojaId, $produtoId, $acao, $quantidade, $motivo = '') {
        try {
            $conn = $this->db->getConnection();
            
            // Verificar se loja tem permissão para gerenciar estoque
            if (!$this->verificarPermissaoLoja($lojaId, 'estoque')) {
                return ['sucesso' => false, 'erro' => 'Loja não tem permissão para gerenciar estoque'];
            }
            
            // Buscar estoque atual
            $estoqueAtual = $this->buscarEstoqueLoja($lojaId, $produtoId);
            
            switch ($acao) {
                case 'entrada':
                    $novoEstoque = $estoqueAtual + $quantidade;
                    break;
                case 'saida':
                    if ($estoqueAtual < $quantidade) {
                        return ['sucesso' => false, 'erro' => 'Estoque insuficiente'];
                    }
                    $novoEstoque = $estoqueAtual - $quantidade;
                    break;
                case 'ajuste':
                    $novoEstoque = $quantidade;
                    break;
                default:
                    return ['sucesso' => false, 'erro' => 'Ação inválida'];
            }
            
            // Atualizar estoque
            $sql = "INSERT INTO estoque_loja (loja_id, produto_id, quantidade) 
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE quantidade = VALUES(quantidade)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$lojaId, $produtoId, $novoEstoque]);
            
            // Registrar movimentação
            $this->registrarMovimentacaoEstoque($lojaId, $produtoId, $acao, $quantidade, $motivo);
            
            // Verificar estoque mínimo
            $this->verificarEstoqueMinimo($lojaId, $produtoId, $novoEstoque);
            
            return [
                'sucesso' => true,
                'estoque_anterior' => $estoqueAtual,
                'estoque_atual' => $novoEstoque,
                'mensagem' => 'Estoque atualizado com sucesso'
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao gerenciar estoque: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro ao atualizar estoque'];
        }
    }
    
    /**
     * Transferir estoque entre lojas
     */
    public function transferirEstoque($lojaOrigemId, $lojaDestinoId, $produtoId, $quantidade, $motivo = '') {
        try {
            $conn = $this->db->getConnection();
            $conn->beginTransaction();
            
            // Verificar permissões
            if (!$this->verificarPermissaoLoja($lojaOrigemId, 'transferencia_estoque')) {
                throw new Exception('Loja origem não tem permissão para transferir estoque');
            }
            
            // Verificar estoque disponível na origem
            $estoqueOrigemAtual = $this->buscarEstoqueLoja($lojaOrigemId, $produtoId);
            if ($estoqueOrigemAtual < $quantidade) {
                throw new Exception('Estoque insuficiente na loja origem');
            }
            
            // Realizar transferência
            $resultadoSaida = $this->gerenciarEstoqueLoja($lojaOrigemId, $produtoId, 'saida', $quantidade, "Transferência para loja {$lojaDestinoId}: {$motivo}");
            if (!$resultadoSaida['sucesso']) {
                throw new Exception($resultadoSaida['erro']);
            }
            
            $resultadoEntrada = $this->gerenciarEstoqueLoja($lojaDestinoId, $produtoId, 'entrada', $quantidade, "Transferência da loja {$lojaOrigemId}: {$motivo}");
            if (!$resultadoEntrada['sucesso']) {
                throw new Exception($resultadoEntrada['erro']);
            }
            
            // Registrar transferência
            $sql = "INSERT INTO transferencias_estoque (
                loja_origem_id, loja_destino_id, produto_id, quantidade, motivo, status
            ) VALUES (?, ?, ?, ?, ?, 'concluida')";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$lojaOrigemId, $lojaDestinoId, $produtoId, $quantidade, $motivo]);
            
            $transferencia_id = $conn->lastInsertId();
            
            $conn->commit();
            
            return [
                'sucesso' => true,
                'transferencia_id' => $transferencia_id,
                'mensagem' => 'Transferência realizada com sucesso'
            ];
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Erro na transferência: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }
    
    /**
     * Calcular comissões
     */
    public function calcularComissoes($lojaId, $periodo = '30d') {
        try {
            $conn = $this->db->getConnection();
            
            $loja = $this->buscarLoja($lojaId);
            if (!$loja || !in_array($loja['tipo'], ['franquia', 'parceiro'])) {
                return ['sucesso' => false, 'erro' => 'Loja não elegível para comissões'];
            }
            
            $dataInicio = date('Y-m-d', strtotime("-{$periodo}"));
            
            // Buscar vendas do período
            $sql = "SELECT 
                p.id as pedido_id,
                p.valor_total,
                p.valor_produtos,
                p.valor_frete,
                p.criado_em,
                COUNT(pi.id) as total_itens
            FROM pedidos p
            JOIN pedido_itens pi ON p.id = pi.pedido_id
            WHERE p.loja_id = ? 
            AND p.status = 'entregue'
            AND p.criado_em >= ?
            GROUP BY p.id";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$lojaId, $dataInicio]);
            $vendas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalVendas = 0;
            $totalComissao = 0;
            $percentualComissao = $this->config['comissoes_padrao'][$loja['tipo']];
            
            foreach ($vendas as $venda) {
                $valorComissionavel = $venda['valor_produtos']; // Não incluir frete na comissão
                $comissaoVenda = $valorComissionavel * $percentualComissao;
                
                $totalVendas += $valorComissionavel;
                $totalComissao += $comissaoVenda;
            }
            
            // Salvar cálculo de comissão
            $this->salvarCalculoComissao($lojaId, $periodo, $totalVendas, $totalComissao, $percentualComissao);
            
            return [
                'sucesso' => true,
                'periodo' => $periodo,
                'total_vendas' => $totalVendas,
                'percentual_comissao' => $percentualComissao * 100,
                'valor_comissao' => $totalComissao,
                'total_pedidos' => count($vendas)
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao calcular comissões: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro ao calcular comissões'];
        }
    }
    
    /**
     * Obter relatório consolidado de todas as lojas
     */
    public function obterRelatorioConsolidado($periodo = '30d') {
        try {
            $conn = $this->db->getConnection();
            
            $dataInicio = date('Y-m-d', strtotime("-{$periodo}"));
            
            $sql = "SELECT 
                l.id,
                l.codigo,
                l.nome,
                l.tipo,
                l.cidade,
                l.estado,
                COUNT(DISTINCT p.id) as total_pedidos,
                COALESCE(SUM(p.valor_total), 0) as valor_total_vendas,
                COALESCE(AVG(p.valor_total), 0) as ticket_medio,
                COUNT(DISTINCT c.id) as clientes_ativos,
                (SELECT COUNT(*) FROM estoque_loja el WHERE el.loja_id = l.id) as produtos_em_estoque
            FROM lojas l
            LEFT JOIN pedidos p ON l.id = p.loja_id AND p.criado_em >= ? AND p.status = 'entregue'
            LEFT JOIN clientes c ON l.id = c.loja_id AND c.ultima_compra >= ?
            WHERE l.ativa = 1
            GROUP BY l.id
            ORDER BY valor_total_vendas DESC";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$dataInicio, $dataInicio]);
            $lojas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular totais gerais
            $totaisGerais = [
                'total_lojas' => count($lojas),
                'total_pedidos' => array_sum(array_column($lojas, 'total_pedidos')),
                'valor_total_vendas' => array_sum(array_column($lojas, 'valor_total_vendas')),
                'clientes_ativos' => array_sum(array_column($lojas, 'clientes_ativos')),
                'produtos_em_estoque' => array_sum(array_column($lojas, 'produtos_em_estoque'))
            ];
            
            $totaisGerais['ticket_medio_geral'] = $totaisGerais['total_pedidos'] > 0 ? 
                $totaisGerais['valor_total_vendas'] / $totaisGerais['total_pedidos'] : 0;
            
            // Adicionar ranking de performance
            foreach ($lojas as &$loja) {
                $loja['performance'] = $this->calcularPerformanceLoja($loja);
            }
            
            return [
                'sucesso' => true,
                'periodo' => $periodo,
                'totais_gerais' => $totaisGerais,
                'lojas' => $lojas
            ];
            
        } catch (Exception $e) {
            error_log("Erro no relatório consolidado: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro ao gerar relatório'];
        }
    }
    
    /**
     * Configurar permissões específicas de uma loja
     */
    public function configurarPermissoesLoja($lojaId, $permissoes) {
        try {
            $conn = $this->db->getConnection();
            
            // Validar permissões
            $permissoesValidas = $this->validarPermissoes($permissoes);
            if (!$permissoesValidas) {
                return ['sucesso' => false, 'erro' => 'Permissões inválidas'];
            }
            
            // Atualizar permissões
            $sql = "UPDATE lojas SET permissoes = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([json_encode($permissoes), $lojaId]);
            
            // Log da alteração
            $this->registrarLog('permissoes_alteradas', $lojaId, ['permissoes' => $permissoes]);
            
            return [
                'sucesso' => true,
                'mensagem' => 'Permissões atualizadas com sucesso'
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao configurar permissões: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro ao atualizar permissões'];
        }
    }
    
    /**
     * Métodos auxiliares
     */
    
    private function validarDadosLoja($dados) {
        $camposObrigatorios = ['nome', 'tipo', 'cnpj', 'email', 'responsavel_nome', 'responsavel_email'];
        
        foreach ($camposObrigatorios as $campo) {
            if (empty($dados[$campo])) {
                return ['valido' => false, 'erro' => "Campo obrigatório: {$campo}"];
            }
        }
        
        if (!in_array($dados['tipo'], $this->config['tipos_loja'])) {
            return ['valido' => false, 'erro' => 'Tipo de loja inválido'];
        }
        
        if (!$this->validarCNPJ($dados['cnpj'])) {
            return ['valido' => false, 'erro' => 'CNPJ inválido'];
        }
        
        if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            return ['valido' => false, 'erro' => 'Email inválido'];
        }
        
        return ['valido' => true];
    }
    
    private function cnpjJaExiste($cnpj) {
        $conn = $this->db->getConnection();
        $sql = "SELECT COUNT(*) FROM lojas WHERE cnpj = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$cnpj]);
        return $stmt->fetchColumn() > 0;
    }
    
    private function gerarCodigoLoja($tipo) {
        $prefixos = [
            'matriz' => 'MTZ',
            'filial' => 'FIL',
            'franquia' => 'FRQ',
            'parceiro' => 'PRC'
        ];
        
        $prefixo = $prefixos[$tipo] ?? 'LJA';
        $numero = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        return $prefixo . $numero;
    }
    
    private function getConfiguracoesPadrao($tipo) {
        return [
            'tema' => 'peluciapet',
            'moeda' => 'BRL',
            'fuso_horario' => 'America/Sao_Paulo',
            'idioma' => 'pt-BR',
            'notificacoes_email' => true,
            'backup_automatico' => true,
            'permissoes' => $this->config['permissoes_padrao'][$tipo] ?? []
        ];
    }
    
    private function criarUsuarioLoja($lojaId, $dados) {
        $conn = $this->db->getConnection();
        
        $sql = "INSERT INTO usuarios (
            nome, email, senha, nivel, loja_id, ativo
        ) VALUES (?, ?, ?, 'gerente_loja', ?, 1)";
        
        $senhaHash = password_hash($dados['senha_inicial'] ?? 'peluciapet123', PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $dados['responsavel_nome'],
            $dados['responsavel_email'],
            $senhaHash,
            $lojaId
        ]);
        
        return $conn->lastInsertId();
    }
    
    private function configurarCatalogoInicial($lojaId, $dados) {
        // Configurar catálogo inicial baseado no tipo de loja
        if ($dados['tipo'] === 'franquia') {
            // Franquias herdam todo o catálogo da matriz
            $this->copiarCatalogoMatriz($lojaId);
        } elseif ($dados['tipo'] === 'parceiro') {
            // Parceiros têm catálogo limitado
            $this->configurarCatalogoLimitado($lojaId);
        }
    }
    
    private function copiarCatalogoMatriz($lojaId) {
        $conn = $this->db->getConnection();
        
        // Copiar produtos da matriz para a franquia
        $sql = "INSERT INTO estoque_loja (loja_id, produto_id, quantidade)
                SELECT ?, id, 0 FROM produtos WHERE ativo = 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$lojaId]);
    }
    
    private function configurarCatalogoLimitado($lojaId) {
        $conn = $this->db->getConnection();
        
        // Apenas produtos básicos para parceiros
        $sql = "INSERT INTO estoque_loja (loja_id, produto_id, quantidade)
                SELECT ?, id, 0 FROM produtos 
                WHERE ativo = 1 AND categoria_id IN (
                    SELECT id FROM categorias WHERE nome IN ('Caminhas', 'Roupinhas')
                )";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$lojaId]);
    }
    
    private function configurarComissoes($lojaId, $tipo) {
        $conn = $this->db->getConnection();
        
        $percentual = $this->config['comissoes_padrao'][$tipo];
        
        $sql = "INSERT INTO configuracao_comissoes (loja_id, tipo, percentual) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$lojaId, $tipo, $percentual]);
    }
    
    private function buscarEstoqueLoja($lojaId, $produtoId) {
        $conn = $this->db->getConnection();
        $sql = "SELECT COALESCE(quantidade, 0) FROM estoque_loja WHERE loja_id = ? AND produto_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$lojaId, $produtoId]);
        return $stmt->fetchColumn() ?: 0;
    }
    
    private function verificarPermissaoLoja($lojaId, $permissao) {
        $conn = $this->db->getConnection();
        $sql = "SELECT permissoes FROM lojas WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$lojaId]);
        $permissoes = json_decode($stmt->fetchColumn() ?: '[]', true);
        
        return in_array('*', $permissoes) || in_array($permissao, $permissoes);
    }
    
    private function registrarMovimentacaoEstoque($lojaId, $produtoId, $acao, $quantidade, $motivo) {
        $conn = $this->db->getConnection();
        $sql = "INSERT INTO movimentacao_estoque (loja_id, produto_id, tipo, quantidade, motivo) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$lojaId, $produtoId, $acao, $quantidade, $motivo]);
    }
    
    private function verificarEstoqueMinimo($lojaId, $produtoId, $estoqueAtual) {
        $conn = $this->db->getConnection();
        $sql = "SELECT estoque_minimo FROM produtos WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$produtoId]);
        $estoqueMinimo = $stmt->fetchColumn();
        
        if ($estoqueAtual <= $estoqueMinimo) {
            $this->notificarEstoqueBaixo($lojaId, $produtoId, $estoqueAtual, $estoqueMinimo);
        }
    }
    
    private function notificarEstoqueBaixo($lojaId, $produtoId, $estoqueAtual, $estoqueMinimo) {
        // Implementar notificação de estoque baixo
    }
    
    private function buscarLoja($lojaId) {
        $conn = $this->db->getConnection();
        $sql = "SELECT * FROM lojas WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$lojaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function salvarCalculoComissao($lojaId, $periodo, $totalVendas, $totalComissao, $percentual) {
        $conn = $this->db->getConnection();
        $sql = "INSERT INTO comissoes_calculadas (
            loja_id, periodo, total_vendas, percentual_comissao, valor_comissao, status
        ) VALUES (?, ?, ?, ?, ?, 'calculada')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$lojaId, $periodo, $totalVendas, $percentual, $totalComissao]);
    }
    
    private function calcularPerformanceLoja($loja) {
        // Calcular score de performance baseado em vendas, clientes, etc.
        $score = 0;
        
        if ($loja['total_pedidos'] > 0) $score += 25;
        if ($loja['valor_total_vendas'] > 1000) $score += 25;
        if ($loja['clientes_ativos'] > 10) $score += 25;
        if ($loja['ticket_medio'] > 100) $score += 25;
        
        return $score;
    }
    
    private function validarCNPJ($cnpj) {
        // Implementar validação de CNPJ
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        return strlen($cnpj) === 14;
    }
    
    private function validarPermissoes($permissoes) {
        $permissoesValidas = [
            'vendas', 'estoque', 'clientes', 'produtos', 'relatorios',
            'configuracoes', 'usuarios', 'transferencia_estoque', '*'
        ];
        
        foreach ($permissoes as $permissao) {
            if (!in_array($permissao, $permissoesValidas)) {
                return false;
            }
        }
        
        return true;
    }
    
    private function registrarLog($acao, $lojaId, $dados) {
        $conn = $this->db->getConnection();
        $sql = "INSERT INTO logs_sistema (acao, tabela, registro_id, dados_novos, ip, user_agent) 
                VALUES (?, 'lojas', ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $acao,
            $lojaId,
            json_encode($dados),
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
}
?>

