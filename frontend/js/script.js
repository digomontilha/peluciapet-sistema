document.addEventListener('DOMContentLoaded', () => {
    // Lógica para a galeria de imagens do produto
    const productCards = document.querySelectorAll('.product-card');

    console.log('Total de cartões de produto encontrados:', productCards.length); // DEBUG

    productCards.forEach((card, cardIndex) => {
        const mainImageWrapper = card.querySelector('.main-image-wrapper');
        const mainImages = mainImageWrapper ? mainImageWrapper.querySelectorAll('img') : [];
        const thumbnails = card.querySelectorAll('.image-thumbnails .thumbnail');

        console.log(`Card ${cardIndex}: Encontradas ${mainImages.length} imagens principais e ${thumbnails.length} miniaturas.`); // DEBUG

        // Garante que haja imagens principais e miniaturas para este card
        if (mainImages.length > 0 && thumbnails.length > 0) {
            // ATIVA A PRIMEIRA IMAGEM E MINIATURA AO CARREGAR
            // Isso garante que uma imagem seja visível mesmo se o HTML não tiver as classes iniciais.
            mainImages[0].classList.add('active-image');
            thumbnails[0].classList.add('active-thumbnail');

            thumbnails.forEach((thumbnail, index) => {
                thumbnail.addEventListener('click', () => {
                    console.log(`Card ${cardIndex}: Miniatura ${index} clicada.`); // DEBUG

                    // Remove a classe 'active-image' de todas as imagens principais
                    mainImages.forEach(img => img.classList.remove('active-image'));
                    // Adiciona a classe 'active-image' à imagem correspondente
                    if (mainImages[index]) {
                        mainImages[index].classList.add('active-image');
                    }

                    // Remove a classe 'active-thumbnail' de todas as miniaturas
                    thumbnails.forEach(thumb => thumb.classList.remove('active-thumbnail'));
                    // Adiciona a classe 'active-thumbnail' à miniatura clicada
                    thumbnail.classList.add('active-thumbnail');
                    console.log(`Card ${cardIndex}: Classe 'active-thumbnail' adicionada à miniatura ${index}.`); // DEBUG
                });
            });
        } else {
            console.warn(`Card ${cardIndex}: Galeria de imagens incompleta ou não encontrada.`); // DEBUG
        }
    });
    // ... (o restante do seu script.js continua aqui)


    // ... (resto do seu script para menu mobile, tamanhos e cores)
    // Lógica para o menu mobile
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const mainNav = document.querySelector('.main-nav');

    if (mobileMenuToggle && mainNav) { // Verifica se ambos os elementos existem
        mobileMenuToggle.addEventListener('click', () => {
            mainNav.classList.toggle('active');
        });
    }

    // Lógica para os botões de tamanho (P, M, G)
    const sizeButtons = document.querySelectorAll('.size-btn');
    sizeButtons.forEach(button => {
        button.addEventListener('click', () => {
            const parentOptions = button.closest('.size-options');
            if (parentOptions) { // Verifica se o pai existe antes de manipular
                parentOptions.querySelectorAll('.size-btn').forEach(btn => btn.classList.remove('active'));
            }
            button.classList.add('active');
        });
    });

    // Lógica para os botões de cor
    const colorButtons = document.querySelectorAll('.color-btn');
    colorButtons.forEach(button => {
        button.addEventListener('click', () => {
            const parentOptions = button.closest('.color-options');
            if (parentOptions) { // Verifica se o pai existe antes de manipular
                parentOptions.querySelectorAll('.color-btn').forEach(btn => btn.classList.remove('active'));
            }
            button.classList.add('active');
        });
    });
});