<?php
session_start();
require_once '../config/database.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['farmacia_id'])) {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Buscar informações da farmácia
$query = "SELECT * FROM farmacias WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $_SESSION['farmacia_id']);
$stmt->execute();
$farmacia = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar estoque da farmácia
$query = "SELECT e.id as estoque_id, m.id as medicamento_id, m.nome, m.principio_ativo, m.categoria, e.quantidade, e.preco, e.ultima_atualizacao 
          FROM estoque_farmacia e 
          JOIN medicamentos m ON e.medicamento_id = m.id 
          WHERE e.farmacia_id = :farmacia_id 
          ORDER BY m.nome";
$stmt = $db->prepare($query);
$stmt->bindParam(':farmacia_id', $_SESSION['farmacia_id']);
$stmt->execute();
$estoque = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar todos os medicamentos disponíveis para adicionar
$query = "SELECT id, nome, principio_ativo FROM medicamentos ORDER BY nome";
$stmt = $db->prepare($query);
$stmt->execute();
$medicamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar estatísticas reais
$query = "SELECT 
            COUNT(*) as total_medicamentos,
            SUM(quantidade) as total_estoque,
            AVG(preco) as preco_medio,
            COUNT(CASE WHEN quantidade = 0 THEN 1 END) as sem_estoque,
            COUNT(CASE WHEN quantidade < 10 THEN 1 END) as estoque_baixo
          FROM estoque_farmacia 
          WHERE farmacia_id = :farmacia_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':farmacia_id', $_SESSION['farmacia_id']);
$stmt->execute();
$estatisticas = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar medicamentos mais procurados (simulação)
$query = "SELECT m.nome, COUNT(*) as buscas 
          FROM medicamentos m 
          GROUP BY m.id 
          ORDER BY buscas DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$medicamentos_populares = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Processar atualização de estoque
$sucesso = '';
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        if ($_POST['acao'] === 'adicionar_medicamento') {
            $medicamento_id = $_POST['medicamento_id'];
            $quantidade = $_POST['quantidade'];
            $preco = $_POST['preco'];
            
            // Validar dados
            if ($quantidade < 0 || $preco < 0) {
                $erro = "Quantidade e preço devem ser valores positivos!";
            } else {
                // Verificar se já existe
                $query = "SELECT id FROM estoque_farmacia WHERE farmacia_id = :farmacia_id AND medicamento_id = :medicamento_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':farmacia_id', $_SESSION['farmacia_id']);
                $stmt->bindParam(':medicamento_id', $medicamento_id);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $erro = "Este medicamento já está no seu estoque! Use a opção editar para modificar.";
                } else {
                    $query = "INSERT INTO estoque_farmacia (farmacia_id, medicamento_id, quantidade, preco) 
                             VALUES (:farmacia_id, :medicamento_id, :quantidade, :preco)";
                    
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':farmacia_id', $_SESSION['farmacia_id']);
                    $stmt->bindParam(':medicamento_id', $medicamento_id);
                    $stmt->bindParam(':quantidade', $quantidade);
                    $stmt->bindParam(':preco', $preco);
                    
                    if ($stmt->execute()) {
                        $sucesso = "Medicamento adicionado ao estoque com sucesso!";
                        header('Location: dashboard.php?sucesso=1');
                        exit;
                    } else {
                        $erro = "Erro ao adicionar medicamento!";
                    }
                }
            }
        }
        elseif ($_POST['acao'] === 'atualizar_estoque') {
            $estoque_id = $_POST['estoque_id'];
            $quantidade = $_POST['quantidade'];
            $preco = $_POST['preco'];
            
            if ($quantidade < 0 || $preco < 0) {
                $erro = "Quantidade e preço devem ser valores positivos!";
            } else {
                $query = "UPDATE estoque_farmacia SET quantidade = :quantidade, preco = :preco WHERE id = :id AND farmacia_id = :farmacia_id";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':quantidade', $quantidade);
                $stmt->bindParam(':preco', $preco);
                $stmt->bindParam(':id', $estoque_id);
                $stmt->bindParam(':farmacia_id', $_SESSION['farmacia_id']);
                
                if ($stmt->execute()) {
                    $sucesso = "Estoque atualizado com sucesso!";
                    header('Location: dashboard.php?sucesso=1');
                    exit;
                } else {
                    $erro = "Erro ao atualizar estoque!";
                }
            }
        }
        elseif ($_POST['acao'] === 'remover_medicamento') {
            $estoque_id = $_POST['estoque_id'];
            
            $query = "DELETE FROM estoque_farmacia WHERE id = :id AND farmacia_id = :farmacia_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $estoque_id);
            $stmt->bindParam(':farmacia_id', $_SESSION['farmacia_id']);
            
            if ($stmt->execute()) {
                $sucesso = "Medicamento removido do estoque!";
                header('Location: dashboard.php?sucesso=1');
                exit;
            } else {
                $erro = "Erro ao remover medicamento!";
            }
        }
        elseif ($_POST['acao'] === 'atualizar_perfil') {
            $nome = $_POST['nome'];
            $endereco = $_POST['endereco'];
            $telefone = $_POST['telefone'];
            $horario_funcionamento = $_POST['horario_funcionamento'];
            $aberta_24h = isset($_POST['aberta_24h']) ? 1 : 0;
            
            $query = "UPDATE farmacias SET nome = :nome, endereco = :endereco, telefone = :telefone, 
                     horario_funcionamento = :horario_funcionamento, aberta_24h = :aberta_24h 
                     WHERE id = :id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindParam(':endereco', $endereco);
            $stmt->bindParam(':telefone', $telefone);
            $stmt->bindParam(':horario_funcionamento', $horario_funcionamento);
            $stmt->bindParam(':aberta_24h', $aberta_24h);
            $stmt->bindParam(':id', $_SESSION['farmacia_id']);
            
            if ($stmt->execute()) {
                $sucesso = "Perfil atualizado com sucesso!";
                header('Location: dashboard.php?sucesso=1');
                exit;
            } else {
                $erro = "Erro ao atualizar perfil!";
            }
        }
    }
}

// Verificar se veio redirecionamento com sucesso
if (isset($_GET['sucesso']) && $_GET['sucesso'] == 1) {
    $sucesso = "Operação realizada com sucesso!";
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmApp - Dashboard</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .alert {
            padding: 12px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid transparent;
        }
        .alert-success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .alert-error {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        .stock-low {
            color: #e74c3c;
            font-weight: bold;
        }
        .stock-medium {
            color: #f39c12;
            font-weight: bold;
        }
        .stock-good {
            color: #27ae60;
            font-weight: bold;
        }
        .stats-highlight {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        .popular-medicines {
            margin-top: 20px;
        }
        .popular-medicine-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
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
                    <li><a href="logout.php" id="logout-btn">Sair (<?php echo htmlspecialchars($farmacia['nome']); ?>)</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="dashboard-container">
        <div class="sidebar">
            <div class="pharmacy-info">
                <h2><?php echo htmlspecialchars($farmacia['nome']); ?></h2>
                <p><?php echo htmlspecialchars($farmacia['endereco']); ?></p>
                <div class="status-indicator">
                    <span class="status-dot open"></span>
                    <span>Status: <?php echo $farmacia['aberta_24h'] ? '24 Horas' : 'Aberta'; ?></span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li><a href="#estoque" class="nav-link active">Gerenciar Estoque</a></li>
                    <li><a href="#horarios" class="nav-link">Horários</a></li>
                    <li><a href="#estatisticas" class="nav-link">Estatísticas</a></li>
                    <li><a href="#perfil" class="nav-link">Perfil</a></li>
                </ul>
            </nav>
        </div>
        
        <div class="main-content">
            <!-- Seção Estoque -->
            <div class="content-section active" id="estoque">
                <h1>Gerenciar Estoque</h1>
                
                <?php if (!empty($sucesso)): ?>
                    <div class="alert alert-success"><?php echo $sucesso; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($erro)): ?>
                    <div class="alert alert-error"><?php echo $erro; ?></div>
                <?php endif; ?>
                
                <div class="search-add-bar">
                    <div class="search-box">
                        <input type="text" id="search-medicine" placeholder="Buscar medicamento...">
                        <button id="search-medicine-btn" class="btn">Buscar</button>
                    </div>
                    <button id="add-medicine-btn" class="btn btn-primary">Adicionar Medicamento</button>
                </div>
                
                <div class="stock-table-container">
                    <table class="stock-table">
                        <thead>
                            <tr>
                                <th>Medicamento</th>
                                <th>Princípio Ativo</th>
                                <th>Estoque</th>
                                <th>Preço (MZN)</th>
                                <th>Última Atualização</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody id="stock-table-body">
                            <?php if (count($estoque) > 0): ?>
                                <?php foreach ($estoque as $item): ?>
                                    <?php
                                    $stock_class = '';
                                    if ($item['quantidade'] == 0) {
                                        $stock_class = 'stock-low';
                                    } elseif ($item['quantidade'] < 10) {
                                        $stock_class = 'stock-medium';
                                    } else {
                                        $stock_class = 'stock-good';
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($item['principio_ativo']); ?></td>
                                        <td class="<?php echo $stock_class; ?>"><?php echo $item['quantidade']; ?></td>
                                        <td><?php echo number_format($item['preco'], 2, ',', '.'); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($item['ultima_atualizacao'])); ?></td>
                                        <td class="actions">
                                            <button class="btn btn-small btn-primary editar-medicamento" 
                                                    data-id="<?php echo $item['estoque_id']; ?>"
                                                    data-nome="<?php echo htmlspecialchars($item['nome']); ?>"
                                                    data-quantidade="<?php echo $item['quantidade']; ?>"
                                                    data-preco="<?php echo $item['preco']; ?>">
                                                Editar
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="acao" value="remover_medicamento">
                                                <input type="hidden" name="estoque_id" value="<?php echo $item['estoque_id']; ?>">
                                                <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('Tem certeza que deseja remover este medicamento?')">Remover</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-state">
                                        <p>Nenhum medicamento em estoque.</p>
                                        <button id="add-first-medicine" class="btn btn-primary">Adicionar Primeiro Medicamento</button>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Seção Horários -->
            <div class="content-section" id="horarios">
                <h1>Horários de Funcionamento</h1>
                <div class="schedule-container">
                    <div class="schedule-card">
                        <h3>Horário Regular</h3>
                        <div class="schedule-form">
                            <div class="form-group">
                                <label>Horário Atual</label>
                                <p style="padding: 10px; background: #f5f5f5; border-radius: 5px;">
                                    <?php echo $farmacia['horario_funcionamento'] ? htmlspecialchars($farmacia['horario_funcionamento']) : 'Não definido'; ?>
                                </p>
                            </div>
                            <div class="form-group checkbox-group">
                                <input type="checkbox" id="24h-checkbox" <?php echo $farmacia['aberta_24h'] ? 'checked' : ''; ?> disabled>
                                <label for="24h-checkbox">Aberta 24 horas</label>
                            </div>
                            <p><small>Para alterar horários, atualize no seu perfil.</small></p>
                        </div>
                    </div>
                    
                    <div class="schedule-card">
                        <h3>Informações de Contato</h3>
                        <div class="contact-info">
                            <p><strong>Telefone:</strong> <?php echo htmlspecialchars($farmacia['telefone']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($farmacia['email']); ?></p>
                            <p><strong>Endereço:</strong> <?php echo htmlspecialchars($farmacia['endereco']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Seção Estatísticas -->
            <div class="content-section" id="estatisticas">
                <h1>Estatísticas da Farmácia</h1>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $estatisticas['total_medicamentos'] ?? 0; ?></div>
                        <div class="stat-label">Medicamentos em Estoque</div>
                        <div class="stats-highlight">Total de itens cadastrados</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $estatisticas['total_estoque'] ?? 0; ?></div>
                        <div class="stat-label">Unidades Disponíveis</div>
                        <div class="stats-highlight">Soma de todo o estoque</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo number_format($estatisticas['preco_medio'] ?? 0, 2, ',', '.'); ?></div>
                        <div class="stat-label">Preço Médio (MZN)</div>
                        <div class="stats-highlight">Média dos preços</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $estatisticas['estoque_baixo'] ?? 0; ?></div>
                        <div class="stat-label">Estoques Baixos</div>
                        <div class="stats-highlight">Itens com menos de 10 unidades</div>
                    </div>
                </div>
                
                <div class="chart-container">
                    <h3>Resumo do Estoque</h3>
                    <div class="chart-placeholder">
                        <div style="text-align: center; padding: 40px;">
                            <h4>Distribuição do Seu Estoque</h4>
                            <p>✅ Medicamentos com estoque bom: <strong><?php echo ($estatisticas['total_medicamentos'] ?? 0) - ($estatisticas['estoque_baixo'] ?? 0) - ($estatisticas['sem_estoque'] ?? 0); ?></strong></p>
                            <p>⚠️ Medicamentos com estoque baixo: <strong><?php echo $estatisticas['estoque_baixo'] ?? 0; ?></strong></p>
                            <p>❌ Medicamentos sem estoque: <strong><?php echo $estatisticas['sem_estoque'] ?? 0; ?></strong></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Seção Perfil -->
            <div class="content-section" id="perfil">
                <h1>Perfil da Farmácia</h1>
                
                <?php if (!empty($sucesso)): ?>
                    <div class="alert alert-success"><?php echo $sucesso; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($erro)): ?>
                    <div class="alert alert-error"><?php echo $erro; ?></div>
                <?php endif; ?>
                
                <div class="profile-form">
                    <form method="POST">
                        <input type="hidden" name="acao" value="atualizar_perfil">
                        
                        <div class="form-group">
                            <label for="profile-name">Nome da Farmácia</label>
                            <input type="text" id="profile-name" name="nome" value="<?php echo htmlspecialchars($farmacia['nome']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="profile-address">Endereço</label>
                            <textarea id="profile-address" name="endereco" required><?php echo htmlspecialchars($farmacia['endereco']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="profile-phone">Telefone</label>
                            <input type="tel" id="profile-phone" name="telefone" value="<?php echo htmlspecialchars($farmacia['telefone']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="profile-email">E-mail</label>
                            <input type="email" id="profile-email" value="<?php echo htmlspecialchars($farmacia['email']); ?>" readonly style="background: #f5f5f5;">
                            <small>O e-mail não pode ser alterado.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="profile-schedule">Horário de Funcionamento</label>
                            <input type="text" id="profile-schedule" name="horario_funcionamento" value="<?php echo htmlspecialchars($farmacia['horario_funcionamento']); ?>" placeholder="Ex: Seg-Sex: 8h-20h, Sáb: 8h-18h">
                        </div>
                        
                        <div class="form-group checkbox-group">
                            <input type="checkbox" id="profile-24h" name="aberta_24h" <?php echo $farmacia['aberta_24h'] ? 'checked' : ''; ?>>
                            <label for="profile-24h">Aberta 24 horas</label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Atualizar Perfil</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para adicionar medicamento -->
    <div id="add-medicine-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Adicionar Medicamento ao Estoque</h2>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="add-medicine-form" method="POST">
                    <input type="hidden" name="acao" value="adicionar_medicamento">
                    
                    <div class="form-group">
                        <label for="medicamento_id">Selecionar Medicamento</label>
                        <select id="medicamento_id" name="medicamento_id" required>
                            <option value="">Selecione um medicamento...</option>
                            <?php foreach ($medicamentos as $med): ?>
                                <option value="<?php echo $med['id']; ?>">
                                    <?php echo htmlspecialchars($med['nome']); ?> - <?php echo htmlspecialchars($med['principio_ativo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="quantidade">Quantidade em Estoque</label>
                            <input type="number" id="quantidade" name="quantidade" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="preco">Preço (MZN)</label>
                            <input type="number" id="preco" name="preco" min="0" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-outline close-modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Adicionar ao Estoque</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal para editar medicamento -->
    <div id="edit-medicine-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Editar Medicamento no Estoque</h2>
                <span class="close-modal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="edit-medicine-form" method="POST">
                    <input type="hidden" name="acao" value="atualizar_estoque">
                    <input type="hidden" id="edit-estoque-id" name="estoque_id">
                    
                    <div class="form-group">
                        <label>Medicamento</label>
                        <input type="text" id="edit-medicine-name" readonly style="background: #f5f5f5;">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-quantidade">Quantidade em Estoque</label>
                            <input type="number" id="edit-quantidade" name="quantidade" min="0" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-preco">Preço (MZN)</label>
                            <input type="number" id="edit-preco" name="preco" min="0" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="button" class="btn btn-outline close-modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Atualizar Estoque</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/dashboard.js"></script>
</body>
</html>