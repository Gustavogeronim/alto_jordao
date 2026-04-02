<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Segurança: Somente admins
if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] !== 'admin') {
    header("Location: login.php?erro=acesso_negado");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $preco = $_POST['preco'];
    $categoria = $_POST['categoria'];
    $genero = $_POST['genero'];
    
    // CORREÇÃO: Pegamos os nomes do formulário e preparamos para o banco
    // Usamos as variáveis no singular para bater com o resto do sistema
    $tamanho = $_POST['tamanhos']; 
    $cor = $_POST['cores'];
    $estoque = $_POST['estoque'];

    // Lógica da Imagem
    $imagem = "default.jpg"; 
    if (!empty($_FILES['imagem']['name'])) {
        $extensao = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        $nome_imagem = md5(time() . rand()) . "." . $extensao;
        
        // Garante que a pasta existe
        if (!is_dir("img/produtos/")) {
            mkdir("img/produtos/", 0777, true);
        }
        
        move_uploaded_file($_FILES['imagem']['tmp_name'], "img/produtos/" . $nome_imagem);
        $imagem = $nome_imagem;
    }

    // SQL CORRIGIDO: Colunas 'tamanho' e 'cor' no singular
    try {
        $sql = "INSERT INTO produtos (nome, preco, categoria, genero, tamanho, cor, estoque, imagem) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$nome, $preco, $categoria, $genero, $tamanho, $cor, $estoque, $imagem])) {
            echo "<script>alert('Produto cadastrado com sucesso!'); window.location.href='admin.php';</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Erro ao salvar no banco: " . addslashes($e->getMessage()) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Novo Produto | Alto Jordão</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap" rel="stylesheet">
</head>
<body class="admin-body">

    <?php include 'header.php'; ?>

    <div class="user-full-wrapper">
        <div class="user-main-wide" style="max-width: 1000px; margin: 40px auto;">
            
            <div class="user-content-section-wide" style="background: #fff; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                <header class="admin-page-header" style="margin-bottom: 30px;">
                    <h1 style="font-weight: 900; text-transform: uppercase; letter-spacing: -1px;">Novo Produto</h1>
                    <p style="color: #888;">Adicione novos itens ao catálogo da Alto Jordão</p>
                </header>

                <form method="POST" enctype="multipart/form-data" class="user-form">
                    <div class="profile-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px;">
                        
                        <div class="input-grupo" style="grid-column: span 2;">
                            <label style="font-weight: 700; font-size: 12px; color: #555;">NOME DO PRODUTO</label>
                            <input type="text" name="nome" class="auth-input" placeholder="Ex: Jaqueta Puffer Black Edition" required 
                                   style="width: 100%; padding: 15px; border: 1px solid #eee; border-radius: 10px; margin-top: 8px;">
                        </div>

                        <div class="input-grupo">
                            <label style="font-weight: 700; font-size: 12px; color: #555;">PREÇO DE VENDA (R$)</label>
                            <input type="number" step="0.01" name="preco" class="auth-input" placeholder="0.00" required
                                   style="width: 100%; padding: 15px; border: 1px solid #eee; border-radius: 10px; margin-top: 8px;">
                        </div>

                        <div class="input-grupo">
                            <label style="font-weight: 700; font-size: 12px; color: #555;">QUANTIDADE EM ESTOQUE</label>
                            <input type="number" name="estoque" class="auth-input" placeholder="Ex: 10" required
                                   style="width: 100%; padding: 15px; border: 1px solid #eee; border-radius: 10px; margin-top: 8px;">
                        </div>

                        <div class="input-grupo">
                            <label style="font-weight: 700; font-size: 12px; color: #555;">CATEGORIA</label>
                            <input type="text" name="categoria" class="auth-input" placeholder="Ex: Calçados, Inverno" required
                                   style="width: 100%; padding: 15px; border: 1px solid #eee; border-radius: 10px; margin-top: 8px;">
                        </div>

                        <div class="input-grupo">
                            <label style="font-weight: 700; font-size: 12px; color: #555;">GÊNERO</label>
                            <select name="genero" class="auth-input" style="width: 100%; padding: 15px; border: 1px solid #eee; border-radius: 10px; margin-top: 8px; background: #fff;">
                                <option value="unissex">Unissex</option>
                                <option value="masculino">Masculino</option>
                                <option value="feminino">Feminino</option>
                            </select>
                        </div>

                        <div class="input-grupo">
                            <label style="font-weight: 700; font-size: 12px; color: #555;">TAMANHOS (Separe por vírgula)</label>
                            <input type="text" name="tamanhos" class="auth-input" placeholder="Ex: P, M, G, GG"
                                   style="width: 100%; padding: 15px; border: 1px solid #eee; border-radius: 10px; margin-top: 8px;">
                        </div>

                        <div class="input-grupo">
                            <label style="font-weight: 700; font-size: 12px; color: #555;">CORES (Separe por vírgula)</label>
                            <input type="text" name="cores" class="auth-input" placeholder="Ex: Preto, Branco, Azul"
                                   style="width: 100%; padding: 15px; border: 1px solid #eee; border-radius: 10px; margin-top: 8px;">
                        </div>

                        <div class="input-grupo" style="grid-column: span 2;">
                            <label style="font-weight: 700; font-size: 12px; color: #555;">FOTOGRAFIA DO PRODUTO</label>
                            <input type="file" name="imagem" class="auth-input" required
                                   style="width: 100%; padding: 15px; border: 1px solid #eee; border-radius: 10px; margin-top: 8px;">
                        </div>

                        <div class="form-actions" style="grid-column: span 2; display: flex; gap: 15px; margin-top: 20px;">
                            <a href="admin.php" style="flex: 1; text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 50px; color: #666; font-weight: 700; text-decoration: none;">CANCELAR</a>
                            <button type="submit" style="flex: 2; padding: 20px; background: #000; color: #fff; border: none; border-radius: 50px; font-weight: 900; cursor: pointer; text-transform: uppercase; letter-spacing: 1px;">CADASTRAR ITEM</button>
                        </div>

                    </div>
                </form>
            </div>
        </div>
    </div>

</body>
</html>