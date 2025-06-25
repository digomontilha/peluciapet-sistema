<?php
/**
 * Classe CorreiosAPI - Sistema PelúciaPet v2.1
 * Integração com API dos Correios para cálculo de frete
 */

class CorreiosAPI {
    private $usuario;
    private $senha;
    private $codigoEmpresa;
    private $cepOrigem;
    private $baseUrl;
    private $timeout;
    
    public function __construct() {
        // Configurações dos Correios (devem ser configuradas no painel admin)
        $this->usuario = 'ECT'; // Usuário padrão para teste
        $this->senha = 'SRO'; // Senha padrão para teste
        $this->codigoEmpresa = ''; // Código da empresa (opcional)
        $this->cepOrigem = '01310-100'; // CEP de origem (São Paulo - SP)
        $this->baseUrl = 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx';
        $this->timeout = 30;
    }
    
    /**
     * Configurar credenciais dos Correios
     */
    public function configurar($usuario, $senha, $codigoEmpresa = '', $cepOrigem = '01310-100') {
        $this->usuario = $usuario;
        $this->senha = $senha;
        $this->codigoEmpresa = $codigoEmpresa;
        $this->cepOrigem = $cepOrigem;
    }
    
    /**
     * Calcular frete para um produto
     */
    public function calcularFrete($cepDestino, $peso, $comprimento, $altura, $largura, $valorDeclarado = 0, $servicos = null) {
        try {
            // Validar CEP
            $cepDestino = $this->limparCep($cepDestino);
            if (!$this->validarCep($cepDestino)) {
                return [
                    'success' => false,
                    'message' => 'CEP de destino inválido'
                ];
            }
            
            // Serviços padrão se não especificados
            if ($servicos === null) {
                $servicos = [
                    '04014', // SEDEX
                    '04510', // PAC
                    '04782'  // SEDEX 12
                ];
            }
            
            $resultados = [];
            
            foreach ($servicos as $servico) {
                $resultado = $this->consultarServico(
                    $servico,
                    $this->cepOrigem,
                    $cepDestino,
                    $peso,
                    $comprimento,
                    $altura,
                    $largura,
                    $valorDeclarado
                );
                
                if ($resultado['success']) {
                    $resultados[] = $resultado['data'];
                }
            }
            
            if (empty($resultados)) {
                return [
                    'success' => false,
                    'message' => 'Não foi possível calcular o frete para nenhum serviço'
                ];
            }
            
            // Ordenar por preço
            usort($resultados, function($a, $b) {
                return $a['valor'] <=> $b['valor'];
            });
            
            return [
                'success' => true,
                'message' => 'Frete calculado com sucesso',
                'data' => $resultados
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao calcular frete: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Calcular frete para carrinho de compras
     */
    public function calcularFreteCarrinho($cepDestino, $itens, $servicos = null) {
        try {
            // Validar CEP
            $cepDestino = $this->limparCep($cepDestino);
            if (!$this->validarCep($cepDestino)) {
                return [
                    'success' => false,
                    'message' => 'CEP de destino inválido'
                ];
            }
            
            // Calcular dimensões e peso total do carrinho
            $dimensoes = $this->calcularDimensoesCarrinho($itens);
            
            if (!$dimensoes['success']) {
                return $dimensoes;
            }
            
            $dados = $dimensoes['data'];
            
            return $this->calcularFrete(
                $cepDestino,
                $dados['peso'],
                $dados['comprimento'],
                $dados['altura'],
                $dados['largura'],
                $dados['valor_declarado'],
                $servicos
            );
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao calcular frete do carrinho: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Consultar um serviço específico dos Correios
     */
    private function consultarServico($codigoServico, $cepOrigem, $cepDestino, $peso, $comprimento, $altura, $largura, $valorDeclarado) {
        try {
            // Parâmetros para a API dos Correios
            $params = [
                'nCdEmpresa' => $this->codigoEmpresa,
                'sDsSenha' => $this->senha,
                'nCdServico' => $codigoServico,
                'sCepOrigem' => $cepOrigem,
                'sCepDestino' => $cepDestino,
                'nVlPeso' => $peso,
                'nCdFormato' => 1, // Caixa/pacote
                'nVlComprimento' => $comprimento,
                'nVlAltura' => $altura,
                'nVlLargura' => $largura,
                'nVlDiametro' => 0,
                'sCdMaoPropria' => 'N',
                'nVlValorDeclarado' => $valorDeclarado,
                'sCdAvisoRecebimento' => 'N'
            ];
            
            // Fazer requisição
            $url = $this->baseUrl . '?' . http_build_query($params);
            $response = $this->fazerRequisicao($url);
            
            if (!$response['success']) {
                return $response;
            }
            
            // Processar resposta XML
            $xml = simplexml_load_string($response['data']);
            
            if ($xml === false) {
                return [
                    'success' => false,
                    'message' => 'Erro ao processar resposta dos Correios'
                ];
            }
            
            $servico = $xml->cServico;
            
            // Verificar se houve erro
            if ((string)$servico->Erro !== '0') {
                return [
                    'success' => false,
                    'message' => 'Erro dos Correios: ' . (string)$servico->MsgErro
                ];
            }
            
            // Extrair dados do serviço
            $nomeServico = $this->getNomeServico($codigoServico);
            $valor = (float)str_replace(',', '.', str_replace('.', '', (string)$servico->Valor));
            $prazo = (int)$servico->PrazoEntrega;
            
            return [
                'success' => true,
                'data' => [
                    'codigo' => $codigoServico,
                    'nome' => $nomeServico,
                    'valor' => $valor,
                    'prazo' => $prazo,
                    'prazo_formatado' => $this->formatarPrazo($prazo),
                    'observacoes' => (string)$servico->ObsFim,
                    'valor_mao_propria' => (float)str_replace(',', '.', (string)$servico->ValorMaoPropria),
                    'valor_aviso_recebimento' => (float)str_replace(',', '.', (string)$servico->ValorAvisoRecebimento),
                    'valor_declarado' => (float)str_replace(',', '.', (string)$servico->ValorValorDeclarado),
                    'entrega_domiciliar' => (string)$servico->EntregaDomiciliar === 'S',
                    'entrega_sabado' => (string)$servico->EntregaSabado === 'S'
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro na consulta: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Calcular dimensões combinadas do carrinho
     */
    private function calcularDimensoesCarrinho($itens) {
        try {
            $pesoTotal = 0;
            $valorTotal = 0;
            $volumes = [];
            
            foreach ($itens as $item) {
                // Validar item
                if (!isset($item['peso']) || !isset($item['comprimento']) || 
                    !isset($item['altura']) || !isset($item['largura']) || 
                    !isset($item['quantidade']) || !isset($item['valor'])) {
                    return [
                        'success' => false,
                        'message' => 'Dados incompletos do item: ' . ($item['nome'] ?? 'sem nome')
                    ];
                }
                
                $quantidade = (int)$item['quantidade'];
                
                for ($i = 0; $i < $quantidade; $i++) {
                    $volumes[] = [
                        'peso' => (float)$item['peso'],
                        'comprimento' => (float)$item['comprimento'],
                        'altura' => (float)$item['altura'],
                        'largura' => (float)$item['largura']
                    ];
                }
                
                $pesoTotal += (float)$item['peso'] * $quantidade;
                $valorTotal += (float)$item['valor'] * $quantidade;
            }
            
            // Calcular dimensões da embalagem combinada
            $dimensoesCombinadas = $this->combinarVolumes($volumes);
            
            return [
                'success' => true,
                'data' => [
                    'peso' => $pesoTotal,
                    'comprimento' => $dimensoesCombinadas['comprimento'],
                    'altura' => $dimensoesCombinadas['altura'],
                    'largura' => $dimensoesCombinadas['largura'],
                    'valor_declarado' => $valorTotal,
                    'total_volumes' => count($volumes)
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao calcular dimensões: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Combinar volumes em uma única embalagem
     */
    private function combinarVolumes($volumes) {
        if (empty($volumes)) {
            return [
                'comprimento' => 16,
                'altura' => 2,
                'largura' => 11
            ];
        }
        
        // Estratégia simples: usar as maiores dimensões
        $maxComprimento = 0;
        $maxAltura = 0;
        $maxLargura = 0;
        
        foreach ($volumes as $volume) {
            $maxComprimento = max($maxComprimento, $volume['comprimento']);
            $maxAltura = max($maxAltura, $volume['altura']);
            $maxLargura = max($maxLargura, $volume['largura']);
        }
        
        // Aplicar limites mínimos e máximos dos Correios
        $comprimento = max(16, min(105, $maxComprimento));
        $altura = max(2, min(105, $maxAltura));
        $largura = max(11, min(105, $maxLargura));
        
        // Verificar soma das dimensões
        $soma = $comprimento + $altura + $largura;
        if ($soma > 200) {
            // Reduzir proporcionalmente
            $fator = 200 / $soma;
            $comprimento = max(16, $comprimento * $fator);
            $altura = max(2, $altura * $fator);
            $largura = max(11, $largura * $fator);
        }
        
        return [
            'comprimento' => round($comprimento, 1),
            'altura' => round($altura, 1),
            'largura' => round($largura, 1)
        ];
    }
    
    /**
     * Consultar CEP
     */
    public function consultarCep($cep) {
        try {
            $cep = $this->limparCep($cep);
            
            if (!$this->validarCep($cep)) {
                return [
                    'success' => false,
                    'message' => 'CEP inválido'
                ];
            }
            
            // URL da API de CEP dos Correios
            $url = "https://viacep.com.br/ws/{$cep}/json/";
            
            $response = $this->fazerRequisicao($url);
            
            if (!$response['success']) {
                return $response;
            }
            
            $data = json_decode($response['data'], true);
            
            if (isset($data['erro'])) {
                return [
                    'success' => false,
                    'message' => 'CEP não encontrado'
                ];
            }
            
            return [
                'success' => true,
                'data' => [
                    'cep' => $data['cep'],
                    'logradouro' => $data['logradouro'],
                    'complemento' => $data['complemento'],
                    'bairro' => $data['bairro'],
                    'localidade' => $data['localidade'],
                    'uf' => $data['uf'],
                    'ibge' => $data['ibge'],
                    'gia' => $data['gia'],
                    'ddd' => $data['ddd'],
                    'siafi' => $data['siafi']
                ]
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao consultar CEP: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Rastrear encomenda
     */
    public function rastrearEncomenda($codigoRastreio) {
        try {
            // URL da API de rastreamento
            $url = "https://api.correios.com.br/sro/v1/objetos/{$codigoRastreio}";
            
            $headers = [
                'Accept: application/json',
                'Authorization: Bearer ' . $this->obterTokenRastreamento()
            ];
            
            $response = $this->fazerRequisicao($url, $headers);
            
            if (!$response['success']) {
                return $response;
            }
            
            $data = json_decode($response['data'], true);
            
            return [
                'success' => true,
                'data' => $data
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro ao rastrear encomenda: ' . $e->getMessage()
            ];
        }
    }
    
    // Métodos auxiliares
    
    private function limparCep($cep) {
        return preg_replace('/[^0-9]/', '', $cep);
    }
    
    private function validarCep($cep) {
        return preg_match('/^[0-9]{8}$/', $cep);
    }
    
    private function getNomeServico($codigo) {
        $servicos = [
            '04014' => 'SEDEX',
            '04510' => 'PAC',
            '04782' => 'SEDEX 12',
            '04790' => 'SEDEX Hoje',
            '04804' => 'SEDEX 10',
            '41106' => 'PAC',
            '40010' => 'SEDEX Varejo',
            '40045' => 'SEDEX a Cobrar Varejo',
            '40215' => 'SEDEX 10 Varejo',
            '40290' => 'SEDEX Hoje Varejo'
        ];
        
        return $servicos[$codigo] ?? 'Serviço ' . $codigo;
    }
    
    private function formatarPrazo($dias) {
        if ($dias == 0) {
            return 'No mesmo dia';
        } elseif ($dias == 1) {
            return '1 dia útil';
        } else {
            return $dias . ' dias úteis';
        }
    }
    
    private function fazerRequisicao($url, $headers = []) {
        try {
            $ch = curl_init();
            
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_USERAGENT => 'PelúciaPet Sistema v2.1'
            ]);
            
            if (!empty($headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_close($ch);
            
            if ($error) {
                return [
                    'success' => false,
                    'message' => 'Erro na requisição: ' . $error
                ];
            }
            
            if ($httpCode !== 200) {
                return [
                    'success' => false,
                    'message' => 'Erro HTTP: ' . $httpCode
                ];
            }
            
            return [
                'success' => true,
                'data' => $response
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro na requisição: ' . $e->getMessage()
            ];
        }
    }
    
    private function obterTokenRastreamento() {
        // Implementar obtenção de token para API de rastreamento
        // Por enquanto, retorna um token fictício
        return 'token_ficticio';
    }
    
    /**
     * Validar configurações dos Correios
     */
    public function validarConfiguracao() {
        try {
            // Teste simples com CEP conhecido
            $resultado = $this->calcularFrete(
                '01310-100', // CEP de destino (São Paulo)
                0.3,         // 300g
                16,          // 16cm
                2,           // 2cm
                11,          // 11cm
                50,          // R$ 50,00
                ['04510']    // Apenas PAC
            );
            
            return $resultado;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Erro na validação: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obter configurações salvas
     */
    public function obterConfiguracoes() {
        return [
            'usuario' => $this->usuario,
            'codigo_empresa' => $this->codigoEmpresa,
            'cep_origem' => $this->cepOrigem,
            'timeout' => $this->timeout
        ];
    }
}
?>

