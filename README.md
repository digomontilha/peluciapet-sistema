# 🐾 Sistema PelúciaPet v2.1

> **Sistema completo de e-commerce para produtos pet com funcionalidades avançadas**

[![Versão](https://img.shields.io/badge/versão-2.1.0-FF6B9D.svg)](https://github.com/digomontilha/peluciapet-sistema)
[![PHP](https://img.shields.io/badge/PHP-8.3+-A0522D.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-D4A04C.svg)](https://mysql.com)
[![License](https://img.shields.io/badge/license-MIT-5C2C0D.svg)](LICENSE)

## ✨ Novidades da Versão 2.1

### 📸 **Sistema de Upload de Imagens**
- Upload múltiplo de imagens por produto
- Redimensionamento automático e otimização
- Galeria com reordenação por arrastar e soltar
- Suporte a WebP, JPEG, PNG
- Compressão inteligente para web

### 🏷️ **Sistema de Categorias Hierárquico**
- Categorias e subcategorias ilimitadas (até 3 níveis)
- URLs amigáveis (SEO-friendly)
- Breadcrumbs automáticos
- Cores e ícones personalizáveis
- Meta tags para SEO

### 📊 **Relatórios Avançados de Vendas**
- Dashboard em tempo real com gráficos
- Análise de performance por produto/categoria
- Relatórios de clientes e retenção
- Previsão de vendas baseada em IA
- Exportação para CSV/Excel

### 📦 **Integração com Correios**
- Cálculo automático de frete (PAC, SEDEX)
- Consulta de CEP em tempo real
- Rastreamento de encomendas
- Múltiplas modalidades de entrega
- Frete grátis configurável

### 🎫 **Sistema de Cupons Inteligente**
- Cupons percentuais, valor fixo e frete grátis
- Restrições por categoria, produto ou cliente
- Limites de uso e validade
- Cupons para primeira compra
- Relatórios de performance

## 🚀 Funcionalidades Principais

### 🛍️ **E-commerce Completo**
- ✅ Catálogo de produtos responsivo
- ✅ Carrinho de compras inteligente
- ✅ Checkout simplificado
- ✅ Múltiplas formas de pagamento
- ✅ Gestão de pedidos completa

### 🎨 **Design Profissional**
- ✅ Interface moderna e responsiva
- ✅ Paleta de cores da marca PelúciaPet
- ✅ Animações suaves e micro-interações
- ✅ Otimizado para mobile e desktop
- ✅ Acessibilidade (WCAG 2.1)

### 🔐 **Segurança Avançada**
- ✅ Sistema de autenticação robusto
- ✅ Proteção contra ataques comuns
- ✅ Criptografia de dados sensíveis
- ✅ Logs de auditoria
- ✅ Backup automático

### 📱 **Integração Social**
- ✅ WhatsApp Business integrado
- ✅ Compartilhamento em redes sociais
- ✅ Instagram Shopping (preparado)
- ✅ Google Analytics integrado
- ✅ Facebook Pixel (preparado)

## 🛠️ Tecnologias Utilizadas

### **Backend**
- **PHP 8.3+** - Linguagem principal
- **MySQL 8.0+** - Banco de dados
- **PDO** - Abstração de banco
- **JWT** - Autenticação
- **cURL** - Integrações externas

### **Frontend**
- **HTML5** - Estrutura semântica
- **CSS3** - Estilização avançada
- **JavaScript ES6+** - Interatividade
- **Chart.js** - Gráficos e relatórios
- **Font Awesome** - Ícones

### **APIs Integradas**
- **Correios** - Cálculo de frete
- **ViaCEP** - Consulta de endereços
- **WhatsApp Business** - Atendimento
- **Google Analytics** - Métricas

## 📋 Requisitos do Sistema

### **Servidor Web**
- Apache 2.4+ ou Nginx 1.18+
- PHP 8.3+ com extensões:
  - PDO MySQL
  - GD ou ImageMagick
  - cURL
  - JSON
  - mbstring
  - OpenSSL

### **Banco de Dados**
- MySQL 8.0+ ou MariaDB 10.6+
- Mínimo 100MB de espaço
- Suporte a UTF-8 (utf8mb4)

### **Recursos**
- Mínimo 512MB RAM
- 1GB espaço em disco
- SSL/TLS (recomendado)

## 🚀 Instalação Rápida

### 1. **Download e Extração**
```bash
# Baixar o sistema
wget https://github.com/digomontilha/peluciapet-sistema/archive/v2.1.zip

# Extrair arquivos
unzip v2.1.zip -d /var/www/html/
cd /var/www/html/peluciapet-sistema-2.1/
```

### 2. **Configuração do Banco**
```bash
# Criar banco de dados
mysql -u root -p -e "CREATE DATABASE peluciapet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importar estrutura
mysql -u root -p peluciapet < database/update-v2.1.sql
```

### 3. **Configuração do Sistema**
```bash
# Copiar arquivo de configuração
cp admin/config/config-exemplo.php admin/config/config.php

# Editar configurações
nano admin/config/config.php
```

### 4. **Permissões**
```bash
# Definir permissões
chmod 755 -R .
chmod 777 -R frontend/uploads/
chmod 777 -R frontend/logs/
chown -R www-data:www-data .
```

### 5. **Verificação**
```bash
# Executar verificador
php verificar-sistema.php
```

## ⚙️ Configuração Detalhada

### **Banco de Dados** (`admin/config/config.php`)
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'peluciapet');
define('DB_USER', 'seu_usuario');
define('DB_PASS', 'sua_senha');
define('DB_CHARSET', 'utf8mb4');
```

### **Correios** (Painel Admin)
- Usuário dos Correios
- Senha dos Correios
- CEP de origem
- Código da empresa (opcional)

### **WhatsApp** (`frontend/js/config.js`)
```javascript
const WHATSAPP_CONFIG = {
    numero: '5511999999999',
    mensagem_padrao: 'Olá! Tenho interesse nos produtos da PelúciaPet.'
};
```

## 📊 Estrutura do Projeto

```
peluciapet-v2.1/
├── admin/                      # Painel administrativo
│   ├── api/                   # APIs REST
│   │   ├── auth.php          # Autenticação
│   │   ├── produtos.php      # Gestão de produtos
│   │   ├── categorias.php    # Sistema de categorias
│   │   ├── cupons.php        # Sistema de cupons
│   │   ├── relatorios.php    # Relatórios e analytics
│   │   ├── frete.php         # Integração Correios
│   │   └── upload-imagens.php # Upload de imagens
│   ├── classes/              # Classes PHP
│   │   ├── Auth.php          # Autenticação
│   │   ├── Database.php      # Conexão BD
│   │   ├── Produto.php       # Gestão produtos
│   │   ├── Categoria.php     # Categorias
│   │   ├── Cupom.php         # Sistema cupons
│   │   ├── RelatorioVendas.php # Relatórios
│   │   ├── CorreiosAPI.php   # API Correios
│   │   └── ImageUpload.php   # Upload imagens
│   ├── config/               # Configurações
│   │   └── config.php        # Config principal
│   ├── public/               # Interface admin
│   │   ├── dashboard-v2.html # Dashboard v2.1
│   │   ├── categorias.html   # Gestão categorias
│   │   ├── cupons.html       # Gestão cupons
│   │   └── relatorios.html   # Relatórios
│   └── auth/                 # Autenticação
│       └── login.php         # Tela de login
├── frontend/                 # Site público
│   ├── css/                  # Estilos
│   ├── js/                   # Scripts
│   ├── images/               # Imagens
│   ├── uploads/              # Uploads
│   └── *.html               # Páginas
├── database/                 # Banco de dados
│   └── update-v2.1.sql      # Script atualização
├── docs/                     # Documentação
│   └── INSTALACAO.md        # Guia instalação
├── scripts/                  # Scripts utilitários
│   └── backup-mysql.sh      # Backup automático
└── verificar-sistema.php    # Verificador
```

## 🔧 APIs Disponíveis

### **Autenticação**
- `POST /admin/api/auth.php` - Login/logout
- `GET /admin/api/auth.php?action=check` - Verificar sessão

### **Produtos**
- `GET /admin/api/produtos.php` - Listar produtos
- `POST /admin/api/produtos.php` - Criar produto
- `PUT /admin/api/produtos.php` - Atualizar produto
- `DELETE /admin/api/produtos.php` - Excluir produto

### **Categorias**
- `GET /admin/api/categorias.php` - Listar categorias
- `GET /admin/api/categorias.php?action=tree` - Árvore hierárquica
- `POST /admin/api/categorias.php` - Criar categoria
- `PUT /admin/api/categorias.php` - Atualizar categoria

### **Cupons**
- `GET /admin/api/cupons.php` - Listar cupons
- `GET /admin/api/cupons.php?action=validar&codigo=XXX` - Validar cupom
- `POST /admin/api/cupons.php` - Criar cupom
- `PUT /admin/api/cupons.php` - Atualizar cupom

### **Relatórios**
- `GET /admin/api/relatorios.php?action=dashboard` - Dashboard
- `GET /admin/api/relatorios.php?action=detalhado` - Relatório detalhado
- `GET /admin/api/relatorios.php?action=export` - Exportar dados

### **Frete**
- `POST /admin/api/frete.php` - Calcular frete
- `GET /admin/api/frete.php?action=consultar_cep` - Consultar CEP
- `GET /admin/api/frete.php?action=rastrear` - Rastrear encomenda

## 📈 Métricas e Analytics

### **Dashboard Principal**
- Vendas em tempo real
- Receita total e ticket médio
- Produtos mais vendidos
- Performance por categoria
- Análise de clientes

### **Relatórios Disponíveis**
- Vendas por período
- Performance de produtos
- Análise de categorias
- Efetividade de cupons
- Relatório de frete
- Análise de clientes

### **Exportação**
- CSV para Excel
- Relatórios personalizados
- Dados para BI
- Backup de dados

## 🔒 Segurança

### **Autenticação**
- Login seguro com hash
- Sessões com timeout
- Proteção CSRF
- Rate limiting

### **Dados**
- Validação de entrada
- Sanitização de dados
- Prepared statements
- Logs de auditoria

### **Arquivos**
- Upload seguro de imagens
- Validação de tipos
- Proteção contra malware
- Quarentena automática

## 🚀 Deploy e Produção

### **Hospedagem Compartilhada**
1. Upload via FTP/cPanel
2. Importar banco via phpMyAdmin
3. Configurar permissões
4. Testar funcionalidades

### **VPS/Servidor Dedicado**
1. Configurar Apache/Nginx
2. Instalar PHP e extensões
3. Configurar MySQL
4. SSL/TLS obrigatório
5. Backup automático

### **Docker** (Opcional)
```bash
# Build da imagem
docker build -t peluciapet:v2.1 .

# Executar container
docker run -d -p 80:80 peluciapet:v2.1
```

## 🔄 Backup e Manutenção

### **Backup Automático**
```bash
# Executar script de backup
./scripts/backup-mysql.sh

# Agendar no crontab
0 2 * * * /path/to/backup-mysql.sh
```

### **Monitoramento**
- Logs de erro do PHP
- Logs de acesso do Apache
- Monitoramento de espaço
- Verificação de integridade

### **Atualizações**
- Backup antes de atualizar
- Testar em ambiente de desenvolvimento
- Verificar compatibilidade
- Documentar mudanças

## 🆘 Suporte e Troubleshooting

### **Problemas Comuns**

**Erro de conexão com banco:**
```bash
# Verificar configurações
php verificar-sistema.php

# Testar conexão manual
mysql -u usuario -p -h localhost peluciapet
```

**Upload de imagens não funciona:**
```bash
# Verificar permissões
chmod 777 frontend/uploads/
chown www-data:www-data frontend/uploads/
```

**Erro 500 no painel admin:**
```bash
# Verificar logs do PHP
tail -f /var/log/apache2/error.log

# Verificar .htaccess
cat admin/.htaccess
```

### **Logs Importantes**
- `/var/log/apache2/error.log` - Erros do servidor
- `frontend/logs/sistema.log` - Logs do sistema
- `admin/logs/auth.log` - Logs de autenticação

## 📞 Contato e Suporte

- **Email:** suporte@peluciapet.com.br
- **WhatsApp:** (11) 99999-9999
- **GitHub:** [Issues](https://github.com/digomontilha/peluciapet-sistema/issues)
- **Documentação:** [Wiki](https://github.com/digomontilha/peluciapet-sistema/wiki)

## 📄 Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

## 🙏 Agradecimentos

- Equipe PelúciaPet pelo feedback constante
- Comunidade PHP pela documentação
- Desenvolvedores das bibliotecas utilizadas
- Beta testers que ajudaram nos testes

---

**Desenvolvido com ❤️ para a PelúciaPet**

*Sistema PelúciaPet v2.1 - Transformando o cuidado pet em experiências digitais incríveis*

