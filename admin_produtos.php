<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config.php'; 

// Segurança: Bloqueia quem não é admin
if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] !== 'admin') {
    header("Location: login.php?erro=acesso_negado");
    exit();
}

// Busca de dados
try {
    $totalProdutos = $pdo->query("SELECT COUNT(*) FROM produtos")->fetchColumn();
    $estoqueTotal = $pdo->query("SELECT SUM(estoque) FROM produtos")->fetchColumn() ?: 0;
    $faturamento = $pdo->query("SELECT SUM(total) FROM pedidos WHERE status = 'pago'")->fetchColumn() ?: 0;
    $vendasHoje = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE DATE(data_pedido) = CURDATE()")->fetchColumn() ?: 0;
    $produtosRecentes = $pdo->query("SELECT * FROM produtos ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $faturamento = 0; $vendasHoje = 0;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Admin | Alto Jordão</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-width: 260px; --bg-gray: #f8f9fa; }
        body { display: flex; background: var(--bg-gray); margin: 0; font-family: 'Inter', sans-serif; }

        /* SIDEBAR */
        .admin-sidebar {
            width: var(--sidebar-width); background: #000; color: #fff;
            height: 100vh; position: fixed; padding: 40px 25px;
            display: flex; flex-direction: column; box-sizing: border-box;
        }
        .sidebar-brand { font-weight: 900; font-size: 20px; letter-spacing: 2px; margin-bottom: 50px; text-transform: uppercase; }
        .sidebar-brand span { color: #555; display: block; font-size: 10px; letter-spacing: 1px; }

        .nav-section { margin-bottom: 35px; }
        .nav-label { font-size: 10px; color: #444; font-weight: 800; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 15px; display: block; }
        
        .nav-item {
            color: #888; text-decoration: none; font-size: 13px; font-weight: 600;
            padding: 12px 0; display: block; transition: 0.3s;
        }
        .nav-item:hover { color: #fff; padding-left: 5px; }

        /* MAIN CONTENT */
        .admin-content { margin-left: var(--sidebar-width); flex: 1; padding: 60px; box-sizing: border-box; }

        .welcome-header { 
            background: #fff; padding: 40px; border-radius: 20px; border: 1px solid #eee;
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px;
        }
        .welcome-header h1 { margin: 0; font-weight: 900; font-size: 32px; letter-spacing: -1.5px; }
        .welcome-header p { margin: 5px 0 0; color: #888; font-size: 14px; }

        /* CARDS */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .stat-card { 
            background: #fff; padding: 25px; border-radius: 18px; border: 1px solid #eee;
            transition: 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .stat-card small { font-size: 10px; font-weight: 800; color: #bbb; text-transform: uppercase; letter-spacing: 1px; }
        .stat-card h2 { margin: 10px 0 0; font-weight: 900; font-size: 24px; letter-spacing: -0.5px; }

        /* TABELA */
        .recent-box { background: #fff; padding: 35px; border-radius: 20px; border: 1px solid #eee; }
        .recent-box h3 { margin: 0 0 25px; font-weight: 800; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }
        
        .table-premium { width: 100%; border-collapse: collapse; }
        .table-premium th { text-align: left; font-size: 11px; color: #bbb; text-transform: uppercase; padding-bottom: 15px; }
        .table-premium td { padding: 18px 0; border-top: 1px solid #f8f8f8; font-size: 14px; }
        
        .stock-tag { font-weight: 700; padding: 4px 10px; border-radius: 6px; font-size: 12px; }
        .out-of-stock { background: #ffebeb; color: #ff4d4d; }
        .in-stock { background: #f0fdf4; color: #16a34a; }

        .btn-edit { color: #000; text-decoration: none; font-weight: 800; font-size: 11px; text-transform: uppercase; border-bottom: 2px solid #000; }
    </style>
</head>
<body>

    <aside class="admin-sidebar">
        <div class="sidebar-brand">ALTO JORDÃO <span>ADMIN PANEL</span></div>
        
        <div class="nav-section">
            <span class="nav-label">Vendas</span>
            <a href="admin_vendas.php" class="nav-item">Controle de Vendas</a>
        </div>

        <div class="nav-section">
            <span class="nav-label">Estoque & Catálogo</span>
            <a href="admin_produtos.php" class="nav-item">Controle de Produtos</a>
            <a href="admin_estoque.php" class="nav-item">Gestão de Estoque</a>
            <a href="cadastrar_produto.php" class="nav-item">Novo Produto</a>
        </div>

        <div style="margin-top: auto;">
            <a href="index.php" class="nav-item" style="color: #666;">Voltar para Loja</a>
            <a href="logout.php" class="nav-item" style="color: #ff4d4d;">Sair da Conta</a>
        </div>
    </aside>

    <main class="admin-content">
        <header class="welcome-header">
            <div>
                <h1>Visão Geral</h1>
                <p>Performance da Alto Jordão em tempo real.</p>
            </div>
            <div style="text-align: right;">
                <span style="font-weight: 800; font-size: 12px;"><?= date('d . M . Y') ?></span>
            </div>
        </header>

        <section class="stats-grid">
            <div class="stat-card">
                <small>Faturamento Total</small>
                <h2>R$ <?= number_format($faturamento, 2, ',', '.') ?></h2>
            </div>
            <div class="stat-card">
                <small>Vendas Hoje</small>
                <h2><?= $vendasHoje ?></h2>
            </div>
            <div class="stat-card">
                <small>Modelos Ativos</small>
                <h2><?= $totalProdutos ?></h2>
            </div>
            <div class="stat-card">
                <small>Total em Estoque</small>
                <h2><?= $estoqueTotal ?> <span style="font-size:12px; color:#ccc">un.</span></h2>
            </div>
        </section>

        <section class="recent-box">
            <h3>Adicionados Recentemente</h3>
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Preço Unitário</th>
                        <th>Status Estoque</th>
                        <th style="text-align: right;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($produtosRecentes as $p): ?>
                    <tr>
                        <td style="font-weight: 700; color: #1a1a1a;"><?= htmlspecialchars($p['nome']) ?></td>
                        <td style="font-weight: 600;">R$ <?= number_format($p['preco'], 2, ',', '.') ?></td>
                        <td>
                            <span class="stock-tag <?= $p['estoque'] <= 0 ? 'out-of-stock' : 'in-stock' ?>">
                                <?= $p['estoque'] ?> unidades
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <a href="editar_produto.php?id=<?= $p['id'] ?>" class="btn-edit">Gerenciar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    </main>

</body>
</html>