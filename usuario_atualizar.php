<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$id = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Se for atualização de Perfil (Nome, CPF, Telefone)
    if (isset($_POST['nome'])) {
        $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, cpf = ?, telefone = ? WHERE id = ?");
        $stmt->execute([$_POST['nome'], $_POST['cpf'], $_POST['telefone'], $id]);
    } 
    
    // Se for atualização de Endereço (CEP, Rua, Número, Bairro, Cidade, Estado)
    elseif (isset($_POST['cep'])) {
        $stmt = $pdo->prepare("UPDATE usuarios SET cep = ?, endereco = ?, numero = ?, bairro = ?, cidade = ?, estado = ? WHERE id = ?");
        $stmt->execute([
            $_POST['cep'], 
            $_POST['endereco'], 
            $_POST['numero'], 
            $_POST['bairro'], 
            $_POST['cidade'], // Campo adicionado
            $_POST['estado'], 
            $id
        ]);
    }

    // Redireciona de volta para a página do usuário com sinal de sucesso
    header("Location: usuario.php?sucesso=1");
    exit();
}