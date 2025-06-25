# 🐾 Sistema PelúciaPet

> **Solução completa para gerenciamento de produtos pet com sistema administrativo avançado e frontend integrado**

[![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat&logo=php&logoColor=white)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat&logo=mysql&logoColor=white)](https://mysql.com)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat&logo=javascript&logoColor=black)](https://javascript.info)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## 🎯 Sobre o Projeto

O **Sistema PelúciaPet** é uma plataforma completa desenvolvida especificamente para lojas de produtos pet, oferecendo:

- **🛡️ Sistema Administrativo Seguro** com autenticação e controle de permissões
- **📦 Gerenciamento Completo de Produtos** com variações de tamanho e cor
- **🌐 Frontend Responsivo** integrado para exibição pública
- **📱 Integração WhatsApp** para facilitar vendas
- **📊 Dashboard com Estatísticas** em tempo real
- **🔄 API REST Completa** para integrações futuras

## ✨ Principais Funcionalidades

### 🔐 Sistema de Autenticação
- Login seguro com proteção contra ataques
- Controle de sessões e permissões
- Bloqueio automático por tentativas excessivas
- Diferentes níveis de acesso (Admin, Gerente, Editor, Visualizador)

### 📦 Gerenciamento de Produtos
- Cadastro completo com informações detalhadas
- Sistema de variações (tamanhos e cores)
- Controle de estoque automatizado
- Produtos em destaque
- SEO otimizado para cada produto

### 🎨 Interface Moderna
- Design responsivo para todos os dispositivos
- Paleta de cores personalizada da marca
- Animações suaves e micro-interações
- Dashboard intuitivo com estatísticas visuais

### 📱 Integração WhatsApp
- Links diretos para WhatsApp com produto
- Mensagens personalizadas automáticas
- Facilita o processo de vendas

### 🔧 Tecnologias Utilizadas

#### Backend
- **PHP 8.0+** - Linguagem principal
- **MySQL 5.7+** - Banco de dados
- **PDO** - Camada de abstração de dados
- **Arquitetura MVC** - Organização do código

#### Frontend
- **HTML5/CSS3** - Estrutura e estilização
- **JavaScript ES6+** - Interatividade
- **Font Awesome** - Ícones
- **Google Fonts** - Tipografia

#### Segurança
- **Autenticação baseada em sessões**
- **Proteção CSRF**
- **Sanitização de dados**
- **Headers de segurança**
- **Rate limiting**

## 🚀 Instalação Rápida

### Pré-requisitos
- Servidor web (Apache/Nginx)
- PHP 7.4+ com extensões: PDO, MySQL, JSON, mbstring
- MySQL 5.7+ ou MariaDB 10.3+
- Certificado SSL (recomendado)

### Passos de Instalação

1. **Clone ou baixe o projeto**
   ```bash
   # Extrair arquivos para o diretório web
   unzip peluciapet-final.zip -d /var/www/html/
   ```

2. **Configure o banco de dados**
   ```sql
   CREATE DATABASE peluciapet CHARACTER SET utf8mb4;
   -- Execute o script database/install.sql
   ```

3. **Configure as credenciais**
   ```php
   // Edite admin/config/config.php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'peluciapet');
   define('DB_USER', 'seu_usuario');
   define('DB_PASS', 'sua_senha');
   define('BASE_URL', 'https://seudominio.com.br');
   ```

4. **Configure o frontend**
   ```javascript
   // Edite js/config.js
   const API_BASE_URL = 'https://seudominio.com.br/admin/api/api-publica.php';
   ```

5. **Acesse o sistema**
   - **Site público:** `https://seudominio.com.br`
   - **Admin:** `https://seudominio.com.br/admin/auth/login.php`

### Credenciais Padrão
- **Usuário:** `admin` | **Senha:** `password`
- **Usuário:** `peluciapet` | **Senha:** `peluciapet123`

> ⚠️ **Altere as senhas imediatamente após a instalação!**

## 📁 Estrutura do Projeto

```
peluciapet-final/
├── admin/                      # Sistema administrativo
│   ├── api/                   # APIs REST
│   │   ├── auth.php          # Autenticação
│   │   ├── produtos.php      # API administrativa
│   │   └── api-publica.php   # API pública
│   ├── auth/                 # Sistema de login
│   │   └── login.php         # Página de login
│   ├── classes/              # Classes PHP
│   │   ├── Auth.php          # Gerenciamento de autenticação
│   │   ├── Database.php      # Conexão com banco
│   │   └── Produto.php       # Gerenciamento de produtos
│   ├── config/               # Configurações
│   │   └── config.php        # Configurações principais
│   └── public/               # Interface administrativa
│       ├── index.html        # Dashboard
│       └── cadastro-produto.html # Cadastro de produtos
├── frontend/                  # Site público
│   ├── css/                  # Estilos
│   ├── js/                   # Scripts
│   ├── images/               # Imagens
│   └── *.html               # Páginas do site
├── database/                 # Banco de dados
│   └── install.sql          # Script de instalação
├── docs/                     # Documentação
│   ├── INSTALACAO.md        # Guia de instalação
│   └── README.md            # Este arquivo
└── scripts/                  # Scripts utilitários
    └── backup-mysql.sh      # Script de backup
```

## 🎨 Screenshots

### Dashboard Administrativo
![Dashboard](docs/images/dashboard.png)

### Cadastro de Produtos
![Cadastro](docs/images/cadastro.png)

### Site Público
![Frontend](docs/images/frontend.png)

## 📊 Funcionalidades Detalhadas

### Sistema Administrativo
- ✅ Dashboard com estatísticas em tempo real
- ✅ Cadastro completo de produtos
- ✅ Gerenciamento de variações (tamanho + cor)
- ✅ Controle de estoque
- ✅ Sistema de autenticação robusto
- ✅ Diferentes níveis de permissão
- ✅ Logs de atividades
- ✅ Interface responsiva

### API REST
- ✅ Endpoints para gerenciamento completo
- ✅ Autenticação via sessão
- ✅ Rate limiting
- ✅ Documentação automática
- ✅ Versionamento
- ✅ CORS configurado
- ✅ Tratamento de erros

### Frontend Público
- ✅ Carregamento dinâmico de produtos
- ✅ Filtros por categoria
- ✅ Produtos em destaque
- ✅ Integração WhatsApp
- ✅ SEO otimizado
- ✅ Design responsivo
- ✅ Performance otimizada

## 🔧 Configurações Avançadas

### Backup Automático
```bash
# Configurar cron job para backup diário
0 2 * * * /caminho/para/scripts/backup-mysql.sh
```

### Otimização de Performance
```php
// Habilitar cache OPcache
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
```

### Monitoramento
```bash
# Verificar logs em tempo real
tail -f /var/log/apache2/error.log
tail -f logs/peluciapet.log
```

## 🛠️ Desenvolvimento

### Ambiente de Desenvolvimento
```bash
# Configurar ambiente local
git clone https://github.com/usuario/peluciapet.git
cd peluciapet
cp admin/config/config.example.php admin/config/config.php
# Editar configurações locais
```

### Contribuindo
1. Fork o projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## 📈 Roadmap

### Versão 2.1 (Próxima)
- [ ] Upload de imagens de produtos
- [ ] Sistema de categorias avançado
- [ ] Relatórios de vendas
- [ ] Integração com correios
- [ ] Sistema de cupons

### Versão 2.2 (Futuro)
- [ ] App mobile
- [ ] Integração com marketplaces
- [ ] Sistema de avaliações
- [ ] Chat online
- [ ] Multi-loja

## 🐛 Problemas Conhecidos

- Upload de imagens em desenvolvimento
- Relatórios avançados pendentes
- Integração com pagamentos futura

## 📞 Suporte

### Documentação
- 📖 [Guia de Instalação](docs/INSTALACAO.md)
- 🔧 [Manual do Usuário](docs/manual-usuario.md)
- 🚀 [API Reference](docs/api-reference.md)

### Contato
- **Email:** contato@peluciapet.com.br
- **WhatsApp:** +55 11 99999-9999
- **Site:** https://peluciapet.com.br

## 📄 Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

## 🙏 Agradecimentos

- **Font Awesome** - Ícones incríveis
- **Google Fonts** - Tipografia moderna
- **Comunidade PHP** - Suporte e recursos
- **Comunidade MySQL** - Banco de dados robusto

---

<div align="center">

**🐾 Desenvolvido com ❤️ para o mundo pet 🐾**

[⬆ Voltar ao topo](#-sistema-pelúciapet)

</div>

