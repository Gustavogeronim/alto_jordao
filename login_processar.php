<?php
// 1. Inicia a sessão obrigatoriamente no topo para salvar as permissões
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // 2. Busca o usuário por e-mail e senha
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? AND senha = ?");
    $stmt->execute([$email, $senha]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        // 3. Sucesso: Gravamos os dados na SESSÃO para usar em outras páginas
        $_SESSION['usuario_id']    = $usuario['id'];
        $_SESSION['usuario_nome']  = $usuario['nome'];
        $_SESSION['usuario_nivel'] = $usuario['nivel']; // 'admin' ou 'cliente'

        // 4. Redirecionamento Inteligente por Permissão
        if ($_SESSION['usuario_nivel'] === 'admin') {
            // Se for admin, vai para o painel de controle
            header("Location: dashboard_admin.php");
        } else {
            // Se for cliente comum, vai para a home ou perfil
            header("Location: index.php");
        }
        exit();
        
    } else {
        // 5. Erro: Credenciais incorretas
        header("Location: login.php?erro=1");
        exit();
    }
}
?>