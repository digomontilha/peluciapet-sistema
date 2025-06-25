<?php
/**
 * Classe Categoria - Sistema PelúciaPet v2.1
 * Sistema de categorias hierárquico avançado
 */

class Categoria {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Criar nova categoria
     */
    public function criar($dados) {
        try {
            // Validar dados obrigatórios
            $required = ['nome', 'slug'];
            foreach ($required as $field) {
                if (empty($dados[$field])) {
                    return [
                        'success' => false,
                        'message' => "Campo '{$field}' é obrigatório"
                    ];
                }
            }
            
            // Verificar se slug já existe
            if ($this->slugExists($dados['slug'])) {
                return [
                    'success' => false,
                    'message' => 'Slug já existe. Use outro identificador.'
                ];
            }
            
            // Validar categoria pai se informada
            if (!empty($dados['categoria_pai_id'])) {
                $categoriaPai = $this->buscarPorId($dados['categoria_pai_id']);
                if (!$categoriaPai) {
                    return [
                        'success' => false,
                        'message' => 'Categoria pai não encontrada'
                    ];
                }
                
                // Verificar nível máximo (3 níveis)
                if ($categoriaPai['nivel'] >= 3) {
                    return [
                        'success' => false,
                        'message' => 'Máximo de 3 níveis de categoria permitido'
                    ];
                }
            }
            
            // Calcular nível e caminho
            $nivel = 1;
            $caminho = $dados['slug'];
            
            if (!empty($dados['categoria_pai_id'])) {
                $categoriaPai = $this->buscarPorId($dados['categoria_pai_id']);
                $nivel = $categoriaPai['nivel'] + 1;
                $caminho = $categoriaPai['caminho'] . '/' . $dados['slug'];
            }
            
            // Preparar dados para inserção
            $dadosInsercao = [
                'nome' => trim($dados['nome']),
                'slug' => trim($dados['slug']),
                'descricao' => trim($dados['descricao'] ?? ''),
                'categoria_pai_id' => !empty($dados['categoria_pai_id']) ? (int)$dados['categoria_pai_id'] : null,
                'nivel' => $nivel,
                'caminho' => $caminho,
                'ordem' => (int)($dados['ordem'] ?? 0),
                'ativo' => isset($dados['ativo']) ? (int)$dados['ativo'] : 1,
                'meta_title' => trim($dados['meta_title'] ?? $dados['nome']),
                'meta_description' => trim($dados['meta_description'] ?? ''),
                'meta_keywords' => trim($dados['meta_keywords'] ?? ''),
                'imagem' => trim($dados['imagem'] ?? ''),
                'cor_destaque' => trim($dados['cor_destaque'] ?? '#FF6B9D'),
                'icone' => trim($dados['icone'] ?? 'fas fa-paw'),
                'mostrar_home' => isset($dados['mostrar_home']) ? (int)$dados['mostrar_home'] : 0,
                'data_criacao' => date('Y-m-d H:i:s'),
                'data_atualizacao' => date('Y-m-d H:i:s')
            ];
            
            $sql = "INSERT INTO categorias (
                nome, slug, descricao, categoria_pai_id, nivel, caminho, ordem, ativo,
                meta_title, meta_description, meta_keywords, imagem, cor_destaque, 
                icone, mostrar_home, data_criacao, data_atualizacao
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $this->db->execute($sql, array_values($dadosInsercao));
            $categoriaId = $this->db->lastInsertId();
            
            // Atualizar contadores da categoria pai
            if (!empty($dados['categoria_pai_id'])) {
                $this->atualizarContadores($dados['categoria_pai_id']);
            }
            
            return [
                'success' => true,
                'message' => 'Categoria criada com sucesso',
                'data' => ['id' => $categoriaId]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao criar categoria: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Atualizar categoria
     */
    public function atualizar($id, $dados) {
        try {
            $categoria = $this->buscarPorId($id);
            if (!$categoria) {
                return [
                    'success' => false,
                    'message' => 'Categoria não encontrada'
                ];
            }
            
            // Verificar se slug já existe (exceto para a própria categoria)
            if (!empty($dados['slug']) && $dados['slug'] !== $categoria['slug']) {
                if ($this->slugExists($dados['slug'], $id)) {
                    return [
                        'success' => false,
                        'message' => 'Slug já existe. Use outro identificador.'
                    ];
                }
            }
            
            // Preparar dados para atualização
            $campos = [];
            $valores = [];
            
            $camposPermitidos = [
                'nome', 'slug', 'descricao', 'ordem', 'ativo',
                'meta_title', 'meta_description', 'meta_keywords',
                'imagem', 'cor_destaque', 'icone', 'mostrar_home'
            ];
            
            foreach ($camposPermitidos as $campo) {
                if (isset($dados[$campo])) {
                    $campos[] = "{$campo} = ?";
                    $valores[] = trim($dados[$campo]);
                }
            }
            
            if (empty($campos)) {
                return [
                    'success' => false,
                    'message' => 'Nenhum campo para atualizar'
                ];
            }
            
            // Adicionar data de atualização
            $campos[] = "data_atualizacao = ?";
            $valores[] = date('Y-m-d H:i:s');
            $valores[] = $id;
            
            $sql = "UPDATE categorias SET " . implode(', ', $campos) . " WHERE id = ?";
            $this->db->execute($sql, $valores);
            
            // Se o slug mudou, atualizar caminhos dos filhos
            if (!empty($dados['slug']) && $dados['slug'] !== $categoria['slug']) {
                $this->atualizarCaminhosFilhos($id);
            }
            
            return [
                'success' => true,
                'message' => 'Categoria atualizada com sucesso'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao atualizar categoria: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Excluir categoria
     */
    public function excluir($id) {
        try {
            $categoria = $this->buscarPorId($id);
            if (!$categoria) {
                return [
                    'success' => false,
                    'message' => 'Categoria não encontrada'
                ];
            }
            
            // Verificar se tem subcategorias
            $subcategorias = $this->listarFilhos($id);
            if (!empty($subcategorias)) {
                return [
                    'success' => false,
                    'message' => 'Não é possível excluir categoria com subcategorias. Exclua as subcategorias primeiro.'
                ];
            }
            
            // Verificar se tem produtos
            $produtos = $this->db->fetch(
                "SELECT COUNT(*) as total FROM produtos WHERE categoria_id = ?",
                [$id]
            );
            
            if ($produtos['total'] > 0) {
                return [
                    'success' => false,
                    'message' => "Não é possível excluir categoria com {$produtos['total']} produto(s). Mova os produtos para outra categoria primeiro."
                ];
            }
            
            // Excluir categoria
            $this->db->execute("DELETE FROM categorias WHERE id = ?", [$id]);
            
            // Atualizar contadores da categoria pai
            if ($categoria['categoria_pai_id']) {
                $this->atualizarContadores($categoria['categoria_pai_id']);
            }
            
            return [
                'success' => true,
                'message' => 'Categoria excluída com sucesso'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao excluir categoria: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Buscar categoria por ID
     */
    public function buscarPorId($id) {
        try {
            $sql = "SELECT c.*, 
                           cp.nome as categoria_pai_nome,
                           (SELECT COUNT(*) FROM categorias WHERE categoria_pai_id = c.id) as total_subcategorias,
                           (SELECT COUNT(*) FROM produtos WHERE categoria_id = c.id) as total_produtos
                    FROM categorias c
                    LEFT JOIN categorias cp ON c.categoria_pai_id = cp.id
                    WHERE c.id = ?";
            
            return $this->db->fetch($sql, [$id]);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Buscar categoria por slug
     */
    public function buscarPorSlug($slug) {
        try {
            $sql = "SELECT c.*, 
                           cp.nome as categoria_pai_nome,
                           (SELECT COUNT(*) FROM categorias WHERE categoria_pai_id = c.id) as total_subcategorias,
                           (SELECT COUNT(*) FROM produtos WHERE categoria_id = c.id) as total_produtos
                    FROM categorias c
                    LEFT JOIN categorias cp ON c.categoria_pai_id = cp.id
                    WHERE c.slug = ? AND c.ativo = 1";
            
            return $this->db->fetch($sql, [$slug]);
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Listar todas as categorias
     */
    public function listar($filtros = []) {
        try {
            $where = ['1=1'];
            $params = [];
            
            // Filtro por categoria pai
            if (isset($filtros['categoria_pai_id'])) {
                if ($filtros['categoria_pai_id'] === null) {
                    $where[] = "c.categoria_pai_id IS NULL";
                } else {
                    $where[] = "c.categoria_pai_id = ?";
                    $params[] = $filtros['categoria_pai_id'];
                }
            }
            
            // Filtro por nível
            if (isset($filtros['nivel'])) {
                $where[] = "c.nivel = ?";
                $params[] = $filtros['nivel'];
            }
            
            // Filtro por ativo
            if (isset($filtros['ativo'])) {
                $where[] = "c.ativo = ?";
                $params[] = $filtros['ativo'];
            }
            
            // Filtro para mostrar na home
            if (isset($filtros['mostrar_home'])) {
                $where[] = "c.mostrar_home = ?";
                $params[] = $filtros['mostrar_home'];
            }
            
            // Filtro por busca
            if (!empty($filtros['busca'])) {
                $where[] = "(c.nome LIKE ? OR c.descricao LIKE ?)";
                $busca = '%' . $filtros['busca'] . '%';
                $params[] = $busca;
                $params[] = $busca;
            }
            
            $orderBy = $filtros['order_by'] ?? 'c.nivel ASC, c.ordem ASC, c.nome ASC';
            
            $sql = "SELECT c.*, 
                           cp.nome as categoria_pai_nome,
                           (SELECT COUNT(*) FROM categorias WHERE categoria_pai_id = c.id) as total_subcategorias,
                           (SELECT COUNT(*) FROM produtos WHERE categoria_id = c.id) as total_produtos
                    FROM categorias c
                    LEFT JOIN categorias cp ON c.categoria_pai_id = cp.id
                    WHERE " . implode(' AND ', $where) . "
                    ORDER BY {$orderBy}";
            
            return $this->db->fetchAll($sql, $params);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Listar categorias filhas
     */
    public function listarFilhos($categoriaId) {
        return $this->listar(['categoria_pai_id' => $categoriaId]);
    }
    
    /**
     * Listar categorias principais (nível 1)
     */
    public function listarPrincipais($apenasAtivas = true) {
        $filtros = ['categoria_pai_id' => null];
        if ($apenasAtivas) {
            $filtros['ativo'] = 1;
        }
        return $this->listar($filtros);
    }
    
    /**
     * Obter árvore de categorias
     */
    public function obterArvore($apenasAtivas = true) {
        $filtros = [];
        if ($apenasAtivas) {
            $filtros['ativo'] = 1;
        }
        
        $categorias = $this->listar($filtros);
        return $this->construirArvore($categorias);
    }
    
    /**
     * Construir árvore hierárquica
     */
    private function construirArvore($categorias, $paiId = null) {
        $arvore = [];
        
        foreach ($categorias as $categoria) {
            if ($categoria['categoria_pai_id'] == $paiId) {
                $categoria['filhos'] = $this->construirArvore($categorias, $categoria['id']);
                $arvore[] = $categoria;
            }
        }
        
        return $arvore;
    }
    
    /**
     * Obter breadcrumb de uma categoria
     */
    public function obterBreadcrumb($categoriaId) {
        try {
            $breadcrumb = [];
            $categoria = $this->buscarPorId($categoriaId);
            
            while ($categoria) {
                array_unshift($breadcrumb, [
                    'id' => $categoria['id'],
                    'nome' => $categoria['nome'],
                    'slug' => $categoria['slug'],
                    'caminho' => $categoria['caminho']
                ]);
                
                if ($categoria['categoria_pai_id']) {
                    $categoria = $this->buscarPorId($categoria['categoria_pai_id']);
                } else {
                    break;
                }
            }
            
            return $breadcrumb;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Verificar se slug existe
     */
    private function slugExists($slug, $excludeId = null) {
        $sql = "SELECT id FROM categorias WHERE slug = ?";
        $params = [$slug];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $result = $this->db->fetch($sql, $params);
        return !empty($result);
    }
    
    /**
     * Atualizar caminhos dos filhos
     */
    private function atualizarCaminhosFilhos($categoriaId) {
        $categoria = $this->buscarPorId($categoriaId);
        if (!$categoria) return;
        
        $filhos = $this->listarFilhos($categoriaId);
        
        foreach ($filhos as $filho) {
            $novoCaminho = $categoria['caminho'] . '/' . $filho['slug'];
            
            $this->db->execute(
                "UPDATE categorias SET caminho = ?, data_atualizacao = ? WHERE id = ?",
                [$novoCaminho, date('Y-m-d H:i:s'), $filho['id']]
            );
            
            // Recursivo para filhos dos filhos
            $this->atualizarCaminhosFilhos($filho['id']);
        }
    }
    
    /**
     * Atualizar contadores
     */
    private function atualizarContadores($categoriaId) {
        // Atualizar total de subcategorias
        $totalSubcategorias = $this->db->fetch(
            "SELECT COUNT(*) as total FROM categorias WHERE categoria_pai_id = ?",
            [$categoriaId]
        );
        
        // Atualizar total de produtos
        $totalProdutos = $this->db->fetch(
            "SELECT COUNT(*) as total FROM produtos WHERE categoria_id = ?",
            [$categoriaId]
        );
        
        // Aqui você pode adicionar campos na tabela para armazenar esses contadores
        // Por enquanto, eles são calculados dinamicamente nas consultas
    }
    
    /**
     * Mover categoria para nova posição
     */
    public function mover($categoriaId, $novaCategoriaPaiId = null, $novaOrdem = null) {
        try {
            $categoria = $this->buscarPorId($categoriaId);
            if (!$categoria) {
                return [
                    'success' => false,
                    'message' => 'Categoria não encontrada'
                ];
            }
            
            // Validar nova categoria pai
            if ($novaCategoriaPaiId) {
                $novaCategoriaPai = $this->buscarPorId($novaCategoriaPaiId);
                if (!$novaCategoriaPai) {
                    return [
                        'success' => false,
                        'message' => 'Nova categoria pai não encontrada'
                    ];
                }
                
                // Verificar se não está tentando mover para um filho
                if ($this->isFilho($categoriaId, $novaCategoriaPaiId)) {
                    return [
                        'success' => false,
                        'message' => 'Não é possível mover categoria para um de seus filhos'
                    ];
                }
                
                // Verificar nível máximo
                if ($novaCategoriaPai['nivel'] >= 3) {
                    return [
                        'success' => false,
                        'message' => 'Máximo de 3 níveis de categoria permitido'
                    ];
                }
            }
            
            // Calcular novo nível e caminho
            $novoNivel = 1;
            $novoCaminho = $categoria['slug'];
            
            if ($novaCategoriaPaiId) {
                $novaCategoriaPai = $this->buscarPorId($novaCategoriaPaiId);
                $novoNivel = $novaCategoriaPai['nivel'] + 1;
                $novoCaminho = $novaCategoriaPai['caminho'] . '/' . $categoria['slug'];
            }
            
            // Atualizar categoria
            $dados = [
                'categoria_pai_id' => $novaCategoriaPaiId,
                'nivel' => $novoNivel,
                'caminho' => $novoCaminho,
                'data_atualizacao' => date('Y-m-d H:i:s')
            ];
            
            if ($novaOrdem !== null) {
                $dados['ordem'] = (int)$novaOrdem;
            }
            
            $campos = [];
            $valores = [];
            foreach ($dados as $campo => $valor) {
                $campos[] = "{$campo} = ?";
                $valores[] = $valor;
            }
            $valores[] = $categoriaId;
            
            $sql = "UPDATE categorias SET " . implode(', ', $campos) . " WHERE id = ?";
            $this->db->execute($sql, $valores);
            
            // Atualizar caminhos dos filhos
            $this->atualizarCaminhosFilhos($categoriaId);
            
            // Atualizar contadores das categorias afetadas
            if ($categoria['categoria_pai_id']) {
                $this->atualizarContadores($categoria['categoria_pai_id']);
            }
            if ($novaCategoriaPaiId) {
                $this->atualizarContadores($novaCategoriaPaiId);
            }
            
            return [
                'success' => true,
                'message' => 'Categoria movida com sucesso'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao mover categoria: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar se uma categoria é filha de outra
     */
    private function isFilho($categoriaId, $possivelPaiId) {
        $categoria = $this->buscarPorId($possivelPaiId);
        
        while ($categoria && $categoria['categoria_pai_id']) {
            if ($categoria['categoria_pai_id'] == $categoriaId) {
                return true;
            }
            $categoria = $this->buscarPorId($categoria['categoria_pai_id']);
        }
        
        return false;
    }
    
    /**
     * Obter estatísticas das categorias
     */
    public function obterEstatisticas() {
        try {
            $stats = [];
            
            // Total de categorias
            $stats['total_categorias'] = $this->db->fetch(
                "SELECT COUNT(*) as total FROM categorias"
            )['total'];
            
            // Categorias ativas
            $stats['categorias_ativas'] = $this->db->fetch(
                "SELECT COUNT(*) as total FROM categorias WHERE ativo = 1"
            )['total'];
            
            // Categorias por nível
            $stats['por_nivel'] = $this->db->fetchAll(
                "SELECT nivel, COUNT(*) as total FROM categorias GROUP BY nivel ORDER BY nivel"
            );
            
            // Categorias com mais produtos
            $stats['mais_produtos'] = $this->db->fetchAll(
                "SELECT c.nome, c.slug, COUNT(p.id) as total_produtos
                 FROM categorias c
                 LEFT JOIN produtos p ON c.id = p.categoria_id
                 GROUP BY c.id, c.nome, c.slug
                 ORDER BY total_produtos DESC
                 LIMIT 10"
            );
            
            return $stats;
        } catch (Exception $e) {
            return [];
        }
    }
}
?>

