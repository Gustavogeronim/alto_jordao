<?php
require_once 'config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Segurança: Se não estiver logado, vai para o login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];

// Busca os pedidos do usuário logado
// Unimos com a tabela de itens se você quiser mostrar total de itens (opcional)
$stmt = $pdo->prepare("SELECT * FROM pedidos WHERE usuario_id = ? ORDER BY data_pedido DESC");
$stmt->execute([$usuario_id]);
$pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Função para formatar o status com cores
function formatarStatus($status) {
    $status = strtolower($status);
    switch ($status) {
        case 'pago': case 'aprovado': 
            return '<span style="color: #27ae60; font-weight: 800;">● APROVADO</span>';
        case 'pendente': case 'aguardando':
            return '<span style="color: #f1c40f; font-weight: 800;">● PENDENTE</span>';
        case 'cancelado':
            return '<span style="color: #e74c3c; font-weight: 800;">● CANCELADO</span>';
        default:
            return '<span style="color: #888; font-weight: 800;">' . strtoupper($status) . '</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Pedidos | Alto Jordão</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        .orders-container { max-width: 1000px; margin: 60px auto; padding: 0 20px; }
        .page-title { text-align: center; margin-bottom: 50px; }
        .page-title h1 { font-weight: 900; text-transform: uppercase; letter-spacing: -1px; }
        
        .order-card {
            background: #fff;
            border: 1px solid #eee;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            align-items: center;
            transition: 0.3s;
        }
        .order-card:hover { border-color: #000; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }

        .order-info span { display: block; font-size: 11px; color: #aaa; font-weight: 700; text-transform: uppercase; margin-bottom: 5px; }
        .order-info p { font-weight: 800; font-size: 14px; margin: 0; }

        .btn-detail {
            background: #000; color: #fff; text-decoration: none; padding: 10px 20px;
            border-radius: 50px; font-size: 11px; font-weight: 800; text-align: center;
            text-transform: uppercase; transition: 0.3s;
        }
        .btn-detail:hover { background: #333; }

        @media (max-width: 768px) {
            .order-card { grid-template-columns: 1fr 1fr; gap: 20px; }
            .btn-detail { grid-column: span 2; }
        }
    </style>
</head>
<body class="bg-light">

    <?php include 'header.php'; ?>

    <main class="orders-container">
        <div class="page-title">
            <span style="letter-spacing: 5px; color: #bbb; font-weight: 700; font-size: 11px;">HISTÓRICO</span>
            <h1>Meus Pedidos</h1>
        </div>

        <?php if (count($pedidos) > 0): ?>
            <?php foreach ($pedidos as $pedido): ?>
                <div class="order-card">
                    <div class="order-info">
                        <span>Número</span>
                        <p>#<?= str_pad($pedido['id'], 5, "0", STR_PAD_LEFT) ?></p>
                    </div>
                    <div class="order-info">
                        <span>Data</span>
                        <p><?= date('d/m/Y', strtotime($pedido['data_pedido'])) ?></p>
                    </div>
                    <div class="order-info">
                        <span>Status</span>
                        <p><?= formatarStatus($pedido['status']) ?></p>
                    </div>
                    <div class="order-info" style="text-align: right; padding-right: 20px;">
                        <span>Total</span>
                        <p>R$ <?= number_format($pedido['total'], 2, ',', '.') ?></p>
                    </div>
                    <a href="pedido_detalhes.php?id=<?= $pedido['id'] ?>" class="btn-detail" style="margin-top: 15px; grid-column: span 4;">Ver Detalhes do Pedido</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; padding: 80px 20px;">
                <span style="font-size: 50px; opacity: 0.2;">📦</span>
                <h2 style="font-weight: 900; text-transform: uppercase; margin-top: 20px;">Nenhum pedido encontrado</h2>
                <p style="color: #666; margin-bottom: 30px;">Você ainda não realizou compras em nossa loja.</p>
                <a href="index.php" class="btn-black-capsule" style="background: #000; color: #fff; padding: 15px 40px; border-radius: 50px; text-decoration: none; font-weight: 800; font-size: 11px;">IR ÀS COMPRAS</a>
            </div>
        <?php endif; ?>
    </main>

    <footer style="background: #000; color: #fff; padding: 40px; text-align: center; margin-top: 100px;">
        <p style="font-size: 11px; opacity: 0.5;">ALTO JORDÃO ORIGINALS &copy; 2026</p>
    </footer>

</body>
</html>