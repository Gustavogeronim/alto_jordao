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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin | Alto Jordão</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
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
        .nav-section-title { font-size: 10px; color: #444; font-weight: 800; text-transform: uppercase; margin-bottom: 15px; display: block; letter-spacing: 1px; }
        
        .nav-item {
            color: #888; text-decoration: none; font-size: 13px; font-weight: 600;
            padding: 12px 15px; border-radius: 8px; display: block; transition: 0.3s; margin-bottom: 5px;
        }
        .nav-item:hover, .nav-item.active { background: #1a1a1a; color: #fff; }

        /* CONTEÚDO */
        .admin-content { margin-left: var(--sidebar-width); flex: 1; padding: 40px; box-sizing: border-box; }
        
        h1 { font-weight: 900; letter-spacing: -1.5px; margin: 0; font-size: 32px; }
        .subtitle { color: #666; margin: 5px 0 30px 0; }

        /* CARDS DE ESTATÍSTICAS */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { 
            background: #fff; padding: 25px; border-radius: 18px; border: 1px solid #eee;
            transition: 0.3s;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .stat-card small { font-size: 10px; font-weight: 800; color: #bbb; text-transform: uppercase; letter-spacing: 1px; }
        .stat-card h2 { margin: 10px 0 0; font-weight: 900; font-size: 24px; letter-spacing: -0.5px; }

        /* TABELA PREMIUM */
        .recent-box { background: #fff; padding: 30px; border-radius: 20px; border: 1px solid #eee; }
        .recent-box h3 { margin: 0 0 25px; font-weight: 800; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }
        
        .table-premium { width: 100%; border-collapse: collapse; }
        .table-premium th { text-align: left; font-size: 11px; color: #bbb; text-transform: uppercase; padding-bottom: 15px; border-bottom: 1px solid #f8f8f8; }
        .table-premium td { padding: 18px 0; border-bottom: 1px solid #f8f8f8; font-size: 14px; }
        
        .stock-tag { font-weight: 700; padding: 4px 10px; border-radius: 6px; font-size: 11px; text-transform: uppercase; }
        .out-of-stock { background: #ffebeb; color: #ff4d4d; }
        .in-stock { background: #f0fdf4; color: #16a34a; }

        .btn-edit { color: #000; text-decoration: none; font-weight: 800; font-size: 11px; text-transform: uppercase; border-bottom: 2px solid #000; transition: 0.2s; }
        .btn-edit:hover { color: #666; border-color: #666; }
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
            <a href="cadastrar_produto.php" class="nav-item">Novo Produto</a>
        </div>

        <div class="nav-section" style="margin-top: auto;">
            <a href="index.php" class="nav-item">Voltar para Loja</a>
            <a href="logout.php" class="nav-item" style="color: #ff4d4d;">Sair da Conta</a>
        </div>
    </aside>

    <main class="admin-content">
        <header>
            <h1>Visão Geral</h1>
            <p class="subtitle">Performance da Alto Jordão em tempo real.</p>
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
                                <?= $p['estoque'] ?> UNIDADES
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <a href="editar_produto.php?id=<?= $p['id'] ?>" class="btn-edit">Gerenciar</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($produtosRecentes)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; color: #888; padding: 40px;">Nenhum produto cadastrado ainda.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>

</body>
</html>