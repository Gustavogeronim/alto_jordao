/* ==========================================================================
   ALTO JORDÃO - SCRIPT GLOBAL (CARRINHO, FAVORITOS E INTERFACE)
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {
    console.log("Alto Jordão: Sistema carregado v2.1");

    verificarERepararFavoritos();
    setupHeaderActions();
    setupMenuIndicator(); 
    
    atualizarInterfaceFavoritos();
    renderizarCarrinho(); 
    
    if (document.getElementById('favsGrid')) {
        renderizarPaginaFavoritos();
    }
});

/* --- UTILS --- */

// Garante que o caminho da imagem esteja correto e evita erro 'undefined'
function resolverCaminhoImagem(imgRaw) {
    if (!imgRaw || imgRaw === 'undefined') return 'img/produtos/cb74cbfc6e4fa08cecc6bd257fc0f000.webp';
    if (imgRaw.startsWith('http') || imgRaw.startsWith('data:') || imgRaw.includes('/')) {
        return imgRaw;
    }
    return `img/produtos/${imgRaw}`;
}

function verificarERepararFavoritos() {
    try {
        const favs = localStorage.getItem('fashion_favs');
        if (favs) JSON.parse(favs); 
    } catch (e) {
        localStorage.setItem('fashion_favs', JSON.stringify([]));
    }
}

/* --- 0. CONFIGURAÇÃO DO HEADER --- */
function setupHeaderActions() {
    const overlay = document.getElementById('overlay');
    if (overlay) {
        overlay.addEventListener('click', fecharTodosModais);
    }
}

/* --- 1. LÓGICA DE COMPRA (PRODUTO) --- */

// Função chamada pelo botão "ADICIONAR AO CARRINHO" na produto.php
function adicionarAoCarrinhoDireto(produto) {
    // Busca os valores dos inputs selecionados (que seu script de botões deve preencher)
    const inputTam = document.getElementById('selected-tamanho');
    const inputCor = document.getElementById('selected-cor');
    
    const tamSelecionado = inputTam ? inputTam.value : "";
    const corSelecionada = inputCor ? inputCor.value : "";

    // Validação de tamanho obrigatório
    if (!tamSelecionado) {
        alert("Por favor, selecione um tamanho antes de adicionar.");
        return;
    }

    const itemParaCarrinho = {
        id: produto.id,
        nome: produto.nome,
        preco: parseFloat(produto.preco),
        img: resolverCaminhoImagem(produto.imagem || produto.img),
        tamanho_escolhido: tamSelecionado,
        cor_escolhida: corSelecionada || 'Padrão',
        opcoes: `Tam: ${tamSelecionado}${corSelecionada ? ' | Cor: ' + corSelecionada : ''}`
    };

    adicionarAoCarrinho(itemParaCarrinho);
}

/* --- 2. LÓGICA DO CARRINHO --- */
function abrirCarrinho() {
    const sidebar = document.getElementById('cartSidebar');
    const overlay = document.getElementById('overlay');
    if (sidebar && overlay) {
        sidebar.classList.add('active');
        overlay.classList.add('active');
        renderizarCarrinho();
    }
}

function fecharTodosModais() {
    const sidebar = document.getElementById('cartSidebar');
    const overlay = document.getElementById('overlay');
    if(sidebar) sidebar.classList.remove('active');
    if(overlay) overlay.classList.remove('active');
}

function adicionarAoCarrinho(p) {
    let carrinho = JSON.parse(sessionStorage.getItem('fashion_cart')) || [];
    
    // O cartId agora leva em conta tamanho e cor para não misturar itens iguais de tamanhos diferentes
    const cartId = `${p.id}-${p.tamanho_escolhido}-${p.cor_escolhida}`; 

    const index = carrinho.findIndex(item => item.cartId === cartId);

    if (index > -1) {
        carrinho[index].qtd += 1;
    } else {
        carrinho.push({ ...p, cartId: cartId, qtd: 1 });
    }

    sessionStorage.setItem('fashion_cart', JSON.stringify(carrinho));
    renderizarCarrinho();
    abrirCarrinho(); 
}

function renderizarCarrinho() {
    const container = document.getElementById('cartListSide');
    const totalElemento = document.getElementById('totalValor');
    const badge = document.getElementById('cartCountBadge');
    
    const carrinho = JSON.parse(sessionStorage.getItem('fashion_cart')) || [];
    let totalGeral = 0;
    let totalItens = 0;
    
    if (badge) {
        totalItens = carrinho.reduce((acc, item) => acc + (parseInt(item.qtd) || 0), 0);
        badge.innerText = totalItens;
        badge.style.display = totalItens > 0 ? "flex" : "none";
    }

    if (!container) return;
    
    if (carrinho.length === 0) {
        container.innerHTML = '<div style="text-align:center; padding:40px; color:#bbb; font-size:13px;">Sua sacola está vazia.</div>';
        if(totalElemento) totalElemento.innerText = "R$ 0,00";
        return;
    }

    container.innerHTML = carrinho.map(item => {
        const precoNum = parseFloat(item.preco) || 0;
        const qtdNum = parseInt(item.qtd) || 1;
        totalGeral += (precoNum * qtdNum);

        return `
            <div class="cart-item" style="display: flex; gap: 15px; padding: 15px 0; border-bottom: 1px solid #f5f5f5; align-items: center;">
                <img src="${resolverCaminhoImagem(item.img)}" style="width:60px; height:75px; object-fit:contain; background:#f9f9f9; border-radius:4px;" onerror="this.src='img/produtos/cb74cbfc6e4fa08cecc6bd257fc0f000.webp'">
                <div style="flex: 1;">
                    <h5 style="margin: 0; font-size: 11px; font-weight:800; text-transform:uppercase; letter-spacing:0.5px;">${item.nome}</h5>
                    <p style="margin:2px 0; font-size:10px; color:#999; font-weight:600;">${item.opcoes}</p>
                    <span style="font-weight: 700; font-size: 13px; color:#000;">${qtdNum}x R$ ${precoNum.toLocaleString('pt-br', {minimumFractionDigits: 2})}</span>
                </div>
                <button onclick="removerDoCarrinho('${item.cartId}')" style="background:none; border:none; color:#ccc; cursor:pointer; font-size:20px; transition:0.2s;" onmouseover="this.style.color='#000'" onmouseout="this.style.color='#ccc'">&times;</button>
            </div>
        `;
    }).join('');

    if(totalElemento) totalElemento.innerText = `R$ ${totalGeral.toLocaleString('pt-br', {minimumFractionDigits: 2})}`;
}

function removerDoCarrinho(cartId) {
    let carrinho = JSON.parse(sessionStorage.getItem('fashion_cart')) || [];
    carrinho = carrinho.filter(i => i.cartId !== cartId);
    sessionStorage.setItem('fashion_cart', JSON.stringify(carrinho));
    renderizarCarrinho();
}

/* --- 3. LÓGICA DE FAVORITOS --- */
function toggleFavorito(produto) {
    if (!produto || !produto.id) return;

    let favoritos = JSON.parse(localStorage.getItem('fashion_favs')) || [];
    const index = favoritos.findIndex(item => String(item.id) === String(produto.id));

    if (index > -1) {
        favoritos.splice(index, 1);
    } else {
        favoritos.push({
            id: produto.id,
            nome: produto.nome,
            preco: produto.preco,
            img: resolverCaminhoImagem(produto.imagem || produto.img)
        });
    }

    localStorage.setItem('fashion_favs', JSON.stringify(favoritos));
    atualizarInterfaceFavoritos();
    
    if (document.getElementById('favsGrid')) {
        renderizarPaginaFavoritos();
    }
}

function atualizarInterfaceFavoritos() {
    const favoritos = JSON.parse(localStorage.getItem('fashion_favs')) || [];
    
    document.querySelectorAll('.btn-fav').forEach(btn => {
        const idProd = btn.getAttribute('data-id'); 
        const isFav = favoritos.some(item => String(item.id) === String(idProd));
        btn.innerHTML = isFav ? '❤️' : '🤍';
        btn.classList.toggle('active', isFav);
    });
}

function renderizarPaginaFavoritos() {
    const container = document.getElementById('favsGrid');
    if (!container) return;
    
    const favoritos = JSON.parse(localStorage.getItem('fashion_favs')) || [];
    
    if (favoritos.length === 0) {
        container.innerHTML = `
            <div style="grid-column: 1/-1; text-align:center; padding:100px 20px;">
                <h2 style="font-weight:900; color:#eee; font-size:3rem; margin-bottom:10px; text-transform:uppercase;">Vazio</h2>
                <p style="color:#999; margin-bottom:30px;">Sua lista de desejos está aguardando por você.</p>
                <a href="index.php" class="btn-black-capsule" style="display:inline-block; background:#000; color:#fff; padding:18px 50px; border-radius:50px; text-decoration:none; font-weight:800; font-size:12px; letter-spacing:1px;">EXPLORAR LANÇAMENTOS</a>
            </div>`;
        return;
    }

    container.innerHTML = favoritos.map(prod => {
        const prodData = JSON.stringify(prod).replace(/"/g, '&quot;');
        
        return `
            <div class="product-card">
                <div class="product-thumb">
                    <button class="btn-fav active" data-id="${prod.id}" onclick="toggleFavorito(${prodData})">❤️</button>
                    <img src="${resolverCaminhoImagem(prod.img)}" alt="${prod.nome}" onerror="this.src='img/produtos/cb74cbfc6e4fa08cecc6bd257fc0f000.webp'">
                </div>
                <div class="product-details">
                    <h4 onclick="location.href='produto.php?id=${prod.id}'">${prod.nome}</h4>
                    <p class="price">R$ ${parseFloat(prod.preco).toLocaleString('pt-br', {minimumFractionDigits: 2})}</p>
                    <button class="btn-buy-only" style="width:100%; border-radius:30px;" onclick="location.href='produto.php?id=${prod.id}'">DETALHES</button>
                </div>
            </div>
        `;
    }).join('');
}

/* --- 4. ESTÉTICA --- */
function setupMenuIndicator() {
    const indicator = document.querySelector('.nav-indicator');
    const items = document.querySelectorAll('.main-nav a');
    if (!indicator || items.length === 0) return;

    const move = (el) => {
        indicator.style.width = `${el.offsetWidth}px`;
        indicator.style.left = `${el.offsetLeft}px`;
        indicator.style.opacity = "1";
    };

    items.forEach(item => {
        item.addEventListener('mouseenter', () => move(item));
        if (item.classList.contains('active')) move(item);
    });
}