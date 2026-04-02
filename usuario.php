<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Minha Conta | Alto Jordão</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
</head>
<body>

    <?php include 'header.php'; ?>

    <div class="user-full-wrapper"> 
        <main class="user-main-wide">

            <section id="perfil" class="user-content-section-wide">
                <h2>Meu Perfil</h2>
                <form action="usuario_atualizar.php" method="POST" class="profile-grid">
                    <div class="input-grupo span-2">
                        <label class="auth-label">NOME COMPLETO</label>
                        <input type="text" name="nome" class="auth-input" value="<?= htmlspecialchars($user['nome']) ?>">
                    </div>
                    
                    <div class="input-grupo span-2">
                        <label class="auth-label">E-MAIL (Login)</label>
                        <input type="email" class="auth-input input-readonly" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                    </div>

                    <div class="input-grupo">
                        <label class="auth-label">CPF</label>
                        <input type="text" name="cpf" class="auth-input" value="<?= $user['cpf'] ?? '' ?>" placeholder="000.000.000-00">
                    </div>

                    <div class="input-grupo">
                        <label class="auth-label">TELEFONE</label>
                        <input type="text" name="telefone" class="auth-input" value="<?= $user['telefone'] ?? '' ?>" placeholder="(00) 00000-0000">
                    </div>

                    <div class="form-actions span-4">
                        <button type="submit" class="btn-black-capsule">SALVAR ALTERAÇÕES</button>
                    </div>
                </form>
            </section>

            <section id="enderecos" class="user-content-section-wide">
                <h2>Endereço de Entrega</h2>
                <form action="usuario_atualizar.php" method="POST" class="profile-grid">
                    <div class="input-grupo">
                        <label class="auth-label">CEP</label>
                        <input type="text" name="cep" class="auth-input" value="<?= $user['cep'] ?? '' ?>">
                    </div>

                    <div class="input-grupo span-3">
                        <label class="auth-label">ENDEREÇO / RUA</label>
                        <input type="text" name="endereco" class="auth-input" value="<?= $user['endereco'] ?? '' ?>">
                    </div>

                    <div class="input-grupo">
                        <label class="auth-label">NÚMERO</label>
                        <input type="text" name="numero" class="auth-input" value="<?= $user['numero'] ?? '' ?>">
                    </div>

                    <div class="input-grupo span-2">
                        <label class="auth-label">BAIRRO</label>
                        <input type="text" name="bairro" class="auth-input" value="<?= $user['bairro'] ?? '' ?>">
                    </div>

                    <div class="input-grupo">
                        <label class="auth-label">ESTADO (UF)</label>
                        <input type="text" name="estado" class="auth-input" value="<?= $user['estado'] ?? '' ?>" maxlength="2">
                    </div>

                    <div class="form-actions span-4">
                        <button type="submit" class="btn-black-capsule">ATUALIZAR ENDEREÇO</button>
                    </div>
                </form>
            </section>

            <section class="user-content-section-wide logout-section">
                <div class="logout-container" style="text-align: center;">
                    <p style="color: #888; text-transform: uppercase; letter-spacing: 1px; font-size: 13px; margin-bottom: 20px;">Deseja encerrar sua sessão?</p>
                    <a href="logout.php" class="btn-outline-red">SAIR DA CONTA</a>
                </div>
            </section>

        </main>
    </div>

</body>
</html>