-- ========================================
-- SCRIPT DE INSTALAÇÃO MYSQL COMPATÍVEL
-- Sistema PelúciaPet v2.1
-- Compatível com MySQL 5.7+ e MariaDB 10.2+
-- ========================================

-- Configurações iniciais
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- ========================================
-- 1. CRIAÇÃO DAS TABELAS PRINCIPAIS
-- ========================================

-- Tabela de usuários (se não existir)
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel` enum('admin','gerente','editor','visualizador') DEFAULT 'visualizador',
  `ativo` tinyint(1) DEFAULT 1,
  `ultimo_login` datetime DEFAULT NULL,
  `tentativas_login` int(11) DEFAULT 0,
  `bloqueado_ate` datetime DEFAULT NULL,
  `token_recuperacao` varchar(255) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de categorias hierárquicas
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `descricao` text,
  `categoria_pai_id` int(11) DEFAULT NULL,
  `nivel` int(11) DEFAULT 1,
  `ordem` int(11) DEFAULT 0,
  `icone` varchar(50) DEFAULT NULL,
  `cor` varchar(7) DEFAULT '#8B4513',
  `imagem` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` text,
  `meta_keywords` text,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `categoria_pai_id` (`categoria_pai_id`),
  KEY `ativo` (`ativo`),
  KEY `nivel` (`nivel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de produtos (criar nova ou verificar existente)
CREATE TABLE IF NOT EXISTS `produtos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoria_id` int(11) DEFAULT NULL,
  `nome` varchar(200) NOT NULL,
  `sku` varchar(50) DEFAULT NULL,
  `descricao` text,
  `preco` decimal(10,2) NOT NULL DEFAULT 0.00,
  `peso` decimal(8,3) DEFAULT 0.000,
  `comprimento` decimal(8,2) DEFAULT 0.00,
  `altura` decimal(8,2) DEFAULT 0.00,
  `largura` decimal(8,2) DEFAULT 0.00,
  `estoque` int(11) DEFAULT 0,
  `estoque_minimo` int(11) DEFAULT 0,
  `meta_title` varchar(200) DEFAULT NULL,
  `meta_description` text,
  `meta_keywords` text,
  `ativo` tinyint(1) DEFAULT 1,
  `destaque` tinyint(1) DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku` (`sku`),
  KEY `categoria_id` (`categoria_id`),
  KEY `ativo` (`ativo`),
  KEY `destaque` (`destaque`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de imagens dos produtos
CREATE TABLE IF NOT EXISTS `produto_imagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `produto_id` int(11) NOT NULL,
  `nome_arquivo` varchar(255) NOT NULL,
  `nome_original` varchar(255) NOT NULL,
  `caminho` varchar(500) NOT NULL,
  `tamanho` int(11) NOT NULL,
  `tipo_mime` varchar(100) NOT NULL,
  `largura` int(11) DEFAULT NULL,
  `altura` int(11) DEFAULT NULL,
  `ordem` int(11) DEFAULT 0,
  `principal` tinyint(1) DEFAULT 0,
  `alt_text` varchar(200) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `produto_id` (`produto_id`),
  KEY `principal` (`principal`),
  KEY `ordem` (`ordem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de clientes
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `endereco` text,
  `cep` varchar(10) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(2) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `primeira_compra` datetime DEFAULT NULL,
  `ultima_compra` datetime DEFAULT NULL,
  `total_compras` decimal(10,2) DEFAULT 0.00,
  `numero_pedidos` int(11) DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `ativo` (`ativo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de pedidos
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `numero_pedido` varchar(20) NOT NULL,
  `status` enum('pendente','confirmado','preparando','enviado','entregue','cancelado') DEFAULT 'pendente',
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `desconto` decimal(10,2) DEFAULT 0.00,
  `frete` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `metodo_pagamento` varchar(50) DEFAULT NULL,
  `status_pagamento` enum('pendente','aprovado','rejeitado','cancelado') DEFAULT 'pendente',
  `endereco_entrega` text,
  `cep_entrega` varchar(10) DEFAULT NULL,
  `cidade_entrega` varchar(100) DEFAULT NULL,
  `estado_entrega` varchar(2) DEFAULT NULL,
  `codigo_rastreamento` varchar(50) DEFAULT NULL,
  `transportadora` varchar(100) DEFAULT NULL,
  `prazo_entrega` int(11) DEFAULT NULL,
  `observacoes` text,
  `cupom_codigo` varchar(20) DEFAULT NULL,
  `cupom_desconto` decimal(10,2) DEFAULT 0.00,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `numero_pedido` (`numero_pedido`),
  KEY `cliente_id` (`cliente_id`),
  KEY `status` (`status`),
  KEY `status_pagamento` (`status_pagamento`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de itens do pedido
CREATE TABLE IF NOT EXISTS `pedido_itens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pedido_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `nome_produto` varchar(200) NOT NULL,
  `sku_produto` varchar(50) DEFAULT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  `quantidade` int(11) NOT NULL DEFAULT 1,
  `subtotal` decimal(10,2) NOT NULL,
  `observacoes` text,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `pedido_id` (`pedido_id`),
  KEY `produto_id` (`produto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de cupons
CREATE TABLE IF NOT EXISTS `cupons` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` text,
  `tipo` enum('percentual','valor_fixo','frete_gratis') NOT NULL DEFAULT 'percentual',
  `valor` decimal(10,2) NOT NULL DEFAULT 0.00,
  `valor_minimo_pedido` decimal(10,2) DEFAULT 0.00,
  `valor_maximo_desconto` decimal(10,2) DEFAULT NULL,
  `limite_uso_total` int(11) DEFAULT NULL,
  `limite_uso_cliente` int(11) DEFAULT 1,
  `usado_total` int(11) DEFAULT 0,
  `data_inicio` datetime NOT NULL,
  `data_fim` datetime NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `primeira_compra` tinyint(1) DEFAULT 0,
  `categorias_permitidas` text,
  `produtos_permitidos` text,
  `clientes_permitidos` text,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `ativo` (`ativo`),
  KEY `data_inicio` (`data_inicio`),
  KEY `data_fim` (`data_fim`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de uso de cupons
CREATE TABLE IF NOT EXISTS `cupom_usos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cupom_id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `valor_desconto` decimal(10,2) NOT NULL,
  `usado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `cupom_id` (`cupom_id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `pedido_id` (`pedido_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de configurações do sistema
CREATE TABLE IF NOT EXISTS `configuracoes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chave` varchar(100) NOT NULL,
  `valor` text,
  `tipo` enum('string','number','boolean','json','text') DEFAULT 'string',
  `categoria` varchar(50) DEFAULT 'geral',
  `descricao` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `chave` (`chave`),
  KEY `categoria` (`categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de logs do sistema
CREATE TABLE IF NOT EXISTS `logs_sistema` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `acao` varchar(100) NOT NULL,
  `tabela` varchar(50) DEFAULT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `dados_anteriores` json DEFAULT NULL,
  `dados_novos` json DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `acao` (`acao`),
  KEY `tabela` (`tabela`),
  KEY `criado_em` (`criado_em`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- 2. FOREIGN KEYS (CHAVES ESTRANGEIRAS)
-- ========================================

-- Adicionar foreign keys
ALTER TABLE `categorias`
  ADD CONSTRAINT `fk_categoria_pai` FOREIGN KEY (`categoria_pai_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL;

ALTER TABLE `produtos`
  ADD CONSTRAINT `fk_produto_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL;

ALTER TABLE `produto_imagens`
  ADD CONSTRAINT `fk_imagem_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE;

ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedido_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

ALTER TABLE `pedido_itens`
  ADD CONSTRAINT `fk_item_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_item_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE;

ALTER TABLE `cupom_usos`
  ADD CONSTRAINT `fk_uso_cupom` FOREIGN KEY (`cupom_id`) REFERENCES `cupons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_uso_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_uso_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE;

ALTER TABLE `logs_sistema`
  ADD CONSTRAINT `fk_log_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

-- ========================================
-- 3. ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ========================================

-- Índices para otimização de consultas
CREATE INDEX `idx_produtos_categoria_ativo` ON `produtos` (`categoria_id`, `ativo`);
CREATE INDEX `idx_produtos_preco` ON `produtos` (`preco`);
CREATE INDEX `idx_pedidos_data` ON `pedidos` (`criado_em`);
CREATE INDEX `idx_pedidos_total` ON `pedidos` (`total`);
CREATE INDEX `idx_clientes_ultima_compra` ON `clientes` (`ultima_compra`);
CREATE INDEX `idx_cupons_validade` ON `cupons` (`data_inicio`, `data_fim`, `ativo`);

-- ========================================
-- 4. TRIGGERS PARA AUTOMAÇÃO
-- ========================================

-- Trigger para atualizar slug da categoria
DELIMITER $$
CREATE TRIGGER `tr_categoria_slug` BEFORE INSERT ON `categorias`
FOR EACH ROW BEGIN
    IF NEW.slug IS NULL OR NEW.slug = '' THEN
        SET NEW.slug = LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(NEW.nome, ' ', '-'), 'ã', 'a'), 'ç', 'c'), 'ê', 'e'), 'ô', 'o'));
    END IF;
END$$
DELIMITER ;

-- Trigger para gerar número do pedido
DELIMITER $$
CREATE TRIGGER `tr_pedido_numero` BEFORE INSERT ON `pedidos`
FOR EACH ROW BEGIN
    IF NEW.numero_pedido IS NULL OR NEW.numero_pedido = '' THEN
        SET NEW.numero_pedido = CONCAT('PED', YEAR(NOW()), LPAD(NEW.id, 6, '0'));
    END IF;
END$$
DELIMITER ;

-- Trigger para calcular total do pedido
DELIMITER $$
CREATE TRIGGER `tr_pedido_total` BEFORE UPDATE ON `pedidos`
FOR EACH ROW BEGIN
    SET NEW.total = NEW.subtotal + NEW.frete - NEW.desconto - NEW.cupom_desconto;
END$$
DELIMITER ;

-- Trigger para atualizar estatísticas do cliente
DELIMITER $$
CREATE TRIGGER `tr_cliente_stats` AFTER INSERT ON `pedidos`
FOR EACH ROW BEGIN
    UPDATE `clientes` SET 
        `numero_pedidos` = `numero_pedidos` + 1,
        `total_compras` = `total_compras` + NEW.total,
        `ultima_compra` = NOW(),
        `primeira_compra` = COALESCE(`primeira_compra`, NOW())
    WHERE `id` = NEW.cliente_id;
END$$
DELIMITER ;

-- ========================================
-- 5. VIEWS PARA RELATÓRIOS
-- ========================================

-- View para dashboard de vendas
CREATE OR REPLACE VIEW `vw_dashboard_vendas` AS
SELECT 
    DATE(p.criado_em) as data,
    COUNT(*) as total_pedidos,
    SUM(p.total) as total_vendas,
    AVG(p.total) as ticket_medio,
    COUNT(DISTINCT p.cliente_id) as clientes_unicos
FROM `pedidos` p 
WHERE p.status NOT IN ('cancelado')
GROUP BY DATE(p.criado_em)
ORDER BY data DESC;

-- View para produtos mais vendidos
CREATE OR REPLACE VIEW `vw_produtos_vendidos` AS
SELECT 
    p.id,
    p.nome,
    p.sku,
    c.nome as categoria,
    SUM(pi.quantidade) as total_vendido,
    SUM(pi.subtotal) as total_faturado,
    COUNT(DISTINCT pi.pedido_id) as pedidos_count
FROM `produtos` p
LEFT JOIN `categorias` c ON p.categoria_id = c.id
LEFT JOIN `pedido_itens` pi ON p.id = pi.produto_id
LEFT JOIN `pedidos` ped ON pi.pedido_id = ped.id
WHERE ped.status NOT IN ('cancelado')
GROUP BY p.id, p.nome, p.sku, c.nome
ORDER BY total_vendido DESC;

-- View para análise de clientes
CREATE OR REPLACE VIEW `vw_analise_clientes` AS
SELECT 
    c.id,
    c.nome,
    c.email,
    c.numero_pedidos,
    c.total_compras,
    c.primeira_compra,
    c.ultima_compra,
    DATEDIFF(NOW(), c.ultima_compra) as dias_sem_comprar,
    CASE 
        WHEN c.total_compras >= 1000 THEN 'VIP'
        WHEN c.total_compras >= 500 THEN 'Premium'
        WHEN c.numero_pedidos >= 5 THEN 'Fiel'
        ELSE 'Regular'
    END as categoria_cliente
FROM `clientes` c
WHERE c.ativo = 1
ORDER BY c.total_compras DESC;

-- ========================================
-- 6. DADOS INICIAIS
-- ========================================

-- Inserir usuário administrador padrão
INSERT INTO `usuarios` (`nome`, `email`, `senha`, `nivel`) VALUES
('Administrador', 'admin@peluciapet.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('PelúciaPet', 'peluciapet@peluciapet.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gerente')
ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`);

-- Inserir categorias padrão
INSERT INTO `categorias` (`nome`, `slug`, `descricao`, `categoria_pai_id`, `nivel`, `ordem`, `icone`, `cor`) VALUES
('Caminhas', 'caminhas', 'Caminhas confortáveis para pets', NULL, 1, 1, 'bed', '#8B4513'),
('Roupinhas', 'roupinhas', 'Roupas estilosas para pets', NULL, 1, 2, 'shirt', '#D4A04C'),
('Acessórios', 'acessorios', 'Acessórios diversos para pets', NULL, 1, 3, 'star', '#A0522D'),
('Brinquedos', 'brinquedos', 'Brinquedos divertidos para pets', NULL, 1, 4, 'toy', '#5C2C0D')
ON DUPLICATE KEY UPDATE `descricao` = VALUES(`descricao`);

-- Inserir configurações padrão
INSERT INTO `configuracoes` (`chave`, `valor`, `tipo`, `categoria`, `descricao`) VALUES
('site_nome', 'PelúciaPet', 'string', 'geral', 'Nome do site'),
('site_email', 'contato@peluciapet.com.br', 'string', 'geral', 'Email principal'),
('site_telefone', '(11) 99999-9999', 'string', 'geral', 'Telefone de contato'),
('site_whatsapp', '5511999999999', 'string', 'geral', 'WhatsApp para contato'),
('frete_gratis_valor', '100.00', 'number', 'frete', 'Valor mínimo para frete grátis'),
('correios_usuario', '', 'string', 'frete', 'Usuário dos Correios'),
('correios_senha', '', 'string', 'frete', 'Senha dos Correios'),
('cep_origem', '01310-100', 'string', 'frete', 'CEP de origem para cálculo de frete'),
('backup_automatico', '1', 'boolean', 'sistema', 'Ativar backup automático'),
('manutencao_modo', '0', 'boolean', 'sistema', 'Modo manutenção ativo')
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`);

-- ========================================
-- 7. FINALIZAÇÃO
-- ========================================

COMMIT;

-- Mensagem de sucesso
SELECT 'Banco de dados PelúciaPet v2.1 instalado com sucesso!' as status;

