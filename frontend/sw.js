/**
 * Service Worker - PelúciaPet PWA v2.2
 * Funcionalidades offline e cache inteligente
 */

const CACHE_NAME = 'peluciapet-v2.2.0';
const STATIC_CACHE = 'peluciapet-static-v2.2.0';
const DYNAMIC_CACHE = 'peluciapet-dynamic-v2.2.0';
const API_CACHE = 'peluciapet-api-v2.2.0';

// Arquivos essenciais para cache
const STATIC_FILES = [
  '/',
  '/index.html',
  '/roupinhas.html',
  '/como-comprar.html',
  '/contato.html',
  '/css/styles.css',
  '/js/script.js',
  '/js/pwa.js',
  '/js/notifications.js',
  '/manifest.json',
  '/images/logo-peluciapet.png',
  '/images/banner-caminhas.jpg',
  '/images/banner-roupinhas.jpg',
  '/offline.html'
];

// URLs da API para cache
const API_URLS = [
  '/admin/api/api-publica.php',
  '/admin/api/produtos.php',
  '/admin/api/categorias.php'
];

// Estratégias de cache
const CACHE_STRATEGIES = {
  CACHE_FIRST: 'cache-first',
  NETWORK_FIRST: 'network-first',
  STALE_WHILE_REVALIDATE: 'stale-while-revalidate',
  NETWORK_ONLY: 'network-only',
  CACHE_ONLY: 'cache-only'
};

/**
 * Evento de instalação do Service Worker
 */
self.addEventListener('install', event => {
  console.log('[SW] Instalando Service Worker v2.2.0');
  
  event.waitUntil(
    Promise.all([
      // Cache dos arquivos estáticos
      caches.open(STATIC_CACHE).then(cache => {
        console.log('[SW] Cacheando arquivos estáticos');
        return cache.addAll(STATIC_FILES);
      }),
      
      // Pular waiting para ativar imediatamente
      self.skipWaiting()
    ])
  );
});

/**
 * Evento de ativação do Service Worker
 */
self.addEventListener('activate', event => {
  console.log('[SW] Ativando Service Worker v2.2.0');
  
  event.waitUntil(
    Promise.all([
      // Limpar caches antigos
      cleanOldCaches(),
      
      // Tomar controle de todas as abas
      self.clients.claim()
    ])
  );
});

/**
 * Interceptar requisições de rede
 */
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);
  
  // Ignorar requisições não-HTTP
  if (!request.url.startsWith('http')) {
    return;
  }
  
  // Estratégia baseada no tipo de recurso
  if (isStaticAsset(url)) {
    event.respondWith(handleStaticAsset(request));
  } else if (isAPIRequest(url)) {
    event.respondWith(handleAPIRequest(request));
  } else if (isHTMLPage(url)) {
    event.respondWith(handleHTMLPage(request));
  } else {
    event.respondWith(handleDynamicContent(request));
  }
});

/**
 * Lidar com mensagens do cliente
 */
self.addEventListener('message', event => {
  const { type, data } = event.data;
  
  switch (type) {
    case 'SKIP_WAITING':
      self.skipWaiting();
      break;
      
    case 'GET_VERSION':
      event.ports[0].postMessage({ version: CACHE_NAME });
      break;
      
    case 'CLEAR_CACHE':
      clearAllCaches().then(() => {
        event.ports[0].postMessage({ success: true });
      });
      break;
      
    case 'CACHE_URLS':
      cacheUrls(data.urls).then(() => {
        event.ports[0].postMessage({ success: true });
      });
      break;
  }
});

/**
 * Lidar com notificações push
 */
self.addEventListener('push', event => {
  console.log('[SW] Push recebido:', event);
  
  const options = {
    body: 'Nova promoção disponível na PelúciaPet!',
    icon: '/images/icons/icon-192x192.png',
    badge: '/images/icons/badge-72x72.png',
    vibrate: [100, 50, 100],
    data: {
      dateOfArrival: Date.now(),
      primaryKey: 1
    },
    actions: [
      {
        action: 'explore',
        title: 'Ver Promoção',
        icon: '/images/icons/action-explore.png'
      },
      {
        action: 'close',
        title: 'Fechar',
        icon: '/images/icons/action-close.png'
      }
    ]
  };
  
  if (event.data) {
    const payload = event.data.json();
    options.body = payload.body || options.body;
    options.title = payload.title || 'PelúciaPet';
  }
  
  event.waitUntil(
    self.registration.showNotification('PelúciaPet', options)
  );
});

/**
 * Lidar com cliques em notificações
 */
self.addEventListener('notificationclick', event => {
  console.log('[SW] Notificação clicada:', event);
  
  event.notification.close();
  
  if (event.action === 'explore') {
    event.waitUntil(
      clients.openWindow('/')
    );
  } else if (event.action === 'close') {
    // Apenas fechar a notificação
  } else {
    // Clique na notificação principal
    event.waitUntil(
      clients.matchAll().then(clientList => {
        if (clientList.length > 0) {
          return clientList[0].focus();
        }
        return clients.openWindow('/');
      })
    );
  }
});

/**
 * Funções auxiliares
 */

function isStaticAsset(url) {
  return url.pathname.match(/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff|woff2|ttf)$/);
}

function isAPIRequest(url) {
  return url.pathname.includes('/api/') || API_URLS.some(apiUrl => url.pathname.includes(apiUrl));
}

function isHTMLPage(url) {
  return url.pathname.endsWith('.html') || url.pathname === '/';
}

async function handleStaticAsset(request) {
  // Cache First para assets estáticos
  const cachedResponse = await caches.match(request);
  if (cachedResponse) {
    return cachedResponse;
  }
  
  try {
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      const cache = await caches.open(STATIC_CACHE);
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    console.log('[SW] Erro ao buscar asset estático:', error);
    return new Response('Asset não disponível offline', { status: 503 });
  }
}

async function handleAPIRequest(request) {
  // Network First para APIs
  try {
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      const cache = await caches.open(API_CACHE);
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    console.log('[SW] Erro na API, tentando cache:', error);
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    return new Response(JSON.stringify({
      error: 'Sem conexão',
      offline: true
    }), {
      status: 503,
      headers: { 'Content-Type': 'application/json' }
    });
  }
}

async function handleHTMLPage(request) {
  // Stale While Revalidate para páginas HTML
  const cachedResponse = await caches.match(request);
  const networkResponsePromise = fetch(request).then(response => {
    if (response.ok) {
      const cache = caches.open(DYNAMIC_CACHE);
      cache.then(c => c.put(request, response.clone()));
    }
    return response;
  }).catch(() => null);
  
  if (cachedResponse) {
    networkResponsePromise; // Atualizar em background
    return cachedResponse;
  }
  
  try {
    const networkResponse = await networkResponsePromise;
    if (networkResponse) {
      return networkResponse;
    }
  } catch (error) {
    console.log('[SW] Erro ao carregar página:', error);
  }
  
  // Fallback para página offline
  return caches.match('/offline.html');
}

async function handleDynamicContent(request) {
  // Network First para conteúdo dinâmico
  try {
    const networkResponse = await fetch(request);
    if (networkResponse.ok) {
      const cache = await caches.open(DYNAMIC_CACHE);
      cache.put(request, networkResponse.clone());
    }
    return networkResponse;
  } catch (error) {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    return new Response('Conteúdo não disponível offline', { status: 503 });
  }
}

async function cleanOldCaches() {
  const cacheNames = await caches.keys();
  const oldCaches = cacheNames.filter(name => 
    name.startsWith('peluciapet-') && name !== CACHE_NAME && 
    name !== STATIC_CACHE && name !== DYNAMIC_CACHE && name !== API_CACHE
  );
  
  return Promise.all(
    oldCaches.map(cacheName => {
      console.log('[SW] Removendo cache antigo:', cacheName);
      return caches.delete(cacheName);
    })
  );
}

async function clearAllCaches() {
  const cacheNames = await caches.keys();
  return Promise.all(
    cacheNames.map(cacheName => caches.delete(cacheName))
  );
}

async function cacheUrls(urls) {
  const cache = await caches.open(DYNAMIC_CACHE);
  return cache.addAll(urls);
}

// Logs de debug
console.log('[SW] Service Worker PelúciaPet v2.2.0 carregado');

