<?php
/**
 * Classe ImageUpload - Sistema PelúciaPet v2.1
 * Gerenciamento completo de upload de imagens
 */

class ImageUpload {
    private $uploadDir;
    private $allowedTypes;
    private $maxFileSize;
    private $maxWidth;
    private $maxHeight;
    private $thumbnailSizes;
    
    public function __construct() {
        $this->uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/produtos/';
        $this->allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $this->maxFileSize = 5 * 1024 * 1024; // 5MB
        $this->maxWidth = 2000;
        $this->maxHeight = 2000;
        $this->thumbnailSizes = [
            'thumb' => ['width' => 150, 'height' => 150],
            'medium' => ['width' => 400, 'height' => 400],
            'large' => ['width' => 800, 'height' => 800]
        ];
        
        $this->createDirectories();
    }
    
    /**
     * Criar diretórios necessários
     */
    private function createDirectories() {
        $directories = [
            $this->uploadDir,
            $this->uploadDir . 'originals/',
            $this->uploadDir . 'thumbnails/',
            $this->uploadDir . 'medium/',
            $this->uploadDir . 'large/'
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    /**
     * Upload múltiplo de imagens
     */
    public function uploadMultiple($files, $produtoId) {
        $results = [];
        $uploadedFiles = [];
        
        try {
            // Verificar se é array de arquivos
            if (!is_array($files['name'])) {
                $files = $this->normalizeFileArray($files);
            }
            
            foreach ($files['name'] as $index => $fileName) {
                if ($files['error'][$index] === UPLOAD_ERR_OK) {
                    $fileData = [
                        'name' => $files['name'][$index],
                        'type' => $files['type'][$index],
                        'tmp_name' => $files['tmp_name'][$index],
                        'error' => $files['error'][$index],
                        'size' => $files['size'][$index]
                    ];
                    
                    $result = $this->uploadSingle($fileData, $produtoId, $index);
                    
                    if ($result['success']) {
                        $uploadedFiles[] = $result['data'];
                    }
                    
                    $results[] = $result;
                }
            }
            
            // Salvar informações no banco
            if (!empty($uploadedFiles)) {
                $this->saveImageDatabase($produtoId, $uploadedFiles);
            }
            
            return [
                'success' => !empty($uploadedFiles),
                'message' => count($uploadedFiles) . ' imagens enviadas com sucesso',
                'data' => $uploadedFiles,
                'details' => $results
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro no upload: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Upload de uma única imagem
     */
    public function uploadSingle($file, $produtoId, $ordem = 0) {
        try {
            // Validações
            $validation = $this->validateFile($file);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['message']
                ];
            }
            
            // Gerar nome único
            $fileName = $this->generateFileName($file['name'], $produtoId, $ordem);
            $originalPath = $this->uploadDir . 'originals/' . $fileName;
            
            // Mover arquivo original
            if (!move_uploaded_file($file['tmp_name'], $originalPath)) {
                throw new Exception('Falha ao mover arquivo');
            }
            
            // Otimizar imagem original
            $this->optimizeImage($originalPath);
            
            // Gerar thumbnails
            $thumbnails = $this->generateThumbnails($originalPath, $fileName);
            
            // Informações da imagem
            $imageInfo = $this->getImageInfo($originalPath);
            
            return [
                'success' => true,
                'message' => 'Imagem enviada com sucesso',
                'data' => [
                    'produto_id' => $produtoId,
                    'nome_arquivo' => $fileName,
                    'nome_original' => $file['name'],
                    'caminho_original' => 'uploads/produtos/originals/' . $fileName,
                    'caminho_thumb' => 'uploads/produtos/thumbnails/' . $fileName,
                    'caminho_medium' => 'uploads/produtos/medium/' . $fileName,
                    'caminho_large' => 'uploads/produtos/large/' . $fileName,
                    'tamanho_arquivo' => $file['size'],
                    'largura' => $imageInfo['width'],
                    'altura' => $imageInfo['height'],
                    'tipo_mime' => $file['type'],
                    'ordem' => $ordem,
                    'ativo' => 1,
                    'data_upload' => date('Y-m-d H:i:s')
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro no upload: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validar arquivo
     */
    private function validateFile($file) {
        // Verificar erro de upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return [
                'valid' => false,
                'message' => $this->getUploadErrorMessage($file['error'])
            ];
        }
        
        // Verificar tamanho
        if ($file['size'] > $this->maxFileSize) {
            return [
                'valid' => false,
                'message' => 'Arquivo muito grande. Máximo: ' . ($this->maxFileSize / 1024 / 1024) . 'MB'
            ];
        }
        
        // Verificar tipo MIME
        if (!in_array($file['type'], $this->allowedTypes)) {
            return [
                'valid' => false,
                'message' => 'Tipo de arquivo não permitido. Use: JPG, PNG ou WebP'
            ];
        }
        
        // Verificar se é realmente uma imagem
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            return [
                'valid' => false,
                'message' => 'Arquivo não é uma imagem válida'
            ];
        }
        
        // Verificar dimensões
        if ($imageInfo[0] > $this->maxWidth || $imageInfo[1] > $this->maxHeight) {
            return [
                'valid' => false,
                'message' => "Imagem muito grande. Máximo: {$this->maxWidth}x{$this->maxHeight}px"
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Gerar nome único para arquivo
     */
    private function generateFileName($originalName, $produtoId, $ordem) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $timestamp = time();
        $random = substr(md5(uniqid()), 0, 8);
        
        return "produto_{$produtoId}_{$ordem}_{$timestamp}_{$random}.{$extension}";
    }
    
    /**
     * Otimizar imagem original
     */
    private function optimizeImage($imagePath) {
        $imageInfo = getimagesize($imagePath);
        $mimeType = $imageInfo['mime'];
        
        // Carregar imagem baseada no tipo
        switch ($mimeType) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($imagePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($imagePath);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($imagePath);
                break;
            default:
                return false;
        }
        
        if (!$image) {
            return false;
        }
        
        // Salvar com qualidade otimizada
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($image, $imagePath, 85);
                break;
            case 'image/png':
                imagepng($image, $imagePath, 6);
                break;
            case 'image/webp':
                imagewebp($image, $imagePath, 85);
                break;
        }
        
        imagedestroy($image);
        return true;
    }
    
    /**
     * Gerar thumbnails em diferentes tamanhos
     */
    private function generateThumbnails($originalPath, $fileName) {
        $thumbnails = [];
        
        foreach ($this->thumbnailSizes as $size => $dimensions) {
            $thumbnailPath = $this->uploadDir . $size . '/' . $fileName;
            
            if ($this->resizeImage($originalPath, $thumbnailPath, $dimensions['width'], $dimensions['height'])) {
                $thumbnails[$size] = $thumbnailPath;
            }
        }
        
        return $thumbnails;
    }
    
    /**
     * Redimensionar imagem
     */
    private function resizeImage($sourcePath, $destPath, $newWidth, $newHeight) {
        $imageInfo = getimagesize($sourcePath);
        $mimeType = $imageInfo['mime'];
        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        
        // Calcular proporções
        $ratio = min($newWidth / $sourceWidth, $newHeight / $sourceHeight);
        $finalWidth = round($sourceWidth * $ratio);
        $finalHeight = round($sourceHeight * $ratio);
        
        // Carregar imagem original
        switch ($mimeType) {
            case 'image/jpeg':
                $sourceImage = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $sourceImage = imagecreatefrompng($sourcePath);
                break;
            case 'image/webp':
                $sourceImage = imagecreatefromwebp($sourcePath);
                break;
            default:
                return false;
        }
        
        if (!$sourceImage) {
            return false;
        }
        
        // Criar nova imagem
        $destImage = imagecreatetruecolor($finalWidth, $finalHeight);
        
        // Preservar transparência para PNG
        if ($mimeType === 'image/png') {
            imagealphablending($destImage, false);
            imagesavealpha($destImage, true);
            $transparent = imagecolorallocatealpha($destImage, 255, 255, 255, 127);
            imagefill($destImage, 0, 0, $transparent);
        }
        
        // Redimensionar
        imagecopyresampled(
            $destImage, $sourceImage,
            0, 0, 0, 0,
            $finalWidth, $finalHeight,
            $sourceWidth, $sourceHeight
        );
        
        // Salvar
        $success = false;
        switch ($mimeType) {
            case 'image/jpeg':
                $success = imagejpeg($destImage, $destPath, 85);
                break;
            case 'image/png':
                $success = imagepng($destImage, $destPath, 6);
                break;
            case 'image/webp':
                $success = imagewebp($destImage, $destPath, 85);
                break;
        }
        
        imagedestroy($sourceImage);
        imagedestroy($destImage);
        
        return $success;
    }
    
    /**
     * Obter informações da imagem
     */
    private function getImageInfo($imagePath) {
        $imageInfo = getimagesize($imagePath);
        
        return [
            'width' => $imageInfo[0],
            'height' => $imageInfo[1],
            'type' => $imageInfo[2],
            'mime' => $imageInfo['mime'],
            'size' => filesize($imagePath)
        ];
    }
    
    /**
     * Salvar informações no banco de dados
     */
    private function saveImageDatabase($produtoId, $images) {
        try {
            $db = Database::getInstance();
            
            foreach ($images as $image) {
                $sql = "INSERT INTO produto_imagens (
                    produto_id, nome_arquivo, nome_original, 
                    caminho_original, caminho_thumb, caminho_medium, caminho_large,
                    tamanho_arquivo, largura, altura, tipo_mime, ordem, ativo, data_upload
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $db->execute($sql, [
                    $image['produto_id'],
                    $image['nome_arquivo'],
                    $image['nome_original'],
                    $image['caminho_original'],
                    $image['caminho_thumb'],
                    $image['caminho_medium'],
                    $image['caminho_large'],
                    $image['tamanho_arquivo'],
                    $image['largura'],
                    $image['altura'],
                    $image['tipo_mime'],
                    $image['ordem'],
                    $image['ativo'],
                    $image['data_upload']
                ]);
            }
            
            return true;
        } catch (Exception $e) {
            throw new Exception('Erro ao salvar no banco: ' . $e->getMessage());
        }
    }
    
    /**
     * Listar imagens de um produto
     */
    public function getProductImages($produtoId) {
        try {
            $db = Database::getInstance();
            
            $sql = "SELECT * FROM produto_imagens 
                    WHERE produto_id = ? AND ativo = 1 
                    ORDER BY ordem ASC, id ASC";
            
            return $db->fetchAll($sql, [$produtoId]);
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Excluir imagem
     */
    public function deleteImage($imageId) {
        try {
            $db = Database::getInstance();
            
            // Buscar informações da imagem
            $image = $db->fetch("SELECT * FROM produto_imagens WHERE id = ?", [$imageId]);
            
            if (!$image) {
                return [
                    'success' => false,
                    'message' => 'Imagem não encontrada'
                ];
            }
            
            // Excluir arquivos físicos
            $files = [
                $_SERVER['DOCUMENT_ROOT'] . '/' . $image['caminho_original'],
                $_SERVER['DOCUMENT_ROOT'] . '/' . $image['caminho_thumb'],
                $_SERVER['DOCUMENT_ROOT'] . '/' . $image['caminho_medium'],
                $_SERVER['DOCUMENT_ROOT'] . '/' . $image['caminho_large']
            ];
            
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
            
            // Excluir do banco
            $db->execute("DELETE FROM produto_imagens WHERE id = ?", [$imageId]);
            
            return [
                'success' => true,
                'message' => 'Imagem excluída com sucesso'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao excluir imagem: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Reordenar imagens
     */
    public function reorderImages($produtoId, $imageOrders) {
        try {
            $db = Database::getInstance();
            
            foreach ($imageOrders as $imageId => $ordem) {
                $db->execute(
                    "UPDATE produto_imagens SET ordem = ? WHERE id = ? AND produto_id = ?",
                    [$ordem, $imageId, $produtoId]
                );
            }
            
            return [
                'success' => true,
                'message' => 'Ordem das imagens atualizada'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao reordenar: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Normalizar array de arquivos para upload múltiplo
     */
    private function normalizeFileArray($files) {
        return [
            'name' => [$files['name']],
            'type' => [$files['type']],
            'tmp_name' => [$files['tmp_name']],
            'error' => [$files['error']],
            'size' => [$files['size']]
        ];
    }
    
    /**
     * Obter mensagem de erro de upload
     */
    private function getUploadErrorMessage($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Arquivo muito grande (limite do servidor)',
            UPLOAD_ERR_FORM_SIZE => 'Arquivo muito grande (limite do formulário)',
            UPLOAD_ERR_PARTIAL => 'Upload incompleto',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo enviado',
            UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário não encontrado',
            UPLOAD_ERR_CANT_WRITE => 'Falha ao escrever arquivo',
            UPLOAD_ERR_EXTENSION => 'Upload bloqueado por extensão'
        ];
        
        return $errors[$errorCode] ?? 'Erro desconhecido no upload';
    }
    
    /**
     * Limpar imagens órfãs (sem produto)
     */
    public function cleanOrphanImages() {
        try {
            $db = Database::getInstance();
            
            $sql = "SELECT pi.* FROM produto_imagens pi 
                    LEFT JOIN produtos p ON pi.produto_id = p.id 
                    WHERE p.id IS NULL";
            
            $orphanImages = $db->fetchAll($sql);
            $deletedCount = 0;
            
            foreach ($orphanImages as $image) {
                if ($this->deleteImage($image['id'])['success']) {
                    $deletedCount++;
                }
            }
            
            return [
                'success' => true,
                'message' => "Limpeza concluída. {$deletedCount} imagens órfãs removidas."
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro na limpeza: ' . $e->getMessage()
            ];
        }
    }
}
?>

