<?php 
require_once 'config.php'; 

// 1. Pegar ID e validar
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$query = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
$query->execute([$id]);

// Usamos FETCH_ASSOC para garantir que os nomes das colunas venham como chaves
$p = $query->fetch(PDO::FETCH_ASSOC);

if (!$p) { 
    header("Location: index.php"); 
    exit; 
}

/** * TRATAMENTO DE DADOS BLINDADO
 * Tentamos pegar 'tamanho' ou 'TAMANHO' (alguns bancos variam)
 */
$strTamanho = $p['tamanho'] ?? $p['TAMANHO'] ?? '';
$strCor = $p['cor'] ?? $p['COR'] ?? '';

// Explode e limpa espaços de cada item. Filtra para remover vazios.
$tamanhosDisponiveis = array_filter(array_map('trim', explode(',', $strTamanho)));
$coresDisponiveis = array_filter(array_map('trim', explode(',', $strCor)));

$produtoJson = htmlspecialchars(json_encode($p), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($p['nome'] ?? 'Produto') ?> | Alto Jordão</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        .produto-container { display: flex; flex-wrap: wrap; gap: 60px; max-width: 1200px; margin: 60px auto; padding: 0 20px; align-items: flex-start; }
        .produto-media { flex: 1.2; min-width: 350px; }
        .produto-info { flex: 0.8; min-width: 350px; }
        .glass-card { background: #fff; border-radius: 40px; padding: 50px; position: relative; box-shadow: 0 20px 60px rgba(0,0,0,0.03); text-align: center; border: 1px solid #f0f0f0; }
        .glass-card img { width: 100%; max-height: 550px; object-fit: contain; }
        .brand-tag { color: #bbb; font-size: 11px; font-weight: 900; letter-spacing: 3px; text-transform: uppercase; }
        .product-title { font-size: 2.8rem; font-weight: 900; margin: 15px 0; text-transform: uppercase; letter-spacing: -1.5px; line-height: 1; }
        .product-price { font-size: 1.8rem; font-weight: 400; color: #000; margin-bottom: 40px; font-family: 'Inter', sans-serif; }
        .selection-label { font-size: 10px; font-weight: 900; letter-spacing: 1.5px; margin-bottom: 15px; display: block; color: #888; }
        .chips-container { display: flex; gap: 12px; margin-bottom: 35px; flex-wrap: wrap; min-height: 20px; }
        
        /* Botões de Tamanho */
        .chip-size { min-width: 55px; height: 55px; display: flex; align-items: center; justify-content: center; border: 2px solid #eee; background: #fff; border-radius: 15px; font-weight: 800; font-size: 14px; cursor: pointer; transition: 0.3s; }
        .chip-size.active { border-color: #000; background: #000; color: #fff; }
        
        /* Círculos de Cor */
        .chip-color { width: 40px; height: 40px; border-radius: 50%; border: 3px solid #fff; box-shadow: 0 0 0 2px #eee; cursor: pointer; transition: 0.3s; position: relative; }
        .chip-color.active { box-shadow: 0 0 0 2px #000; transform: scale(1.15); }
        .chip-color span { position: absolute; bottom: -25px; left: 50%; transform: translateX(-50%); font-size: 9px; font-weight: 800; text-transform: uppercase; white-space: nowrap; opacity: 0; transition: 0.3s; }
        .chip-color.active span { opacity: 1; }

        .btn-add-cart { width: 100%; padding: 25px; background: #000; color: #fff; border: none; border-radius: 50px; font-weight: 900; font-size: 15px; letter-spacing: 2px; cursor: pointer; transition: 0.4s; margin-top: 30px; text-transform: uppercase; }
        .btn-add-cart:hover { background: #333; transform: translateY(-5px); box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
    </style>
</head>
<body> 

<?php include 'header.php'; ?>

<main class="produto-container">
    <div class="produto-media">
        <div class="glass-card">
            <button class="btn-fav" data-id="<?= $p['id'] ?>" style="position: absolute; right: 40px; top: 40px; font-size: 28px; background: none; border: none; cursor: pointer;" 
                    onclick='toggleFavorito(<?= $produtoJson ?>)'>🤍</button>
            <img src="<?= !empty($p['imagem']) ? 'img/produtos/'.$p['imagem'] : 'img/produtos/default.png' ?>" alt="<?= htmlspecialchars($p['nome']) ?>">
        </div>
    </div>

    <div class="produto-info">
        <span class="brand-tag">Alto Jordão • Originals</span>
        <h1 class="product-title"><?= htmlspecialchars($p['nome']) ?></h1>
        <p class="product-price">R$ <?= number_format($p['preco'], 2, ',', '.') ?></p>

        <?php if(!empty($tamanhosDisponiveis)): ?>
        <div class="selection-section">
            <label class="selection-label">TAMANHO DISPONÍVEL</label>
            <div class="chips-container" id="container-tamanhos">
                <?php foreach ($tamanhosDisponiveis as $t): ?>
                    <button type="button" class="chip-size" onclick="selecionarUnico(this, 'tamanho', '<?= $t ?>')">
                        <?= $t ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <input type="hidden" id="selected-tamanho">
        </div>
        <?php endif; ?>

        <?php if(!empty($coresDisponiveis)): ?>
        <div class="selection-section" style="margin-top: 20px;">
            <label class="selection-label">COR</label>
            <div class="chips-container" id="container-cores">
                <?php foreach ($coresDisponiveis as $cor): 
                    $corNome = trim($cor);
                    $corChave = strtolower($corNome);
                    $coresMap = [
                        'preto'=>'black', 'branco'=>'white', 'azul'=>'blue', 'vermelho'=>'red', 
                        'cinza'=>'#808080', 'marrom'=>'#8B4513', 'verde'=>'green', 'amarelo'=>'yellow'
                    ];
                    $corCss = $coresMap[$corChave] ?? $corChave;
                ?>
                    <div class="chip-color" 
                         style="background-color: <?= $corCss ?>;" 
                         onclick="selecionarUnico(this, 'cor', '<?= $corNome ?>')"
                         title="<?= ucfirst($corNome) ?>">
                         <span><?= ucfirst($corNome) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <input type="hidden" id="selected-cor">
        </div>
        <?php endif; ?>

        <button class="btn-add-cart" onclick='validarEComprar(<?= $produtoJson ?>)'>
            Adicionar à Sacola
        </button>
    </div>
</main>

<script src="script.js?v=<?= time() ?>"></script>
<script>
    function selecionarUnico(el, tipo, valor) {
        const parent = el.parentElement;
        const selector = el.classList.contains('chip-size') ? '.chip-size' : '.chip-color';
        parent.querySelectorAll(selector).forEach(s => s.classList.remove('active'));
        el.classList.add('active');
        const input = document.getElementById('selected-' + tipo);
        if(input) input.value = valor;
    }

    function validarEComprar(produto) {
        const inputTam = document.getElementById('selected-tamanho');
        const inputCor = document.getElementById('selected-cor');
        
        // Verifica se as seções existem no HTML
        const temOpcaoTam = !!document.getElementById('container-tamanhos');
        const temOpcaoCor = !!document.getElementById('container-cores');

        const vTam = inputTam ? inputTam.value : '';
        const vCor = inputCor ? inputCor.value : '';

        if ((temOpcaoTam && !vTam) || (temOpcaoCor && !vCor)) {
            alert("Por favor, selecione o tamanho e a cor.");
            return;
        }

        const itemFinal = {
            ...produto,
            tamanho_escolhido: vTam || 'N/A',
            cor_escolhida: vCor || 'N/A'
        };

        if (typeof adicionarAoCarrinho === "function") {
            adicionarAoCarrinho(itemFinal);
        } else {
            console.log("Carrinho não encontrado. Dados:", itemFinal);
            alert("Produto pronto para o carrinho!");
        }
    }
</script>
</body>
</html>