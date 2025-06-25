/**
 * Integração com API PelúciaPet
 * Carrega produtos dinamicamente do sistema de administração
 */

class PeluciaAPI {
    constructor() {
        this.baseURL = 'https://80-i7rf7jngiyt3fkzaajnzh-708a6019.manusvm.computer/peluciapet-admin/api/api-publica.php';
    }

    async fetchProdutos(filtros = {}) {
        try {
            const params = new URLSearchParams(filtros);
            const response = await fetch(`${this.baseURL}?action=produtos&${params}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('Erro ao buscar produtos:', error);
            return [];
        }
    }

    async fetchProduto(id) {
        try {
            const response = await fetch(`${this.baseURL}?action=produto&id=${id}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('Erro ao buscar produto:', error);
            return null;
        }
    }

    async fetchCategorias() {
        try {
            const response = await fetch(`${this.baseURL}?action=categorias`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error('Erro ao buscar categorias:', error);
            return [];
        }
    }
}

class ProdutoRenderer {
    constructor() {
        this.api = new PeluciaAPI();
    }

    formatarPreco(preco) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(preco);
    }

    criarCardProduto(produto) {
        const precoExibir = produto.preco_promocional || produto.preco_base;
        const temPromocao = produto.preco_promocional && produto.preco_promocional < produto.preco_base;
        
        // Criar tamanhos únicos
        const tamanhos = [...new Set(produto.variacoes.map(v => v.tamanho.nome))];
        const cores = produto.cores_disponiveis || [];

        return `
            <div class="product-card" data-produto-id="${produto.id}">
                <div class="product-image">
                    <div class="main-image-wrapper">
                        ${produto.imagens && produto.imagens.length > 0 
                            ? produto.imagens.map((img, index) => 
                                `<img src="${img.caminho_arquivo}" alt="${img.alt_text || produto.nome}" ${index === 0 ? 'class="active-image"' : ''}>`
                              ).join('')
                            : `<div class="placeholder-image">
                                 <i class="fas fa-image"></i>
                                 <p>Sem imagem</p>
                               </div>`
                        }
                    </div>
                    ${produto.destaque ? '<div class="product-badge">Destaque</div>' : ''}
                    ${temPromocao ? '<div class="product-badge promotion">Promoção</div>' : ''}
                </div>
                <div class="product-info">
                    <h3 class="product-title">${produto.nome}</h3>
                    <p class="product-description">${produto.descricao || ''}</p>
                    <div class="product-price">
                        ${temPromocao 
                            ? `<span class="old-price">${this.formatarPreco(produto.preco_base)}</span>
                               <span class="price">${this.formatarPreco(produto.preco_promocional)}</span>`
                            : `<span class="price">A partir de ${this.formatarPreco(produto.preco_minimo)}</span>`
                        }
                    </div>
                    ${tamanhos.length > 0 ? `
                        <div class="product-sizes">
                            <span>Tamanhos:</span>
                            <div class="size-options">
                                <div class="tooltip-info-container">
                                    <i class="fas fa-question-circle tooltip-info-icon"></i>
                                    <div class="custom-info-tooltip">
                                        <p>Largura x Comprimento x Altura</p>
                                    </div>
                                </div>
                                ${tamanhos.map(tamanho => {
                                    const variacao = produto.variacoes.find(v => v.tamanho.nome === tamanho);
                                    const dimensoes = variacao ? variacao.tamanho.dimensoes : '';
                                    return `<button class="size-btn" data-dimension="${dimensoes}">${tamanho}</button>`;
                                }).join('')}
                            </div>
                        </div>
                    ` : ''}
                    ${cores.length > 0 ? `
                        <div class="product-colors">
                            <span>Cores:</span>
                            <div class="color-options">
                                ${cores.map(cor => 
                                    `<button class="color-btn" style="background-color: ${cor.codigo};" aria-label="${cor.nome}"></button>`
                                ).join('')}
                            </div>
                        </div>
                    ` : ''}
                    <div class="product-actions">
                        <a href="https://wa.me/5511999999999?text=Olá! Tenho interesse na ${produto.nome}" 
                           class="btn btn-whatsapp" target="_blank">
                            <i class="fab fa-whatsapp"></i> Comprar no WhatsApp
                        </a>
                    </div>
                    ${produto.material ? `<p class="product-material"><strong>Material:</strong> ${produto.material}</p>` : ''}
                </div>
            </div>
        `;
    }

    async carregarProdutos(categoria = null, container = '.products-grid') {
        const containerElement = document.querySelector(container);
        if (!containerElement) {
            console.error('Container não encontrado:', container);
            return;
        }

        // Mostrar loading
        containerElement.innerHTML = `
            <div class="loading-products">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Carregando produtos...</p>
            </div>
        `;

        try {
            const filtros = categoria ? { categoria_id: categoria } : {};
            const produtos = await this.api.fetchProdutos(filtros);

            if (produtos.length === 0) {
                containerElement.innerHTML = `
                    <div class="no-products">
                        <i class="fas fa-box-open"></i>
                        <p>Nenhum produto encontrado.</p>
                        <p>Use o sistema de administração para adicionar produtos.</p>
                    </div>
                `;
                return;
            }

            // Renderizar produtos
            containerElement.innerHTML = produtos.map(produto => this.criarCardProduto(produto)).join('');

            // Reativar funcionalidades do script original
            this.ativarInteracoesProdutos();

        } catch (error) {
            console.error('Erro ao carregar produtos:', error);
            containerElement.innerHTML = `
                <div class="error-products">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Erro ao carregar produtos.</p>
                    <p>Verifique a conexão com o sistema de administração.</p>
                </div>
            `;
        }
    }

    ativarInteracoesProdutos() {
        // Reativar galeria de imagens
        const productCards = document.querySelectorAll('.product-card');
        productCards.forEach((card) => {
            const mainImageWrapper = card.querySelector('.main-image-wrapper');
            const mainImages = mainImageWrapper ? mainImageWrapper.querySelectorAll('img') : [];
            const thumbnails = card.querySelectorAll('.image-thumbnails .thumbnail');

            if (mainImages.length > 1) {
                // Criar thumbnails se não existirem
                if (thumbnails.length === 0) {
                    const thumbnailContainer = document.createElement('div');
                    thumbnailContainer.className = 'image-thumbnails';
                    
                    mainImages.forEach((img, index) => {
                        const thumbnail = document.createElement('img');
                        thumbnail.src = img.src;
                        thumbnail.alt = img.alt;
                        thumbnail.className = `thumbnail ${index === 0 ? 'active-thumbnail' : ''}`;
                        thumbnail.addEventListener('click', () => {
                            mainImages.forEach(mainImg => mainImg.classList.remove('active-image'));
                            img.classList.add('active-image');
                            
                            thumbnailContainer.querySelectorAll('.thumbnail').forEach(thumb => 
                                thumb.classList.remove('active-thumbnail')
                            );
                            thumbnail.classList.add('active-thumbnail');
                        });
                        thumbnailContainer.appendChild(thumbnail);
                    });
                    
                    card.querySelector('.product-image').appendChild(thumbnailContainer);
                }
            }
        });

        // Reativar tooltips de tamanho
        const sizeButtons = document.querySelectorAll('.size-btn');
        sizeButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active de todos os botões de tamanho do mesmo produto
                const productCard = this.closest('.product-card');
                productCard.querySelectorAll('.size-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Reativar botões de cor
        const colorButtons = document.querySelectorAll('.color-btn');
        colorButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active de todos os botões de cor do mesmo produto
                const productCard = this.closest('.product-card');
                productCard.querySelectorAll('.color-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
            });
        });
    }
}

// Inicializar quando o DOM estiver carregado
document.addEventListener('DOMContentLoaded', function() {
    const renderer = new ProdutoRenderer();
    
    // Detectar página atual e carregar produtos apropriados
    const currentPage = window.location.pathname;
    
    if (currentPage.includes('index.html') || currentPage === '/' || currentPage.includes('caminhas')) {
        // Página de caminhas - carregar categoria 1
        renderer.carregarProdutos(1);
    } else if (currentPage.includes('roupinhas')) {
        // Página de roupinhas - carregar categoria 2
        renderer.carregarProdutos(2);
    }
    
    // Disponibilizar globalmente para debug
    window.PeluciaAPI = renderer;
});

