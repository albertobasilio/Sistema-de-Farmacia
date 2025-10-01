<?php
require_once '../config/database.php';

$medicamento_busca = isset($_GET['medicamento']) ? trim($_GET['medicamento']) : '';
$resultados = [];
$total_resultados = 0;
$mensagem = '';

if (!empty($medicamento_busca)) {
    $database = new Database();
    $db = $database->getConnection();
    
    // Buscar medicamentos que correspondem à pesquisa
    $query = "SELECT 
                m.id as medicamento_id,
                m.nome as medicamento_nome,
                m.principio_ativo,
                m.categoria,
                f.id as farmacia_id,
                f.nome as farmacia_nome,
                f.endereco as farmacia_endereco,
                f.telefone as farmacia_telefone,
                f.horario_funcionamento,
                f.aberta_24h,
                e.quantidade,
                e.preco,
                e.ultima_atualizacao
              FROM medicamentos m
              JOIN estoque_farmacia e ON m.id = e.medicamento_id
              JOIN farmacias f ON e.farmacia_id = f.id
              WHERE (m.nome LIKE :medicamento OR m.principio_ativo LIKE :medicamento)
                AND e.quantidade > 0 
                AND f.ativo = 1
              ORDER BY f.nome, e.quantidade DESC";
    
    $stmt = $db->prepare($query);
    $termo_busca = '%' . $medicamento_busca . '%';
    $stmt->bindParam(':medicamento', $termo_busca);
    $stmt->execute();
    
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total_resultados = count($resultados);
    
    // Agrupar resultados por farmácia para melhor organização
    $farmacias_agrupadas = [];
    foreach ($resultados as $resultado) {
        $farmacia_id = $resultado['farmacia_id'];
        if (!isset($farmacias_agrupadas[$farmacia_id])) {
            $farmacias_agrupadas[$farmacia_id] = [
                'farmacia_id' => $resultado['farmacia_id'],
                'farmacia_nome' => $resultado['farmacia_nome'],
                'farmacia_endereco' => $resultado['farmacia_endereco'],
                'farmacia_telefone' => $resultado['farmacia_telefone'],
                'horario_funcionamento' => $resultado['horario_funcionamento'],
                'aberta_24h' => $resultado['aberta_24h'],
                'medicamentos' => []
            ];
        }
        
        $farmacias_agrupadas[$farmacia_id]['medicamentos'][] = [
            'medicamento_id' => $resultado['medicamento_id'],
            'medicamento_nome' => $resultado['medicamento_nome'],
            'principio_ativo' => $resultado['principio_ativo'],
            'categoria' => $resultado['categoria'],
            'quantidade' => $resultado['quantidade'],
            'preco' => $resultado['preco'],
            'ultima_atualizacao' => $resultado['ultima_atualizacao']
        ];
    }
    
    $resultados = array_values($farmacias_agrupadas);
    
    if ($total_resultados === 0) {
        $mensagem = "Nenhum resultado encontrado para \"{$medicamento_busca}\". Tente outros termos como Paracetamol, Amoxicilina, etc.";
    }
} else {
    header('Location: index.html');
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmApp - Resultados da Busca</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .search-info {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .results-count {
            font-size: 18px;
            color: #333;
            margin-bottom: 10px;
        }
        .search-term {
            color: #4a6ee0;
            font-weight: bold;
        }
        .new-search {
            margin-top: 20px;
        }
        .medicine-match {
            background: #e8f4ff;
            padding: 8px 12px;
            border-radius: 5px;
            margin: 5px 0;
            border-left: 4px solid #4a6ee0;
        }
        .stock-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .stock-high { background: #27ae60; }
        .stock-medium { background: #f39c12; }
        .stock-low { background: #e74c3c; }
        .last-update {
            font-size: 12px;
            color: #666;
            font-style: italic;
        }
        .contact-info {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        .contact-info p {
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
                    <li><a href="index.html">Nova Busca</a></li>
                    <li><a href="farmacias-proximas.php">Farmácias Próximas</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="search-section" style="padding: 120px 0 40px; background: #f5f7fa;">
        <div class="container">
            <div class="search-info">
                <div class="results-count">
                    <?php if ($total_resultados > 0): ?>
                        <strong><?php echo $total_resultados; ?></strong> resultado(s) encontrado(s) para: 
                        <span class="search-term">"<?php echo htmlspecialchars($medicamento_busca); ?>"</span>
                    <?php else: ?>
                        Nenhum resultado para: <span class="search-term">"<?php echo htmlspecialchars($medicamento_busca); ?>"</span>
                    <?php endif; ?>
                </div>
                
                <form class="search-box" method="GET" action="buscar-medicamentos.php" style="max-width: 500px; margin: 0;">
                    <input type="text" name="medicamento" value="<?php echo htmlspecialchars($medicamento_busca); ?>" placeholder="Buscar outro medicamento..." required>
                    <button type="submit" class="btn">Buscar</button>
                </form>
                
                <div class="new-search">
                    <a href="index.html" class="btn btn-outline">← Voltar para busca principal</a>
                </div>
            </div>
        </div>
    </section>

    <section class="results-section">
        <div class="container">
            <?php if (!empty($mensagem)): ?>
                <div class="no-results">
                    <h3>😔 Nenhum resultado encontrado</h3>
                    <p><?php echo $mensagem; ?></p>
                    <div class="suggestions">
                        <h4>Sugestões:</h4>
                        <ul>
                            <li>Verifique a ortografia do medicamento</li>
                            <li>Tente buscar pelo princípio ativo</li>
                            <li>Use termos mais genéricos como "analgésico" ou "antibiótico"</li>
                            <li>Consulte <a href="farmacias-proximas.php">farmácias próximas</a> para ver todos os medicamentos disponíveis</li>
                        </ul>
                    </div>
                </div>
            <?php elseif ($total_resultados > 0): ?>
                <div class="results-grid">
                    <?php foreach ($resultados as $farmacia): ?>
                        <div class="pharmacy-card">
                            <div class="pharmacy-header">
                                <div class="pharmacy-name"><?php echo htmlspecialchars($farmacia['farmacia_nome']); ?></div>
                                <div class="pharmacy-address">📍 <?php echo htmlspecialchars($farmacia['farmacia_endereco']); ?></div>
                                <?php if ($farmacia['aberta_24h']): ?>
                                    <div class="pharmacy-status" style="color: #27ae60; margin-top: 5px;">🕒 Aberta 24 horas</div>
                                <?php else: ?>
                                    <div class="pharmacy-status" style="margin-top: 5px;">🕒 <?php echo htmlspecialchars($farmacia['horario_funcionamento']); ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="pharmacy-body">
                                <h4 style="margin-bottom: 15px; color: #333;">Medicamentos Disponíveis:</h4>
                                <?php foreach ($farmacia['medicamentos'] as $medicamento): ?>
                                    <div class="medicine-match">
                                        <div style="display: flex; justify-content: between; align-items: center;">
                                            <div style="flex: 1;">
                                                <strong><?php echo htmlspecialchars($medicamento['medicamento_nome']); ?></strong>
                                                <?php if (!empty($medicamento['principio_ativo'])): ?>
                                                    <br><small>Princípio ativo: <?php echo htmlspecialchars($medicamento['principio_ativo']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <div style="text-align: right;">
                                                <div style="display: flex; align-items: center; justify-content: flex-end; margin-bottom: 5px;">
                                                    <?php
                                                    $stock_class = 'stock-high';
                                                    $stock_text = 'Disponível';
                                                    if ($medicamento['quantidade'] < 10) {
                                                        $stock_class = 'stock-medium';
                                                        $stock_text = 'Estoque baixo';
                                                    } elseif ($medicamento['quantidade'] < 5) {
                                                        $stock_class = 'stock-low';
                                                        $stock_text = 'Últimas unidades';
                                                    }
                                                    ?>
                                                    <span class="stock-indicator <?php echo $stock_class; ?>"></span>
                                                    <span style="font-size: 12px; color: #666;"><?php echo $stock_text; ?></span>
                                                </div>
                                                <div style="font-weight: bold; color: #27ae60; font-size: 18px;">
                                                    <?php echo number_format($medicamento['preco'], 2, ',', '.'); ?> MZN
                                                </div>
                                            </div>
                                        </div>
                                        <div class="last-update">
                                            Atualizado: <?php echo date('d/m/Y H:i', strtotime($medicamento['ultima_atualizacao'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="pharmacy-footer">
                                <div class="contact-info">
                                    <p>📞 <?php echo htmlspecialchars($farmacia['farmacia_telefone']); ?></p>
                                    <p>💊 <?php echo count($farmacia['medicamentos']); ?> medicamento(s) encontrado(s)</p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="how-it-works" style="background: #f5f7fa;">
        <div class="container">
            <h2>Não encontrou o que procurava?</h2>
            <div class="steps">
                <div class="step">
                    <div class="step-number">🔍</div>
                    <h3>Tente outra busca</h3>
                    <p>Use termos diferentes ou busque pelo princípio ativo</p>
                    <a href="index.html" class="btn-small">Nova Busca</a>
                </div>
                <div class="step">
                    <div class="step-number">🏪</div>
                    <h3>Veja farmácias próximas</h3>
                    <p>Consulte todas as farmácias e seus medicamentos</p>
                    <a href="farmacias-proximas.php" class="btn-small">Farmácias Próximas</a>
                </div>
                <div class="step">
                    <div class="step-number">💡</div>
                    <h3>Sugira um medicamento</h3>
                    <p>Nos diga qual medicamento gostaria de encontrar</p>
                    <a href="mailto:contato@farmapp.co.mz" class="btn-small">Sugerir</a>
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
                    <a href="index.html">Buscar Medicamentos</a>
                    <a href="farmacias-proximas.php">Farmácias Próximas</a>
                </div>
                <div class="footer-section">
                    <h3>Ajuda</h3>
                    <a href="mailto:contato@farmapp.co.mz">Contato</a>
                    <a href="mailto:suporte@farmapp.co.mz">Suporte</a>
                </div>
            </div>
            <div class="copyright">
                &copy; 2023 FarmApp. Todos os direitos reservados.
            </div>
        </div>
    </footer>
</body>
</html>