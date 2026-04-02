<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Segurança: Somente admins acessam
if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] !== 'admin') {
    header("Location: login.php?erro=acesso_negado");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) { header("Location: admin.php"); exit; }

// Busca o produto atual - Usando singular tamanho/cor
$query = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
$query->execute([$id]);
$p = $query->fetch(PDO::FETCH_ASSOC);

if (!$p) { die("Produto não encontrado."); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $preco_novo = $_POST['preco_novo'];
    
    // Lógica de preço antigo
    $preco_antigo = !empty($_POST['preco_antigo_hidden']) ? $_POST['preco_antigo_hidden'] : $p['preco'];
    
    $categoria = $_POST['categoria'] ?? $p['categoria'];
    $genero = $_POST['genero'];
    
    // CORREÇÃO: Pegando dos campos do formulário para salvar no singular
    $tamanho = $_POST['tamanhos']; 
    $cor = $_POST['cores'];

    $imagem = $p['imagem']; 
    if (!empty($_FILES['nova_imagem']['name'])) {
        $extensao = pathinfo($_FILES['nova_imagem']['name'], PATHINFO_EXTENSION);
        $novo_nome = md5(time()) . "." . $extensao;
        move_uploaded_file($_FILES['nova_imagem']['tmp_name'], "img/produtos/" . $novo_nome);
        $imagem = $novo_nome;
    }

    // SQL CORRIGIDO: Salvando em 'tamanho' e 'cor'
    $sql = "UPDATE produtos SET nome=?, preco=?, preco_antigo=?, categoria=?, genero=?, tamanho=?, cor=?, imagem=? WHERE id=?";
    $update = $pdo->prepare($sql);
    
    if ($update->execute([$nome, $preco_novo, $preco_antigo, $categoria, $genero, $tamanho, $cor, $imagem, $id])) {
        echo "<script>alert('Produto atualizado com sucesso!'); window.location.href='admin.php';</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Produto #<?= $id ?> | Alto Jordão</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
</head>
<body class="admin-body">

    <?php include 'header.php'; ?>

    <div class="user-full-wrapper">
        <div class="user-main-wide" style="max-width: 1000px; margin: 40px auto;">
            
            <div class="user-content-section-wide" style="background: #fff; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                <header class="admin-page-header" style="margin-bottom: 30px;">
                    <div>
                        <h1 style="font-weight: 900; text-transform: uppercase;">Editar Produto</h1>
                        <p style="color: #888;">Ref: #<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></p>
                    </div>
                </header>

                <form method="POST" enctype="multipart/form-data" class="user-form">
                    
                    <div class="profile-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                        
                        <div class="input-grupo" style="grid-column: span 2;">
                            <label style="font-weight: 700; font-size: 12px;">NOME DO PRODUTO</label>
                            <input type="text" name="nome" class="auth-input" value="<?= htmlspecialchars($p['nome']) ?>" required 
                                   style="width: 100%; padding: 15px; border: 1px solid #eee; border-radius: 10px; margin-top: 8px;">
                        </div>

                        <div class="input-grupo">
                            <label style="font-weight: 700; font-size: 12px;">PREÇO ATUAL (DE:)</label>
                            <input type="text" class="auth-input" value="R$ <?= number_format($p['preco'], 2, ',', '.') ?>" readonly 
                                   style="width: 100%; padding: 15px; border: 1px solid #eee; border-radius: 10px; margin-top: 8px; background: #f9f9f9; color: #999;">
                            <input type="hidden" name="preco_antigo_hidden" value="<?= $p['preco'] ?>">
                        </div>

                        <div class="input-grupo">
                            <label style="font-weight: 700; font-size: 12px;">NOVO PREÇO (POR:)</label>
                            <input type="number" step="0.01" name="preco_novo" class="auth-input" value="<?= $p['preco'] ?>" required
                                   style="width: 100%; padding: 15px; border: 1px solid #eee; border-radius: 10px; margin-top: 8px;">
                        </div>

                        <div class="input-grupo">
                            <label style="font-weight: 700; font-size: 12px;">TAMANHOS (Ex: P, M, G)</label>
                            <input type="text" name="tamanhos" class="auth-input" value="<?= htmlspecialchars($p['tamanho'] ?? '') ?>"
                                   style="width: 100%; padding: 15px; border: 1px solid #eee; border-radius: 10px; margin-top: 8px;">
                        </div>

                        <div class="input-grupo">
                            <label style="font-weight: 700; font-size: 12px;">CORES (Ex: Preto, Azul)</label>
                            <input type="text" name="cores" class="auth-input" value="<?= htmlspecialchars($p['cor'] ?? '') ?>"
                                   style="width: 100%; padding: 15px; border: 1px solid #eee; border-radius: 10px; margin-top: 8px;">
                        </div>

                        <div class="input-grupo">
                            <label style="font-weight: 700; font-size: 12px;">GÊNERO</label>
                            <select name="genero" class="auth-input" style="width: 100%; padding: 15px; border: 1px solid #eee; border-radius: 10px; margin-top: 8px; background: #fff;">
                                <option value="unissex" <?= $p['genero'] == 'unissex' ? 'selected' : '' ?>>Unissex</option>
                                <option value="masculino" <?= $p['genero'] == 'masculino' ? 'selected' : '' ?>>Masculino</option>
                                <option value="feminino" <?= $p['genero'] == 'feminino' ? 'selected' : '' ?>>Feminino</option>
                            </select>
                        </div>

                        <div class="input-grupo">
                            <label style="font-weight: 700; font-size: 12px;">CATEGORIA</label>
                            <input type="text" name="categoria" class="auth-input" value="<?= htmlspecialchars($p['categoria'] ?? '') ?>"
                                   style="width: 100%; padding: 15px; border: 1px solid #eee; border-radius: 10px; margin-top: 8px;">
                        </div>

                        <div class="input-grupo" style="grid-column: span 2;">
                            <label style="font-weight: 700; font-size: 12px;">ALTERAR IMAGEM (Deixe vazio para manter atual)</label>
                            <input type="file" name="nova_imagem" class="auth-input" style="width: 100%; padding: 15px; border: 1px solid #eee; border-radius: 10px; margin-top: 8px;">
                            <p style="font-size: 10px; color: #bbb; margin-top: 5px;">Imagem atual: <?= $p['imagem'] ?></p>
                        </div>

                        <div class="form-actions" style="grid-column: span 2; display: flex; gap: 15px; margin-top: 20px;">
                            <a href="admin.php" style="flex: 1; text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 50px; color: #666; font-weight: 700; text-decoration: none;">CANCELAR</a>
                            <button type="submit" style="flex: 2; padding: 20px; background: #000; color: #fff; border: none; border-radius: 50px; font-weight: 900; cursor: pointer; text-transform: uppercase;">SALVAR ALTERAÇÕES</button>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>