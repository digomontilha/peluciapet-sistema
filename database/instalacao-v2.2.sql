-- ========================================
-- SISTEMA PELUCIAPET v2.2 - BANCO DE DADOS
-- Instalação completa com todas as funcionalidades
-- ========================================

-- Remover tabelas existentes (se houver)
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `avaliacao_votos`;
DROP TABLE IF EXISTS `avaliacao_imagens`;
DROP TABLE IF EXISTS `avaliacoes`;
DROP TABLE IF EXISTS `chat_arquivos`;
DROP TABLE IF EXISTS `chat_mensagens`;
DROP TABLE IF EXISTS `chat_conversas`;
DROP TABLE IF EXISTS `produto_marketplace`;
DROP TABLE IF EXISTS `categoria_marketplace`;
DROP TABLE IF EXISTS `pedido_marketplace`;
DROP TABLE IF EXISTS `sincronizacao_log`;
DROP TABLE IF EXISTS `marketplace_config`;
DROP TABLE IF EXISTS `transferencias_estoque`;
DROP TABLE IF EXISTS `movimentacao_estoque`;
DROP TABLE IF EXISTS `estoque_loja`;
DROP TABLE IF EXISTS `comissoes_calculadas`;
DROP TABLE IF EXISTS `configuracao_comissoes`;
DROP TABLE IF EXISTS `lojas`;
DROP TABLE IF EXISTS `notificacoes_push`;
DROP TABLE IF EXISTS `produto_imagens`;
DROP TABLE IF EXISTS `cupom_usos`;
DROP TABLE IF EXISTS `cupons`;
DROP TABLE IF EXISTS `pedido_itens`;
DROP TABLE IF EXISTS `pedidos`;
DROP TABLE IF EXISTS `clientes`;
DROP TABLE IF EXISTS `categorias`;
DROP TABLE IF EXISTS `produtos`;
DROP TABLE IF EXISTS `configuracoes`;
DROP TABLE IF EXISTS `logs_sistema`;
DROP TABLE IF EXISTS `usuarios`;

SET FOREIGN_KEY_CHECKS = 1;

-- ========================================
-- 1. TABELAS PRINCIPAIS
-- ========================================

-- Tabela de usuários (sistema administrativo)
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL UNIQUE,
  `senha` varchar(255) NOT NULL,
  `nivel` enum('admin','gerente','editor','visualizador','gerente_loja') DEFAULT 'visualizador',
  `loja_id` int(11) NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `ultimo_login` datetime NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_nivel` (`nivel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de lojas (sistema multi-loja)
CREATE TABLE `lojas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL UNIQUE,
  `nome` varchar(100) NOT NULL,
  `tipo` enum('matriz','filial','franquia','parceiro') DEFAULT 'filial',
  `cnpj` varchar(18) NOT NULL UNIQUE,
  `email` varchar(100) NOT NULL,
  `telefone` varchar(20) NULL,
  `endereco` text NULL,
  `cidade` varchar(100) NULL,
  `estado` varchar(2) NULL,
  `cep` varchar(10) NULL,
  `pais` varchar(50) DEFAULT 'Brasil',
  `responsavel_nome` varchar(100) NOT NULL,
  `responsavel_email` varchar(100) NOT NULL,
  `responsavel_telefone` varchar(20) NULL,
  `loja_pai_id` int(11) NULL,
  `ativa` tinyint(1) DEFAULT 1,
  `configuracoes` json NULL,
  `permissoes` json NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_codigo` (`codigo`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_cnpj` (`cnpj`),
  KEY `fk_loja_pai` (`loja_pai_id`),
  CONSTRAINT `fk_loja_pai` FOREIGN KEY (`loja_pai_id`) REFERENCES `lojas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de categorias (hierárquica)
CREATE TABLE `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL UNIQUE,
  `descricao` text NULL,
  `categoria_pai_id` int(11) NULL,
  `nivel` int(11) DEFAULT 1,
  `ordem` int(11) DEFAULT 0,
  `cor` varchar(7) DEFAULT '#D4A04C',
  `icone` varchar(50) NULL,
  `imagem` varchar(255) NULL,
  `meta_title` varchar(200) NULL,
  `meta_description` text NULL,
  `meta_keywords` text NULL,
  `ativa` tinyint(1) DEFAULT 1,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_categoria_pai` (`categoria_pai_id`),
  KEY `idx_nivel` (`nivel`),
  CONSTRAINT `fk_categoria_pai` FOREIGN KEY (`categoria_pai_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de produtos (atualizada v2.2)
CREATE TABLE `produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria_id` int(11) NULL,
  `nome` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL UNIQUE,
  `sku` varchar(50) NULL UNIQUE,
  `descricao` text NULL,
  `preco` decimal(10,2) NOT NULL DEFAULT 0.00,
  `preco_promocional` decimal(10,2) NULL,
  `peso` decimal(8,3) DEFAULT 0.000,
  `comprimento` decimal(8,2) DEFAULT 0.00,
  `altura` decimal(8,2) DEFAULT 0.00,
  `largura` decimal(8,2) DEFAULT 0.00,
  `estoque` int(11) DEFAULT 0,
  `estoque_minimo` int(11) DEFAULT 0,
  `nota_media` decimal(3,2) DEFAULT 0.00,
  `total_avaliacoes` int(11) DEFAULT 0,
  `total_vendas` int(11) DEFAULT 0,
  `meta_title` varchar(200) NULL,
  `meta_description` text NULL,
  `meta_keywords` text NULL,
  `tags` text NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `destaque` tinyint(1) DEFAULT 0,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_categoria` (`categoria_id`),
  KEY `idx_slug` (`slug`),
  KEY `idx_sku` (`sku`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_destaque` (`destaque`),
  KEY `idx_nota_media` (`nota_media`),
  CONSTRAINT `fk_produto_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de imagens de produtos (upload múltiplo)
CREATE TABLE `produto_imagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produto_id` int(11) NOT NULL,
  `caminho` varchar(255) NOT NULL,
  `nome_original` varchar(255) NULL,
  `alt_text` varchar(255) NULL,
  `ordem` int(11) DEFAULT 0,
  `tamanho` int(11) NULL,
  `largura` int(11) NULL,
  `altura` int(11) NULL,
  `tipo_mime` varchar(100) NULL,
  `principal` tinyint(1) DEFAULT 0,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_produto` (`produto_id`),
  KEY `idx_ordem` (`ordem`),
  KEY `idx_principal` (`principal`),
  CONSTRAINT `fk_produto_imagem` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de clientes
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loja_id` int(11) NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefone` varchar(20) NULL,
  `cpf` varchar(14) NULL,
  `data_nascimento` date NULL,
  `endereco` text NULL,
  `cidade` varchar(100) NULL,
  `estado` varchar(2) NULL,
  `cep` varchar(10) NULL,
  `pais` varchar(50) DEFAULT 'Brasil',
  `numero_pedidos` int(11) DEFAULT 0,
  `valor_total_compras` decimal(10,2) DEFAULT 0.00,
  `ultima_compra` datetime NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_loja` (`loja_id`),
  KEY `idx_cpf` (`cpf`),
  CONSTRAINT `fk_cliente_loja` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de pedidos
CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loja_id` int(11) NULL,
  `cliente_id` int(11) NOT NULL,
  `numero_pedido` varchar(50) NOT NULL UNIQUE,
  `status` enum('pendente','confirmado','processando_pagamento','pago','preparando','enviado','entregue','cancelado') DEFAULT 'pendente',
  `valor_produtos` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_frete` decimal(10,2) DEFAULT 0.00,
  `valor_desconto` decimal(10,2) DEFAULT 0.00,
  `valor_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `forma_pagamento` varchar(50) NULL,
  `observacoes` text NULL,
  `endereco_entrega` json NULL,
  `dados_frete` json NULL,
  `codigo_rastreamento` varchar(100) NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_numero_pedido` (`numero_pedido`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_loja` (`loja_id`),
  KEY `idx_status` (`status`),
  KEY `idx_criado_em` (`criado_em`),
  CONSTRAINT `fk_pedido_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pedido_loja` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de itens do pedido
CREATE TABLE `pedido_itens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL DEFAULT 1,
  `preco_unitario` decimal(10,2) NOT NULL,
  `preco_total` decimal(10,2) NOT NULL,
  `observacoes` text NULL,
  PRIMARY KEY (`id`),
  KEY `idx_pedido` (`pedido_id`),
  KEY `idx_produto` (`produto_id`),
  CONSTRAINT `fk_item_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_item_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 2. SISTEMA DE CUPONS
-- ========================================

-- Tabela de cupons
CREATE TABLE `cupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) NOT NULL UNIQUE,
  `nome` varchar(100) NOT NULL,
  `tipo` enum('percentual','valor_fixo','frete_gratis') NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `valor_minimo_pedido` decimal(10,2) DEFAULT 0.00,
  `limite_uso_total` int(11) NULL,
  `limite_uso_cliente` int(11) DEFAULT 1,
  `usado_total` int(11) DEFAULT 0,
  `categorias_permitidas` json NULL,
  `produtos_permitidos` json NULL,
  `clientes_permitidos` json NULL,
  `primeira_compra_apenas` tinyint(1) DEFAULT 0,
  `data_inicio` datetime NOT NULL,
  `data_fim` datetime NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_codigo` (`codigo`),
  KEY `idx_ativo` (`ativo`),
  KEY `idx_data_inicio` (`data_inicio`),
  KEY `idx_data_fim` (`data_fim`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de uso de cupons
CREATE TABLE `cupom_usos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cupom_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `valor_desconto` decimal(10,2) NOT NULL,
  `usado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cupom` (`cupom_id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_pedido` (`pedido_id`),
  CONSTRAINT `fk_uso_cupom` FOREIGN KEY (`cupom_id`) REFERENCES `cupons` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uso_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uso_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 3. SISTEMA DE AVALIAÇÕES
-- ========================================

-- Tabela de avaliações
CREATE TABLE `avaliacoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produto_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `nota` tinyint(1) NOT NULL CHECK (`nota` >= 1 AND `nota` <= 5),
  `titulo` varchar(200) NULL,
  `comentario` text NULL,
  `recomenda` tinyint(1) DEFAULT 1,
  `anonimo` tinyint(1) DEFAULT 0,
  `status` enum('pendente','aprovada','rejeitada') DEFAULT 'pendente',
  `motivo_moderacao` text NULL,
  `votos_uteis` int(11) DEFAULT 0,
  `votos_nao_uteis` int(11) DEFAULT 0,
  `ip_cliente` varchar(45) NULL,
  `user_agent` text NULL,
  `moderado_em` datetime NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_produto` (`produto_id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_nota` (`nota`),
  KEY `idx_status` (`status`),
  KEY `idx_criado_em` (`criado_em`),
  CONSTRAINT `fk_avaliacao_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_avaliacao_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de imagens das avaliações
CREATE TABLE `avaliacao_imagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `avaliacao_id` int(11) NOT NULL,
  `caminho` varchar(255) NOT NULL,
  `nome_original` varchar(255) NULL,
  `alt_text` varchar(255) NULL,
  `ordem` int(11) DEFAULT 0,
  `tamanho` int(11) NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_avaliacao` (`avaliacao_id`),
  KEY `idx_ordem` (`ordem`),
  CONSTRAINT `fk_avaliacao_imagem` FOREIGN KEY (`avaliacao_id`) REFERENCES `avaliacoes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de votos de utilidade das avaliações
CREATE TABLE `avaliacao_votos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `avaliacao_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `util` tinyint(1) NOT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_avaliacao_cliente` (`avaliacao_id`, `cliente_id`),
  KEY `idx_avaliacao` (`avaliacao_id`),
  KEY `idx_cliente` (`cliente_id`),
  CONSTRAINT `fk_voto_avaliacao` FOREIGN KEY (`avaliacao_id`) REFERENCES `avaliacoes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_voto_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 4. SISTEMA DE CHAT
-- ========================================

-- Tabela de conversas do chat
CREATE TABLE `chat_conversas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NULL,
  `email_visitante` varchar(100) NULL,
  `nome_visitante` varchar(100) NULL,
  `atendente_id` int(11) NULL,
  `assunto` varchar(200) NULL,
  `departamento` enum('vendas','suporte','financeiro','geral') DEFAULT 'geral',
  `prioridade` enum('baixa','normal','alta','urgente') DEFAULT 'normal',
  `status` enum('aguardando','ativa','finalizada','abandonada') DEFAULT 'aguardando',
  `ip_cliente` varchar(45) NULL,
  `user_agent` text NULL,
  `canal_origem` varchar(50) DEFAULT 'website',
  `motivo_finalizacao` text NULL,
  `avaliacao_atendimento` tinyint(1) NULL,
  `comentario_avaliacao` text NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `atribuida_em` datetime NULL,
  `finalizada_em` datetime NULL,
  `finalizada_por` int(11) NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_atendente` (`atendente_id`),
  KEY `idx_status` (`status`),
  KEY `idx_departamento` (`departamento`),
  KEY `idx_prioridade` (`prioridade`),
  KEY `idx_criado_em` (`criado_em`),
  CONSTRAINT `fk_conversa_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_conversa_atendente` FOREIGN KEY (`atendente_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de mensagens do chat
CREATE TABLE `chat_mensagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `conversa_id` int(11) NOT NULL,
  `remetente_tipo` enum('cliente','atendente','sistema') NOT NULL,
  `remetente_id` int(11) NULL,
  `conteudo` text NOT NULL,
  `tipo_mensagem` enum('texto','arquivo','imagem','sistema') DEFAULT 'texto',
  `metadata` json NULL,
  `lida_em` datetime NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_conversa` (`conversa_id`),
  KEY `idx_remetente` (`remetente_tipo`, `remetente_id`),
  KEY `idx_criado_em` (`criado_em`),
  CONSTRAINT `fk_mensagem_conversa` FOREIGN KEY (`conversa_id`) REFERENCES `chat_conversas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de arquivos do chat
CREATE TABLE `chat_arquivos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mensagem_id` int(11) NOT NULL,
  `nome_arquivo` varchar(255) NOT NULL,
  `nome_original` varchar(255) NOT NULL,
  `caminho` varchar(255) NOT NULL,
  `tamanho` int(11) NOT NULL,
  `tipo_mime` varchar(100) NOT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mensagem` (`mensagem_id`),
  CONSTRAINT `fk_arquivo_mensagem` FOREIGN KEY (`mensagem_id`) REFERENCES `chat_mensagens` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 5. SISTEMA MULTI-LOJA
-- ========================================

-- Tabela de estoque por loja
CREATE TABLE `estoque_loja` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loja_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL DEFAULT 0,
  `reservado` int(11) DEFAULT 0,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_loja_produto` (`loja_id`, `produto_id`),
  KEY `idx_loja` (`loja_id`),
  KEY `idx_produto` (`produto_id`),
  CONSTRAINT `fk_estoque_loja` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_estoque_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de movimentação de estoque
CREATE TABLE `movimentacao_estoque` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loja_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `tipo` enum('entrada','saida','ajuste','transferencia') NOT NULL,
  `quantidade` int(11) NOT NULL,
  `quantidade_anterior` int(11) NOT NULL,
  `quantidade_atual` int(11) NOT NULL,
  `motivo` text NULL,
  `usuario_id` int(11) NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_loja` (`loja_id`),
  KEY `idx_produto` (`produto_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_criado_em` (`criado_em`),
  CONSTRAINT `fk_movimentacao_loja` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_movimentacao_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_movimentacao_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de transferências entre lojas
CREATE TABLE `transferencias_estoque` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loja_origem_id` int(11) NOT NULL,
  `loja_destino_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `motivo` text NULL,
  `status` enum('pendente','em_transito','concluida','cancelada') DEFAULT 'pendente',
  `usuario_origem_id` int(11) NULL,
  `usuario_destino_id` int(11) NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_loja_origem` (`loja_origem_id`),
  KEY `idx_loja_destino` (`loja_destino_id`),
  KEY `idx_produto` (`produto_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_transferencia_origem` FOREIGN KEY (`loja_origem_id`) REFERENCES `lojas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transferencia_destino` FOREIGN KEY (`loja_destino_id`) REFERENCES `lojas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transferencia_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de configuração de comissões
CREATE TABLE `configuracao_comissoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loja_id` int(11) NOT NULL,
  `tipo` enum('franquia','parceiro') NOT NULL,
  `percentual` decimal(5,4) NOT NULL,
  `valor_minimo` decimal(10,2) DEFAULT 0.00,
  `ativa` tinyint(1) DEFAULT 1,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_loja` (`loja_id`),
  KEY `idx_tipo` (`tipo`),
  CONSTRAINT `fk_comissao_loja` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de comissões calculadas
CREATE TABLE `comissoes_calculadas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loja_id` int(11) NOT NULL,
  `periodo` varchar(20) NOT NULL,
  `data_inicio` date NOT NULL,
  `data_fim` date NOT NULL,
  `total_vendas` decimal(10,2) NOT NULL,
  `percentual_comissao` decimal(5,4) NOT NULL,
  `valor_comissao` decimal(10,2) NOT NULL,
  `status` enum('calculada','paga','cancelada') DEFAULT 'calculada',
  `paga_em` datetime NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_loja` (`loja_id`),
  KEY `idx_periodo` (`periodo`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_comissao_calculada_loja` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 6. INTEGRAÇÃO COM MARKETPLACES
-- ========================================

-- Tabela de configuração dos marketplaces
CREATE TABLE `marketplace_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `marketplace` varchar(50) NOT NULL,
  `configuracoes` json NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_marketplace` (`marketplace`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de produtos nos marketplaces
CREATE TABLE `produto_marketplace` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produto_id` int(11) NOT NULL,
  `marketplace` varchar(50) NOT NULL,
  `id_externo` varchar(100) NOT NULL,
  `url_produto` varchar(500) NULL,
  `preco_marketplace` decimal(10,2) NULL,
  `estoque_marketplace` int(11) NULL,
  `status_marketplace` varchar(50) NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `ultima_sincronizacao` datetime NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_produto_marketplace` (`produto_id`, `marketplace`),
  KEY `idx_produto` (`produto_id`),
  KEY `idx_marketplace` (`marketplace`),
  KEY `idx_id_externo` (`id_externo`),
  CONSTRAINT `fk_produto_marketplace` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de mapeamento de categorias
CREATE TABLE `categoria_marketplace` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria_id` int(11) NOT NULL,
  `marketplace` varchar(50) NOT NULL,
  `categoria_externa` varchar(100) NOT NULL,
  `nome_categoria_externa` varchar(200) NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categoria_marketplace` (`categoria_id`, `marketplace`),
  KEY `idx_categoria` (`categoria_id`),
  KEY `idx_marketplace` (`marketplace`),
  CONSTRAINT `fk_categoria_marketplace` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de pedidos dos marketplaces
CREATE TABLE `pedido_marketplace` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `marketplace` varchar(50) NOT NULL,
  `id_externo` varchar(100) NOT NULL,
  `status_externo` varchar(50) NULL,
  `dados_marketplace` json NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pedido_marketplace` (`pedido_id`, `marketplace`),
  KEY `idx_pedido` (`pedido_id`),
  KEY `idx_marketplace` (`marketplace`),
  KEY `idx_id_externo` (`id_externo`),
  CONSTRAINT `fk_pedido_marketplace` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de log de sincronização
CREATE TABLE `sincronizacao_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produto_id` int(11) NULL,
  `pedido_id` int(11) NULL,
  `marketplace` varchar(50) NOT NULL,
  `tipo` enum('produto','estoque','preco','pedido') NOT NULL,
  `acao` enum('criar','atualizar','deletar','sincronizar') NOT NULL,
  `resultado` enum('sucesso','erro','pendente') NOT NULL,
  `detalhes` json NULL,
  `erro_mensagem` text NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_produto` (`produto_id`),
  KEY `idx_pedido` (`pedido_id`),
  KEY `idx_marketplace` (`marketplace`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_resultado` (`resultado`),
  KEY `idx_criado_em` (`criado_em`),
  CONSTRAINT `fk_sync_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sync_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 7. PWA E NOTIFICAÇÕES
-- ========================================

-- Tabela de notificações push
CREATE TABLE `notificacoes_push` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NULL,
  `endpoint` text NOT NULL,
  `p256dh_key` text NOT NULL,
  `auth_key` text NOT NULL,
  `user_agent` text NULL,
  `ativa` tinyint(1) DEFAULT 1,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cliente` (`cliente_id`),
  KEY `idx_ativa` (`ativa`),
  CONSTRAINT `fk_notificacao_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 8. CONFIGURAÇÕES E LOGS
-- ========================================

-- Tabela de configurações do sistema
CREATE TABLE `configuracoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chave` varchar(100) NOT NULL UNIQUE,
  `valor` text NULL,
  `tipo` enum('string','integer','boolean','json','text') DEFAULT 'string',
  `categoria` varchar(50) DEFAULT 'geral',
  `descricao` text NULL,
  `editavel` tinyint(1) DEFAULT 1,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_chave` (`chave`),
  KEY `idx_categoria` (`categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de logs do sistema
CREATE TABLE `logs_sistema` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) NULL,
  `acao` varchar(100) NOT NULL,
  `tabela` varchar(50) NULL,
  `registro_id` int(11) NULL,
  `dados_anteriores` json NULL,
  `dados_novos` json NULL,
  `ip` varchar(45) NULL,
  `user_agent` text NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_acao` (`acao`),
  KEY `idx_tabela` (`tabela`),
  KEY `idx_criado_em` (`criado_em`),
  CONSTRAINT `fk_log_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 9. FOREIGN KEYS ADICIONAIS
-- ========================================

-- Adicionar foreign key para usuários -> lojas
ALTER TABLE `usuarios`
ADD CONSTRAINT `fk_usuario_loja` FOREIGN KEY (`loja_id`) REFERENCES `lojas` (`id`) ON DELETE SET NULL;

-- ========================================
-- 10. DADOS INICIAIS
-- ========================================

-- Inserir usuários padrão
INSERT INTO `usuarios` (`nome`, `email`, `senha`, `nivel`) VALUES
('Administrador', 'admin@peluciapet.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('PelúciaPet', 'peluciapet@peluciapet.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gerente');

-- Inserir loja matriz
INSERT INTO `lojas` (`codigo`, `nome`, `tipo`, `cnpj`, `email`, `telefone`, `responsavel_nome`, `responsavel_email`, `configuracoes`) VALUES
('MTZ0001', 'PelúciaPet Matriz', 'matriz', '12.345.678/0001-90', 'matriz@peluciapet.com.br', '(11) 99999-9999', 'Administrador', 'admin@peluciapet.com.br', '{"tema": "peluciapet", "moeda": "BRL", "fuso_horario": "America/Sao_Paulo"}');

-- Inserir categorias principais
INSERT INTO `categorias` (`nome`, `slug`, `descricao`, `categoria_pai_id`, `nivel`, `ordem`, `cor`, `ativa`) VALUES
('Caminhas', 'caminhas', 'Caminhas confortáveis para pets de todos os tamanhos', NULL, 1, 1, '#8B4513', 1),
('Roupinhas', 'roupinhas', 'Roupas estilosas e confortáveis para pets', NULL, 1, 2, '#D4A04C', 1),
('Acessórios', 'acessorios', 'Acessórios diversos para pets', NULL, 1, 3, '#A0522D', 1),
('Brinquedos', 'brinquedos', 'Brinquedos divertidos para entretenimento', NULL, 1, 4, '#CD853F', 1);

-- Inserir subcategorias
INSERT INTO `categorias` (`nome`, `slug`, `descricao`, `categoria_pai_id`, `nivel`, `ordem`, `cor`, `ativa`) VALUES
('Caminhas Pequenas', 'caminhas-pequenas', 'Para pets de porte pequeno', 1, 2, 1, '#8B4513', 1),
('Caminhas Médias', 'caminhas-medias', 'Para pets de porte médio', 1, 2, 2, '#8B4513', 1),
('Caminhas Grandes', 'caminhas-grandes', 'Para pets de porte grande', 1, 2, 3, '#8B4513', 1),
('Camisetas', 'camisetas', 'Camisetas para pets', 2, 2, 1, '#D4A04C', 1),
('Moletons', 'moletons', 'Moletons quentinhos', 2, 2, 2, '#D4A04C', 1),
('Fantasias', 'fantasias', 'Fantasias divertidas', 2, 2, 3, '#D4A04C', 1);

-- Inserir produtos de exemplo
INSERT INTO `produtos` (`categoria_id`, `nome`, `slug`, `sku`, `descricao`, `preco`, `peso`, `comprimento`, `altura`, `largura`, `estoque`, `estoque_minimo`, `ativo`, `destaque`) VALUES
(5, 'Caminha Pequena Confort', 'caminha-pequena-confort', 'CAM-PEQ-001', 'Caminha super confortável para pets pequenos, feita com materiais de alta qualidade.', 89.90, 0.500, 40.00, 10.00, 30.00, 50, 5, 1, 1),
(6, 'Caminha Média Premium', 'caminha-media-premium', 'CAM-MED-001', 'Caminha premium para pets de porte médio, com enchimento especial.', 129.90, 0.800, 60.00, 15.00, 45.00, 30, 3, 1, 1),
(8, 'Camiseta Pet Fashion', 'camiseta-pet-fashion', 'CAM-PET-001', 'Camiseta estilosa para pets, 100% algodão.', 39.90, 0.100, 25.00, 20.00, 15.00, 100, 10, 1, 0),
(9, 'Moletom Pet Inverno', 'moletom-pet-inverno', 'MOL-PET-001', 'Moletom quentinho para os dias frios, com capuz.', 69.90, 0.200, 30.00, 25.00, 20.00, 75, 8, 1, 1);

-- Inserir cupons de exemplo
INSERT INTO `cupons` (`codigo`, `nome`, `tipo`, `valor`, `valor_minimo_pedido`, `limite_uso_total`, `data_inicio`, `data_fim`, `ativo`) VALUES
('BEMVINDO10', 'Cupom de Boas-vindas', 'percentual', 10.00, 50.00, 1000, '2024-01-01 00:00:00', '2024-12-31 23:59:59', 1),
('FRETEGRATIS', 'Frete Grátis', 'frete_gratis', 0.00, 100.00, 500, '2024-01-01 00:00:00', '2024-12-31 23:59:59', 1),
('DESCONTO20', 'Desconto 20%', 'percentual', 20.00, 200.00, 100, '2024-01-01 00:00:00', '2024-12-31 23:59:59', 1);

-- Inserir configurações padrão
INSERT INTO `configuracoes` (`chave`, `valor`, `tipo`, `categoria`, `descricao`) VALUES
('site_nome', 'PelúciaPet', 'string', 'geral', 'Nome do site'),
('site_email', 'contato@peluciapet.com.br', 'string', 'geral', 'Email principal do site'),
('site_telefone', '(11) 99999-9999', 'string', 'geral', 'Telefone principal'),
('whatsapp_numero', '5511999999999', 'string', 'contato', 'Número do WhatsApp'),
('frete_gratis_valor', '150.00', 'string', 'vendas', 'Valor mínimo para frete grátis'),
('moeda_padrao', 'BRL', 'string', 'vendas', 'Moeda padrão do sistema'),
('estoque_baixo_notificar', '5', 'integer', 'estoque', 'Quantidade para notificar estoque baixo'),
('backup_automatico', 'true', 'boolean', 'sistema', 'Ativar backup automático'),
('manutencao_modo', 'false', 'boolean', 'sistema', 'Modo manutenção ativo'),
('analytics_google', '', 'string', 'marketing', 'ID do Google Analytics');

-- ========================================
-- 11. ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ========================================

-- Índices compostos para consultas frequentes
CREATE INDEX `idx_produto_categoria_ativo` ON `produtos` (`categoria_id`, `ativo`);
CREATE INDEX `idx_produto_destaque_ativo` ON `produtos` (`destaque`, `ativo`);
CREATE INDEX `idx_pedido_status_data` ON `pedidos` (`status`, `criado_em`);
CREATE INDEX `idx_avaliacao_produto_status` ON `avaliacoes` (`produto_id`, `status`);
CREATE INDEX `idx_chat_conversa_status_data` ON `chat_conversas` (`status`, `criado_em`);
CREATE INDEX `idx_estoque_loja_quantidade` ON `estoque_loja` (`loja_id`, `quantidade`);

-- ========================================
-- 12. TRIGGERS PARA AUTOMAÇÃO
-- ========================================

-- Trigger para atualizar estatísticas do produto após nova avaliação
DELIMITER $$
CREATE TRIGGER `tr_avaliacao_after_insert` 
AFTER INSERT ON `avaliacoes` 
FOR EACH ROW 
BEGIN
    IF NEW.status = 'aprovada' THEN
        UPDATE produtos SET 
            nota_media = (
                SELECT AVG(nota) FROM avaliacoes 
                WHERE produto_id = NEW.produto_id AND status = 'aprovada'
            ),
            total_avaliacoes = (
                SELECT COUNT(*) FROM avaliacoes 
                WHERE produto_id = NEW.produto_id AND status = 'aprovada'
            )
        WHERE id = NEW.produto_id;
    END IF;
END$$

-- Trigger para atualizar estatísticas após aprovação de avaliação
CREATE TRIGGER `tr_avaliacao_after_update` 
AFTER UPDATE ON `avaliacoes` 
FOR EACH ROW 
BEGIN
    IF OLD.status != NEW.status AND NEW.status = 'aprovada' THEN
        UPDATE produtos SET 
            nota_media = (
                SELECT AVG(nota) FROM avaliacoes 
                WHERE produto_id = NEW.produto_id AND status = 'aprovada'
            ),
            total_avaliacoes = (
                SELECT COUNT(*) FROM avaliacoes 
                WHERE produto_id = NEW.produto_id AND status = 'aprovada'
            )
        WHERE id = NEW.produto_id;
    END IF;
END$$

-- Trigger para atualizar total de vendas do produto
CREATE TRIGGER `tr_pedido_item_after_insert` 
AFTER INSERT ON `pedido_itens` 
FOR EACH ROW 
BEGIN
    DECLARE pedido_status VARCHAR(50);
    
    SELECT status INTO pedido_status 
    FROM pedidos 
    WHERE id = NEW.pedido_id;
    
    IF pedido_status = 'entregue' THEN
        UPDATE produtos SET 
            total_vendas = total_vendas + NEW.quantidade
        WHERE id = NEW.produto_id;
    END IF;
END$$

-- Trigger para atualizar estatísticas do cliente
CREATE TRIGGER `tr_pedido_after_update` 
AFTER UPDATE ON `pedidos` 
FOR EACH ROW 
BEGIN
    IF OLD.status != NEW.status AND NEW.status = 'entregue' THEN
        UPDATE clientes SET 
            numero_pedidos = numero_pedidos + 1,
            valor_total_compras = valor_total_compras + NEW.valor_total,
            ultima_compra = NOW()
        WHERE id = NEW.cliente_id;
        
        -- Atualizar total de vendas dos produtos
        UPDATE produtos p
        JOIN pedido_itens pi ON p.id = pi.produto_id
        SET p.total_vendas = p.total_vendas + pi.quantidade
        WHERE pi.pedido_id = NEW.id;
    END IF;
END$$

DELIMITER ;

-- ========================================
-- 13. VIEWS PARA RELATÓRIOS
-- ========================================

-- View para relatório de vendas por produto
CREATE VIEW `vw_vendas_produto` AS
SELECT 
    p.id,
    p.nome,
    p.sku,
    c.nome as categoria,
    COUNT(DISTINCT pe.id) as total_pedidos,
    SUM(pi.quantidade) as quantidade_vendida,
    SUM(pi.preco_total) as valor_total_vendas,
    AVG(pi.preco_unitario) as preco_medio,
    p.nota_media,
    p.total_avaliacoes
FROM produtos p
LEFT JOIN categorias c ON p.categoria_id = c.id
LEFT JOIN pedido_itens pi ON p.id = pi.produto_id
LEFT JOIN pedidos pe ON pi.pedido_id = pe.id AND pe.status = 'entregue'
WHERE p.ativo = 1
GROUP BY p.id, p.nome, p.sku, c.nome, p.nota_media, p.total_avaliacoes;

-- View para dashboard de vendas
CREATE VIEW `vw_dashboard_vendas` AS
SELECT 
    DATE(p.criado_em) as data_venda,
    COUNT(*) as total_pedidos,
    SUM(p.valor_total) as valor_total,
    AVG(p.valor_total) as ticket_medio,
    COUNT(DISTINCT p.cliente_id) as clientes_unicos
FROM pedidos p
WHERE p.status = 'entregue'
AND p.criado_em >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(p.criado_em)
ORDER BY data_venda DESC;

-- View para estoque consolidado
CREATE VIEW `vw_estoque_consolidado` AS
SELECT 
    p.id as produto_id,
    p.nome as produto_nome,
    p.sku,
    c.nome as categoria,
    SUM(el.quantidade) as estoque_total,
    p.estoque_minimo,
    CASE 
        WHEN SUM(el.quantidade) <= p.estoque_minimo THEN 'BAIXO'
        WHEN SUM(el.quantidade) <= p.estoque_minimo * 2 THEN 'MEDIO'
        ELSE 'OK'
    END as status_estoque
FROM produtos p
LEFT JOIN categorias c ON p.categoria_id = c.id
LEFT JOIN estoque_loja el ON p.id = el.produto_id
WHERE p.ativo = 1
GROUP BY p.id, p.nome, p.sku, c.nome, p.estoque_minimo;

-- ========================================
-- FINALIZAÇÃO
-- ========================================

-- Reativar verificação de foreign keys
SET FOREIGN_KEY_CHECKS = 1;

-- Mensagem de sucesso
SELECT 'Sistema PelúciaPet v2.2 instalado com sucesso!' as status;

