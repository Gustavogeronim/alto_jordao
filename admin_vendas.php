<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config.php'; 

// Segurança: Bloqueia quem não é admin
if (!isset($_SESSION['usuario_nivel']) || $_SESSION['usuario_nivel'] !== 'admin') {
    header("Location: login.php"); exit();
}

// Pega as datas do filtro ou define um padrão (últimos 30 dias)
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : date('Y-m-d', strtotime('-30 days'));
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : date('Y-m-d');

try {
    // 1. Vendas PAGAS no período
    $stmtPagas = $pdo->prepare("SELECT p.*, u.nome as cliente FROM pedidos p 
                                JOIN usuarios u ON p.usuario_id = u.id 
                                WHERE p.status = 'pago' AND DATE(p.data_pedido) BETWEEN ? AND ? 
                                ORDER BY p.data_pedido DESC");
    $stmtPagas->execute([$data_inicio, $data_fim]);
    $vendasPagas = $stmtPagas->fetchAll(PDO::FETCH_ASSOC);

    // 2. Vendas PENDENTES no período
    $stmtPendentes = $pdo->prepare("SELECT p.*, u.nome as cliente FROM pedidos p 
                                    JOIN usuarios u ON p.usuario_id = u.id 
                                    WHERE p.status = 'pendente' AND DATE(p.data_pedido) BETWEEN ? AND ? 
                                    ORDER BY p.data_pedido DESC");
    $stmtPendentes->execute([$data_inicio, $data_fim]);
    $vendasPendentes = $stmtPendentes->fetchAll(PDO::FETCH_ASSOC);

    // 3. Dados para o gráfico
    $stmtGrafico = $pdo->prepare("SELECT DATE(data_pedido) as dia, SUM(total) as total 
                                  FROM pedidos WHERE status = 'pago' AND DATE(data_pedido) BETWEEN ? AND ? 
                                  GROUP BY DATE(data_pedido) ORDER BY dia ASC");
    $stmtGrafico->execute([$data_inicio, $data_fim]);
    $dadosGrafico = $stmtGrafico->fetchAll(PDO::FETCH_ASSOC);

    // 4. Produto MAIS vendido
    $stmtTop = $pdo->prepare("SELECT p.nome, SUM(it.quantidade) as total_vendas 
                             FROM itens_pedido it 
                             JOIN produtos p ON it.produto_id = p.id 
                             JOIN pedidos ped ON it.pedido_id = ped.id
                             WHERE ped.status = 'pago' AND DATE(ped.data_pedido) BETWEEN ? AND ?
                             GROUP BY it.produto_id ORDER BY total_vendas DESC LIMIT 1");
    $stmtTop->execute([$data_inicio, $data_fim]);
    $produtoTop = $stmtTop->fetch(PDO::FETCH_ASSOC);

    // 5. Produto MENOS vendido
    $stmtLow = $pdo->prepare("SELECT p.nome, IFNULL(SUM(it.quantidade), 0) as total_vendas 
                              FROM produtos p
                              LEFT JOIN itens_pedido it ON p.id = it.produto_id
                              LEFT JOIN pedidos ped ON it.pedido_id = ped.id AND ped.status = 'pago'
                              WHERE (DATE(ped.data_pedido) BETWEEN ? AND ? OR ped.id IS NULL)
                              GROUP BY p.id ORDER BY total_vendas ASC LIMIT 1");
    $stmtLow->execute([$data_inicio, $data_fim]);
    $produtoLow = $stmtLow->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $produtoTop = $produtoLow = null;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Vendas | Alto Jordão</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --sidebar-width: 260px; }
        body { display: flex; background: #f8f9fa; margin: 0; font-family: 'Inter', sans-serif; color: #1a1a1a; }

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
        
        h1 { font-weight: 900; letter-spacing: -1px; margin: 0 0 10px 0; }
        .subtitle { color: #888; margin-bottom: 40px; }

        /* FILTRO */
        .filter-box { background: #fff; padding: 25px; border-radius: 20px; border: 1px solid #eee; margin-bottom: 30px; display: flex; align-items: center; gap: 20px; }
        .filter-box input { padding: 12px; border: 1px solid #eee; border-radius: 10px; font-family: 'Inter'; font-size: 13px; background: #fafafa; }
        .btn-filter { background: #000; color: #fff; border: none; padding: 12px 25px; border-radius: 10px; font-weight: 800; cursor: pointer; font-size: 11px; }

        /* CARDS */
        .perf-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
        .perf-card { background: #fff; padding: 25px; border-radius: 20px; border: 1px solid #eee; display: flex; align-items: center; gap: 20px; }
        .perf-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }

        .chart-card { background: #fff; padding: 30px; border-radius: 20px; border: 1px solid #eee; margin-bottom: 30px; }

        /* TABS & TABLE */
        .tabs-container { display: flex; gap: 30px; margin-bottom: 30px; border-bottom: 2px solid #eee; }
        .tab-btn { background: none; border: none; font-family: 'Inter'; font-weight: 800; font-size: 14px; color: #bbb; cursor: pointer; padding: 15px 0; position: relative; }
        .tab-btn.active { color: #000; }
        .tab-btn.active::after { content: ''; position: absolute; bottom: -2px; left: 0; width: 100%; height: 3px; background: #000; }

        .vendas-table { width: 100%; background: #fff; border-radius: 15px; border-collapse: collapse; display: none; }
        .vendas-table.active { display: table; }
        .vendas-table th { background: #fafafa; text-align: left; font-size: 11px; color: #aaa; padding: 20px; text-transform: uppercase; }
        .vendas-table td { padding: 20px; border-top: 1px solid #f8f8f8; font-size: 14px; }

        .badge { padding: 6px 12px; border-radius: 8px; font-weight: 800; font-size: 10px; text-transform: uppercase; }
        .badge-pago { background: #e6fffa; color: #16a34a; }
        .badge-pendente { background: #fff9e6; color: #d97706; }
    </style>
</head>
<body>

    <aside class="admin-sidebar">
        <div class="sidebar-brand">ALTO JORDÃO <span>ADMIN PANEL</span></div>
        
        <div class="nav-section">
            <span class="nav-section-title">Financeiro</span>
            <a href="admin_vendas.php" class="nav-item active">Controle de Vendas</a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Catálogo</span>
            <a href="admin_produtos.php" class="nav-item">Controle de Produtos</a>
            <a href="admin_estoque.php" class="nav-item">Gestão de Estoque</a>
            <a href="cadastrar_produto.php" class="nav-item">Novo Produto</a>
        </div>

        <div class="nav-section" style="margin-top: auto;">
            <a href="index.php" class="nav-item">Voltar para Loja</a>
            <a href="logout.php" class="nav-item" style="color: #ff4d4d;">Sair</a>
        </div>
    </aside>

    <main class="admin-content">
        <h1>Relatório de Vendas</h1>
        <p class="subtitle">Análise detalhada de movimentação e performance por período.</p>

        <form method="GET" class="filter-box">
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label style="font-size: 10px; font-weight: 800; color: #aaa;">DATA INICIAL</label>
                <input type="date" name="data_inicio" value="<?= $data_inicio ?>">
            </div>
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <label style="font-size: 10px; font-weight: 800; color: #aaa;">DATA FINAL</label>
                <input type="date" name="data_fim" value="<?= $data_fim ?>">
            </div>
            <button type="submit" class="btn-filter" style="margin-top: 15px;">FILTRAR</button>
        </form>

        <div class="perf-grid">
            <div class="perf-card">
                <div class="perf-icon" style="background: #000; color: #fff;">🏆</div>
                <div>
                    <small style="color:#888; font-weight:800; font-size:10px;">MAIS VENDIDO</small>
                    <h3 style="margin:5px 0 0; font-size: 16px;"><?= $produtoTop ? $produtoTop['nome'] : 'Sem dados' ?></h3>
                    <span style="color:#16a34a; font-size:12px; font-weight:700;"><?= $produtoTop ? $produtoTop['total_vendas'].' unidades' : '-' ?></span>
                </div>
            </div>
            <div class="perf-card">
                <div class="perf-icon" style="background: #f8f9fa; border: 1px solid #eee;">📉</div>
                <div>
                    <small style="color:#888; font-weight:800; font-size:10px;">MENOS VENDIDO</small>
                    <h3 style="margin:5px 0 0; font-size: 16px;"><?= $produtoLow ? $produtoLow['nome'] : 'Sem dados' ?></h3>
                    <span style="color:#dc2626; font-size:12px; font-weight:700;"><?= $produtoLow ? $produtoLow['total_vendas'].' unidades' : '-' ?></span>
                </div>
            </div>
        </div>

        <div class="chart-card">
            <h3 style="margin-top:0; font-weight:800; font-size:14px; text-transform:uppercase; color:#888;">Faturamento no período</h3>
            <canvas id="graficoVendas" height="80"></canvas>
        </div>

        <div class="tabs-container">
            <button class="tab-btn active" onclick="switchTab('pagas', this)">CONCLUÍDAS (<?= count($vendasPagas) ?>)</button>
            <button class="tab-btn" onclick="switchTab('pendentes', this)">PENDENTES (<?= count($vendasPendentes) ?>)</button>
        </div>

        <table class="vendas-table active" id="table-pagas">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>CLIENTE</th>
                    <th>VALOR</th>
                    <th>STATUS</th>
                    <th>DATA</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($vendasPagas as $v): ?>
                <tr>
                    <td style="font-weight: 800;">#<?= $v['id'] ?></td>
                    <td style="font-weight: 700;"><?= htmlspecialchars($v['cliente']) ?></td>
                    <td><strong>R$ <?= number_format($v['total'], 2, ',', '.') ?></strong></td>
                    <td><span class="badge badge-pago">PAGO</span></td>
                    <td style="color:#888;"><?= date('d/m/Y H:i', strtotime($v['data_pedido'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <table class="vendas-table" id="table-pendentes">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>CLIENTE</th>
                    <th>VALOR</th>
                    <th>STATUS</th>
                    <th>DATA</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($vendasPendentes as $v): ?>
                <tr>
                    <td style="font-weight: 800;">#<?= $v['id'] ?></td>
                    <td style="font-weight: 700;"><?= htmlspecialchars($v['cliente']) ?></td>
                    <td><strong>R$ <?= number_format($v['total'], 2, ',', '.') ?></strong></td>
                    <td><span class="badge badge-pendente">AGUARDANDO</span></td>
                    <td style="color:#888;"><?= date('d/m/Y H:i', strtotime($v['data_pedido'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

    <script>
        function switchTab(type, btn) {
            document.querySelectorAll('.vendas-table').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            document.getElementById('table-' + type).classList.add('active');
            btn.classList.add('active');
        }

        const ctx = document.getElementById('graficoVendas').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: [<?php foreach($dadosGrafico as $d) echo "'".date('d/m', strtotime($d['dia']))."',"; ?>],
                datasets: [{
                    label: 'Receita (R$)',
                    data: [<?php foreach($dadosGrafico as $d) echo $d['total'].","; ?>],
                    borderColor: '#000',
                    backgroundColor: 'rgba(0,0,0,0.02)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointBackgroundColor: '#000'
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>