<?php
/**
 * Classe Avaliacao - Sistema de Reviews v2.2
 * Gerencia avaliações e reviews de produtos
 */

class Avaliacao {
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = new Database();
        $this->config = [
            'max_avaliacoes_por_produto_por_usuario' => 1,
            'tempo_edicao_avaliacao' => 24 * 60 * 60, // 24 horas
            'moderacao_automatica' => true,
            'palavras_proibidas' => ['spam', 'fake', 'golpe'],
            'min_caracteres_comentario' => 10,
            'max_caracteres_comentario' => 1000
        ];
    }
    
    /**
     * Criar nova avaliação
     */
    public function criarAvaliacao($dados) {
        try {
            // Validar dados
            $validacao = $this->validarDadosAvaliacao($dados);
            if (!$validacao['valido']) {
                return ['sucesso' => false, 'erro' => $validacao['erro']];
            }
            
            // Verificar se usuário já avaliou este produto
            if ($this->usuarioJaAvaliou($dados['produto_id'], $dados['cliente_id'])) {
                return ['sucesso' => false, 'erro' => 'Você já avaliou este produto'];
            }
            
            // Verificar se cliente comprou o produto
            if (!$this->clienteComprouProduto($dados['produto_id'], $dados['cliente_id'])) {
                return ['sucesso' => false, 'erro' => 'Apenas clientes que compraram podem avaliar'];
            }
            
            $conn = $this->db->getConnection();
            
            // Inserir avaliação
            $sql = "INSERT INTO avaliacoes (
                produto_id, cliente_id, nota, titulo, comentario, 
                recomenda, anonimo, status, ip_cliente, user_agent
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $status = $this->config['moderacao_automatica'] ? 'pendente' : 'aprovada';
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $dados['produto_id'],
                $dados['cliente_id'],
                $dados['nota'],
                $dados['titulo'],
                $dados['comentario'],
                $dados['recomenda'] ?? 1,
                $dados['anonimo'] ?? 0,
                $status,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            $avaliacaoId = $conn->lastInsertId();
            
            // Processar imagens se houver
            if (!empty($dados['imagens'])) {
                $this->processarImagensAvaliacao($avaliacaoId, $dados['imagens']);
            }
            
            // Atualizar estatísticas do produto
            $this->atualizarEstatisticasProduto($dados['produto_id']);
            
            // Enviar notificação para moderação se necessário
            if ($status === 'pendente') {
                $this->notificarModeracaoNecessaria($avaliacaoId);
            }
            
            // Log da ação
            $this->registrarLog('avaliacao_criada', $avaliacaoId, $dados);
            
            return [
                'sucesso' => true,
                'avaliacao_id' => $avaliacaoId,
                'status' => $status,
                'mensagem' => $status === 'pendente' ? 
                    'Avaliação enviada para moderação' : 
                    'Avaliação publicada com sucesso'
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao criar avaliação: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro interno do servidor'];
        }
    }
    
    /**
     * Listar avaliações de um produto
     */
    public function listarAvaliacoesProduto($produtoId, $filtros = []) {
        try {
            $conn = $this->db->getConnection();
            
            $where = ["a.produto_id = ?", "a.status = 'aprovada'"];
            $params = [$produtoId];
            
            // Filtros opcionais
            if (!empty($filtros['nota'])) {
                $where[] = "a.nota = ?";
                $params[] = $filtros['nota'];
            }
            
            if (!empty($filtros['com_comentario'])) {
                $where[] = "a.comentario IS NOT NULL AND a.comentario != ''";
            }
            
            if (!empty($filtros['com_imagens'])) {
                $where[] = "EXISTS (SELECT 1 FROM avaliacao_imagens ai WHERE ai.avaliacao_id = a.id)";
            }
            
            // Ordenação
            $orderBy = "a.criado_em DESC";
            if (!empty($filtros['ordenacao'])) {
                switch ($filtros['ordenacao']) {
                    case 'nota_alta':
                        $orderBy = "a.nota DESC, a.criado_em DESC";
                        break;
                    case 'nota_baixa':
                        $orderBy = "a.nota ASC, a.criado_em DESC";
                        break;
                    case 'mais_uteis':
                        $orderBy = "a.votos_uteis DESC, a.criado_em DESC";
                        break;
                    case 'mais_antigas':
                        $orderBy = "a.criado_em ASC";
                        break;
                }
            }
            
            // Paginação
            $limite = $filtros['limite'] ?? 10;
            $offset = ($filtros['pagina'] ?? 1 - 1) * $limite;
            
            $sql = "SELECT 
                a.id, a.nota, a.titulo, a.comentario, a.recomenda, 
                a.anonimo, a.criado_em, a.votos_uteis, a.votos_nao_uteis,
                CASE 
                    WHEN a.anonimo = 1 THEN 'Cliente Anônimo'
                    ELSE c.nome 
                END as nome_cliente,
                c.numero_pedidos,
                (SELECT COUNT(*) FROM avaliacao_imagens ai WHERE ai.avaliacao_id = a.id) as total_imagens
            FROM avaliacoes a
            LEFT JOIN clientes c ON a.cliente_id = c.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY {$orderBy}
            LIMIT {$limite} OFFSET {$offset}";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $avaliacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Buscar imagens para cada avaliação
            foreach ($avaliacoes as &$avaliacao) {
                $avaliacao['imagens'] = $this->buscarImagensAvaliacao($avaliacao['id']);
                $avaliacao['tempo_relativo'] = $this->calcularTempoRelativo($avaliacao['criado_em']);
            }
            
            // Contar total para paginação
            $sqlCount = "SELECT COUNT(*) FROM avaliacoes a WHERE " . implode(' AND ', $where);
            $stmtCount = $conn->prepare($sqlCount);
            $stmtCount->execute($params);
            $total = $stmtCount->fetchColumn();
            
            return [
                'sucesso' => true,
                'avaliacoes' => $avaliacoes,
                'total' => $total,
                'pagina_atual' => $filtros['pagina'] ?? 1,
                'total_paginas' => ceil($total / $limite)
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao listar avaliações: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro ao carregar avaliações'];
        }
    }
    
    /**
     * Obter estatísticas de avaliações de um produto
     */
    public function obterEstatisticasProduto($produtoId) {
        try {
            $conn = $this->db->getConnection();
            
            $sql = "SELECT 
                COUNT(*) as total_avaliacoes,
                AVG(nota) as nota_media,
                SUM(CASE WHEN nota = 5 THEN 1 ELSE 0 END) as nota_5,
                SUM(CASE WHEN nota = 4 THEN 1 ELSE 0 END) as nota_4,
                SUM(CASE WHEN nota = 3 THEN 1 ELSE 0 END) as nota_3,
                SUM(CASE WHEN nota = 2 THEN 1 ELSE 0 END) as nota_2,
                SUM(CASE WHEN nota = 1 THEN 1 ELSE 0 END) as nota_1,
                SUM(CASE WHEN recomenda = 1 THEN 1 ELSE 0 END) as total_recomendacoes,
                COUNT(CASE WHEN comentario IS NOT NULL AND comentario != '' THEN 1 END) as total_comentarios
            FROM avaliacoes 
            WHERE produto_id = ? AND status = 'aprovada'";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$produtoId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calcular percentuais
            if ($stats['total_avaliacoes'] > 0) {
                $stats['percentual_recomendacao'] = round(($stats['total_recomendacoes'] / $stats['total_avaliacoes']) * 100, 1);
                $stats['nota_media'] = round($stats['nota_media'], 1);
                
                for ($i = 1; $i <= 5; $i++) {
                    $stats["percentual_nota_{$i}"] = round(($stats["nota_{$i}"] / $stats['total_avaliacoes']) * 100, 1);
                }
            } else {
                $stats['percentual_recomendacao'] = 0;
                $stats['nota_media'] = 0;
                for ($i = 1; $i <= 5; $i++) {
                    $stats["percentual_nota_{$i}"] = 0;
                }
            }
            
            return ['sucesso' => true, 'estatisticas' => $stats];
            
        } catch (Exception $e) {
            error_log("Erro ao obter estatísticas: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro ao carregar estatísticas'];
        }
    }
    
    /**
     * Votar se avaliação foi útil
     */
    public function votarUtilidadeAvaliacao($avaliacaoId, $clienteId, $util) {
        try {
            $conn = $this->db->getConnection();
            
            // Verificar se já votou
            $sql = "SELECT id, util FROM avaliacao_votos WHERE avaliacao_id = ? AND cliente_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$avaliacaoId, $clienteId]);
            $votoExistente = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($votoExistente) {
                if ($votoExistente['util'] == $util) {
                    return ['sucesso' => false, 'erro' => 'Você já votou desta forma'];
                }
                
                // Atualizar voto existente
                $sql = "UPDATE avaliacao_votos SET util = ?, atualizado_em = NOW() WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$util, $votoExistente['id']]);
            } else {
                // Criar novo voto
                $sql = "INSERT INTO avaliacao_votos (avaliacao_id, cliente_id, util) VALUES (?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$avaliacaoId, $clienteId, $util]);
            }
            
            // Atualizar contadores na avaliação
            $this->atualizarContadoresVotos($avaliacaoId);
            
            return ['sucesso' => true, 'mensagem' => 'Voto registrado com sucesso'];
            
        } catch (Exception $e) {
            error_log("Erro ao votar utilidade: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro ao registrar voto'];
        }
    }
    
    /**
     * Moderar avaliação (admin)
     */
    public function moderarAvaliacao($avaliacaoId, $acao, $motivo = '') {
        try {
            $conn = $this->db->getConnection();
            
            $statusPermitidos = ['aprovada', 'rejeitada', 'pendente'];
            if (!in_array($acao, $statusPermitidos)) {
                return ['sucesso' => false, 'erro' => 'Ação inválida'];
            }
            
            $sql = "UPDATE avaliacoes SET 
                status = ?, 
                moderado_em = NOW(), 
                motivo_moderacao = ?
            WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$acao, $motivo, $avaliacaoId]);
            
            if ($stmt->rowCount() > 0) {
                // Se aprovada, atualizar estatísticas do produto
                if ($acao === 'aprovada') {
                    $sql = "SELECT produto_id FROM avaliacoes WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$avaliacaoId]);
                    $produtoId = $stmt->fetchColumn();
                    
                    if ($produtoId) {
                        $this->atualizarEstatisticasProduto($produtoId);
                    }
                }
                
                // Log da moderação
                $this->registrarLog('avaliacao_moderada', $avaliacaoId, [
                    'acao' => $acao,
                    'motivo' => $motivo
                ]);
                
                return ['sucesso' => true, 'mensagem' => 'Avaliação moderada com sucesso'];
            }
            
            return ['sucesso' => false, 'erro' => 'Avaliação não encontrada'];
            
        } catch (Exception $e) {
            error_log("Erro ao moderar avaliação: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro ao moderar avaliação'];
        }
    }
    
    /**
     * Métodos auxiliares
     */
    
    private function validarDadosAvaliacao($dados) {
        if (empty($dados['produto_id']) || !is_numeric($dados['produto_id'])) {
            return ['valido' => false, 'erro' => 'ID do produto inválido'];
        }
        
        if (empty($dados['cliente_id']) || !is_numeric($dados['cliente_id'])) {
            return ['valido' => false, 'erro' => 'ID do cliente inválido'];
        }
        
        if (empty($dados['nota']) || !in_array($dados['nota'], [1, 2, 3, 4, 5])) {
            return ['valido' => false, 'erro' => 'Nota deve ser entre 1 e 5'];
        }
        
        if (!empty($dados['comentario'])) {
            $comentario = trim($dados['comentario']);
            if (strlen($comentario) < $this->config['min_caracteres_comentario']) {
                return ['valido' => false, 'erro' => 'Comentário muito curto'];
            }
            if (strlen($comentario) > $this->config['max_caracteres_comentario']) {
                return ['valido' => false, 'erro' => 'Comentário muito longo'];
            }
            
            // Verificar palavras proibidas
            foreach ($this->config['palavras_proibidas'] as $palavra) {
                if (stripos($comentario, $palavra) !== false) {
                    return ['valido' => false, 'erro' => 'Comentário contém conteúdo inadequado'];
                }
            }
        }
        
        return ['valido' => true];
    }
    
    private function usuarioJaAvaliou($produtoId, $clienteId) {
        $conn = $this->db->getConnection();
        $sql = "SELECT COUNT(*) FROM avaliacoes WHERE produto_id = ? AND cliente_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$produtoId, $clienteId]);
        return $stmt->fetchColumn() > 0;
    }
    
    private function clienteComprouProduto($produtoId, $clienteId) {
        $conn = $this->db->getConnection();
        $sql = "SELECT COUNT(*) FROM pedido_itens pi 
                JOIN pedidos p ON pi.pedido_id = p.id 
                WHERE pi.produto_id = ? AND p.cliente_id = ? AND p.status = 'entregue'";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$produtoId, $clienteId]);
        return $stmt->fetchColumn() > 0;
    }
    
    private function processarImagensAvaliacao($avaliacaoId, $imagens) {
        // Implementar upload e processamento de imagens
        // Similar ao sistema de upload de produtos
    }
    
    private function atualizarEstatisticasProduto($produtoId) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE produtos SET 
            nota_media = (
                SELECT AVG(nota) FROM avaliacoes 
                WHERE produto_id = ? AND status = 'aprovada'
            ),
            total_avaliacoes = (
                SELECT COUNT(*) FROM avaliacoes 
                WHERE produto_id = ? AND status = 'aprovada'
            )
        WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$produtoId, $produtoId, $produtoId]);
    }
    
    private function atualizarContadoresVotos($avaliacaoId) {
        $conn = $this->db->getConnection();
        
        $sql = "UPDATE avaliacoes SET 
            votos_uteis = (
                SELECT COUNT(*) FROM avaliacao_votos 
                WHERE avaliacao_id = ? AND util = 1
            ),
            votos_nao_uteis = (
                SELECT COUNT(*) FROM avaliacao_votos 
                WHERE avaliacao_id = ? AND util = 0
            )
        WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([$avaliacaoId, $avaliacaoId, $avaliacaoId]);
    }
    
    private function buscarImagensAvaliacao($avaliacaoId) {
        $conn = $this->db->getConnection();
        $sql = "SELECT caminho, alt_text FROM avaliacao_imagens WHERE avaliacao_id = ? ORDER BY ordem";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$avaliacaoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function calcularTempoRelativo($dataHora) {
        $agora = new DateTime();
        $data = new DateTime($dataHora);
        $diff = $agora->diff($data);
        
        if ($diff->days > 30) {
            return $data->format('d/m/Y');
        } elseif ($diff->days > 0) {
            return $diff->days . ' dia' . ($diff->days > 1 ? 's' : '') . ' atrás';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hora' . ($diff->h > 1 ? 's' : '') . ' atrás';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '') . ' atrás';
        } else {
            return 'Agora mesmo';
        }
    }
    
    private function notificarModeracaoNecessaria($avaliacaoId) {
        // Implementar notificação para moderadores
        // Email, webhook, etc.
    }
    
    private function registrarLog($acao, $avaliacaoId, $dados) {
        $conn = $this->db->getConnection();
        $sql = "INSERT INTO logs_sistema (acao, tabela, registro_id, dados_novos, ip, user_agent) 
                VALUES (?, 'avaliacoes', ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $acao,
            $avaliacaoId,
            json_encode($dados),
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
}
?>

