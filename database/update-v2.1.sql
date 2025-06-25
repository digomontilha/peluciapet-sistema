-- ========================================
-- ATUALIZAÇÃO BANCO DE DADOS - PelúciaPet v2.1
-- Sistema completo com novas funcionalidades
-- ========================================

-- Tabela de imagens de produtos
CREATE TABLE IF NOT EXISTS produto_imagens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    nome_arquivo VARCHAR(255) NOT NULL,
    nome_original VARCHAR(255) NOT NULL,
    caminho VARCHAR(500) NOT NULL,
    tamanho INT NOT NULL,
    tipo_mime VARCHAR(100) NOT NULL,
    largura INT DEFAULT 0,
    altura INT DEFAULT 0,
    ordem INT DEFAULT 0,
    principal TINYINT(1) DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    data_upload TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
    INDEX idx_produto_id (produto_id),
    INDEX idx_ordem (ordem),
    INDEX idx_principal (principal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de categorias hierárquicas
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    descricao TEXT,
    categoria_pai_id INT NULL,
    nivel INT DEFAULT 1,
    caminho VARCHAR(500) NOT NULL,
    ordem INT DEFAULT 0,
    ativo TINYINT(1) DEFAULT 1,
    meta_title VARCHAR(200),
    meta_description TEXT,
    meta_keywords TEXT,
    imagem VARCHAR(255),
    cor_destaque VARCHAR(7) DEFAULT '#FF6B9D',
    icone VARCHAR(50) DEFAULT 'fas fa-paw',
    mostrar_home TINYINT(1) DEFAULT 0,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_pai_id) REFERENCES categorias(id) ON DELETE SET NULL,
    INDEX idx_slug (slug),
    INDEX idx_categoria_pai (categoria_pai_id),
    INDEX idx_nivel (nivel),
    INDEX idx_ativo (ativo),
    INDEX idx_mostrar_home (mostrar_home)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de pedidos
CREATE TABLE IF NOT EXISTS pedidos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_pedido VARCHAR(20) UNIQUE NOT NULL,
    cliente_id INT NULL,
    cliente_nome VARCHAR(100) NOT NULL,
    cliente_email VARCHAR(100) NOT NULL,
    cliente_telefone VARCHAR(20),
    cliente_documento VARCHAR(20),
    
    -- Endereço de entrega
    cep VARCHAR(10) NOT NULL,
    endereco VARCHAR(200) NOT NULL,
    numero VARCHAR(20) NOT NULL,
    complemento VARCHAR(100),
    bairro VARCHAR(100) NOT NULL,
    cidade VARCHAR(100) NOT NULL,
    estado VARCHAR(2) NOT NULL,
    
    -- Valores
    valor_produtos DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_frete DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_desconto DECIMAL(10,2) NOT NULL DEFAULT 0,
    valor_total DECIMAL(10,2) NOT NULL DEFAULT 0,
    
    -- Pagamento e entrega
    metodo_pagamento VARCHAR(50) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'pendente',
    codigo_rastreamento VARCHAR(50),
    
    -- Cupom aplicado
    cupom_id INT NULL,
    cupom_codigo VARCHAR(50),
    cupom_desconto DECIMAL(10,2) DEFAULT 0,
    
    -- Datas
    data_pedido TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_pagamento TIMESTAMP NULL,
    data_envio TIMESTAMP NULL,
    data_entrega TIMESTAMP NULL,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Observações
    observacoes TEXT,
    observacoes_internas TEXT,
    
    INDEX idx_numero_pedido (numero_pedido),
    INDEX idx_cliente_email (cliente_email),
    INDEX idx_status (status),
    INDEX idx_data_pedido (data_pedido),
    INDEX idx_cupom_id (cupom_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de itens do pedido
CREATE TABLE IF NOT EXISTS pedido_itens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    produto_id INT NOT NULL,
    produto_nome VARCHAR(200) NOT NULL,
    produto_sku VARCHAR(50),
    quantidade INT NOT NULL DEFAULT 1,
    preco_unitario DECIMAL(10,2) NOT NULL,
    preco_total DECIMAL(10,2) NOT NULL,
    observacoes TEXT,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT,
    INDEX idx_pedido_id (pedido_id),
    INDEX idx_produto_id (produto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de cupons
CREATE TABLE IF NOT EXISTS cupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(50) UNIQUE NOT NULL,
    nome VARCHAR(100),
    descricao TEXT,
    tipo_desconto ENUM('percentual', 'valor_fixo', 'frete_gratis') NOT NULL,
    valor_desconto DECIMAL(10,2) NOT NULL,
    valor_minimo_pedido DECIMAL(10,2) DEFAULT 0,
    valor_maximo_desconto DECIMAL(10,2) DEFAULT 0,
    limite_uso_total INT DEFAULT 0,
    limite_uso_cliente INT DEFAULT 1,
    data_inicio TIMESTAMP NULL,
    data_fim TIMESTAMP NULL,
    ativo TINYINT(1) DEFAULT 1,
    primeira_compra_apenas TINYINT(1) DEFAULT 0,
    categorias_permitidas JSON NULL,
    produtos_permitidos JSON NULL,
    clientes_permitidos JSON NULL,
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_codigo (codigo),
    INDEX idx_ativo (ativo),
    INDEX idx_data_inicio (data_inicio),
    INDEX idx_data_fim (data_fim)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de uso de cupons
CREATE TABLE IF NOT EXISTS cupom_usos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cupom_id INT NOT NULL,
    pedido_id INT NOT NULL,
    cliente_id INT NULL,
    valor_desconto DECIMAL(10,2) NOT NULL,
    data_uso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cupom_id) REFERENCES cupons(id) ON DELETE CASCADE,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    INDEX idx_cupom_id (cupom_id),
    INDEX idx_pedido_id (pedido_id),
    INDEX idx_cliente_id (cliente_id),
    INDEX idx_data_uso (data_uso)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de configurações do sistema
CREATE TABLE IF NOT EXISTS configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) UNIQUE NOT NULL,
    valor TEXT NOT NULL,
    descricao TEXT,
    tipo ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    data_criacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Atualizar tabela de produtos para incluir dimensões e categoria
ALTER TABLE produtos 
ADD COLUMN IF NOT EXISTS categoria_id INT NULL AFTER id,
ADD COLUMN IF NOT EXISTS peso DECIMAL(8,3) DEFAULT 0 AFTER preco,
ADD COLUMN IF NOT EXISTS comprimento DECIMAL(8,2) DEFAULT 0 AFTER peso,
ADD COLUMN IF NOT EXISTS altura DECIMAL(8,2) DEFAULT 0 AFTER comprimento,
ADD COLUMN IF NOT EXISTS largura DECIMAL(8,2) DEFAULT 0 AFTER altura,
ADD COLUMN IF NOT EXISTS sku VARCHAR(50) AFTER nome,
ADD COLUMN IF NOT EXISTS estoque INT DEFAULT 0 AFTER largura,
ADD COLUMN IF NOT EXISTS estoque_minimo INT DEFAULT 0 AFTER estoque,
ADD COLUMN IF NOT EXISTS meta_title VARCHAR(200) AFTER descricao,
ADD COLUMN IF NOT EXISTS meta_description TEXT AFTER meta_title,
ADD COLUMN IF NOT EXISTS meta_keywords TEXT AFTER meta_description,
ADD FOREIGN KEY IF NOT EXISTS fk_produto_categoria (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL;

-- Índices adicionais para produtos
ALTER TABLE produtos 
ADD INDEX IF NOT EXISTS idx_categoria_id (categoria_id),
ADD INDEX IF NOT EXISTS idx_sku (sku),
ADD INDEX IF NOT EXISTS idx_estoque (estoque);

-- ========================================
-- DADOS INICIAIS
-- ========================================

-- Categorias principais
INSERT IGNORE INTO categorias (id, nome, slug, descricao, categoria_pai_id, nivel, caminho, ordem, ativo, cor_destaque, icone, mostrar_home) VALUES
(1, 'Caminhas', 'caminhas', 'Caminhas confortáveis para pets de todos os tamanhos', NULL, 1, 'caminhas', 1, 1, '#FF6B9D', 'fas fa-bed', 1),
(2, 'Roupinhas', 'roupinhas', 'Roupas estilosas e confortáveis para seus pets', NULL, 1, 'roupinhas', 2, 1, '#A0522D', 'fas fa-tshirt', 1),
(3, 'Acessórios', 'acessorios', 'Acessórios diversos para pets', NULL, 1, 'acessorios', 3, 1, '#D4A04C', 'fas fa-bone', 1);

-- Subcategorias de caminhas
INSERT IGNORE INTO categorias (nome, slug, descricao, categoria_pai_id, nivel, caminho, ordem, ativo, cor_destaque, icone) VALUES
('Caminhas Pequenas', 'caminhas-pequenas', 'Para pets de pequeno porte', 1, 2, 'caminhas/caminhas-pequenas', 1, 1, '#FF6B9D', 'fas fa-bed'),
('Caminhas Médias', 'caminhas-medias', 'Para pets de médio porte', 1, 2, 'caminhas/caminhas-medias', 2, 1, '#FF6B9D', 'fas fa-bed'),
('Caminhas Grandes', 'caminhas-grandes', 'Para pets de grande porte', 1, 2, 'caminhas/caminhas-grandes', 3, 1, '#FF6B9D', 'fas fa-bed');

-- Subcategorias de roupinhas
INSERT IGNORE INTO categorias (nome, slug, descricao, categoria_pai_id, nivel, caminho, ordem, ativo, cor_destaque, icone) VALUES
('Roupas de Inverno', 'roupas-inverno', 'Roupas quentinhas para o frio', 2, 2, 'roupinhas/roupas-inverno', 1, 1, '#A0522D', 'fas fa-snowflake'),
('Roupas de Verão', 'roupas-verao', 'Roupas leves para o calor', 2, 2, 'roupinhas/roupas-verao', 2, 1, '#A0522D', 'fas fa-sun'),
('Fantasias', 'fantasias', 'Fantasias divertidas para pets', 2, 2, 'roupinhas/fantasias', 3, 1, '#A0522D', 'fas fa-mask');

-- Atualizar produtos existentes com categorias
UPDATE produtos SET categoria_id = 1, peso = 0.5, comprimento = 50, altura = 10, largura = 40, estoque = 10 WHERE tipo = 'caminha';
UPDATE produtos SET categoria_id = 2, peso = 0.2, comprimento = 30, altura = 2, largura = 25, estoque = 15 WHERE tipo = 'roupinha';

-- Configurações iniciais
INSERT IGNORE INTO configuracoes (chave, valor, descricao, tipo) VALUES
('site_nome', 'PelúciaPet', 'Nome do site', 'string'),
('site_email', 'contato@peluciapet.com.br', 'Email de contato', 'string'),
('site_telefone', '(11) 99999-9999', 'Telefone de contato', 'string'),
('frete_gratis_valor', '100.00', 'Valor mínimo para frete grátis', 'number'),
('correios_cep_origem', '01310-100', 'CEP de origem para cálculo de frete', 'string'),
('pagamento_pix_chave', 'contato@peluciapet.com.br', 'Chave PIX para pagamentos', 'string'),
('whatsapp_numero', '5511999999999', 'Número do WhatsApp', 'string'),
('instagram_url', 'https://instagram.com/peluciapet', 'URL do Instagram', 'string'),
('facebook_url', 'https://facebook.com/peluciapet', 'URL do Facebook', 'string');

-- Cupons de exemplo
INSERT IGNORE INTO cupons (codigo, nome, descricao, tipo_desconto, valor_desconto, valor_minimo_pedido, ativo, primeira_compra_apenas) VALUES
('BEMVINDO10', 'Bem-vindo', 'Desconto de 10% para novos clientes', 'percentual', 10.00, 50.00, 1, 1),
('FRETEGRATIS', 'Frete Grátis', 'Frete grátis em compras acima de R$ 80', 'frete_gratis', 0.00, 80.00, 1, 0),
('PELUCIA20', 'Desconto Especial', 'R$ 20 de desconto em compras acima de R$ 150', 'valor_fixo', 20.00, 150.00, 1, 0);

-- ========================================
-- TRIGGERS E PROCEDURES
-- ========================================

-- Trigger para atualizar número do pedido
DELIMITER //
CREATE TRIGGER IF NOT EXISTS tr_pedidos_numero 
BEFORE INSERT ON pedidos 
FOR EACH ROW 
BEGIN 
    IF NEW.numero_pedido IS NULL OR NEW.numero_pedido = '' THEN
        SET NEW.numero_pedido = CONCAT('PET', YEAR(NOW()), LPAD(LAST_INSERT_ID() + 1, 6, '0'));
    END IF;
END//
DELIMITER ;

-- Trigger para calcular total do pedido
DELIMITER //
CREATE TRIGGER IF NOT EXISTS tr_pedido_itens_total 
AFTER INSERT ON pedido_itens 
FOR EACH ROW 
BEGIN 
    UPDATE pedidos 
    SET valor_produtos = (
        SELECT SUM(preco_total) 
        FROM pedido_itens 
        WHERE pedido_id = NEW.pedido_id
    ),
    valor_total = valor_produtos + valor_frete - valor_desconto
    WHERE id = NEW.pedido_id;
END//
DELIMITER ;

-- Trigger para atualizar estoque
DELIMITER //
CREATE TRIGGER IF NOT EXISTS tr_pedido_estoque 
AFTER UPDATE ON pedidos 
FOR EACH ROW 
BEGIN 
    IF NEW.status = 'confirmado' AND OLD.status != 'confirmado' THEN
        UPDATE produtos p
        INNER JOIN pedido_itens pi ON p.id = pi.produto_id
        SET p.estoque = p.estoque - pi.quantidade
        WHERE pi.pedido_id = NEW.id;
    END IF;
    
    IF NEW.status = 'cancelado' AND OLD.status = 'confirmado' THEN
        UPDATE produtos p
        INNER JOIN pedido_itens pi ON p.id = pi.produto_id
        SET p.estoque = p.estoque + pi.quantidade
        WHERE pi.pedido_id = NEW.id;
    END IF;
END//
DELIMITER ;

-- ========================================
-- VIEWS ÚTEIS
-- ========================================

-- View de produtos com categoria
CREATE OR REPLACE VIEW vw_produtos_completo AS
SELECT 
    p.*,
    c.nome as categoria_nome,
    c.slug as categoria_slug,
    c.cor_destaque as categoria_cor,
    (SELECT COUNT(*) FROM produto_imagens pi WHERE pi.produto_id = p.id AND pi.ativo = 1) as total_imagens,
    (SELECT caminho FROM produto_imagens pi WHERE pi.produto_id = p.id AND pi.principal = 1 AND pi.ativo = 1 LIMIT 1) as imagem_principal
FROM produtos p
LEFT JOIN categorias c ON p.categoria_id = c.id;

-- View de estatísticas de vendas
CREATE OR REPLACE VIEW vw_vendas_estatisticas AS
SELECT 
    DATE(p.data_pedido) as data,
    COUNT(*) as total_pedidos,
    SUM(p.valor_total) as receita_total,
    AVG(p.valor_total) as ticket_medio,
    COUNT(DISTINCT p.cliente_email) as clientes_unicos
FROM pedidos p
WHERE p.status NOT IN ('cancelado', 'devolvido')
GROUP BY DATE(p.data_pedido);

-- View de produtos mais vendidos
CREATE OR REPLACE VIEW vw_produtos_mais_vendidos AS
SELECT 
    p.id,
    p.nome,
    p.preco,
    c.nome as categoria_nome,
    SUM(pi.quantidade) as quantidade_vendida,
    SUM(pi.preco_total) as receita_total,
    COUNT(DISTINCT pi.pedido_id) as pedidos_distintos
FROM produtos p
INNER JOIN pedido_itens pi ON p.id = pi.produto_id
INNER JOIN pedidos ped ON pi.pedido_id = ped.id
LEFT JOIN categorias c ON p.categoria_id = c.id
WHERE ped.status NOT IN ('cancelado', 'devolvido')
GROUP BY p.id, p.nome, p.preco, c.nome
ORDER BY quantidade_vendida DESC;

-- ========================================
-- ÍNDICES DE PERFORMANCE
-- ========================================

-- Índices compostos para consultas frequentes
ALTER TABLE pedidos ADD INDEX IF NOT EXISTS idx_status_data (status, data_pedido);
ALTER TABLE pedido_itens ADD INDEX IF NOT EXISTS idx_produto_pedido (produto_id, pedido_id);
ALTER TABLE produto_imagens ADD INDEX IF NOT EXISTS idx_produto_principal (produto_id, principal);
ALTER TABLE cupom_usos ADD INDEX IF NOT EXISTS idx_cupom_data (cupom_id, data_uso);

-- ========================================
-- COMENTÁRIOS DAS TABELAS
-- ========================================

ALTER TABLE categorias COMMENT = 'Sistema hierárquico de categorias de produtos';
ALTER TABLE produto_imagens COMMENT = 'Imagens dos produtos com suporte a múltiplas imagens';
ALTER TABLE pedidos COMMENT = 'Pedidos do sistema com informações completas';
ALTER TABLE pedido_itens COMMENT = 'Itens dos pedidos';
ALTER TABLE cupons COMMENT = 'Sistema de cupons de desconto';
ALTER TABLE cupom_usos COMMENT = 'Histórico de uso dos cupons';
ALTER TABLE configuracoes COMMENT = 'Configurações gerais do sistema';

-- ========================================
-- FINALIZAÇÃO
-- ========================================

-- Verificar integridade
SET foreign_key_checks = 1;

-- Otimizar tabelas
OPTIMIZE TABLE produtos, categorias, pedidos, pedido_itens, cupons, cupom_usos, produto_imagens, configuracoes;

-- Log da atualização
INSERT INTO configuracoes (chave, valor, descricao) VALUES 
('db_version', '2.1.0', 'Versão do banco de dados')
ON DUPLICATE KEY UPDATE 
valor = '2.1.0', 
data_atualizacao = CURRENT_TIMESTAMP;

SELECT 'Banco de dados PelúciaPet v2.1 atualizado com sucesso!' as status;

