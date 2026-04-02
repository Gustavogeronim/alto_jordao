<?php 
require_once 'config.php'; 

// 1. LOGICA DE BUSCA: Tenta buscar produtos com desconto real ou da categoria 'ofertas'
try {
    $query = $pdo->query("SELECT * FROM produtos WHERE (preco_antigo > preco OR categoria = 'ofertas') ORDER BY id DESC");
} catch (PDOException $e) {
    // Caso a coluna preco_antigo não exista no banco, busca apenas pela categoria
    $query = $pdo->query("SELECT * FROM produtos WHERE categoria = 'ofertas' ORDER BY id DESC");
}
$produtos = $query->fetchAll(PDO::FETCH_ASSOC);

// 2. FUNÇÃO DE IMAGEM (Garante que a foto apareça mesmo se o campo estiver vazio)
function getCaminhoImagemOferta($img) {
    if (empty($img)) return 'img/produtos/cb74cbfc6e4fa08cecc6bd257fc0f000.webp'; 
    return "img/produtos/" . $img;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alto Jordão | Ofertas</title>
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap" rel="stylesheet">
</head>
<body>

    <?php include 'header.php'; ?>

    <main>
        <section class="page-header" style="text-align: center; padding: 60px 20px; background: #f9f9f9;">
            <span style="color: #ff4d4d; font-weight: 800; letter-spacing: 3px; text-transform: uppercase; font-size: 12px;">Preços Especiais</span>
            <h1 style="font-size: 2.5rem; font-weight: 900; margin: 10px 0; letter-spacing: -1px;">OFERTAS IMPERDÍVEIS</h1>
            <p style="color: #666; max-width: 500px; margin: 0 auto;">Produtos selecionados com a curadoria Alto Jordão e condições exclusivas por tempo limitado.</p>
        </section>

        <section class="product-section" style="padding: 40px 20px;">
            <div class="product-grid">
                
                <?php if (count($produtos) > 0): ?>
                    <?php foreach ($produtos as $p): 
                        $pJson = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8');
                    ?>
                        <div class="product-card">
                            <div class="product-thumb">
                                <span class="badge-tag" style="position: absolute; top: 15px; left: 15px; background: #ff4d4d; color: #fff; padding: 5px 12px; font-size: 10px; font-weight: 900; z-index: 10; border-radius: 50px;">SALE</span>

                                <button class="btn-fav" data-id="<?= $p['id'] ?>" onclick='toggleFavorito(<?= $pJson ?>)'>
                                    🤍
                                </button>
                                
                                <a href="produto.php?id=<?= $p['id'] ?>" class="product-link">
                                    <div class="product-image-container">
                                        <img src="<?= getCaminhoImagemOferta($p['imagem']) ?>" 
                                             alt="<?= htmlspecialchars($p['nome']) ?>" 
                                             style="width: 100%; height: 100%; object-fit: contain; display: block;">
                                    </div>
                                </a>
                                
                                <button class="btn-buy-overlay" onclick="window.location.href='produto.php?id=<?= $p['id'] ?>'">
                                    Aproveitar Oferta
                                </button>
                            </div>

                            <div class="product-details">
                                <p class="category" style="font-size: 10px; color: #999; text-transform: uppercase; margin-bottom: 5px;">
                                    <?= htmlspecialchars($p['categoria']) ?>
                                </p>
                                
                                <a href="produto.php?id=<?= $p['id'] ?>" style="text-decoration: none; color: inherit;">
                                    <h4 style="font-weight: 700; margin-bottom: 8px;"><?= htmlspecialchars($p['nome']) ?></h4>
                                </a>
                                
                                <div class="price-container" style="display: flex; align-items: baseline; gap: 8px;">
                                    <?php if (!empty($p['preco_antigo']) && $p['preco_antigo'] > $p['preco']): ?>
                                        <span style="text-decoration: line-through; color: #bbb; font-size: 0.9rem;">
                                            R$ <?= number_format($p['preco_antigo'], 2, ',', '.') ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <span class="price" style="color: #ff4d4d; font-weight: 800; font-size: 1.2rem;">
                                        R$ <?= number_format($p['preco'], 2, ',', '.') ?>
                                    </span>
                                </div>

                                <?php if (isset($_SESSION['usuario_nivel']) && $_SESSION['usuario_nivel'] === 'admin'): ?>
                                    <div style="margin-top: 15px; display: flex; gap: 8px; border-top: 1px solid #eee; padding-top: 10px;">
                                        <a href="editar_produto.php?id=<?= $p['id'] ?>" style="flex:1; background:#f1f1f1; color:#000; text-align:center; padding:8px; border-radius:5px; font-size:10px; text-decoration:none; font-weight:bold;">EDITAR</a>
                                        <a href="excluir_produto.php?id=<?= $p['id'] ?>" onclick="return confirm('Excluir esta oferta?')" style="flex:1; background:#ffebeb; color:#ff4d4d; text-align:center; padding:8px; border-radius:5px; font-size:10px; text-decoration:none; font-weight:bold;">EXCLUIR</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 100px 0;">
                        <p style="color: #999; font-style: italic; margin-bottom: 20px;">Não encontramos ofertas ativas no momento.</p>
                        <a href="index.php" class="btn-black-capsule" style="display: inline-block; padding: 12px 30px; background: #000; color: #fff; text-decoration: none; border-radius: 50px; font-size: 13px;">Voltar para Início</a>
                    </div>
                <?php endif; ?>

            </div>
        </section>
    </main>

    <footer style="background: #000; color: #fff; padding: 40px 20px; text-align: center; margin-top: 50px;">
        <h2 style="letter-spacing: 3px; font-size: 1.2rem;">ALTO JORDÃO</h2>
        <p style="font-size: 10px; opacity: 0.5; margin-top: 10px;">&copy; 2026 Alto Jordão Originals.</p>
    </footer>

    <script src="script.js?v=<?= time(); ?>"></script>
</body>
</html>