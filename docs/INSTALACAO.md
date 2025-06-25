# 📋 Guia de Instalação - Sistema PelúciaPet

## 🎯 Visão Geral

O Sistema PelúciaPet é uma solução completa para gerenciamento de produtos pet, incluindo:

- **Sistema Administrativo** com autenticação segura
- **API REST** completa para gerenciamento de produtos
- **Frontend Público** integrado para exibição dos produtos
- **Banco de Dados MySQL** otimizado para performance
- **Sistema de Autenticação** com controle de permissões

## 📋 Pré-requisitos

### Servidor Web
- **Apache 2.4+** ou **Nginx 1.18+**
- **PHP 7.4+** (recomendado PHP 8.0+)
- **MySQL 5.7+** ou **MariaDB 10.3+**
- **Certificado SSL** (recomendado)

### Extensões PHP Necessárias
- `pdo`
- `pdo_mysql`
- `json`
- `mbstring`
- `curl`
- `openssl`
- `session`

### Permissões de Arquivo
- Diretório web com permissões de leitura/escrita
- Acesso ao banco de dados MySQL
- Capacidade de criar/modificar arquivos `.htaccess`

## 🚀 Processo de Instalação

### Passo 1: Preparar o Banco de Dados

1. **Criar Banco MySQL**
   ```sql
   CREATE DATABASE peluciapet CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'peluciapet'@'localhost' IDENTIFIED BY 'sua_senha_segura';
   GRANT ALL PRIVILEGES ON peluciapet.* TO 'peluciapet'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. **Executar Script de Instalação**
   - Acesse phpMyAdmin ou cliente MySQL
   - Selecione o banco `peluciapet`
   - Execute o conteúdo do arquivo `database/install.sql`
   - Verifique se todas as tabelas foram criadas

### Passo 2: Upload dos Arquivos

1. **Estrutura de Diretórios**
   ```
   public_html/
   ├── admin/                    # Sistema administrativo
   │   ├── api/                 # APIs do sistema
   │   ├── auth/                # Sistema de autenticação
   │   ├── classes/             # Classes PHP
   │   ├── config/              # Configurações
   │   └── public/              # Interface administrativa
   ├── css/                     # Estilos do frontend
   ├── js/                      # Scripts do frontend
   ├── images/                  # Imagens do site
   ├── uploads/                 # Uploads de produtos
   └── *.html                   # Páginas do site público
   ```

2. **Upload via FTP/SFTP**
   - Faça upload da pasta `admin/` para `public_html/admin/`
   - Faça upload dos arquivos do frontend para `public_html/`
   - Mantenha a estrutura de diretórios

### Passo 3: Configuração do Sistema

1. **Configurar Banco de Dados**
   
   Edite o arquivo `admin/config/config.php`:
   ```php
   // Configurações do Banco de Dados
   define('DB_HOST', 'localhost');           // ou seu host MySQL
   define('DB_NAME', 'peluciapet');          // nome do banco
   define('DB_USER', 'peluciapet');          // usuário do banco
   define('DB_PASS', 'sua_senha_segura');    // senha do banco
   ```

2. **Configurar URLs Base**
   ```php
   // URLs do Sistema
   define('BASE_URL', 'https://seudominio.com.br');
   define('ADMIN_URL', BASE_URL . '/admin');
   ```

3. **Configurar WhatsApp**
   ```php
   // Configurações do WhatsApp
   define('WHATSAPP_NUMBER', '5511999999999');  // Seu número
   ```

### Passo 4: Configurar Frontend

1. **Configurar API**
   
   Edite o arquivo `js/config.js`:
   ```javascript
   const API_BASE_URL = 'https://seudominio.com.br/admin/api/api-publica.php';
   const WHATSAPP_CONFIG = {
       numero: '5511999999999',
       mensagem_padrao: 'Olá! Tenho interesse em um produto da PelúciaPet:'
   };
   ```

### Passo 5: Configurar Servidor Web

1. **Apache (.htaccess)**
   
   O sistema inclui arquivos `.htaccess` configurados. Certifique-se de que:
   - `mod_rewrite` está habilitado
   - `AllowOverride All` está configurado
   - URLs amigáveis funcionam corretamente

2. **Nginx (configuração adicional)**
   ```nginx
   location /admin/api/ {
       try_files $uri $uri/ /admin/api/index.php?$query_string;
   }
   
   location /admin/auth/ {
       try_files $uri $uri/ /admin/auth/index.php?$query_string;
   }
   ```

### Passo 6: Verificar Instalação

1. **Executar Verificador**
   - Acesse: `https://seudominio.com.br/admin/verificar-sistema.php`
   - Verifique se todos os testes passaram
   - Corrija eventuais problemas identificados

2. **Testar APIs**
   - API Pública: `https://seudominio.com.br/admin/api/api-publica.php?action=status`
   - API Admin: `https://seudominio.com.br/admin/api/produtos.php?action=test`

3. **Testar Autenticação**
   - Acesse: `https://seudominio.com.br/admin/auth/login.php`
   - Use as credenciais padrão (veja seção de Credenciais)

## 🔐 Credenciais Padrão

### Usuários Administrativos

**Administrador Principal:**
- **Usuário:** `admin`
- **Senha:** `password`
- **Permissões:** Todas

**Gerente:**
- **Usuário:** `peluciapet`
- **Senha:** `peluciapet123`
- **Permissões:** Gerenciamento de produtos

> ⚠️ **IMPORTANTE:** Altere essas senhas imediatamente após a instalação!

## 🛠️ Configurações Avançadas

### Configurar HTTPS

1. **Certificado SSL**
   - Instale certificado SSL válido
   - Configure redirecionamento HTTP → HTTPS
   - Verifique se todas as URLs usam HTTPS

2. **Headers de Segurança**
   ```apache
   Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
   Header always set X-Content-Type-Options nosniff
   Header always set X-Frame-Options DENY
   Header always set X-XSS-Protection "1; mode=block"
   ```

### Configurar Backup Automático

1. **Script de Backup**
   - Use o script `scripts/backup-mysql.sh`
   - Configure cron job para execução automática
   - Teste o processo de backup/restore

2. **Cron Job Exemplo**
   ```bash
   # Backup diário às 2h da manhã
   0 2 * * * /caminho/para/scripts/backup-mysql.sh
   ```

### Otimização de Performance

1. **Cache PHP**
   - Habilite OPcache
   - Configure cache de sessões
   - Use compressão GZIP

2. **Otimização MySQL**
   ```sql
   -- Configurações recomendadas
   SET GLOBAL innodb_buffer_pool_size = 128M;
   SET GLOBAL query_cache_size = 32M;
   SET GLOBAL max_connections = 100;
   ```

## 🔧 Solução de Problemas

### Problemas Comuns

1. **Erro de Conexão com Banco**
   - Verifique credenciais em `config.php`
   - Teste conexão manual com MySQL
   - Verifique se extensão `pdo_mysql` está instalada

2. **Erro 500 nas APIs**
   - Verifique logs de erro do Apache/PHP
   - Confirme permissões de arquivo
   - Teste sintaxe PHP dos arquivos

3. **Problemas de Autenticação**
   - Verifique se sessões PHP funcionam
   - Confirme configurações de cookie
   - Teste em navegador privado

4. **Frontend Não Carrega Produtos**
   - Verifique URL da API em `js/config.js`
   - Teste API pública diretamente
   - Confirme CORS configurado

### Logs e Debugging

1. **Logs do Sistema**
   - Logs PHP: `/var/log/apache2/error.log`
   - Logs MySQL: `/var/log/mysql/error.log`
   - Logs da aplicação: `logs/peluciapet.log`

2. **Modo Debug**
   ```php
   // Em config.php para desenvolvimento
   define('APP_ENV', 'development');
   define('DEBUG_MODE', true);
   ```

## 📞 Suporte

### Contatos
- **Email:** contato@peluciapet.com.br
- **WhatsApp:** +55 11 99999-9999

### Documentação Adicional
- Manual do Usuário: `docs/manual-usuario.md`
- API Reference: `docs/api-reference.md`
- Changelog: `docs/changelog.md`

## 🔄 Atualizações

### Processo de Atualização

1. **Backup Completo**
   - Banco de dados
   - Arquivos do sistema
   - Configurações

2. **Upload Nova Versão**
   - Substitua arquivos do sistema
   - Mantenha configurações personalizadas
   - Execute scripts de migração se necessário

3. **Verificação Pós-Atualização**
   - Execute verificador de sistema
   - Teste funcionalidades principais
   - Monitore logs por 24h

---

## ✅ Checklist de Instalação

- [ ] Banco MySQL criado e configurado
- [ ] Script `install.sql` executado com sucesso
- [ ] Arquivos enviados via FTP/SFTP
- [ ] Arquivo `config.php` configurado
- [ ] Arquivo `config.js` configurado
- [ ] Verificador de sistema executado
- [ ] APIs testadas e funcionando
- [ ] Login administrativo testado
- [ ] Frontend carregando produtos
- [ ] WhatsApp configurado e testando
- [ ] HTTPS configurado e funcionando
- [ ] Backup configurado
- [ ] Senhas padrão alteradas
- [ ] Documentação revisada

**🎉 Instalação Concluída com Sucesso!**

O Sistema PelúciaPet está pronto para uso. Acesse o painel administrativo e comece a cadastrar seus produtos!

