<?php
/**
 * Classe Chat - Sistema de Chat Online v2.2
 * Gerencia conversas em tempo real entre clientes e atendentes
 */

class Chat {
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = new Database();
        $this->config = [
            'max_mensagens_por_conversa' => 1000,
            'tempo_timeout_conversa' => 30 * 60, // 30 minutos
            'max_arquivos_por_mensagem' => 5,
            'tamanho_max_arquivo' => 10 * 1024 * 1024, // 10MB
            'tipos_arquivo_permitidos' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
            'horario_atendimento' => [
                'inicio' => '08:00',
                'fim' => '18:00',
                'dias_semana' => [1, 2, 3, 4, 5, 6] // Segunda a sábado
            ]
        ];
    }
    
    /**
     * Iniciar nova conversa
     */
    public function iniciarConversa($dados) {
        try {
            // Validar dados
            if (empty($dados['cliente_id']) && empty($dados['email_visitante'])) {
                return ['sucesso' => false, 'erro' => 'Cliente ou email do visitante obrigatório'];
            }
            
            $conn = $this->db->getConnection();
            
            // Verificar se já existe conversa ativa para este cliente
            if (!empty($dados['cliente_id'])) {
                $conversaExistente = $this->buscarConversaAtiva($dados['cliente_id']);
                if ($conversaExistente) {
                    return [
                        'sucesso' => true,
                        'conversa_id' => $conversaExistente['id'],
                        'mensagem' => 'Conversa existente retomada'
                    ];
                }
            }
            
            // Criar nova conversa
            $sql = "INSERT INTO chat_conversas (
                cliente_id, email_visitante, nome_visitante, 
                assunto, departamento, prioridade, status, 
                ip_cliente, user_agent, canal_origem
            ) VALUES (?, ?, ?, ?, ?, ?, 'aguardando', ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $dados['cliente_id'] ?? null,
                $dados['email_visitante'] ?? null,
                $dados['nome_visitante'] ?? null,
                $dados['assunto'] ?? 'Atendimento Geral',
                $dados['departamento'] ?? 'vendas',
                $dados['prioridade'] ?? 'normal',
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? '',
                $dados['canal'] ?? 'website'
            ]);
            
            $conversaId = $conn->lastInsertId();
            
            // Enviar mensagem de boas-vindas automática
            $this->enviarMensagemSistema($conversaId, $this->getMensagemBoasVindas());
            
            // Notificar atendentes disponíveis
            $this->notificarAtendentesDisponiveis($conversaId);
            
            // Log da ação
            $this->registrarLog('conversa_iniciada', $conversaId, $dados);
            
            return [
                'sucesso' => true,
                'conversa_id' => $conversaId,
                'mensagem' => 'Conversa iniciada com sucesso'
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao iniciar conversa: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro interno do servidor'];
        }
    }
    
    /**
     * Enviar mensagem
     */
    public function enviarMensagem($dados) {
        try {
            // Validar dados
            $validacao = $this->validarDadosMensagem($dados);
            if (!$validacao['valido']) {
                return ['sucesso' => false, 'erro' => $validacao['erro']];
            }
            
            $conn = $this->db->getConnection();
            
            // Verificar se conversa existe e está ativa
            $conversa = $this->buscarConversa($dados['conversa_id']);
            if (!$conversa) {
                return ['sucesso' => false, 'erro' => 'Conversa não encontrada'];
            }
            
            if ($conversa['status'] === 'finalizada') {
                return ['sucesso' => false, 'erro' => 'Conversa já foi finalizada'];
            }
            
            // Inserir mensagem
            $sql = "INSERT INTO chat_mensagens (
                conversa_id, remetente_tipo, remetente_id, 
                conteudo, tipo_mensagem, metadata
            ) VALUES (?, ?, ?, ?, ?, ?)";
            
            $metadata = json_encode([
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'timestamp_cliente' => $dados['timestamp'] ?? time()
            ]);
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                $dados['conversa_id'],
                $dados['remetente_tipo'], // 'cliente', 'atendente', 'sistema'
                $dados['remetente_id'],
                $dados['conteudo'],
                $dados['tipo'] ?? 'texto', // 'texto', 'arquivo', 'imagem', 'sistema'
                $metadata
            ]);
            
            $mensagemId = $conn->lastInsertId();
            
            // Processar arquivos se houver
            if (!empty($dados['arquivos'])) {
                $this->processarArquivosMensagem($mensagemId, $dados['arquivos']);
            }
            
            // Atualizar timestamp da conversa
            $this->atualizarTimestampConversa($dados['conversa_id']);
            
            // Marcar conversa como ativa se estava aguardando
            if ($conversa['status'] === 'aguardando') {
                $this->atualizarStatusConversa($dados['conversa_id'], 'ativa');
            }
            
            // Notificar outros participantes via WebSocket
            $this->notificarNovaMensagem($dados['conversa_id'], $mensagemId);
            
            // Enviar notificação push se necessário
            if ($dados['remetente_tipo'] === 'cliente') {
                $this->notificarAtendentes($dados['conversa_id'], $mensagemId);
            } else {
                $this->notificarCliente($dados['conversa_id'], $mensagemId);
            }
            
            return [
                'sucesso' => true,
                'mensagem_id' => $mensagemId,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao enviar mensagem: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro ao enviar mensagem'];
        }
    }
    
    /**
     * Buscar mensagens de uma conversa
     */
    public function buscarMensagens($conversaId, $filtros = []) {
        try {
            $conn = $this->db->getConnection();
            
            $limite = $filtros['limite'] ?? 50;
            $offset = ($filtros['pagina'] ?? 1 - 1) * $limite;
            $ultimaId = $filtros['ultima_id'] ?? null;
            
            $where = ["m.conversa_id = ?"];
            $params = [$conversaId];
            
            // Buscar apenas mensagens mais recentes que a última ID
            if ($ultimaId) {
                $where[] = "m.id > ?";
                $params[] = $ultimaId;
            }
            
            $sql = "SELECT 
                m.id, m.remetente_tipo, m.remetente_id, m.conteudo, 
                m.tipo_mensagem, m.criado_em, m.lida_em,
                CASE 
                    WHEN m.remetente_tipo = 'cliente' THEN c.nome
                    WHEN m.remetente_tipo = 'atendente' THEN u.nome
                    ELSE 'Sistema'
                END as nome_remetente,
                (SELECT COUNT(*) FROM chat_arquivos ca WHERE ca.mensagem_id = m.id) as total_arquivos
            FROM chat_mensagens m
            LEFT JOIN clientes c ON m.remetente_tipo = 'cliente' AND m.remetente_id = c.id
            LEFT JOIN usuarios u ON m.remetente_tipo = 'atendente' AND m.remetente_id = u.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY m.criado_em ASC
            LIMIT {$limite} OFFSET {$offset}";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $mensagens = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Buscar arquivos para cada mensagem
            foreach ($mensagens as &$mensagem) {
                if ($mensagem['total_arquivos'] > 0) {
                    $mensagem['arquivos'] = $this->buscarArquivosMensagem($mensagem['id']);
                }
                $mensagem['tempo_relativo'] = $this->calcularTempoRelativo($mensagem['criado_em']);
            }
            
            return [
                'sucesso' => true,
                'mensagens' => $mensagens,
                'total' => count($mensagens)
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao buscar mensagens: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro ao carregar mensagens'];
        }
    }
    
    /**
     * Atribuir conversa a atendente
     */
    public function atribuirConversa($conversaId, $atendenteId) {
        try {
            $conn = $this->db->getConnection();
            
            $sql = "UPDATE chat_conversas SET 
                atendente_id = ?, 
                status = 'ativa', 
                atribuida_em = NOW()
            WHERE id = ? AND status IN ('aguardando', 'ativa')";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$atendenteId, $conversaId]);
            
            if ($stmt->rowCount() > 0) {
                // Enviar mensagem de sistema informando a atribuição
                $atendente = $this->buscarAtendente($atendenteId);
                $mensagem = "Você está sendo atendido por {$atendente['nome']}. Como posso ajudá-lo?";
                $this->enviarMensagemSistema($conversaId, $mensagem);
                
                // Notificar cliente sobre a atribuição
                $this->notificarAtribuicaoConversa($conversaId, $atendenteId);
                
                return ['sucesso' => true, 'mensagem' => 'Conversa atribuída com sucesso'];
            }
            
            return ['sucesso' => false, 'erro' => 'Não foi possível atribuir a conversa'];
            
        } catch (Exception $e) {
            error_log("Erro ao atribuir conversa: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro ao atribuir conversa'];
        }
    }
    
    /**
     * Finalizar conversa
     */
    public function finalizarConversa($conversaId, $atendenteId, $motivo = '') {
        try {
            $conn = $this->db->getConnection();
            
            $sql = "UPDATE chat_conversas SET 
                status = 'finalizada', 
                finalizada_em = NOW(), 
                finalizada_por = ?,
                motivo_finalizacao = ?
            WHERE id = ? AND status = 'ativa'";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute([$atendenteId, $motivo, $conversaId]);
            
            if ($stmt->rowCount() > 0) {
                // Enviar mensagem de sistema
                $this->enviarMensagemSistema($conversaId, 
                    "Conversa finalizada. Obrigado por entrar em contato conosco!");
                
                // Solicitar avaliação do atendimento
                $this->solicitarAvaliacaoAtendimento($conversaId);
                
                return ['sucesso' => true, 'mensagem' => 'Conversa finalizada com sucesso'];
            }
            
            return ['sucesso' => false, 'erro' => 'Conversa não encontrada ou já finalizada'];
            
        } catch (Exception $e) {
            error_log("Erro ao finalizar conversa: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro ao finalizar conversa'];
        }
    }
    
    /**
     * Listar conversas (para atendentes)
     */
    public function listarConversas($filtros = []) {
        try {
            $conn = $this->db->getConnection();
            
            $where = ["1=1"];
            $params = [];
            
            // Filtros
            if (!empty($filtros['status'])) {
                $where[] = "c.status = ?";
                $params[] = $filtros['status'];
            }
            
            if (!empty($filtros['atendente_id'])) {
                $where[] = "c.atendente_id = ?";
                $params[] = $filtros['atendente_id'];
            }
            
            if (!empty($filtros['departamento'])) {
                $where[] = "c.departamento = ?";
                $params[] = $filtros['departamento'];
            }
            
            // Paginação
            $limite = $filtros['limite'] ?? 20;
            $offset = ($filtros['pagina'] ?? 1 - 1) * $limite;
            
            $sql = "SELECT 
                c.id, c.assunto, c.status, c.prioridade, c.departamento,
                c.criado_em, c.atualizado_em, c.finalizada_em,
                COALESCE(cl.nome, c.nome_visitante) as nome_cliente,
                COALESCE(cl.email, c.email_visitante) as email_cliente,
                u.nome as nome_atendente,
                (SELECT COUNT(*) FROM chat_mensagens cm WHERE cm.conversa_id = c.id) as total_mensagens,
                (SELECT COUNT(*) FROM chat_mensagens cm WHERE cm.conversa_id = c.id AND cm.lida_em IS NULL AND cm.remetente_tipo = 'cliente') as mensagens_nao_lidas
            FROM chat_conversas c
            LEFT JOIN clientes cl ON c.cliente_id = cl.id
            LEFT JOIN usuarios u ON c.atendente_id = u.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY 
                CASE c.prioridade 
                    WHEN 'alta' THEN 1 
                    WHEN 'normal' THEN 2 
                    WHEN 'baixa' THEN 3 
                END,
                c.atualizado_em DESC
            LIMIT {$limite} OFFSET {$offset}";
            
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            $conversas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Adicionar informações extras
            foreach ($conversas as &$conversa) {
                $conversa['tempo_relativo'] = $this->calcularTempoRelativo($conversa['atualizado_em']);
                $conversa['tempo_espera'] = $this->calcularTempoEspera($conversa);
            }
            
            return [
                'sucesso' => true,
                'conversas' => $conversas,
                'total' => count($conversas)
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao listar conversas: " . $e->getMessage());
            return ['sucesso' => false, 'erro' => 'Erro ao carregar conversas'];
        }
    }
    
    /**
     * Métodos auxiliares
     */
    
    private function validarDadosMensagem($dados) {
        if (empty($dados['conversa_id'])) {
            return ['valido' => false, 'erro' => 'ID da conversa obrigatório'];
        }
        
        if (empty($dados['remetente_tipo']) || !in_array($dados['remetente_tipo'], ['cliente', 'atendente', 'sistema'])) {
            return ['valido' => false, 'erro' => 'Tipo de remetente inválido'];
        }
        
        if (empty($dados['conteudo']) && empty($dados['arquivos'])) {
            return ['valido' => false, 'erro' => 'Conteúdo ou arquivo obrigatório'];
        }
        
        if (!empty($dados['conteudo']) && strlen($dados['conteudo']) > 2000) {
            return ['valido' => false, 'erro' => 'Mensagem muito longa'];
        }
        
        return ['valido' => true];
    }
    
    private function buscarConversaAtiva($clienteId) {
        $conn = $this->db->getConnection();
        $sql = "SELECT id FROM chat_conversas 
                WHERE cliente_id = ? AND status IN ('aguardando', 'ativa') 
                ORDER BY criado_em DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$clienteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function buscarConversa($conversaId) {
        $conn = $this->db->getConnection();
        $sql = "SELECT * FROM chat_conversas WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$conversaId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function enviarMensagemSistema($conversaId, $conteudo) {
        $conn = $this->db->getConnection();
        $sql = "INSERT INTO chat_mensagens (conversa_id, remetente_tipo, conteudo, tipo_mensagem) 
                VALUES (?, 'sistema', ?, 'sistema')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$conversaId, $conteudo]);
    }
    
    private function getMensagemBoasVindas() {
        $horario = $this->verificarHorarioAtendimento();
        
        if ($horario['aberto']) {
            return "Olá! Bem-vindo ao atendimento da PelúciaPet. Em que posso ajudá-lo hoje?";
        } else {
            return "Olá! No momento estamos fora do horário de atendimento. Deixe sua mensagem que retornaremos em breve. Horário: {$horario['horario_texto']}";
        }
    }
    
    private function verificarHorarioAtendimento() {
        $agora = new DateTime();
        $diaAtual = (int)$agora->format('N'); // 1 = segunda, 7 = domingo
        $horaAtual = $agora->format('H:i');
        
        $config = $this->config['horario_atendimento'];
        
        $aberto = in_array($diaAtual, $config['dias_semana']) && 
                  $horaAtual >= $config['inicio'] && 
                  $horaAtual <= $config['fim'];
        
        return [
            'aberto' => $aberto,
            'horario_texto' => "Segunda a Sábado, {$config['inicio']} às {$config['fim']}"
        ];
    }
    
    private function atualizarTimestampConversa($conversaId) {
        $conn = $this->db->getConnection();
        $sql = "UPDATE chat_conversas SET atualizado_em = NOW() WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$conversaId]);
    }
    
    private function atualizarStatusConversa($conversaId, $status) {
        $conn = $this->db->getConnection();
        $sql = "UPDATE chat_conversas SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$status, $conversaId]);
    }
    
    private function notificarNovaMensagem($conversaId, $mensagemId) {
        // Implementar WebSocket ou Server-Sent Events
        // Para notificação em tempo real
    }
    
    private function notificarAtendentesDisponiveis($conversaId) {
        // Notificar atendentes online sobre nova conversa
    }
    
    private function notificarAtendentes($conversaId, $mensagemId) {
        // Notificar atendente responsável sobre nova mensagem do cliente
    }
    
    private function notificarCliente($conversaId, $mensagemId) {
        // Notificar cliente sobre resposta do atendente
    }
    
    private function processarArquivosMensagem($mensagemId, $arquivos) {
        // Implementar upload e processamento de arquivos
    }
    
    private function buscarArquivosMensagem($mensagemId) {
        $conn = $this->db->getConnection();
        $sql = "SELECT nome_arquivo, caminho, tamanho, tipo_mime FROM chat_arquivos WHERE mensagem_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$mensagemId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    private function buscarAtendente($atendenteId) {
        $conn = $this->db->getConnection();
        $sql = "SELECT nome FROM usuarios WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$atendenteId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function calcularTempoRelativo($dataHora) {
        $agora = new DateTime();
        $data = new DateTime($dataHora);
        $diff = $agora->diff($data);
        
        if ($diff->days > 0) {
            return $diff->days . ' dia' . ($diff->days > 1 ? 's' : '') . ' atrás';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hora' . ($diff->h > 1 ? 's' : '') . ' atrás';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '') . ' atrás';
        } else {
            return 'Agora mesmo';
        }
    }
    
    private function calcularTempoEspera($conversa) {
        if ($conversa['status'] === 'aguardando') {
            $criado = new DateTime($conversa['criado_em']);
            $agora = new DateTime();
            $diff = $agora->diff($criado);
            
            if ($diff->h > 0) {
                return $diff->h . 'h ' . $diff->i . 'm';
            } else {
                return $diff->i . 'm';
            }
        }
        return null;
    }
    
    private function solicitarAvaliacaoAtendimento($conversaId) {
        // Implementar solicitação de avaliação do atendimento
    }
    
    private function registrarLog($acao, $conversaId, $dados) {
        $conn = $this->db->getConnection();
        $sql = "INSERT INTO logs_sistema (acao, tabela, registro_id, dados_novos, ip, user_agent) 
                VALUES (?, 'chat_conversas', ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            $acao,
            $conversaId,
            json_encode($dados),
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
}
?>

