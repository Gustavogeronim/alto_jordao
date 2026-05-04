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
    $preco_antigo = !empty($_POST['preco_antigo']) ? $_POST['preco_antigo'] : null;
    $categoria = $_POST['categoria'];
    $genero = $_POST['genero'];
    $tamanho = $_POST['tamanhos']; 
    $cor = $_POST['cores'];
    $estoque = $_POST['estoque'];

    // Lógica da Imagem
    $imagem = "default.jpg"; 
    if (!empty($_FILES['imagem']['name'])) {
        $extensao = pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION);
        $nome_imagem = md5(time() . rand()) . "." . $extensao;
        
        if (!is_dir("img/produtos/")) {
            mkdir("img/produtos/", 0777, true);
        }
        
        move_uploaded_file($_FILES['imagem']['tmp_name'], "img/produtos/" . $nome_imagem);
        $imagem = $nome_imagem;
    }

    try {
        $sql = "INSERT INTO produtos (nome, preco, preco_antigo, categoria, genero, tamanho, cor, estoque, imagem) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$nome, $preco, $preco_antigo, $categoria, $genero, $tamanho, $cor, $estoque, $imagem])) {
            echo "<script>alert('Produto cadastrado com sucesso!'); window.location.href='admin_produtos.php';</script>";
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-width: 260px; }
        body { display: flex; background: #f8f9fa; margin: 0; font-family: 'Inter', sans-serif; }

        /* SIDEBAR PADRONIZADA */
        .admin-sidebar {
            width: var(--sidebar-width);
            background: #000;
            color: #fff;
            height: 100vh;
            position: fixed;
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            z-index: 1000;
        }
        .sidebar-brand { font-weight: 900; font-size: 20px; letter-spacing: 2px; margin-bottom: 40px; text-align: center; }
        .sidebar-brand span { color: #555; display: block; font-size: 10px; }
        .nav-section { margin-bottom: 30px; }
        .nav-section-title { font-size: 10px; color: #444; font-weight: 800; text-transform: uppercase; margin-bottom: 15px; display: block; }
        .nav-item {
            color: #888; text-decoration: none; font-size: 13px; font-weight: 600;
            padding: 12px 15px; border-radius: 8px; display: block; transition: 0.3s; margin-bottom: 5px;
        }
        .nav-item:hover, .nav-item.active { background: #1a1a1a; color: #fff; }

        /* CONTEÚDO */
        .admin-content { margin-left: var(--sidebar-width); flex: 1; padding: 60px; box-sizing: border-box; display: flex; justify-content: center; }
        .form-container { width: 100%; max-width: 800px; background: #fff; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        
        h1 { font-weight: 900; letter-spacing: -1px; margin: 0 0 10px 0; text-transform: uppercase; }
        .subtitle { color: #888; margin-bottom: 40px; }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }

        .input-group { display: flex; flex-direction: column; gap: 8px; }
        label { font-weight: 800; font-size: 11px; color: #bbb; text-transform: uppercase; letter-spacing: 1px; }
        
        input, select {
            padding: 15px; border: 1px solid #eee; border-radius: 12px; font-family: 'Inter'; font-size: 14px; transition: 0.3s;
        }
        input:focus { border-color: #000; outline: none; background: #fafafa; }

        .btn-submit {
            background: #000; color: #fff; border: none; padding: 20px; border-radius: 50px;
            font-weight: 900; font-size: 12px; text-transform: uppercase; letter-spacing: 1px;
            cursor: pointer; transition: 0.3s; margin-top: 20px;
        }
        .btn-submit:hover { background: #333; transform: translateY(-2px); }
        .btn-cancel {
            text-align: center; padding: 20px; color: #aaa; text-decoration: none; font-weight: 700; font-size: 12px;
        }
    </style>
</head>
<body>

    <aside class="admin-sidebar">
        <div class="sidebar-brand">ALTO JORDÃO <span>ADMIN PANEL</span></div>
        
        <div class="nav-section">
            <span class="nav-section-title">Financeiro</span>
            <a href="admin_vendas.php" class="nav-item">Controle de Vendas</a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Catálogo</span>
            <a href="admin_produtos.php" class="nav-item">Controle de Produtos</a>
            <a href="admin_estoque.php" class="nav-item">Gestão de Estoque</a>
            <a href="cadastrar_produto.php" class="nav-item active">Novo Produto</a>
        </div>

        <div class="nav-section" style="margin-top: auto;">
            <a href="index.php" class="nav-item">Voltar para Loja</a>
            <a href="logout.php" class="nav-item" style="color: #ff4d4d;">Sair</a>
        </div>
    </aside>

    <main class="admin-content">
        <div class="form-container">
            <h1>Novo Produto</h1>
            <p class="subtitle">Adicione novos itens ao catálogo da Alto Jordão.</p>

            <form method="POST" enctype="multipart/form-data">
                <div class="form-grid">
                    <div class="input-group full-width">
                        <label>Nome do Produto</label>
                        <input type="text" name="nome" placeholder="Ex: Moletom Oversized Black" required>
                    </div>

                    <div class="input-group">
                        <label>Preço de Venda (R$)</label>
                        <input type="number" step="0.01" name="preco" placeholder="0.00" required>
                    </div>

                    <div class="input-group">
                        <label>Preço Original (Oferta)</label>
                        <input type="number" step="0.01" name="preco_antigo" placeholder="Opcional">
                    </div>

                    <div class="input-group">
                        <label>Categoria</label>
                        <input type="text" name="categoria" placeholder="Ex: Inverno, Calçados" required>
                    </div>

                    <div class="input-group">
                        <label>Gênero / Público</label>
                        <select name="genero">
                            <option value="unissex">Unissex</option>
                            <option value="masculino">Masculino</option>
                            <option value="feminino">Feminino</option>
                            <option value="kids">Kids</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label>Tamanhos</label>
                        <input type="text" name="tamanhos" placeholder="P, M, G, GG">
                    </div>

                    <div class="input-group">
                        <label>Cores</label>
                        <input type="text" name="cores" placeholder="Preto, Branco">
                    </div>

                    <div class="input-group">
                        <label>Estoque Inicial</label>
                        <input type="number" name="estoque" placeholder="0" required>
                    </div>

                    <div class="input-group">
                        <label>Foto do Produto</label>
                        <input type="file" name="imagem" required>
                    </div>

                    <div class="full-width" style="display: flex; flex-direction: column; gap: 10px;">
                        <button type="submit" class="btn-submit">Finalizar Cadastro</button>
                        <a href="admin_produtos.php" class="btn-cancel">CANCELAR</a>
                    </div>
                </div>
            </form>
        </div>
    </main>

</body>
</html>