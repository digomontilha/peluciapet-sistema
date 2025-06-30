-- ========================================
-- SCRIPT DE INSTALAÇÃO LIMPA
-- Sistema PelúciaPet v2.1
-- Para banco de dados ZERADO
-- ========================================

-- Configurações iniciais
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- ========================================
-- 1. REMOVER TABELAS EXISTENTES (SE HOUVER)
-- ========================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `cupom_usos`;
DROP TABLE IF EXISTS `cupons`;
DROP TABLE IF EXISTS `pedido_itens`;
DROP TABLE IF EXISTS `pedidos`;
DROP TABLE IF EXISTS `produto_imagens`;
DROP TABLE IF EXISTS `produtos`;
DROP TABLE IF EXISTS `clientes`;
DROP TABLE IF EXISTS `categorias`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `configuracoes`;
DROP TABLE IF EXISTS `logs_sistema`;

SET FOREIGN_KEY_CHECKS = 1;

-- ========================================
-- 2. CRIAR TABELAS NA ORDEM CORRETA
-- ========================================

-- Tabela de usuários (sem dependências)
CREATE TABLE `usuarios` (
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

-- Tabela de categorias (auto-referência será adicionada depois)
CREATE TABLE `categorias` (
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

-- Tabela de produtos (depende de categorias)
CREATE TABLE `produtos` (
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

-- Tabela de imagens dos produtos (depende de produtos)
CREATE TABLE `produto_imagens` (
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

-- Tabela de clientes (sem dependências)
CREATE TABLE `clientes` (
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

-- Tabela de cupons (sem dependências)
CREATE TABLE `cupons` (
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

-- Tabela de pedidos (depende de clientes)
CREATE TABLE `pedidos` (
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

-- Tabela de itens do pedido (depende de pedidos e produtos)
CREATE TABLE `pedido_itens` (
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

-- Tabela de uso de cupons (depende de cupons, clientes e pedidos)
CREATE TABLE `cupom_usos` (
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

-- Tabela de configurações (sem dependências)
CREATE TABLE `configuracoes` (
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

-- Tabela de logs do sistema (depende de usuarios)
CREATE TABLE `logs_sistema` (
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
-- 3. ADICIONAR FOREIGN KEYS (APÓS TODAS AS TABELAS)
-- ========================================

-- Foreign key para categorias (auto-referência)
ALTER TABLE `categorias`
  ADD CONSTRAINT `fk_categoria_pai` FOREIGN KEY (`categoria_pai_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL;

-- Foreign key para produtos
ALTER TABLE `produtos`
  ADD CONSTRAINT `fk_produto_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL;

-- Foreign key para produto_imagens
ALTER TABLE `produto_imagens`
  ADD CONSTRAINT `fk_imagem_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE;

-- Foreign key para pedidos
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedido_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

-- Foreign key para pedido_itens
ALTER TABLE `pedido_itens`
  ADD CONSTRAINT `fk_item_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_item_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE;

-- Foreign key para cupom_usos
ALTER TABLE `cupom_usos`
  ADD CONSTRAINT `fk_uso_cupom` FOREIGN KEY (`cupom_id`) REFERENCES `cupons` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_uso_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_uso_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE;

-- Foreign key para logs_sistema
ALTER TABLE `logs_sistema`
  ADD CONSTRAINT `fk_log_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

-- ========================================
-- 4. ÍNDICES ADICIONAIS PARA PERFORMANCE
-- ========================================

CREATE INDEX `idx_produtos_categoria_ativo` ON `produtos` (`categoria_id`, `ativo`);
CREATE INDEX `idx_produtos_preco` ON `produtos` (`preco`);
CREATE INDEX `idx_pedidos_data` ON `pedidos` (`criado_em`);
CREATE INDEX `idx_pedidos_total` ON `pedidos` (`total`);
CREATE INDEX `idx_clientes_ultima_compra` ON `clientes` (`ultima_compra`);
CREATE INDEX `idx_cupons_validade` ON `cupons` (`data_inicio`, `data_fim`, `ativo`);

-- ========================================
-- 5. INSERIR DADOS INICIAIS
-- ========================================

-- Inserir usuários padrão (senhas: password e peluciapet123)
INSERT INTO `usuarios` (`nome`, `email`, `senha`, `nivel`) VALUES
('Administrador', 'admin@peluciapet.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('PelúciaPet', 'peluciapet@peluciapet.com.br', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'gerente');

-- Inserir categorias padrão
INSERT INTO `categorias` (`nome`, `slug`, `descricao`, `categoria_pai_id`, `nivel`, `ordem`, `icone`, `cor`) VALUES
('Caminhas', 'caminhas', 'Caminhas confortáveis para pets de todos os tamanhos', NULL, 1, 1, 'bed', '#8B4513'),
('Roupinhas', 'roupinhas', 'Roupas estilosas e confortáveis para pets', NULL, 1, 2, 'shirt', '#D4A04C'),
('Acessórios', 'acessorios', 'Acessórios diversos para pets', NULL, 1, 3, 'star', '#A0522D'),
('Brinquedos', 'brinquedos', 'Brinquedos divertidos para pets', NULL, 1, 4, 'toy', '#5C2C0D');

-- Inserir subcategorias para Caminhas
INSERT INTO `categorias` (`nome`, `slug`, `descricao`, `categoria_pai_id`, `nivel`, `ordem`, `icone`, `cor`) VALUES
('Caminhas Pequenas', 'caminhas-pequenas', 'Para pets de porte pequeno', 1, 2, 1, 'bed', '#8B4513'),
('Caminhas Médias', 'caminhas-medias', 'Para pets de porte médio', 1, 2, 2, 'bed', '#8B4513'),
('Caminhas Grandes', 'caminhas-grandes', 'Para pets de porte grande', 1, 2, 3, 'bed', '#8B4513');

-- Inserir subcategorias para Roupinhas
INSERT INTO `categorias` (`nome`, `slug`, `descricao`, `categoria_pai_id`, `nivel`, `ordem`, `icone`, `cor`) VALUES
('Camisetas', 'camisetas', 'Camisetas para pets', 2, 2, 1, 'shirt', '#D4A04C'),
('Moletons', 'moletons', 'Moletons quentinhos para pets', 2, 2, 2, 'shirt', '#D4A04C'),
('Fantasias', 'fantasias', 'Fantasias divertidas para pets', 2, 2, 3, 'shirt', '#D4A04C');

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
('manutencao_modo', '0', 'boolean', 'sistema', 'Modo manutenção ativo'),
('versao_sistema', '2.1.0', 'string', 'sistema', 'Versão atual do sistema');

-- Inserir produtos de exemplo
INSERT INTO `produtos` (`categoria_id`, `nome`, `sku`, `descricao`, `preco`, `peso`, `comprimento`, `altura`, `largura`, `estoque`, `estoque_minimo`, `ativo`, `destaque`) VALUES
(5, 'Caminha Pequena Marrom', 'CAM-PEQ-001', 'Caminha confortável para pets pequenos, cor marrom', 89.90, 0.500, 40.00, 10.00, 30.00, 10, 2, 1, 1),
(6, 'Caminha Média Bege', 'CAM-MED-001', 'Caminha confortável para pets médios, cor bege', 129.90, 0.800, 60.00, 12.00, 45.00, 8, 2, 1, 1),
(8, 'Camiseta Pet Azul', 'CAM-AZU-001', 'Camiseta azul para pets, tamanho P', 29.90, 0.100, 25.00, 1.00, 20.00, 15, 3, 1, 0),
(9, 'Moletom Pet Vermelho', 'MOL-VER-001', 'Moletom vermelho quentinho para pets', 49.90, 0.200, 30.00, 2.00, 25.00, 12, 3, 1, 1);

-- Inserir cupons de exemplo
INSERT INTO `cupons` (`codigo`, `nome`, `descricao`, `tipo`, `valor`, `valor_minimo_pedido`, `data_inicio`, `data_fim`, `ativo`, `primeira_compra`) VALUES
('BEMVINDO10', 'Bem-vindo 10%', 'Desconto de 10% para primeira compra', 'percentual', 10.00, 50.00, '2024-01-01 00:00:00', '2024-12-31 23:59:59', 1, 1),
('FRETEGRATIS', 'Frete Grátis', 'Frete grátis para qualquer compra', 'frete_gratis', 0.00, 0.00, '2024-01-01 00:00:00', '2024-12-31 23:59:59', 1, 0),
('DESCONTO20', 'Desconto R$ 20', 'R$ 20 de desconto em compras acima de R$ 100', 'valor_fixo', 20.00, 100.00, '2024-01-01 00:00:00', '2024-12-31 23:59:59', 1, 0);

-- ========================================
-- 6. FINALIZAÇÃO
-- ========================================

COMMIT;

-- Mensagem de sucesso
SELECT 'Sistema PelúciaPet v2.1 instalado com sucesso!' as status,
       'Banco de dados criado do zero com todas as funcionalidades' as detalhes,
       'Usuários padrão: admin@peluciapet.com.br (admin) e peluciapet@peluciapet.com.br (gerente)' as credenciais,
       'Senhas padrão: password e peluciapet123' as senhas;

