-- =====================================================
-- Script de Instalação do Banco de Dados PelúciaPet
-- Sistema: MySQL 5.7+ / MariaDB 10.3+
-- Versão: 2.0.0
-- Data: 2024
-- =====================================================

-- Configurações iniciais
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';
SET time_zone = '-03:00';

-- =====================================================
-- 1. TABELA DE CATEGORIAS
-- =====================================================

DROP TABLE IF EXISTS `categorias`;
CREATE TABLE `categorias` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nome` varchar(100) NOT NULL,
    `slug` varchar(100) NOT NULL,
    `descricao` text,
    `icone` varchar(50) DEFAULT NULL,
    `cor_tema` varchar(7) DEFAULT '#D4A04C',
    `ordem` int(11) DEFAULT 0,
    `ativo` tinyint(1) DEFAULT 1,
    `meta_title` varchar(200) DEFAULT NULL,
    `meta_description` varchar(300) DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_categorias_slug` (`slug`),
    KEY `idx_categorias_ativo` (`ativo`),
    KEY `idx_categorias_ordem` (`ordem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 2. TABELA DE TAMANHOS
-- =====================================================

DROP TABLE IF EXISTS `tamanhos`;
CREATE TABLE `tamanhos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `categoria_id` int(11) NOT NULL,
    `nome` varchar(50) NOT NULL,
    `sigla` varchar(10) NOT NULL,
    `dimensoes` varchar(100) DEFAULT NULL,
    `peso_recomendado` varchar(50) DEFAULT NULL,
    `descricao` text,
    `ordem` int(11) DEFAULT 0,
    `ativo` tinyint(1) DEFAULT 1,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_tamanhos_categoria` (`categoria_id`),
    KEY `idx_tamanhos_ativo` (`ativo`),
    KEY `idx_tamanhos_ordem` (`ordem`),
    CONSTRAINT `fk_tamanhos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 3. TABELA DE CORES
-- =====================================================

DROP TABLE IF EXISTS `cores`;
CREATE TABLE `cores` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nome` varchar(50) NOT NULL,
    `codigo_hex` varchar(7) NOT NULL,
    `codigo_rgb` varchar(20) DEFAULT NULL,
    `familia_cor` varchar(30) DEFAULT NULL,
    `ordem` int(11) DEFAULT 0,
    `ativo` tinyint(1) DEFAULT 1,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_cores_codigo_hex` (`codigo_hex`),
    KEY `idx_cores_ativo` (`ativo`),
    KEY `idx_cores_familia` (`familia_cor`),
    KEY `idx_cores_ordem` (`ordem`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 4. TABELA DE PRODUTOS
-- =====================================================

DROP TABLE IF EXISTS `produtos`;
CREATE TABLE `produtos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `nome` varchar(200) NOT NULL,
    `slug` varchar(200) NOT NULL,
    `descricao` longtext NOT NULL,
    `categoria_id` int(11) NOT NULL,
    `preco_base` decimal(10,2) NOT NULL,
    `preco_promocional` decimal(10,2) DEFAULT NULL,
    `material` varchar(200) DEFAULT NULL,
    `peso` varchar(50) DEFAULT NULL,
    `dimensoes` varchar(100) DEFAULT NULL,
    `cuidados` text,
    `destaque` tinyint(1) DEFAULT 0,
    `ativo` tinyint(1) DEFAULT 1,
    `meta_title` varchar(200) DEFAULT NULL,
    `meta_description` varchar(300) DEFAULT NULL,
    `views` int(11) DEFAULT 0,
    `vendas` int(11) DEFAULT 0,
    `avaliacao_media` decimal(3,2) DEFAULT NULL,
    `total_avaliacoes` int(11) DEFAULT 0,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_produtos_slug` (`slug`),
    KEY `fk_produtos_categoria` (`categoria_id`),
    KEY `idx_produtos_ativo` (`ativo`),
    KEY `idx_produtos_destaque` (`destaque`),
    KEY `idx_produtos_preco` (`preco_base`),
    KEY `idx_produtos_created` (`created_at`),
    KEY `idx_produtos_views` (`views`),
    KEY `idx_produtos_vendas` (`vendas`),
    FULLTEXT KEY `ft_produtos_busca` (`nome`, `descricao`),
    CONSTRAINT `fk_produtos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 5. TABELA DE VARIAÇÕES DE PRODUTOS
-- =====================================================

DROP TABLE IF EXISTS `produto_variacoes`;
CREATE TABLE `produto_variacoes` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `produto_id` int(11) NOT NULL,
    `tamanho_id` int(11) NOT NULL,
    `cor_id` int(11) NOT NULL,
    `sku` varchar(50) NOT NULL,
    `preco_adicional` decimal(10,2) DEFAULT 0.00,
    `estoque_atual` int(11) DEFAULT 0,
    `estoque_minimo` int(11) DEFAULT 1,
    `estoque_maximo` int(11) DEFAULT 999,
    `peso_real` decimal(8,3) DEFAULT NULL,
    `dimensoes_reais` varchar(100) DEFAULT NULL,
    `ativo` tinyint(1) DEFAULT 1,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_produto_variacoes_sku` (`sku`),
    UNIQUE KEY `uk_produto_variacoes_combinacao` (`produto_id`, `tamanho_id`, `cor_id`),
    KEY `fk_variacoes_produto` (`produto_id`),
    KEY `fk_variacoes_tamanho` (`tamanho_id`),
    KEY `fk_variacoes_cor` (`cor_id`),
    KEY `idx_variacoes_ativo` (`ativo`),
    KEY `idx_variacoes_estoque` (`estoque_atual`),
    CONSTRAINT `fk_variacoes_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_variacoes_tamanho` FOREIGN KEY (`tamanho_id`) REFERENCES `tamanhos` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_variacoes_cor` FOREIGN KEY (`cor_id`) REFERENCES `cores` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 6. TABELA DE IMAGENS DE PRODUTOS
-- =====================================================

DROP TABLE IF EXISTS `produto_imagens`;
CREATE TABLE `produto_imagens` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `produto_id` int(11) NOT NULL,
    `url` varchar(500) NOT NULL,
    `alt_text` varchar(200) DEFAULT NULL,
    `titulo` varchar(200) DEFAULT NULL,
    `ordem` int(11) DEFAULT 0,
    `principal` tinyint(1) DEFAULT 0,
    `tamanho_arquivo` int(11) DEFAULT NULL,
    `largura` int(11) DEFAULT NULL,
    `altura` int(11) DEFAULT NULL,
    `formato` varchar(10) DEFAULT NULL,
    `ativo` tinyint(1) DEFAULT 1,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_imagens_produto` (`produto_id`),
    KEY `idx_imagens_ativo` (`ativo`),
    KEY `idx_imagens_principal` (`principal`),
    KEY `idx_imagens_ordem` (`ordem`),
    CONSTRAINT `fk_imagens_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 7. TABELA DE USUÁRIOS ADMIN (FUTURA)
-- =====================================================

DROP TABLE IF EXISTS `usuarios_admin`;
CREATE TABLE `usuarios_admin` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `email` varchar(100) NOT NULL,
    `password_hash` varchar(255) NOT NULL,
    `nome_completo` varchar(200) NOT NULL,
    `role` enum('admin','manager','editor','viewer') DEFAULT 'viewer',
    `ativo` tinyint(1) DEFAULT 1,
    `ultimo_login` datetime DEFAULT NULL,
    `tentativas_login` int(11) DEFAULT 0,
    `bloqueado_ate` datetime DEFAULT NULL,
    `token_reset` varchar(100) DEFAULT NULL,
    `token_reset_expira` datetime DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_usuarios_username` (`username`),
    UNIQUE KEY `uk_usuarios_email` (`email`),
    KEY `idx_usuarios_ativo` (`ativo`),
    KEY `idx_usuarios_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 8. TABELA DE LOG DE ATIVIDADES
-- =====================================================

DROP TABLE IF EXISTS `log_atividades`;
CREATE TABLE `log_atividades` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `usuario_id` int(11) DEFAULT NULL,
    `acao` varchar(100) NOT NULL,
    `tabela_afetada` varchar(50) DEFAULT NULL,
    `registro_id` int(11) DEFAULT NULL,
    `dados_anteriores` longtext,
    `dados_novos` longtext,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` varchar(500) DEFAULT NULL,
    `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_log_usuario` (`usuario_id`),
    KEY `idx_log_acao` (`acao`),
    KEY `idx_log_tabela` (`tabela_afetada`),
    KEY `idx_log_created` (`created_at`),
    CONSTRAINT `fk_log_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios_admin` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- 9. VIEWS ÚTEIS
-- =====================================================

-- View de produtos com informações completas
DROP VIEW IF EXISTS `v_produtos_completos`;
CREATE VIEW `v_produtos_completos` AS
SELECT 
    p.*,
    c.nome as categoria_nome,
    c.slug as categoria_slug,
    c.icone as categoria_icone,
    (SELECT COUNT(*) FROM produto_variacoes pv WHERE pv.produto_id = p.id AND pv.ativo = 1) as total_variacoes,
    (SELECT SUM(pv.estoque_atual) FROM produto_variacoes pv WHERE pv.produto_id = p.id AND pv.ativo = 1) as estoque_total,
    (SELECT MIN(p.preco_base + IFNULL(pv.preco_adicional, 0)) FROM produto_variacoes pv WHERE pv.produto_id = p.id AND pv.ativo = 1) as preco_minimo,
    (SELECT MAX(p.preco_base + IFNULL(pv.preco_adicional, 0)) FROM produto_variacoes pv WHERE pv.produto_id = p.id AND pv.ativo = 1) as preco_maximo,
    (SELECT pi.url FROM produto_imagens pi WHERE pi.produto_id = p.id AND pi.principal = 1 AND pi.ativo = 1 LIMIT 1) as imagem_principal,
    (SELECT COUNT(*) FROM produto_imagens pi WHERE pi.produto_id = p.id AND pi.ativo = 1) as total_imagens
FROM produtos p
LEFT JOIN categorias c ON p.categoria_id = c.id
WHERE p.ativo = 1;

-- View de estatísticas por categoria
DROP VIEW IF EXISTS `v_estatisticas_categoria`;
CREATE VIEW `v_estatisticas_categoria` AS
SELECT 
    c.id,
    c.nome,
    c.slug,
    COUNT(p.id) as total_produtos,
    SUM(CASE WHEN p.destaque = 1 THEN 1 ELSE 0 END) as produtos_destaque,
    IFNULL(SUM(pv.estoque_atual), 0) as estoque_total,
    IFNULL(AVG(p.preco_base), 0) as preco_medio,
    IFNULL(MIN(p.preco_base), 0) as preco_minimo,
    IFNULL(MAX(p.preco_base), 0) as preco_maximo
FROM categorias c
LEFT JOIN produtos p ON c.id = p.categoria_id AND p.ativo = 1
LEFT JOIN produto_variacoes pv ON p.id = pv.produto_id AND pv.ativo = 1
WHERE c.ativo = 1
GROUP BY c.id, c.nome, c.slug;

-- =====================================================
-- 10. TRIGGERS
-- =====================================================

-- Trigger para atualizar slug automaticamente
DELIMITER $$
DROP TRIGGER IF EXISTS `tr_produtos_slug_insert`$$
CREATE TRIGGER `tr_produtos_slug_insert` 
BEFORE INSERT ON `produtos` 
FOR EACH ROW 
BEGIN
    IF NEW.slug IS NULL OR NEW.slug = '' THEN
        SET NEW.slug = LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(
            NEW.nome, ' ', '-'), 'ã', 'a'), 'ç', 'c'), 'é', 'e'), 'ô', 'o'));
    END IF;
END$$

-- Trigger para log de alterações em produtos
DROP TRIGGER IF EXISTS `tr_produtos_log_update`$$
CREATE TRIGGER `tr_produtos_log_update` 
AFTER UPDATE ON `produtos` 
FOR EACH ROW 
BEGIN
    INSERT INTO log_atividades (acao, tabela_afetada, registro_id, dados_anteriores, dados_novos, created_at)
    VALUES ('UPDATE', 'produtos', NEW.id, 
            JSON_OBJECT('nome', OLD.nome, 'preco_base', OLD.preco_base, 'ativo', OLD.ativo),
            JSON_OBJECT('nome', NEW.nome, 'preco_base', NEW.preco_base, 'ativo', NEW.ativo),
            NOW());
END$$

-- Trigger para atualizar estoque quando variação é alterada
DROP TRIGGER IF EXISTS `tr_variacoes_estoque_update`$$
CREATE TRIGGER `tr_variacoes_estoque_update` 
AFTER UPDATE ON `produto_variacoes` 
FOR EACH ROW 
BEGIN
    IF OLD.estoque_atual != NEW.estoque_atual THEN
        INSERT INTO log_atividades (acao, tabela_afetada, registro_id, dados_anteriores, dados_novos, created_at)
        VALUES ('ESTOQUE_UPDATE', 'produto_variacoes', NEW.id,
                JSON_OBJECT('estoque_anterior', OLD.estoque_atual),
                JSON_OBJECT('estoque_novo', NEW.estoque_atual),
                NOW());
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- 11. STORED PROCEDURES
-- =====================================================

-- Procedure para obter estatísticas gerais
DELIMITER $$
DROP PROCEDURE IF EXISTS `sp_obter_estatisticas_gerais`$$
CREATE PROCEDURE `sp_obter_estatisticas_gerais`()
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM produtos WHERE ativo = 1) as total_produtos,
        (SELECT COUNT(*) FROM categorias WHERE ativo = 1) as total_categorias,
        (SELECT COUNT(*) FROM produto_variacoes WHERE ativo = 1) as total_variacoes,
        (SELECT SUM(estoque_atual) FROM produto_variacoes WHERE ativo = 1) as estoque_total,
        (SELECT COUNT(*) FROM produtos WHERE ativo = 1 AND destaque = 1) as produtos_destaque,
        (SELECT COUNT(DISTINCT produto_id) FROM produto_variacoes WHERE ativo = 1 AND estoque_atual <= estoque_minimo) as produtos_estoque_baixo,
        (SELECT AVG(preco_base) FROM produtos WHERE ativo = 1) as preco_medio,
        (SELECT COUNT(*) FROM produto_imagens WHERE ativo = 1) as total_imagens;
END$$

-- Procedure para buscar produtos com filtros
DROP PROCEDURE IF EXISTS `sp_buscar_produtos`$$
CREATE PROCEDURE `sp_buscar_produtos`(
    IN p_categoria_id INT,
    IN p_busca VARCHAR(200),
    IN p_preco_min DECIMAL(10,2),
    IN p_preco_max DECIMAL(10,2),
    IN p_destaque TINYINT,
    IN p_limite INT,
    IN p_offset INT
)
BEGIN
    SET @sql = 'SELECT * FROM v_produtos_completos WHERE 1=1';
    
    IF p_categoria_id IS NOT NULL THEN
        SET @sql = CONCAT(@sql, ' AND categoria_id = ', p_categoria_id);
    END IF;
    
    IF p_busca IS NOT NULL AND p_busca != '' THEN
        SET @sql = CONCAT(@sql, ' AND (nome LIKE "%', p_busca, '%" OR descricao LIKE "%', p_busca, '%")');
    END IF;
    
    IF p_preco_min IS NOT NULL THEN
        SET @sql = CONCAT(@sql, ' AND preco_base >= ', p_preco_min);
    END IF;
    
    IF p_preco_max IS NOT NULL THEN
        SET @sql = CONCAT(@sql, ' AND preco_base <= ', p_preco_max);
    END IF;
    
    IF p_destaque IS NOT NULL THEN
        SET @sql = CONCAT(@sql, ' AND destaque = ', p_destaque);
    END IF;
    
    SET @sql = CONCAT(@sql, ' ORDER BY destaque DESC, created_at DESC');
    
    IF p_limite IS NOT NULL THEN
        SET @sql = CONCAT(@sql, ' LIMIT ', p_offset, ', ', p_limite);
    END IF;
    
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
END$$

DELIMITER ;

-- =====================================================
-- 12. DADOS INICIAIS
-- =====================================================

-- Inserir categorias
INSERT INTO `categorias` (`nome`, `slug`, `descricao`, `icone`, `cor_tema`, `ordem`, `ativo`) VALUES
('Caminhas', 'caminhas', 'Caminhas confortáveis e aconchegantes para cães e gatos de todos os tamanhos', 'fas fa-bed', '#8B4513', 1, 1),
('Roupinhas', 'roupinhas', 'Roupinhas estilosas e funcionais para proteger e encantar seu pet', 'fas fa-tshirt', '#D4A04C', 2, 1);

-- Inserir tamanhos para caminhas
INSERT INTO `tamanhos` (`categoria_id`, `nome`, `sigla`, `dimensoes`, `peso_recomendado`, `descricao`, `ordem`) VALUES
(1, 'Pequeno', 'P', '40x40x17cm', 'Até 8kg', 'Ideal para cães pequenos como Chihuahua, Yorkshire, Pinscher', 1),
(1, 'Médio', 'M', '50x50x17cm', '8kg a 20kg', 'Ideal para cães médios como Beagle, Cocker, Border Collie', 2),
(1, 'Grande', 'G', '60x60x17cm', '20kg a 35kg', 'Ideal para cães grandes como Labrador, Golden Retriever, Pastor Alemão', 3),
(1, 'Extra Grande', 'XG', '70x70x20cm', 'Acima de 35kg', 'Ideal para cães gigantes como Rottweiler, São Bernardo, Dogue Alemão', 4);

-- Inserir tamanhos para roupinhas
INSERT INTO `tamanhos` (`categoria_id`, `nome`, `sigla`, `dimensoes`, `peso_recomendado`, `descricao`, `ordem`) VALUES
(2, 'Pequeno', 'P', 'Dorso: 20-25cm', 'Até 5kg', 'Para pets pequenos como Chihuahua, Yorkshire, Maltês', 1),
(2, 'Médio', 'M', 'Dorso: 25-35cm', '5kg a 15kg', 'Para pets médios como Beagle, Cocker, Shih Tzu', 2),
(2, 'Grande', 'G', 'Dorso: 35-45cm', '15kg a 30kg', 'Para pets grandes como Labrador, Golden, Boxer', 3),
(2, 'Extra Grande', 'GG', 'Dorso: 45-55cm', 'Acima de 30kg', 'Para pets gigantes como Pastor Alemão, Rottweiler', 4);

-- Inserir cores
INSERT INTO `cores` (`nome`, `codigo_hex`, `codigo_rgb`, `familia_cor`, `ordem`) VALUES
('Rosa', '#FFC0CB', 'rgb(255, 192, 203)', 'Rosa', 1),
('Azul', '#87CEEB', 'rgb(135, 206, 235)', 'Azul', 2),
('Vermelho', '#DC143C', 'rgb(220, 20, 60)', 'Vermelho', 3),
('Verde', '#90EE90', 'rgb(144, 238, 144)', 'Verde', 4),
('Amarelo', '#FFD700', 'rgb(255, 215, 0)', 'Amarelo', 5),
('Roxo', '#DDA0DD', 'rgb(221, 160, 221)', 'Roxo', 6),
('Laranja', '#FFA500', 'rgb(255, 165, 0)', 'Laranja', 7),
('Marrom', '#D2B48C', 'rgb(210, 180, 140)', 'Marrom', 8),
('Preto', '#2F2F2F', 'rgb(47, 47, 47)', 'Neutro', 9),
('Branco', '#F8F8FF', 'rgb(248, 248, 255)', 'Neutro', 10),
('Cinza', '#C0C0C0', 'rgb(192, 192, 192)', 'Neutro', 11),
('Bege', '#F5F5DC', 'rgb(245, 245, 220)', 'Neutro', 12);

-- Inserir produto de exemplo
INSERT INTO `produtos` (`nome`, `slug`, `descricao`, `categoria_id`, `preco_base`, `material`, `peso`, `dimensoes`, `cuidados`, `destaque`, `meta_title`, `meta_description`) VALUES
('Caminha Redonda Pelúcia Rosa', 'caminha-redonda-pelucia-rosa', 
'Caminha redonda super macia em pelúcia rosa, perfeita para proporcionar o máximo conforto ao seu pet. Confeccionada com materiais de alta qualidade, possui base antiderrapante e é totalmente lavável. O design elegante combina com qualquer ambiente, enquanto o interior acolchoado garante noites de sono tranquilas. Ideal para cães e gatos que adoram se aconchegar.', 
1, 89.90, 'Pelúcia e Algodão', '800g', 'Variável conforme tamanho', 'Lavar à máquina em água fria, secar à sombra', 1,
'Caminha Redonda Pelúcia Rosa - Conforto Premium para seu Pet | PelúciaPet',
'Caminha redonda em pelúcia rosa super macia. Base antiderrapante, totalmente lavável. Conforto premium para cães e gatos. Compre na PelúciaPet!');

-- Inserir variações do produto de exemplo
INSERT INTO `produto_variacoes` (`produto_id`, `tamanho_id`, `cor_id`, `sku`, `estoque_atual`, `estoque_minimo`) VALUES
(1, 1, 1, 'PET-0001-T1-C1', 15, 3),
(1, 2, 1, 'PET-0001-T2-C1', 12, 3),
(1, 3, 1, 'PET-0001-T3-C1', 8, 2),
(1, 1, 2, 'PET-0001-T1-C2', 10, 3),
(1, 2, 2, 'PET-0001-T2-C2', 7, 3);

-- Inserir imagens de exemplo (URLs placeholder)
INSERT INTO `produto_imagens` (`produto_id`, `url`, `alt_text`, `titulo`, `ordem`, `principal`) VALUES
(1, '/uploads/produtos/caminha-rosa-1.jpg', 'Caminha Redonda Pelúcia Rosa - Vista Principal', 'Caminha Rosa - Imagem Principal', 1, 1),
(1, '/uploads/produtos/caminha-rosa-2.jpg', 'Caminha Redonda Pelúcia Rosa - Vista Lateral', 'Caminha Rosa - Vista Lateral', 2, 0),
(1, '/uploads/produtos/caminha-rosa-3.jpg', 'Caminha Redonda Pelúcia Rosa - Detalhe do Material', 'Caminha Rosa - Detalhe Material', 3, 0);

-- =====================================================
-- 13. CONFIGURAÇÕES FINAIS
-- =====================================================

-- Reativar verificações de chave estrangeira
SET FOREIGN_KEY_CHECKS = 1;

-- Otimizar tabelas
OPTIMIZE TABLE categorias, tamanhos, cores, produtos, produto_variacoes, produto_imagens;

-- Analisar tabelas para estatísticas
ANALYZE TABLE categorias, tamanhos, cores, produtos, produto_variacoes, produto_imagens;

-- =====================================================
-- 14. VERIFICAÇÃO DA INSTALAÇÃO
-- =====================================================

-- Verificar se todas as tabelas foram criadas
SELECT 
    TABLE_NAME as 'Tabela',
    TABLE_ROWS as 'Registros',
    ROUND(((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024), 2) as 'Tamanho_MB'
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;

-- Verificar dados inseridos
SELECT 'Categorias' as Tabela, COUNT(*) as Total FROM categorias
UNION ALL
SELECT 'Tamanhos', COUNT(*) FROM tamanhos
UNION ALL
SELECT 'Cores', COUNT(*) FROM cores
UNION ALL
SELECT 'Produtos', COUNT(*) FROM produtos
UNION ALL
SELECT 'Variações', COUNT(*) FROM produto_variacoes
UNION ALL
SELECT 'Imagens', COUNT(*) FROM produto_imagens;

-- Testar view de produtos completos
SELECT 'Teste da View v_produtos_completos' as Status;
SELECT * FROM v_produtos_completos LIMIT 1;

-- Testar procedure de estatísticas
SELECT 'Teste da Procedure sp_obter_estatisticas_gerais' as Status;
CALL sp_obter_estatisticas_gerais();

-- =====================================================
-- INSTALAÇÃO CONCLUÍDA COM SUCESSO!
-- =====================================================

SELECT 
    '🎉 INSTALAÇÃO CONCLUÍDA COM SUCESSO! 🎉' as Status,
    NOW() as 'Data_Instalacao',
    DATABASE() as 'Banco_Instalado',
    VERSION() as 'Versao_MySQL';

