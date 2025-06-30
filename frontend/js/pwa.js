/**
 * PWA Manager - PelúciaPet v2.2
 * Gerencia funcionalidades do Progressive Web App
 */

class PWAManager {
    constructor() {
        this.deferredPrompt = null;
        this.isInstalled = false;
        this.notificationPermission = 'default';
        
        this.init();
    }
    
    /**
     * Inicializar PWA
     */
    async init() {
        console.log('[PWA] Inicializando PWA Manager v2.2');
        
        // Registrar Service Worker
        await this.registerServiceWorker();
        
        // Configurar eventos
        this.setupEventListeners();
        
        // Verificar se já está instalado
        this.checkInstallStatus();
        
        // Configurar notificações
        this.setupNotifications();
        
        // Mostrar banner de instalação se apropriado
        this.showInstallPrompt();
        
        // Configurar atualizações
        this.setupUpdateManager();
    }
    
    /**
     * Registrar Service Worker
     */
    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/sw.js');
                console.log('[PWA] Service Worker registrado:', registration);
                
                // Verificar atualizações
                registration.addEventListener('updatefound', () => {
                    console.log('[PWA] Nova versão disponível');
                    this.showUpdateAvailable();
                });
                
                return registration;
            } catch (error) {
                console.error('[PWA] Erro ao registrar Service Worker:', error);
            }
        }
    }
    
    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        // Evento de instalação
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('[PWA] Prompt de instalação disponível');
            e.preventDefault();
            this.deferredPrompt = e;
            this.showInstallButton();
        });
        
        // Evento pós-instalação
        window.addEventListener('appinstalled', () => {
            console.log('[PWA] App instalado com sucesso');
            this.isInstalled = true;
            this.hideInstallButton();
            this.showInstalledMessage();
        });
        
        // Mudanças de conectividade
        window.addEventListener('online', () => {
            this.showConnectionStatus('online');
        });
        
        window.addEventListener('offline', () => {
            this.showConnectionStatus('offline');
        });
        
        // Visibilidade da página
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                this.handleAppFocus();
            }
        });
    }
    
    /**
     * Verificar status de instalação
     */
    checkInstallStatus() {
        // Verificar se está rodando como PWA
        if (window.matchMedia('(display-mode: standalone)').matches || 
            window.navigator.standalone === true) {
            this.isInstalled = true;
            console.log('[PWA] App rodando como PWA');
        }
        
        // Verificar se está no iOS Safari
        if (this.isIOSSafari()) {
            this.showIOSInstallInstructions();
        }
    }
    
    /**
     * Configurar notificações
     */
    async setupNotifications() {
        if ('Notification' in window) {
            this.notificationPermission = Notification.permission;
            
            if (this.notificationPermission === 'default') {
                this.showNotificationPermissionPrompt();
            }
        }
    }
    
    /**
     * Solicitar permissão para notificações
     */
    async requestNotificationPermission() {
        if ('Notification' in window) {
            const permission = await Notification.requestPermission();
            this.notificationPermission = permission;
            
            if (permission === 'granted') {
                console.log('[PWA] Permissão de notificação concedida');
                this.subscribeToNotifications();
            }
            
            return permission;
        }
    }
    
    /**
     * Inscrever para notificações push
     */
    async subscribeToNotifications() {
        try {
            const registration = await navigator.serviceWorker.ready;
            
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: this.urlBase64ToUint8Array(
                    'BEl62iUYgUivxIkv69yViEuiBIa40HI80NM9LnqFBHVPrLSHBzpENOxJrUbNdNtoHRBFRdW-cOmhd4borYx5VjE'
                )
            });
            
            console.log('[PWA] Inscrito para notificações:', subscription);
            
            // Enviar subscription para o servidor
            await this.sendSubscriptionToServer(subscription);
            
        } catch (error) {
            console.error('[PWA] Erro ao inscrever para notificações:', error);
        }
    }
    
    /**
     * Mostrar prompt de instalação
     */
    showInstallPrompt() {
        if (!this.isInstalled && this.shouldShowInstallPrompt()) {
            this.createInstallBanner();
        }
    }
    
    /**
     * Instalar PWA
     */
    async installPWA() {
        if (this.deferredPrompt) {
            this.deferredPrompt.prompt();
            
            const { outcome } = await this.deferredPrompt.userChoice;
            console.log('[PWA] Resultado da instalação:', outcome);
            
            if (outcome === 'accepted') {
                this.trackEvent('pwa_install', 'accepted');
            } else {
                this.trackEvent('pwa_install', 'dismissed');
            }
            
            this.deferredPrompt = null;
        }
    }
    
    /**
     * Criar banner de instalação
     */
    createInstallBanner() {
        const banner = document.createElement('div');
        banner.id = 'pwa-install-banner';
        banner.className = 'pwa-install-banner';
        banner.innerHTML = `
            <div class="pwa-banner-content">
                <div class="pwa-banner-icon">
                    <img src="/images/icons/icon-72x72.png" alt="PelúciaPet">
                </div>
                <div class="pwa-banner-text">
                    <h3>Instalar PelúciaPet</h3>
                    <p>Acesso rápido e notificações de promoções</p>
                </div>
                <div class="pwa-banner-actions">
                    <button id="pwa-install-btn" class="btn-install">Instalar</button>
                    <button id="pwa-dismiss-btn" class="btn-dismiss">×</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(banner);
        
        // Event listeners
        document.getElementById('pwa-install-btn').addEventListener('click', () => {
            this.installPWA();
        });
        
        document.getElementById('pwa-dismiss-btn').addEventListener('click', () => {
            this.dismissInstallBanner();
        });
        
        // Mostrar banner com animação
        setTimeout(() => {
            banner.classList.add('show');
        }, 100);
    }
    
    /**
     * Mostrar instruções para iOS
     */
    showIOSInstallInstructions() {
        if (this.isInstalled) return;
        
        const modal = document.createElement('div');
        modal.className = 'ios-install-modal';
        modal.innerHTML = `
            <div class="ios-install-content">
                <h3>Instalar PelúciaPet no iOS</h3>
                <div class="ios-steps">
                    <div class="ios-step">
                        <span class="step-number">1</span>
                        <p>Toque no botão de compartilhar <span class="ios-icon">⎋</span></p>
                    </div>
                    <div class="ios-step">
                        <span class="step-number">2</span>
                        <p>Selecione "Adicionar à Tela de Início"</p>
                    </div>
                    <div class="ios-step">
                        <span class="step-number">3</span>
                        <p>Toque em "Adicionar"</p>
                    </div>
                </div>
                <button class="ios-close-btn">Entendi</button>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        modal.querySelector('.ios-close-btn').addEventListener('click', () => {
            modal.remove();
            localStorage.setItem('ios-install-shown', 'true');
        });
        
        // Auto-fechar após 10 segundos
        setTimeout(() => {
            if (modal.parentNode) {
                modal.remove();
            }
        }, 10000);
    }
    
    /**
     * Mostrar status de conexão
     */
    showConnectionStatus(status) {
        const statusBar = document.getElementById('connection-status') || this.createConnectionStatusBar();
        
        if (status === 'offline') {
            statusBar.textContent = 'Modo offline - Algumas funcionalidades podem estar limitadas';
            statusBar.className = 'connection-status offline';
            statusBar.style.display = 'block';
        } else {
            statusBar.textContent = 'Conexão restaurada';
            statusBar.className = 'connection-status online';
            statusBar.style.display = 'block';
            
            // Esconder após 3 segundos
            setTimeout(() => {
                statusBar.style.display = 'none';
            }, 3000);
        }
    }
    
    /**
     * Criar barra de status de conexão
     */
    createConnectionStatusBar() {
        const statusBar = document.createElement('div');
        statusBar.id = 'connection-status';
        statusBar.className = 'connection-status';
        document.body.appendChild(statusBar);
        return statusBar;
    }
    
    /**
     * Configurar gerenciador de atualizações
     */
    setupUpdateManager() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                console.log('[PWA] Nova versão ativa');
                this.showUpdateCompleted();
            });
        }
    }
    
    /**
     * Mostrar atualização disponível
     */
    showUpdateAvailable() {
        const updateBanner = document.createElement('div');
        updateBanner.className = 'update-banner';
        updateBanner.innerHTML = `
            <div class="update-content">
                <span>Nova versão disponível!</span>
                <button id="update-btn">Atualizar</button>
                <button id="update-dismiss">Depois</button>
            </div>
        `;
        
        document.body.appendChild(updateBanner);
        
        document.getElementById('update-btn').addEventListener('click', () => {
            this.applyUpdate();
        });
        
        document.getElementById('update-dismiss').addEventListener('click', () => {
            updateBanner.remove();
        });
    }
    
    /**
     * Aplicar atualização
     */
    async applyUpdate() {
        if ('serviceWorker' in navigator) {
            const registration = await navigator.serviceWorker.ready;
            if (registration.waiting) {
                registration.waiting.postMessage({ type: 'SKIP_WAITING' });
            }
        }
        window.location.reload();
    }
    
    /**
     * Utilitários
     */
    
    isIOSSafari() {
        return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    }
    
    shouldShowInstallPrompt() {
        const lastShown = localStorage.getItem('install-prompt-shown');
        const daysSinceLastShown = lastShown ? 
            (Date.now() - parseInt(lastShown)) / (1000 * 60 * 60 * 24) : 999;
        
        return daysSinceLastShown > 7; // Mostrar a cada 7 dias
    }
    
    dismissInstallBanner() {
        const banner = document.getElementById('pwa-install-banner');
        if (banner) {
            banner.remove();
            localStorage.setItem('install-prompt-shown', Date.now().toString());
        }
    }
    
    urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
            .replace(/-/g, '+')
            .replace(/_/g, '/');
        
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }
    
    async sendSubscriptionToServer(subscription) {
        try {
            await fetch('/admin/api/notifications.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'subscribe',
                    subscription: subscription
                })
            });
        } catch (error) {
            console.error('[PWA] Erro ao enviar subscription:', error);
        }
    }
    
    trackEvent(event, value) {
        // Integração com Google Analytics ou similar
        if (typeof gtag !== 'undefined') {
            gtag('event', event, {
                'custom_parameter': value
            });
        }
        console.log('[PWA] Evento:', event, value);
    }
    
    handleAppFocus() {
        // Verificar atualizações quando o app volta ao foco
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.ready.then(registration => {
                registration.update();
            });
        }
    }
    
    showInstalledMessage() {
        const message = document.createElement('div');
        message.className = 'install-success-message';
        message.textContent = 'PelúciaPet instalado com sucesso!';
        document.body.appendChild(message);
        
        setTimeout(() => {
            message.remove();
        }, 3000);
    }
    
    showUpdateCompleted() {
        const message = document.createElement('div');
        message.className = 'update-success-message';
        message.textContent = 'PelúciaPet atualizado para a versão mais recente!';
        document.body.appendChild(message);
        
        setTimeout(() => {
            message.remove();
        }, 3000);
    }
    
    showInstallButton() {
        // Mostrar botão de instalação na interface
        const installBtn = document.getElementById('install-app-btn');
        if (installBtn) {
            installBtn.style.display = 'block';
            installBtn.addEventListener('click', () => this.installPWA());
        }
    }
    
    hideInstallButton() {
        const installBtn = document.getElementById('install-app-btn');
        if (installBtn) {
            installBtn.style.display = 'none';
        }
    }
    
    showNotificationPermissionPrompt() {
        // Mostrar prompt para permissão de notificação após interação do usuário
        const notificationPrompt = document.createElement('div');
        notificationPrompt.className = 'notification-permission-prompt';
        notificationPrompt.innerHTML = `
            <div class="notification-prompt-content">
                <h4>Receber notificações?</h4>
                <p>Fique por dentro das promoções e novidades da PelúciaPet</p>
                <div class="notification-prompt-actions">
                    <button id="allow-notifications">Permitir</button>
                    <button id="deny-notifications">Não agora</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(notificationPrompt);
        
        document.getElementById('allow-notifications').addEventListener('click', () => {
            this.requestNotificationPermission();
            notificationPrompt.remove();
        });
        
        document.getElementById('deny-notifications').addEventListener('click', () => {
            notificationPrompt.remove();
            localStorage.setItem('notification-prompt-dismissed', Date.now().toString());
        });
    }
}

// Inicializar PWA quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.pwaManager = new PWAManager();
    });
} else {
    window.pwaManager = new PWAManager();
}

// Exportar para uso global
window.PWAManager = PWAManager;

