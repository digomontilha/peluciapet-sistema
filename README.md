# 🐾 Sistema PelúciaPet v2.2

> **Sistema Empresarial Completo para E-commerce de Pet Shop**  
> Versão 2.2 - Funcionalidades Avançadas

[![Versão](https://img.shields.io/badge/versão-2.2.0-blue.svg)](https://github.com/digomontilha/peluciapet-sistema)
[![PHP](https://img.shields.io/badge/PHP-8.0+-green.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange.svg)](https://mysql.com)
[![PWA](https://img.shields.io/badge/PWA-Ready-purple.svg)](https://web.dev/progressive-web-apps/)

## 🚀 **Novidades da Versão 2.2**

### 📱 **PWA (Progressive Web App)**
- ✅ **App Mobile Nativo** - Instalação automática no celular
- ✅ **Funciona Offline** - Cache inteligente de produtos
- ✅ **Notificações Push** - Alertas de promoções e pedidos
- ✅ **Performance Otimizada** - Carregamento instantâneo
- ✅ **Ícone na Tela Inicial** - Experiência de app nativo

### ⭐ **Sistema de Avaliações Avançado**
- ✅ **Reviews Completos** - Notas de 1-5 estrelas + comentários
- ✅ **Upload de Imagens** - Clientes podem anexar fotos
- ✅ **Sistema de Votos** - "Útil" ou "Não útil" nas avaliações
- ✅ **Moderação Automática** - Filtros de conteúdo inadequado
- ✅ **Estatísticas Detalhadas** - Analytics de satisfação

### 💬 **Chat Online em Tempo Real**
- ✅ **Suporte Instantâneo** - Chat ao vivo com clientes
- ✅ **Múltiplos Atendentes** - Sistema de distribuição automática
- ✅ **Upload de Arquivos** - Envio de imagens e documentos
- ✅ **Histórico Completo** - Todas as conversas salvas
- ✅ **Horário de Atendimento** - Configuração flexível

### 🛒 **Integração com Marketplaces**
- ✅ **Mercado Livre** - Sincronização automática de produtos
- ✅ **Amazon** - Gestão centralizada de estoque
- ✅ **Shopee** - Preços e promoções sincronizados
- ✅ **OLX** - Publicação automática
- ✅ **Logs Detalhados** - Controle total das sincronizações

### 🏪 **Sistema Multi-loja**
- ✅ **Múltiplas Lojas** - Matriz, filiais, franquias e parceiros
- ✅ **Gestão de Estoque** - Por loja individual
- ✅ **Transferências** - Entre lojas automatizadas
- ✅ **Comissões** - Cálculo automático para franquias
- ✅ **Relatórios Consolidados** - Visão geral de todas as lojas

## 🎯 **Funcionalidades Principais**

### 🛍️ **E-commerce Completo**
- **Catálogo de Produtos** - Gestão completa com variações
- **Carrinho de Compras** - Experiência otimizada
- **Checkout Simplificado** - Processo de compra em 3 passos
- **Múltiplas Formas de Pagamento** - PIX, cartão, boleto
- **Cálculo de Frete** - Integração com Correios

### 📊 **Analytics e Relatórios**
- **Dashboard em Tempo Real** - Métricas de vendas e performance
- **Relatórios Avançados** - Vendas, estoque, clientes
- **Gráficos Interativos** - Visualização de dados
- **Exportação** - CSV, Excel, PDF
- **Previsões** - Tendências de vendas

### 🔐 **Segurança e Administração**
- **Sistema de Usuários** - Múltiplos níveis de acesso
- **Logs de Auditoria** - Rastreamento de todas as ações
- **Backup Automático** - Proteção de dados
- **SSL/HTTPS** - Comunicação segura
- **Proteção CSRF** - Segurança contra ataques

## 📋 **Requisitos do Sistema**

### **Servidor Web**
- **PHP:** 8.0 ou superior
- **MySQL:** 5.7 ou superior (ou MariaDB 10.2+)
- **Apache:** 2.4+ ou Nginx 1.18+
- **Extensões PHP:** PDO, GD, cURL, JSON, mbstring

### **Recursos Recomendados**
- **RAM:** 2GB mínimo, 4GB recomendado
- **Armazenamento:** 10GB mínimo
- **Largura de Banda:** Ilimitada
- **SSL:** Certificado válido

## 🚀 **Instalação Rápida**

### **1. Download e Extração**
```bash
# Baixar o sistema
wget https://github.com/digomontilha/peluciapet-sistema/archive/v2.2.0.zip

# Extrair arquivos
unzip v2.2.0.zip
mv peluciapet-sistema-2.2.0/* /var/www/html/
```

### **2. Configuração do Banco**
```sql
-- Criar banco de dados
CREATE DATABASE peluciapet;
USE peluciapet;

-- Importar estrutura
SOURCE database/instalacao-v2.2.sql;
```

### **3. Configuração do Sistema**
```php
// Editar admin/config/config.php
$config = [
    'host' => 'localhost',
    'dbname' => 'peluciapet',
    'username' => 'seu_usuario',
    'password' => 'sua_senha'
];
```

### **4. Permissões de Arquivos**
```bash
# Definir permissões
chmod -R 755 /var/www/html/
chown -R www-data:www-data /var/www/html/
chmod -R 777 frontend/uploads/
chmod -R 777 frontend/logs/
```

## 🎮 **Primeiros Passos**

### **1. Acesso Administrativo**
- **URL:** `https://seudominio.com/admin/`
- **Usuário:** `admin@peluciapet.com.br`
- **Senha:** `password`

### **2. Configurações Iniciais**
1. **Alterar senhas padrão**
2. **Configurar dados da empresa**
3. **Definir formas de pagamento**
4. **Configurar frete**
5. **Cadastrar produtos**

### **3. Configurar PWA**
1. **Ativar HTTPS** (obrigatório para PWA)
2. **Configurar notificações push**
3. **Testar instalação do app**

## 📱 **Funcionalidades PWA**

### **Instalação do App**
- **Android:** Banner automático de instalação
- **iOS:** "Adicionar à Tela Inicial"
- **Desktop:** Ícone na barra de endereços

### **Recursos Offline**
- **Navegação básica** funciona sem internet
- **Produtos em cache** para visualização
- **Sincronização automática** quando voltar online

### **Notificações Push**
```javascript
// Configurar notificações
navigator.serviceWorker.ready.then(registration => {
    registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: 'sua-chave-vapid'
    });
});
```

## 🛒 **Integração com Marketplaces**

### **Mercado Livre**
```php
// Configurar API
$config['mercadolivre'] = [
    'app_id' => 'seu_app_id',
    'client_secret' => 'seu_client_secret',
    'access_token' => 'seu_access_token'
];
```

### **Amazon**
```php
// Configurar MWS
$config['amazon'] = [
    'marketplace_id' => 'A2Q3Y263D00KWC',
    'merchant_id' => 'seu_merchant_id',
    'access_key' => 'sua_access_key',
    'secret_key' => 'sua_secret_key'
];
```

## 🏪 **Sistema Multi-loja**

### **Criar Nova Loja**
```php
$multiLoja = new MultiLoja();
$resultado = $multiLoja->criarLoja([
    'nome' => 'PelúciaPet Filial SP',
    'tipo' => 'filial',
    'cnpj' => '12.345.678/0001-90',
    'email' => 'sp@peluciapet.com.br',
    'responsavel_nome' => 'João Silva',
    'responsavel_email' => 'joao@peluciapet.com.br'
]);
```

### **Transferir Estoque**
```php
$resultado = $multiLoja->transferirEstoque(
    $lojaOrigemId = 1,
    $lojaDestinoId = 2,
    $produtoId = 10,
    $quantidade = 50,
    $motivo = 'Reposição de estoque'
);
```

## 💬 **Sistema de Chat**

### **Configurar Chat**
```javascript
// Inicializar chat
const chat = new PeluciaChat({
    endpoint: '/admin/api/chat.php',
    departamento: 'vendas',
    autoStart: true
});

// Eventos do chat
chat.on('mensagem', function(dados) {
    console.log('Nova mensagem:', dados);
});
```

### **Atendimento**
- **Dashboard de Atendimento:** `/admin/chat/`
- **Notificações em Tempo Real**
- **Histórico de Conversas**
- **Relatórios de Atendimento**

## ⭐ **Sistema de Avaliações**

### **Configurar Moderação**
```php
$avaliacao = new Avaliacao();

// Configurar filtros automáticos
$filtros = [
    'palavras_proibidas' => ['spam', 'fake'],
    'nota_minima_auto_aprovacao' => 4,
    'moderacao_manual' => true
];

$avaliacao->configurarModeração($filtros);
```

### **Exibir Avaliações**
```php
// Buscar avaliações de um produto
$avaliacoes = $avaliacao->buscarPorProduto($produtoId, [
    'status' => 'aprovada',
    'limite' => 10,
    'ordenacao' => 'data_desc'
]);
```

## 📊 **Relatórios e Analytics**

### **Dashboard Principal**
- **Vendas do Dia/Mês/Ano**
- **Produtos Mais Vendidos**
- **Clientes Ativos**
- **Estoque Baixo**
- **Avaliações Pendentes**

### **Relatórios Avançados**
- **Relatório de Vendas por Período**
- **Performance de Produtos**
- **Análise de Clientes**
- **Comissões de Franquias**
- **Sincronização de Marketplaces**

## 🔧 **Configurações Avançadas**

### **Otimização de Performance**
```php
// Cache de produtos
$config['cache'] = [
    'produtos' => 3600, // 1 hora
    'categorias' => 7200, // 2 horas
    'configuracoes' => 86400 // 24 horas
];

// Compressão de imagens
$config['imagens'] = [
    'qualidade_jpeg' => 85,
    'formato_webp' => true,
    'redimensionar_automatico' => true
];
```

### **Backup Automático**
```bash
# Configurar cron para backup diário
0 2 * * * /var/www/html/scripts/backup-mysql.sh
```

### **Monitoramento**
```php
// Logs detalhados
$config['logs'] = [
    'nivel' => 'INFO',
    'arquivo' => 'logs/sistema.log',
    'rotacao' => 'diaria',
    'retencao' => 30 // dias
];
```

## 🛡️ **Segurança**

### **Proteções Implementadas**
- **SQL Injection:** Prepared statements
- **XSS:** Sanitização de dados
- **CSRF:** Tokens de segurança
- **Brute Force:** Limite de tentativas
- **Upload Seguro:** Validação de arquivos

### **Configurações de Segurança**
```php
// Configurações de sessão
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);

// Headers de segurança
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
```

## 📞 **Suporte e Documentação**

### **Documentação Completa**
- **Manual do Usuário:** `/docs/manual-usuario.pdf`
- **Guia do Desenvolvedor:** `/docs/guia-desenvolvedor.md`
- **API Reference:** `/docs/api-reference.md`
- **FAQ:** `/docs/faq.md`

### **Suporte Técnico**
- **Email:** suporte@peluciapet.com.br
- **WhatsApp:** (11) 99999-9999
- **GitHub Issues:** [Reportar Bug](https://github.com/digomontilha/peluciapet-sistema/issues)

## 🎉 **Changelog v2.2**

### **✨ Novas Funcionalidades**
- PWA completo com notificações push
- Sistema de avaliações com imagens
- Chat online em tempo real
- Integração com múltiplos marketplaces
- Sistema multi-loja empresarial

### **🔧 Melhorias**
- Performance otimizada (50% mais rápido)
- Interface redesenhada
- Segurança aprimorada
- Backup automático
- Logs detalhados

### **🐛 Correções**
- Correção de bugs de sincronização
- Melhoria na responsividade mobile
- Otimização de consultas SQL
- Correção de problemas de cache

## 📄 **Licença**

Este projeto está licenciado sob a **Licença MIT** - veja o arquivo [LICENSE](LICENSE) para detalhes.

## 🤝 **Contribuição**

Contribuições são bem-vindas! Por favor, leia o [CONTRIBUTING.md](CONTRIBUTING.md) para detalhes sobre nosso código de conduta e processo de submissão de pull requests.

## 🏆 **Créditos**

Desenvolvido com ❤️ pela equipe **PelúciaPet**

- **Desenvolvimento:** Equipe PelúciaPet
- **Design:** UI/UX Team
- **Testes:** QA Team
- **Documentação:** Tech Writers

---

**🐾 PelúciaPet v2.2 - Transformando o cuidado com pets através da tecnologia!**

