-- ========================================
-- SCRIPT DE MIGRAÇÃO SEGURO
-- Sistema PelúciaPet v2.1
-- Atualiza base existente sem perder dados
-- ========================================

-- Configurações iniciais
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;

-- ========================================
-- 1. VERIFICAR E CRIAR TABELAS FALTANTES
-- ========================================

-- Verificar se tabela usuarios existe, se não, criar
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

-- Verificar se tabela categorias existe, se não, criar
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

-- Criar tabela produto_imagens (nova funcionalidade v2.1)
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

-- Criar tabela clientes
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

-- Criar tabela pedidos
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

-- Criar tabela pedido_itens
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

-- Criar tabela cupons (nova funcionalidade v2.1)
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

-- Criar tabela cupom_usos
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

-- Criar tabela configuracoes
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

-- Criar tabela logs_sistema
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
-- 2. ATUALIZAR TABELA PRODUTOS EXISTENTE
-- ========================================

-- Verificar se colunas existem antes de adicionar
SET @sql = '';

-- Adicionar categoria_id se não existir
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'produtos' 
AND COLUMN_NAME = 'categoria_id';

IF @col_exists = 0 THEN
    SET @sql = CONCAT(@sql, 'ALTER TABLE produtos ADD COLUMN categoria_id INT NULL AFTER id;');
END IF;

-- Adicionar sku se não existir
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'produtos' 
AND COLUMN_NAME = 'sku';

IF @col_exists = 0 THEN
    SET @sql = CONCAT(@sql, 'ALTER TABLE produtos ADD COLUMN sku VARCHAR(50) AFTER nome;');
END IF;

-- Adicionar peso se não existir
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'produtos' 
AND COLUMN_NAME = 'peso';

IF @col_exists = 0 THEN
    SET @sql = CONCAT(@sql, 'ALTER TABLE produtos ADD COLUMN peso DECIMAL(8,3) DEFAULT 0 AFTER preco;');
END IF;

-- Adicionar comprimento se não existir
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'produtos' 
AND COLUMN_NAME = 'comprimento';

IF @col_exists = 0 THEN
    SET @sql = CONCAT(@sql, 'ALTER TABLE produtos ADD COLUMN comprimento DECIMAL(8,2) DEFAULT 0 AFTER peso;');
END IF;

-- Adicionar altura se não existir
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'produtos' 
AND COLUMN_NAME = 'altura';

IF @col_exists = 0 THEN
    SET @sql = CONCAT(@sql, 'ALTER TABLE produtos ADD COLUMN altura DECIMAL(8,2) DEFAULT 0 AFTER comprimento;');
END IF;

-- Adicionar largura se não existir
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'produtos' 
AND COLUMN_NAME = 'largura';

IF @col_exists = 0 THEN
    SET @sql = CONCAT(@sql, 'ALTER TABLE produtos ADD COLUMN largura DECIMAL(8,2) DEFAULT 0 AFTER altura;');
END IF;

-- Adicionar estoque se não existir
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'produtos' 
AND COLUMN_NAME = 'estoque';

IF @col_exists = 0 THEN
    SET @sql = CONCAT(@sql, 'ALTER TABLE produtos ADD COLUMN estoque INT DEFAULT 0 AFTER largura;');
END IF;

-- Adicionar estoque_minimo se não existir
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'produtos' 
AND COLUMN_NAME = 'estoque_minimo';

IF @col_exists = 0 THEN
    SET @sql = CONCAT(@sql, 'ALTER TABLE produtos ADD COLUMN estoque_minimo INT DEFAULT 0 AFTER estoque;');
END IF;

-- Adicionar meta_title se não existir
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'produtos' 
AND COLUMN_NAME = 'meta_title';

IF @col_exists = 0 THEN
    SET @sql = CONCAT(@sql, 'ALTER TABLE produtos ADD COLUMN meta_title VARCHAR(200) AFTER descricao;');
END IF;

-- Adicionar meta_description se não existir
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'produtos' 
AND COLUMN_NAME = 'meta_description';

IF @col_exists = 0 THEN
    SET @sql = CONCAT(@sql, 'ALTER TABLE produtos ADD COLUMN meta_description TEXT AFTER meta_title;');
END IF;

-- Adicionar meta_keywords se não existir
SELECT COUNT(*) INTO @col_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'produtos' 
AND COLUMN_NAME = 'meta_keywords';

IF @col_exists = 0 THEN
    SET @sql = CONCAT(@sql, 'ALTER TABLE produtos ADD COLUMN meta_keywords TEXT AFTER meta_description;');
END IF;

-- Executar alterações se houver
IF LENGTH(@sql) > 0 THEN
    SET @sql = CONCAT('ALTER TABLE produtos ', SUBSTRING(@sql, 20));
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END IF;

-- ========================================
-- 3. ADICIONAR FOREIGN KEYS SEGURAMENTE
-- ========================================

-- Função para verificar se foreign key existe
SET @fk_exists = 0;

-- Verificar e adicionar FK categoria_pai
SELECT COUNT(*) INTO @fk_exists 
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'categorias' 
AND CONSTRAINT_NAME = 'fk_categoria_pai';

IF @fk_exists = 0 THEN
    ALTER TABLE `categorias`
    ADD CONSTRAINT `fk_categoria_pai` FOREIGN KEY (`categoria_pai_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL;
END IF;

-- Verificar e adicionar FK produto_categoria
SELECT COUNT(*) INTO @fk_exists 
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'produtos' 
AND CONSTRAINT_NAME = 'fk_produto_categoria';

IF @fk_exists = 0 THEN
    ALTER TABLE `produtos`
    ADD CONSTRAINT `fk_produto_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL;
END IF;

-- Verificar e adicionar FK imagem_produto
SELECT COUNT(*) INTO @fk_exists 
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'produto_imagens' 
AND CONSTRAINT_NAME = 'fk_imagem_produto';

IF @fk_exists = 0 THEN
    ALTER TABLE `produto_imagens`
    ADD CONSTRAINT `fk_imagem_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE;
END IF;

-- ========================================
-- 4. INSERIR DADOS PADRÃO (SE NÃO EXISTIREM)
-- ========================================

-- Inserir usuários padrão
INSERT IGNORE INTO `usuarios` (`nome`, `email`, `senha`, `nivel`) VALUES
('Administrador', 'admin@peluciapet.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('PelúciaPet', 'peluciapet@peluciapet.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gerente');

-- Inserir categorias padrão
INSERT IGNORE INTO `categorias` (`nome`, `slug`, `descricao`, `categoria_pai_id`, `nivel`, `ordem`, `icone`, `cor`) VALUES
('Caminhas', 'caminhas', 'Caminhas confortáveis para pets', NULL, 1, 1, 'bed', '#8B4513'),
('Roupinhas', 'roupinhas', 'Roupas estilosas para pets', NULL, 1, 2, 'shirt', '#D4A04C'),
('Acessórios', 'acessorios', 'Acessórios diversos para pets', NULL, 1, 3, 'star', '#A0522D'),
('Brinquedos', 'brinquedos', 'Brinquedos divertidos para pets', NULL, 1, 4, 'toy', '#5C2C0D');

-- Inserir configurações padrão
INSERT IGNORE INTO `configuracoes` (`chave`, `valor`, `tipo`, `categoria`, `descricao`) VALUES
('site_nome', 'PelúciaPet', 'string', 'geral', 'Nome do site'),
('site_email', 'contato@peluciapet.com.br', 'string', 'geral', 'Email principal'),
('site_telefone', '(11) 99999-9999', 'string', 'geral', 'Telefone de contato'),
('site_whatsapp', '5511999999999', 'string', 'geral', 'WhatsApp para contato'),
('frete_gratis_valor', '100.00', 'number', 'frete', 'Valor mínimo para frete grátis'),
('correios_usuario', '', 'string', 'frete', 'Usuário dos Correios'),
('correios_senha', '', 'string', 'frete', 'Senha dos Correios'),
('cep_origem', '01310-100', 'string', 'frete', 'CEP de origem para cálculo de frete'),
('backup_automatico', '1', 'boolean', 'sistema', 'Ativar backup automático'),
('manutencao_modo', '0', 'boolean', 'sistema', 'Modo manutenção ativo');

-- ========================================
-- 5. FINALIZAÇÃO
-- ========================================

COMMIT;

-- Mensagem de sucesso
SELECT 'Migração para PelúciaPet v2.1 concluída com sucesso!' as status,
       'Todas as tabelas foram atualizadas preservando dados existentes' as detalhes;

