/* Planejamento do layout e estrutura do catálogo HTML/CSS para as caminhas da PelúciaPet */

# Planejamento do Catálogo de Caminhas PelúciaPet

## Estrutura de Arquivos
- index.html (página principal)
- css/
  - styles.css (estilos principais)
  - normalize.css (reset CSS)
- images/
  - logo.png
  - produtos/
    - caminha-redonda-1.jpg
    - caminha-redonda-2.jpg
    - caminha-retangular-1.jpg
    - caminha-retangular-2.jpg
    - caminha-iglu-1.jpg
    - caminha-iglu-2.jpg
    - caminha-sofa-1.jpg
    - caminha-sofa-2.jpg
    - caminha-suspensa-1.jpg
    - caminha-suspensa-2.jpg
  - icons/
    - paw.svg
    - heart.svg
    - cart.svg
    - filter.svg
    - star.svg

## Estrutura HTML

### Header
- Logo PelúciaPet
- Menu de navegação
- Ícone de carrinho
- Barra de pesquisa

### Banner Principal
- Imagem de destaque com caminhas
- Título: "Caminhas para seu Pet"
- Subtítulo: "Conforto e qualidade para seu melhor amigo"
- Botão CTA: "Ver Coleção"

### Seção de Filtros
- Filtro por tipo de caminha (todas, redonda, retangular, iglu, sofá, suspensa)
- Filtro por tamanho (P, M, G)
- Filtro por faixa de preço
- Ordenação (mais vendidos, menor preço, maior preço)

### Grid de Produtos
- Cards de produtos organizados em grid responsivo
- 3 produtos por linha em desktop
- 2 produtos por linha em tablet
- 1 produto por linha em mobile

### Card de Produto
- Imagem do produto
- Badge de promoção (quando aplicável)
- Nome do produto
- Avaliação (estrelas)
- Preço
- Opções de tamanho (P, M, G)
- Opções de cor (círculos coloridos)
- Botão "Comprar"

### Seção de Destaques
- "Mais Vendidas" - Carrossel com as caminhas mais populares
- "Novidades" - Últimos lançamentos

### Seção de Benefícios
- Frete Grátis
- Parcelamento
- Garantia de Qualidade
- Troca Fácil

### Footer
- Links para categorias
- Redes sociais
- Formas de pagamento
- Newsletter
- Informações de contato
- Copyright

## Layout Responsivo
- Desktop: 1200px+
  - Grid de 3 colunas
  - Menu completo visível
  - Todos os filtros visíveis

- Tablet: 768px - 1199px
  - Grid de 2 colunas
  - Menu hamburger
  - Filtros em dropdown

- Mobile: até 767px
  - Grid de 1 coluna
  - Menu hamburger
  - Filtros em modal
  - Simplificação de alguns elementos

## Componentes Interativos (Visual apenas, sem JS)
- Hover nos cards de produto (leve aumento de escala)
- Hover nos botões (mudança de cor)
- Seleção de tamanhos e cores (visual apenas)
- Dropdown de filtros (visual apenas)

## Animações Simples (CSS)
- Fade-in nos cards de produtos
- Transição suave nos hovers
- Pulse no botão CTA principal

## Acessibilidade
- Contraste adequado entre texto e fundo
- Textos alternativos para imagens
- Hierarquia clara de cabeçalhos
- Tamanho de fonte adequado

## SEO Básico
- Meta tags apropriadas
- Títulos descritivos
- Estrutura semântica HTML5
