<?php
/**
 * Classe Produto - Sistema PelúciaPet
 * Gerenciamento completo de produtos, variações e relacionamentos
 * Otimizada para MySQL
 */

require_once 'Database.php';

class Produto {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Criar novo produto com variações
     */
    public function criar($dados) {
        try {
            $this->db->beginTransaction();
            
            // Validar dados obrigatórios
            $this->validarDados($dados);
            
            // Inserir produto principal
            $produtoId = $this->inserirProduto($dados);
            
            // Inserir variações se fornecidas
            if (!empty($dados['variacoes'])) {
                $this->inserirVariacoes($produtoId, $dados['variacoes']);
            }
            
            // Inserir imagens se fornecidas
            if (!empty($dados['imagens'])) {
                $this->inserirImagens($produtoId, $dados['imagens']);
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'produto_id' => $produtoId,
                'message' => 'Produto criado com sucesso!'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Erro ao criar produto: " . $e->getMessage());
        }
    }
    
    /**
     * Inserir produto principal
     */
    private function inserirProduto($dados) {
        $sql = "INSERT INTO produtos (
            nome, descricao, categoria_id, preco_base, preco_promocional,
            material, peso, dimensoes, cuidados, destaque, ativo,
            meta_title, meta_description, slug, created_at
        ) VALUES (
            :nome, :descricao, :categoria_id, :preco_base, :preco_promocional,
            :material, :peso, :dimensoes, :cuidados, :destaque, :ativo,
            :meta_title, :meta_description, :slug, NOW()
        )";
        
        $params = [
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'],
            'categoria_id' => $dados['categoria_id'],
            'preco_base' => $dados['preco_base'],
            'preco_promocional' => $dados['preco_promocional'] ?? null,
            'material' => $dados['material'] ?? null,
            'peso' => $dados['peso'] ?? null,
            'dimensoes' => $dados['dimensoes'] ?? null,
            'cuidados' => $dados['cuidados'] ?? null,
            'destaque' => (bool)($dados['destaque'] ?? false),
            'ativo' => (bool)($dados['ativo'] ?? true),
            'meta_title' => $dados['meta_title'] ?? $dados['nome'],
            'meta_description' => $dados['meta_description'] ?? substr($dados['descricao'], 0, 160),
            'slug' => $this->gerarSlug($dados['nome'])
        ];
        
        $this->db->execute($sql, $params);
        return $this->db->lastInsertId();
    }
    
    /**
     * Inserir variações do produto
     */
    private function inserirVariacoes($produtoId, $variacoes) {
        $sql = "INSERT INTO produto_variacoes (
            produto_id, tamanho_id, cor_id, estoque_atual, estoque_minimo,
            preco_adicional, sku, ativo, created_at
        ) VALUES (
            :produto_id, :tamanho_id, :cor_id, :estoque_atual, :estoque_minimo,
            :preco_adicional, :sku, :ativo, NOW()
        )";
        
        foreach ($variacoes as $variacao) {
            $params = [
                'produto_id' => $produtoId,
                'tamanho_id' => $variacao['tamanho_id'],
                'cor_id' => $variacao['cor_id'],
                'estoque_atual' => $variacao['estoque_atual'] ?? 0,
                'estoque_minimo' => $variacao['estoque_minimo'] ?? 1,
                'preco_adicional' => $variacao['preco_adicional'] ?? 0,
                'sku' => $variacao['sku'] ?? $this->gerarSKU($produtoId, $variacao),
                'ativo' => (bool)($variacao['ativo'] ?? true)
            ];
            
            $this->db->execute($sql, $params);
        }
    }
    
    /**
     * Inserir imagens do produto
     */
    private function inserirImagens($produtoId, $imagens) {
        $sql = "INSERT INTO produto_imagens (
            produto_id, url, alt_text, ordem, principal, ativo, created_at
        ) VALUES (
            :produto_id, :url, :alt_text, :ordem, :principal, :ativo, NOW()
        )";
        
        foreach ($imagens as $index => $imagem) {
            $params = [
                'produto_id' => $produtoId,
                'url' => $imagem['url'],
                'alt_text' => $imagem['alt_text'] ?? '',
                'ordem' => $imagem['ordem'] ?? $index + 1,
                'principal' => (bool)($imagem['principal'] ?? $index === 0),
                'ativo' => (bool)($imagem['ativo'] ?? true)
            ];
            
            $this->db->execute($sql, $params);
        }
    }
    
    /**
     * Listar produtos com filtros
     */
    public function listar($filtros = []) {
        $where = ['p.ativo = 1'];
        $params = [];
        $joins = [];
        
        // Filtro por categoria
        if (!empty($filtros['categoria_id'])) {
            $where[] = 'p.categoria_id = :categoria_id';
            $params['categoria_id'] = $filtros['categoria_id'];
        }
        
        // Filtro por destaque
        if (isset($filtros['destaque'])) {
            $where[] = 'p.destaque = :destaque';
            $params['destaque'] = (bool)$filtros['destaque'];
        }
        
        // Filtro por busca
        if (!empty($filtros['busca'])) {
            $where[] = '(p.nome LIKE :busca OR p.descricao LIKE :busca)';
            $params['busca'] = '%' . $filtros['busca'] . '%';
        }
        
        // Filtro por faixa de preço
        if (!empty($filtros['preco_min'])) {
            $where[] = 'p.preco_base >= :preco_min';
            $params['preco_min'] = $filtros['preco_min'];
        }
        
        if (!empty($filtros['preco_max'])) {
            $where[] = 'p.preco_base <= :preco_max';
            $params['preco_max'] = $filtros['preco_max'];
        }
        
        // Ordenação
        $orderBy = 'p.created_at DESC';
        if (!empty($filtros['ordem'])) {
            switch ($filtros['ordem']) {
                case 'nome_asc':
                    $orderBy = 'p.nome ASC';
                    break;
                case 'nome_desc':
                    $orderBy = 'p.nome DESC';
                    break;
                case 'preco_asc':
                    $orderBy = 'p.preco_base ASC';
                    break;
                case 'preco_desc':
                    $orderBy = 'p.preco_base DESC';
                    break;
                case 'destaque':
                    $orderBy = 'p.destaque DESC, p.created_at DESC';
                    break;
            }
        }
        
        // Paginação
        $limit = '';
        if (!empty($filtros['limite'])) {
            $limite = (int)$filtros['limite'];
            $offset = (int)($filtros['offset'] ?? 0);
            $limit = "LIMIT $offset, $limite";
        }
        
        $sql = "
            SELECT 
                p.*,
                c.nome as categoria_nome,
                c.slug as categoria_slug,
                (SELECT COUNT(*) FROM produto_variacoes pv WHERE pv.produto_id = p.id AND pv.ativo = 1) as total_variacoes,
                (SELECT SUM(pv.estoque_atual) FROM produto_variacoes pv WHERE pv.produto_id = p.id AND pv.ativo = 1) as estoque_total,
                (SELECT pi.url FROM produto_imagens pi WHERE pi.produto_id = p.id AND pi.principal = 1 AND pi.ativo = 1 LIMIT 1) as imagem_principal
            FROM produtos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            " . implode(' ', $joins) . "
            WHERE " . implode(' AND ', $where) . "
            ORDER BY $orderBy
            $limit
        ";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Obter produto por ID com todas as informações
     */
    public function obterPorId($id) {
        // Produto principal
        $sql = "
            SELECT 
                p.*,
                c.nome as categoria_nome,
                c.slug as categoria_slug
            FROM produtos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            WHERE p.id = :id AND p.ativo = 1
        ";
        
        $produto = $this->db->fetch($sql, ['id' => $id]);
        
        if (!$produto) {
            return null;
        }
        
        // Variações
        $produto['variacoes'] = $this->obterVariacoes($id);
        
        // Imagens
        $produto['imagens'] = $this->obterImagens($id);
        
        return $produto;
    }
    
    /**
     * Obter variações do produto
     */
    public function obterVariacoes($produtoId) {
        $sql = "
            SELECT 
                pv.*,
                t.nome as tamanho_nome,
                t.dimensoes as tamanho_dimensoes,
                t.peso_recomendado as tamanho_peso,
                c.nome as cor_nome,
                c.codigo_hex as cor_codigo
            FROM produto_variacoes pv
            LEFT JOIN tamanhos t ON pv.tamanho_id = t.id
            LEFT JOIN cores c ON pv.cor_id = c.id
            WHERE pv.produto_id = :produto_id AND pv.ativo = 1
            ORDER BY t.ordem ASC, c.nome ASC
        ";
        
        return $this->db->fetchAll($sql, ['produto_id' => $produtoId]);
    }
    
    /**
     * Obter imagens do produto
     */
    public function obterImagens($produtoId) {
        $sql = "
            SELECT *
            FROM produto_imagens
            WHERE produto_id = :produto_id AND ativo = 1
            ORDER BY principal DESC, ordem ASC
        ";
        
        return $this->db->fetchAll($sql, ['produto_id' => $produtoId]);
    }
    
    /**
     * Atualizar produto
     */
    public function atualizar($id, $dados) {
        try {
            $this->db->beginTransaction();
            
            // Atualizar produto principal
            $sql = "UPDATE produtos SET 
                nome = :nome,
                descricao = :descricao,
                categoria_id = :categoria_id,
                preco_base = :preco_base,
                preco_promocional = :preco_promocional,
                material = :material,
                peso = :peso,
                dimensoes = :dimensoes,
                cuidados = :cuidados,
                destaque = :destaque,
                ativo = :ativo,
                meta_title = :meta_title,
                meta_description = :meta_description,
                slug = :slug,
                updated_at = NOW()
                WHERE id = :id";
            
            $params = [
                'id' => $id,
                'nome' => $dados['nome'],
                'descricao' => $dados['descricao'],
                'categoria_id' => $dados['categoria_id'],
                'preco_base' => $dados['preco_base'],
                'preco_promocional' => $dados['preco_promocional'] ?? null,
                'material' => $dados['material'] ?? null,
                'peso' => $dados['peso'] ?? null,
                'dimensoes' => $dados['dimensoes'] ?? null,
                'cuidados' => $dados['cuidados'] ?? null,
                'destaque' => (bool)($dados['destaque'] ?? false),
                'ativo' => (bool)($dados['ativo'] ?? true),
                'meta_title' => $dados['meta_title'] ?? $dados['nome'],
                'meta_description' => $dados['meta_description'] ?? substr($dados['descricao'], 0, 160),
                'slug' => $this->gerarSlug($dados['nome'], $id)
            ];
            
            $this->db->execute($sql, $params);
            
            // Atualizar variações se fornecidas
            if (isset($dados['variacoes'])) {
                $this->atualizarVariacoes($id, $dados['variacoes']);
            }
            
            // Atualizar imagens se fornecidas
            if (isset($dados['imagens'])) {
                $this->atualizarImagens($id, $dados['imagens']);
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Produto atualizado com sucesso!'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Erro ao atualizar produto: " . $e->getMessage());
        }
    }
    
    /**
     * Excluir produto (soft delete)
     */
    public function excluir($id) {
        try {
            $this->db->beginTransaction();
            
            // Desativar produto
            $sql = "UPDATE produtos SET ativo = 0, updated_at = NOW() WHERE id = :id";
            $this->db->execute($sql, ['id' => $id]);
            
            // Desativar variações
            $sql = "UPDATE produto_variacoes SET ativo = 0 WHERE produto_id = :produto_id";
            $this->db->execute($sql, ['produto_id' => $id]);
            
            // Desativar imagens
            $sql = "UPDATE produto_imagens SET ativo = 0 WHERE produto_id = :produto_id";
            $this->db->execute($sql, ['produto_id' => $id]);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Produto excluído com sucesso!'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Exception("Erro ao excluir produto: " . $e->getMessage());
        }
    }
    
    /**
     * Obter estatísticas de produtos
     */
    public function obterEstatisticas() {
        $stats = [];
        
        try {
            // Total de produtos ativos
            $stats['total_produtos'] = $this->db->countRecords('produtos', 'ativo = 1');
            
            // Produtos por categoria
            $sql = "
                SELECT 
                    c.nome as categoria,
                    COUNT(p.id) as total
                FROM categorias c
                LEFT JOIN produtos p ON c.id = p.categoria_id AND p.ativo = 1
                GROUP BY c.id, c.nome
                ORDER BY c.nome
            ";
            $stats['por_categoria'] = $this->db->fetchAll($sql);
            
            // Produtos em destaque
            $stats['produtos_destaque'] = $this->db->countRecords('produtos', 'ativo = 1 AND destaque = 1');
            
            // Total de variações
            $stats['total_variacoes'] = $this->db->countRecords('produto_variacoes', 'ativo = 1');
            
            // Estoque total
            $sql = "SELECT IFNULL(SUM(estoque_atual), 0) as total FROM produto_variacoes WHERE ativo = 1";
            $result = $this->db->fetch($sql);
            $stats['estoque_total'] = (int)($result['total'] ?? 0);
            
            // Produtos com estoque baixo
            $sql = "
                SELECT COUNT(DISTINCT produto_id) as total
                FROM produto_variacoes 
                WHERE ativo = 1 AND estoque_atual <= estoque_minimo
            ";
            $result = $this->db->fetch($sql);
            $stats['estoque_baixo'] = (int)($result['total'] ?? 0);
            
            // Produtos mais recentes
            $stats['produtos_recentes'] = $this->listar([
                'limite' => 5,
                'ordem' => 'created_at_desc'
            ]);
            
        } catch (Exception $e) {
            $stats['error'] = $e->getMessage();
        }
        
        return $stats;
    }
    
    /**
     * Validar dados do produto
     */
    private function validarDados($dados) {
        $erros = [];
        
        if (empty($dados['nome'])) {
            $erros[] = 'Nome é obrigatório';
        }
        
        if (empty($dados['descricao'])) {
            $erros[] = 'Descrição é obrigatória';
        }
        
        if (empty($dados['categoria_id'])) {
            $erros[] = 'Categoria é obrigatória';
        }
        
        if (empty($dados['preco_base']) || $dados['preco_base'] <= 0) {
            $erros[] = 'Preço base deve ser maior que zero';
        }
        
        if (!empty($erros)) {
            throw new Exception('Dados inválidos: ' . implode(', ', $erros));
        }
    }
    
    /**
     * Gerar slug único
     */
    private function gerarSlug($nome, $excludeId = null) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nome)));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Verificar se slug já existe
        $counter = 0;
        $originalSlug = $slug;
        
        do {
            $sql = "SELECT id FROM produtos WHERE slug = :slug";
            $params = ['slug' => $slug];
            
            if ($excludeId) {
                $sql .= " AND id != :exclude_id";
                $params['exclude_id'] = $excludeId;
            }
            
            $exists = $this->db->fetch($sql, $params);
            
            if ($exists) {
                $counter++;
                $slug = $originalSlug . '-' . $counter;
            }
        } while ($exists);
        
        return $slug;
    }
    
    /**
     * Gerar SKU único
     */
    private function gerarSKU($produtoId, $variacao) {
        $sku = 'PET-' . str_pad($produtoId, 4, '0', STR_PAD_LEFT);
        
        if (!empty($variacao['tamanho_id'])) {
            $sku .= '-T' . $variacao['tamanho_id'];
        }
        
        if (!empty($variacao['cor_id'])) {
            $sku .= '-C' . $variacao['cor_id'];
        }
        
        return $sku;
    }
    
    /**
     * Atualizar variações (implementação simplificada)
     */
    private function atualizarVariacoes($produtoId, $variacoes) {
        // Desativar todas as variações existentes
        $sql = "UPDATE produto_variacoes SET ativo = 0 WHERE produto_id = :produto_id";
        $this->db->execute($sql, ['produto_id' => $produtoId]);
        
        // Inserir novas variações
        $this->inserirVariacoes($produtoId, $variacoes);
    }
    
    /**
     * Atualizar imagens (implementação simplificada)
     */
    private function atualizarImagens($produtoId, $imagens) {
        // Desativar todas as imagens existentes
        $sql = "UPDATE produto_imagens SET ativo = 0 WHERE produto_id = :produto_id";
        $this->db->execute($sql, ['produto_id' => $produtoId]);
        
        // Inserir novas imagens
        $this->inserirImagens($produtoId, $imagens);
    }
}

