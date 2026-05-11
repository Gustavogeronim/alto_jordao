<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config.php'; 

// 1. SEGURANÇA
if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] !== 'admin') {
    header("Location: login.php"); exit();
}

// 2. LÓGICA DE ATUALIZAÇÃO (BOTÃO ENTREGUE E ENVIADO)
if (isset($_GET['atualizar_id']) && isset($_GET['novo_status'])) {
    $id = (int)$_GET['atualizar_id'];
    $status = strtolower(trim($_GET['novo_status']));
    
    $stmt = $pdo->prepare("UPDATE pedidos SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $id])) {
        header("Location: entregas.php?sucesso=1");
        exit();
    }
}

// 3. BUSCA DE TODOS OS PEDIDOS (Exceto cancelados)
try {
    $stmt = $pdo->query("SELECT p.*, u.nome as cliente 
                         FROM pedidos p 
                         JOIN usuarios u ON p.usuario_id = u.id 
                         WHERE p.status != 'cancelado'
                         ORDER BY p.data_pedido DESC");
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pedidos = [];
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Logística | Alto Jordão Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root { --sidebar-width: 260px; }
        body { display: flex; background: #f8f9fa; margin: 0; font-family: 'Inter', sans-serif; color: #1a1a1a; }

        /* SIDEBAR OFICIAL */
        .admin-sidebar {
            width: var(--sidebar-width); background: #000; color: #fff; height: 100vh;
            position: fixed; padding: 30px 20px; display: flex; flex-direction: column;
            box-sizing: border-box; z-index: 1000;
        }
        .sidebar-brand { font-weight: 900; font-size: 20px; letter-spacing: 2px; margin-bottom: 40px; text-align: center; }
        .sidebar-brand span { color: #555; display: block; font-size: 10px; letter-spacing: 1px; }
        .nav-section { margin-bottom: 30px; }
        .nav-section-title { font-size: 10px; color: #444; font-weight: 800; text-transform: uppercase; margin-bottom: 15px; display: block; letter-spacing: 1px; }
        .nav-item { color: #888; text-decoration: none; font-size: 13px; font-weight: 600; padding: 12px 15px; border-radius: 8px; display: block; transition: 0.3s; margin-bottom: 5px; }
        .nav-item:hover, .nav-item.active { background: #1a1a1a; color: #fff; }

        /* CONTEÚDO */
        .admin-content { margin-left: var(--sidebar-width); flex: 1; padding: 40px; box-sizing: border-box; }
        h1 { font-weight: 900; letter-spacing: -1.5px; margin: 0 0 10px 0; text-transform: uppercase; }
        .subtitle { color: #888; margin-bottom: 40px; font-size: 14px; }

        /* TABELA */
        .vendas-table { width: 100%; background: #fff; border-radius: 20px; border-collapse: collapse; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.02); border: 1px solid #eee; }
        .vendas-table th { background: #fcfcfc; text-align: left; font-size: 11px; color: #aaa; padding: 20px; text-transform: uppercase; border-bottom: 1px solid #eee; }
        .vendas-table td { padding: 20px; border-bottom: 1px solid #f8f8f8; font-size: 14px; }

        /* BADGES */
        .badge { padding: 6px 12px; border-radius: 6px; font-weight: 800; font-size: 10px; text-transform: uppercase; }
        .status-pago { background: #e3f2fd; color: #1976d2; }
        .status-enviado { background: #fff3e0; color: #f57c00; }
        .status-entregue { background: #e8f5e9; color: #2e7d32; }

        /* BOTÕES DE AÇÃO */
        .action-group { display: flex; gap: 10px; justify-content: flex-end; }
        .btn-status { 
            text-decoration: none; padding: 8px 15px; border-radius: 8px; font-size: 10px; 
            font-weight: 900; text-transform: uppercase; transition: 0.2s; border: none; cursor: pointer;
            display: inline-flex; align-items: center; gap: 5px;
        }
        .btn-ship { background: #000; color: #fff; }
        .btn-deliver { background: #2e7d32; color: #fff; }
        .btn-status:hover { transform: translateY(-2px); opacity: 0.9; }
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
            <span class="nav-section-title">Operações & SAC</span>
            <a href="entregas.php" class="nav-item active">📦 Gestão de Entregas</a>
            <a href="admin_devolucoes.php" class="nav-item">🔄 Trocas e Devoluções</a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Catálogo</span>
            <a href="admin_produtos.php" class="nav-item">Controle de Produtos</a>
            <a href="admin_estoque.php" class="nav-item">Gestão de Estoque</a>
            <a href="cadastrar_produto.php" class="nav-item">Novo Produto</a>
        </div>

        <div class="nav-section" style="margin-top: auto; border-top: 1px solid #1a1a1a; padding-top: 20px;">
            <a href="index.php" class="nav-item">← Voltar para Loja</a>
            <a href="logout.php" class="nav-item" style="color: #ff4d4d;">Sair da Conta</a>
        </div>
    </aside>

    <main class="admin-content">
        <h1>Gestão de Logística</h1>
        <p class="subtitle">Clique nos botões para atualizar o status do pedido em tempo real.</p>

        <table class="vendas-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>CLIENTE</th>
                    <th>STATUS ATUAL</th>
                    <th>DATA</th>
                    <th style="text-align: right;">AÇÕES DE ENTREGA</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($pedidos as $p): 
                    $st = strtolower(trim($p['status']));
                ?>
                <tr>
                    <td style="font-weight: 900; color: #bbb;">#<?= $p['id'] ?></td>
                    <td style="font-weight: 700;"><?= htmlspecialchars($p['cliente']) ?></td>
                    <td><span class="badge status-<?= $st ?>"><?= $p['status'] ?></span></td>
                    <td style="color:#888; font-size: 12px;"><?= date('d/m/Y', strtotime($p['data_pedido'])) ?></td>
                    <td style="text-align: right;">
                        <div class="action-group">
                            <?php if($st != 'entregue'): ?>
                                
                                <?php if($st != 'enviado'): ?>
                                    <a href="?atualizar_id=<?= $p['id'] ?>&novo_status=enviado" class="btn-status btn-ship">
                                        🚀 Despachar
                                    </a>
                                <?php endif; ?>

                                <a href="?atualizar_id=<?= $p['id'] ?>&novo_status=entregue" class="btn-status btn-deliver">
                                    ✅ Entregue
                                </a>

                            <?php else: ?>
                                <span style="color: #2e7d32; font-weight: 900; font-size: 10px;">CONCLUÍDO ✓</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

</body>
</html>