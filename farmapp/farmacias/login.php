<?php
session_start();
require_once '../config/database.php';

// Senha padrão para demonstração: password
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];
    
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SELECT id, nome, email, senha FROM farmacias WHERE email = :email AND ativo = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $farmacia = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verificar senha - usando password_verify para senhas hasheadas
        // Para demonstração, também aceita a senha "demo123"
        if ($senha === 'demo123' || password_verify($senha, $farmacia['senha'])) {
            $_SESSION['farmacia_id'] = $farmacia['id'];
            $_SESSION['farmacia_nome'] = $farmacia['nome'];
            $_SESSION['farmacia_email'] = $farmacia['email'];
            
            header('Location: dashboard.php');
            exit;
        } else {
            $erro = "Senha incorreta!";
        }
    } else {
        $erro = "Farmácia não encontrada! Use: central@farmacia.com";
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmApp - Login</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .alert-error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }
        .alert-success {
            background: #efe;
            border: 1px solid #cfc;
            color: #363;
        }
        .demo-credentials {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #4a6ee0;
        }
        .demo-credentials p {
            margin: 5px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav class="navbar">
                <div class="logo">
                    <a href="../index.html">FarmApp</a>
                </div>
                <ul class="nav-links">
                    <li><a href="../index.html">Início</a></li>
                    <li><a href="../publico/index.html">Para o Público</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="login-section">
        <div class="container">
            <div class="login-container">
                <h1>Área da Farmácia</h1>
                <p>Faça login para gerenciar seu estoque</p>
                
                <?php if (!empty($erro)): ?>
                    <div class="alert alert-error"><?php echo $erro; ?></div>
                <?php endif; ?>
                
                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label for="email">E-mail</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="senha">Senha</label>
                        <input type="password" id="senha" name="senha" required>
                    </div>
                    <button type="submit" class="btn btn-full">Entrar</button>
                </form>
                
                <div class="demo-credentials">
                    <p><strong>Credenciais de Demonstração:</strong></p>
                    <p>Email: central@farmacia.com</p>
                    <p>Email: popular@farmacia.com</p>
                    <p>Email: 24horas@farmacia.com</p>
                    <p>Senha: demo123</p>
                </div>
                
               <div class="register-link">
    <p>Não tem uma conta? <a href="registro.php">Cadastre sua farmácia</a></p>
</div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>FarmApp</h3>
                    <p>Encontre o medicamento certo, na farmácia certa em Moçambique.</p>
                </div>
                <div class="footer-section">
                    <h3>Links Rápidos</h3>
                    <a href="../index.html">Página Inicial</a>
                    <a href="../publico/index.html">Para o Público</a>
                </div>
            </div>
            <div class="copyright">
                &copy; 2023 FarmApp. Todos os direitos reservados.
            </div>
        </div>
    </footer>
</body>
</html>