<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PelúciaPet Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&family=Open+Sans:wght@300;400;500;600;700&family=Satisfy:wght@400&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background: linear-gradient(135deg, #5C2C0D 0%, #A0522D 50%, #D4A04C 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="paws" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="5" cy="5" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="15" cy="15" r="1" fill="rgba(255,255,255,0.1)"/></pattern></defs><rect width="100" height="100" fill="url(%23paws)"/></svg>') repeat;
            opacity: 0.3;
            z-index: 1;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            position: relative;
            z-index: 2;
            border: 2px solid rgba(212, 160, 76, 0.3);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            font-family: 'Satisfy', cursive;
            font-size: 2.5rem;
            color: #5C2C0D;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        .logo i {
            color: #D4A04C;
            margin-right: 10px;
        }

        .subtitle {
            color: #A0522D;
            font-size: 1.1rem;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .admin-badge {
            background: linear-gradient(45deg, #D4A04C, #EBC6A8);
            color: #5C2C0D;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
            margin-top: 10px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #5C2C0D;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #A0522D;
            font-size: 1.1rem;
        }

        .form-control {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #EBC6A8;
            border-radius: 12px;
            font-size: 1rem;
            background: #FDF6ED;
            color: #5C2C0D;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #D4A04C;
            box-shadow: 0 0 0 3px rgba(212, 160, 76, 0.2);
            background: #fff;
        }

        .form-control::placeholder {
            color: #A0522D;
            opacity: 0.7;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #A0522D;
            cursor: pointer;
            font-size: 1.1rem;
            padding: 5px;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: #5C2C0D;
        }

        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }

        .remember-me input[type="checkbox"] {
            margin-right: 10px;
            transform: scale(1.2);
            accent-color: #D4A04C;
        }

        .remember-me label {
            color: #5C2C0D;
            font-size: 0.95rem;
            cursor: pointer;
            margin-bottom: 0;
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(45deg, #5C2C0D, #A0522D);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover {
            background: linear-gradient(45deg, #4A2309, #8B4513);
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(92, 44, 13, 0.3);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-login .spinner {
            display: none;
            margin-right: 10px;
        }

        .btn-login.loading .spinner {
            display: inline-block;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
            display: none;
        }

        .alert-error {
            background: #ffe6e6;
            color: #d63031;
            border: 1px solid #fab1a0;
        }

        .alert-success {
            background: #e6f7e6;
            color: #00b894;
            border: 1px solid #81ecec;
        }

        .alert-warning {
            background: #fff3cd;
            color: #e17055;
            border: 1px solid #fdcb6e;
        }

        .footer-links {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #EBC6A8;
        }

        .footer-links a {
            color: #A0522D;
            text-decoration: none;
            font-size: 0.9rem;
            margin: 0 15px;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #5C2C0D;
        }

        .security-info {
            background: #f8f9fa;
            border: 1px solid #EBC6A8;
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
            font-size: 0.85rem;
            color: #6c757d;
        }

        .security-info i {
            color: #D4A04C;
            margin-right: 8px;
        }

        /* Responsividade */
        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px 25px;
            }

            .logo {
                font-size: 2rem;
            }

            .subtitle {
                font-size: 1rem;
            }
        }

        /* Animações */
        .login-container {
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-group {
            animation: fadeInUp 0.6s ease-out;
            animation-fill-mode: both;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .btn-login { animation-delay: 0.4s; }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-section">
            <div class="logo">
                <i class="fas fa-paw"></i>PelúciaPet
            </div>
            <div class="subtitle">Sistema de Administração</div>
            <div class="admin-badge">
                <i class="fas fa-shield-alt"></i> Área Restrita
            </div>
        </div>

        <div id="alert" class="alert"></div>

        <form id="loginForm">
            <div class="form-group">
                <label for="username">Usuário</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" class="form-control" 
                           placeholder="Digite seu usuário" required autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Senha</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" class="form-control" 
                           placeholder="Digite sua senha" required autocomplete="current-password">
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="passwordIcon"></i>
                    </button>
                </div>
            </div>

            <div class="remember-me">
                <input type="checkbox" id="rememberMe" name="rememberMe">
                <label for="rememberMe">Lembrar de mim por 30 dias</label>
            </div>

            <button type="submit" class="btn-login" id="loginBtn">
                <i class="fas fa-spinner spinner"></i>
                <i class="fas fa-sign-in-alt"></i> Entrar no Sistema
            </button>
        </form>

        <div class="security-info">
            <i class="fas fa-info-circle"></i>
            <strong>Informações de Segurança:</strong><br>
            • Máximo de 5 tentativas de login por IP<br>
            • Bloqueio automático por 15 minutos após tentativas excessivas<br>
            • Sessão expira automaticamente em 2 horas de inatividade
        </div>

        <div class="footer-links">
            <a href="../../../" target="_blank">
                <i class="fas fa-home"></i> Voltar ao Site
            </a>
            <a href="mailto:contato@peluciapet.com.br">
                <i class="fas fa-envelope"></i> Suporte
            </a>
        </div>
    </div>

    <script>
        // Configurações
        const API_BASE = '../api/auth.php';
        
        // Elementos DOM
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const alert = document.getElementById('alert');
        const usernameInput = document.getElementById('username');
        const passwordInput = document.getElementById('password');
        
        // Event Listeners
        loginForm.addEventListener('submit', handleLogin);
        
        // Focar no campo usuário ao carregar
        window.addEventListener('load', () => {
            usernameInput.focus();
        });
        
        // Enter para submeter
        passwordInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                handleLogin(e);
            }
        });
        
        // Função de login
        async function handleLogin(e) {
            e.preventDefault();
            
            const username = usernameInput.value.trim();
            const password = passwordInput.value;
            const rememberMe = document.getElementById('rememberMe').checked;
            
            if (!username || !password) {
                showAlert('Por favor, preencha todos os campos.', 'error');
                return;
            }
            
            setLoading(true);
            hideAlert();
            
            try {
                const response = await fetch(API_BASE, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'login',
                        username: username,
                        password: password,
                        remember_me: rememberMe
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert(data.message, 'success');
                    
                    // Redirecionar após 1 segundo
                    setTimeout(() => {
                        window.location.href = '../public/';
                    }, 1000);
                } else {
                    showAlert(data.message, 'error');
                    
                    // Limpar senha em caso de erro
                    passwordInput.value = '';
                    passwordInput.focus();
                    
                    // Mostrar tentativas restantes se disponível
                    if (data.attempts_remaining !== undefined) {
                        showAlert(
                            `${data.message} Tentativas restantes: ${data.attempts_remaining}`,
                            'warning'
                        );
                    }
                    
                    // Mostrar tempo de bloqueio se aplicável
                    if (data.blocked_until) {
                        const blockedUntil = new Date(data.blocked_until * 1000);
                        showAlert(
                            `Conta bloqueada até ${blockedUntil.toLocaleTimeString()}`,
                            'error'
                        );
                    }
                }
            } catch (error) {
                console.error('Erro no login:', error);
                showAlert('Erro de conexão. Tente novamente.', 'error');
            } finally {
                setLoading(false);
            }
        }
        
        // Alternar visibilidade da senha
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                passwordIcon.className = 'fas fa-eye-slash';
            } else {
                passwordField.type = 'password';
                passwordIcon.className = 'fas fa-eye';
            }
        }
        
        // Mostrar/ocultar alerta
        function showAlert(message, type) {
            alert.textContent = message;
            alert.className = `alert alert-${type}`;
            alert.style.display = 'block';
            
            // Auto-hide após 5 segundos para alertas de sucesso
            if (type === 'success') {
                setTimeout(hideAlert, 5000);
            }
        }
        
        function hideAlert() {
            alert.style.display = 'none';
        }
        
        // Estado de loading
        function setLoading(loading) {
            loginBtn.disabled = loading;
            loginBtn.classList.toggle('loading', loading);
            
            if (loading) {
                loginBtn.innerHTML = '<i class="fas fa-spinner spinner"></i> Entrando...';
            } else {
                loginBtn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Entrar no Sistema';
            }
        }
        
        // Verificar se já está logado
        window.addEventListener('load', async () => {
            try {
                const response = await fetch(API_BASE + '?action=check');
                const data = await response.json();
                
                if (data.authenticated) {
                    window.location.href = '../public/';
                }
            } catch (error) {
                // Ignorar erro - usuário não está logado
            }
        });
        
        // Credenciais de demonstração (remover em produção)
        if (window.location.hostname === 'localhost' || window.location.hostname.includes('manus')) {
            const demoInfo = document.createElement('div');
            demoInfo.innerHTML = `
                <div style="background: #e3f2fd; border: 1px solid #2196f3; border-radius: 10px; padding: 15px; margin-top: 20px; font-size: 0.85rem;">
                    <strong style="color: #1976d2;"><i class="fas fa-info-circle"></i> Credenciais de Demonstração:</strong><br>
                    <strong>Usuário:</strong> admin | <strong>Senha:</strong> password<br>
                    <strong>Usuário:</strong> peluciapet | <strong>Senha:</strong> peluciapet123
                </div>
            `;
            document.querySelector('.login-container').appendChild(demoInfo);
        }
    </script>
</body>
</html>

