<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php'; 

// --- LÓGICA DE BUSCA DE DADOS DO USUÁRIO ---
$user = null;
if (isset($_SESSION['usuario_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $user = $stmt->fetch();
} else {
    // Se você quiser obrigar o login para comprar, descomente a linha abaixo:
    // header("Location: login.php?erro=faca_login"); exit();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Compra | Alto Jordão</title>
    <link rel="stylesheet" href="style.css?v=<?= time(); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-light: #f9f9f9;
            --border-color: #eee;
        }

        .checkout-container {
            max-width: 1200px;
            margin: 60px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 60px;
            align-items: flex-start;
        }

        .checkout-form h2 { 
            font-size: 2rem;
            font-weight: 900; 
            text-transform: uppercase; 
            margin-bottom: 40px; 
            letter-spacing: -1px;
        }

        .section-title {
            font-size: 11px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #999;
            margin-bottom: 20px;
            display: block;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }

        .form-group { margin-bottom: 25px; }
        .form-group label { display: block; font-weight: 700; font-size: 12px; margin-bottom: 8px; text-transform: uppercase; color: #333; }
        .form-group input, .form-group select { 
            width: 100%; 
            padding: 15px; 
            border: 1px solid var(--border-color); 
            border-radius: 12px; 
            font-size: 15px;
            background: var(--bg-light);
            transition: 0.3s;
        }
        .form-group input:focus { border-color: #000; outline: none; background: #fff; }

        .resumo-pedido {
            background: #fff;
            padding: 40px;
            border-radius: 25px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.04);
            position: sticky;
            top: 100px;
        }

        .item-checkout {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px dotted #eee;
        }
        .item-checkout img { 
            width: 70px; 
            height: 90px; 
            object-fit: contain; 
            background: #f9f9f9;
            border-radius: 8px; 
        }
        .item-info h4 { font-size: 13px; font-weight: 800; margin: 0; text-transform: uppercase; letter-spacing: 0.5px; }
        .item-info p { font-size: 11px; color: #999; margin: 5px 0; font-weight: 600; }
        .item-info .item-price { font-weight: 800; font-size: 14px; display: block; margin-top: 5px; color: #000; }

        .btn-finalizar {
            width: 100%;
            padding: 22px;
            background: #000;
            color: #fff;
            border: none;
            border-radius: 50px;
            font-weight: 900;
            font-size: 14px;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 30px;
        }
        .btn-finalizar:hover { transform: scale(1.02); box-shadow: 0 10px 20px rgba(0,0,0,0.15); }

        @media (max-width: 900px) {
            .checkout-container { grid-template-columns: 1fr; }
            .resumo-pedido { position: static; }
        }
    </style>
</head>
<body>

    <?php include 'header.php'; ?>

    <main class="checkout-container">
        <section class="checkout-form">
            <h2>Finalizar Compra</h2>
            
            <form action="processar_pedido.php" method="POST" id="formCheckout">
                <span class="section-title">Informações Pessoais</span>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Nome Completo</label>
                        <input type="text" name="nome" required placeholder="Digite seu nome" value="<?= htmlspecialchars($user['nome'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>E-mail</label>
                        <input type="email" name="email" required placeholder="seu@email.com" value="<?= htmlspecialchars($user['email'] ?? '') ?>" <?= isset($user) ? 'readonly' : '' ?>>
                    </div>
                </div>

                <span class="section-title">Endereço de Entrega</span>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>CEP</label>
                        <input type="text" id="cep" name="cep" required placeholder="00000-000" maxlength="9" value="<?= htmlspecialchars($user['cep'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Estado (UF)</label>
                        <input type="text" id="estado" name="estado" required placeholder="Ex: SP" maxlength="2" value="<?= htmlspecialchars($user['estado'] ?? '') ?>">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 3fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Endereço / Rua</label>
                        <input type="text" id="endereco" name="endereco" required placeholder="Ex: Rua das Flores" value="<?= htmlspecialchars($user['endereco'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Número</label>
                        <input type="text" id="numero" name="numero" required placeholder="123" value="<?= htmlspecialchars($user['numero'] ?? '') ?>">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label>Bairro</label>
                        <input type="text" id="bairro" name="bairro" required placeholder="Centro" value="<?= htmlspecialchars($user['bairro'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Cidade</label>
                        <input type="text" id="cidade" name="cidade" required placeholder="Sua Cidade" value="<?= htmlspecialchars($user['cidade'] ?? '') ?>">
                    </div>
                </div>

                <span class="section-title">Pagamento</span>
                <div class="form-group">
                    <label>Forma de Pagamento</label>
                    <select name="pagamento" required>
                        <option value="pix">PIX (5% de desconto)</option>
                        <option value="cartao">Cartão de Crédito</option>
                        <option value="boleto">Boleto Bancário</option>
                    </select>
                </div>

                <input type="hidden" name="carrinho_dados" id="inputCarrinhoDados">

                <button type="submit" class="btn-finalizar">
                    Confirmar Pedido
                </button>
            </form>
        </section>

        <aside class="resumo-pedido">
            <h3 style="font-weight: 900; margin-bottom: 25px; text-transform: uppercase; font-size: 14px; letter-spacing: 1px;">Resumo do Pedido</h3>
            
            <div id="listaCheckout"></div>
            
            <div style="margin-top: 30px; border-top: 2px solid #000; padding-top: 25px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; color: #666;">
                    <span>Subtotal</span>
                    <span id="subtotalCheckout">R$ 0,00</span>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px; color: #666;">
                    <span>Frete</span>
                    <span style="color: #27ae60; font-weight: 900; letter-spacing: 1px;">GRÁTIS</span>
                </div>
                <div style="display: flex; justify-content: space-between; font-weight: 900; font-size: 22px; color: #000;">
                    <span>TOTAL</span>
                    <span id="totalCheckout">R$ 0,00</span>
                </div>
            </div>
        </aside>
    </main>

    <script src="script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const inputCep = document.getElementById('cep');
            
            // Função para buscar CEP
            inputCep.addEventListener('blur', () => {
                let cep = inputCep.value.replace(/\D/g, ''); 
                if (cep.length === 8) {
                    fetch(`https://viacep.com.br/ws/${cep}/json/`)
                        .then(res => res.json())
                        .then(dados => {
                            if (!dados.erro) {
                                document.getElementById('endereco').value = dados.logradouro;
                                document.getElementById('bairro').value = dados.bairro;
                                document.getElementById('cidade').value = dados.localidade;
                                document.getElementById('estado').value = dados.uf;
                                document.getElementById('numero').focus();
                            }
                        });
                }
            });

            // Lógica do Carrinho (sessionStorage)
            const carrinho = JSON.parse(sessionStorage.getItem('fashion_cart')) || [];
            const inputDados = document.getElementById('inputCarrinhoDados');
            const lista = document.getElementById('listaCheckout');
            
            if(carrinho.length === 0) {
                window.location.href = "index.php";
                return;
            }

            inputDados.value = JSON.stringify(carrinho);
            let total = 0;
            
            lista.innerHTML = carrinho.map(item => {
                total += (item.preco * item.qtd);
                return `
                    <div class="item-checkout">
                        <img src="${item.img}">
                        <div class="item-info">
                            <h4>${item.nome}</h4>
                            <span class="item-price">${item.qtd}x R$ ${parseFloat(item.preco).toLocaleString('pt-br', {minimumFractionDigits: 2})}</span>
                        </div>
                    </div>
                `;
            }).join('');

            document.getElementById('totalCheckout').innerText = `R$ ${total.toLocaleString('pt-br', {minimumFractionDigits: 2})}`;
            document.getElementById('subtotalCheckout').innerText = `R$ ${total.toLocaleString('pt-br', {minimumFractionDigits: 2})}`;
        });
    </script>
</body>
</html>