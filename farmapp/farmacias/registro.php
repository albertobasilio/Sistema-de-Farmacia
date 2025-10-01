<?php
session_start();
require_once '../config/database.php';

$sucesso = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = filter_var($_POST['nome'], FILTER_SANITIZE_STRING);
    $endereco = filter_var($_POST['endereco'], FILTER_SANITIZE_STRING);
    $telefone = filter_var($_POST['telefone'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];
    $horario = filter_var($_POST['horario'], FILTER_SANITIZE_STRING);
    $aberta_24h = isset($_POST['aberta_24h']) ? 1 : 0;

    // Validações
    if ($senha !== $confirmar_senha) {
        $erro = "As senhas não coincidem!";
    } elseif (strlen($senha) < 6) {
        $erro = "A senha deve ter pelo menos 6 caracteres!";
    } else {
        $database = new Database();
        $db = $database->getConnection();

        // Verificar se email já existe
        $query = "SELECT id FROM farmacias WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $erro = "Este email já está registado!";
        } else {
            // Hash da senha
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            // Inserir farmácia
            $query = "INSERT INTO farmacias (nome, endereco, telefone, email, senha, horario_funcionamento, aberta_24h) 
                     VALUES (:nome, :endereco, :telefone, :email, :senha, :horario, :aberta_24h)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':endereco', $endereco);
            $stmt->bindParam(':telefone', $telefone);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':senha', $senha_hash);
            $stmt->bindParam(':horario', $horario);
            $stmt->bindParam(':aberta_24h', $aberta_24h);

            if ($stmt->execute()) {
                $sucesso = "Farmácia registada com sucesso! Agora pode fazer login.";
                // Limpar formulário
                $_POST = array();
            } else {
                $erro = "Erro ao registar farmácia. Tente novamente.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmApp - Registar Farmácia</title>
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
                    <li><a href="login.php">Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="login-section">
        <div class="container">
            <div class="register-container">
                <h1>Registar Farmácia</h1>
                <p>Preencha os dados da sua farmácia</p>
                
                <?php if (!empty($sucesso)): ?>
                    <div class="alert alert-success"><?php echo $sucesso; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($erro)): ?>
                    <div class="alert alert-error"><?php echo $erro; ?></div>
                <?php endif; ?>
                
                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label for="nome">Nome da Farmácia *</label>
                        <input type="text" id="nome" name="nome" value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="endereco">Endereço Completo *</label>
                        <textarea id="endereco" name="endereco" required><?php echo isset($_POST['endereco']) ? htmlspecialchars($_POST['endereco']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="telefone">Telefone *</label>
                        <input type="tel" id="telefone" name="telefone" value="<?php echo isset($_POST['telefone']) ? htmlspecialchars($_POST['telefone']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">E-mail *</label>
                        <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="senha">Senha *</label>
                        <input type="password" id="senha" name="senha" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmar_senha">Confirmar Senha *</label>
                        <input type="password" id="confirmar_senha" name="confirmar_senha" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="horario">Horário de Funcionamento</label>
                        <input type="text" id="horario" name="horario" value="<?php echo isset($_POST['horario']) ? htmlspecialchars($_POST['horario']) : ''; ?>" placeholder="Ex: Seg-Sex: 8h-20h, Sáb: 8h-18h">
                    </div>
                    
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="aberta_24h" name="aberta_24h" <?php echo isset($_POST['aberta_24h']) ? 'checked' : ''; ?>>
                        <label for="aberta_24h">Aberta 24 horas</label>
                    </div>
                    
                    <button type="submit" class="btn btn-full">Registar Farmácia</button>
                </form>
                
                <div class="register-link">
                    <p>Já tem uma conta? <a href="login.php">Fazer login</a></p>
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
                    <a href="login.php">Login</a>
                </div>
            </div>
            <div class="copyright">
                &copy; 2023 FarmApp. Todos os direitos reservados.
            </div>
        </div>
    </footer>
</body>
</html>