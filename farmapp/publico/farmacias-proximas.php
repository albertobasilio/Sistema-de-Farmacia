<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Buscar farmácias para estatísticas
$query = "SELECT 
            f.id,
            f.nome,
            f.latitude,
            f.longitude,
            COUNT(e.id) as total_medicamentos
          FROM farmacias f
          LEFT JOIN estoque_farmacia e ON f.id = e.farmacia_id AND e.quantidade > 0
          WHERE f.ativo = 1 AND f.latitude IS NOT NULL
          GROUP BY f.id";

$stmt = $db->prepare($query);
$stmt->execute();
$farmacias_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_farmacias = count($farmacias_stats);
$total_medicamentos = array_sum(array_column($farmacias_stats, 'total_medicamentos'));
$farmacias_com_gps = count(array_filter($farmacias_stats, function($f) {
    return !is_null($f['latitude']) && !is_null($f['longitude']);
}));
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmApp - Farmácias Próximas</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .location-controls {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .location-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .filter-controls {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filter-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .filter-group label {
            font-weight: 500;
            color: #333;
        }
        .filter-group select, .filter-group input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .location-status {
            margin-top: 10px;
            padding: 10px;
            border-radius: 5px;
            display: none;
        }
        .location-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .location-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .location-loading {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        .distance-badge {
            background: #4a6ee0;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        .pharmacy-marker {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 5px;
        }
        .sort-options {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }
        .sort-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .sort-btn.active {
            background: #4a6ee0;
            color: white;
            border-color: #4a6ee0;
        }
        .no-gps-warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
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
                    <li><a href="index.html">Buscar Medicamentos</a></li>
                    <li><a href="../farmacias/login.php">Área da Farmácia</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="search-section" style="padding: 120px 0 40px; background: #f5f7fa;">
        <div class="container">
            <h1 style="text-align: center; color: #333; margin-bottom: 10px;">📍 Farmácias Próximas</h1>
            <p style="text-align: center; color: #666; max-width: 600px; margin: 0 auto 30px;">
                Encontre farmácias perto de você usando sua localização
            </p>
            
            <!-- Controles de Localização -->
            <div class="location-controls">
                <h3 style="margin-bottom: 15px;">🌍 Usar Minha Localização</h3>
                
                <div class="location-buttons">
                    <button id="get-location-btn" class="btn">
                        📍 Usar Minha Localização Atual
                    </button>
                    <button id="manual-location-btn" class="btn btn-outline">
                        🗺️ Inserir Localização Manual
                    </button>
                </div>
                
                <div class="filter-controls">
                    <div class="filter-group">
                        <label for="raio-filter">Raio de busca:</label>
                        <select id="raio-filter">
                            <option value="5">5 km</option>
                            <option value="10" selected>10 km</option>
                            <option value="15">15 km</option>
                            <option value="20">20 km</option>
                            <option value="50">50 km</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="medicamento-filter">Filtrar por medicamento:</label>
                        <input type="text" id="medicamento-filter" placeholder="Ex: Paracetamol">
                    </div>
                    
                    <button id="apply-filters-btn" class="btn btn-primary">Aplicar Filtros</button>
                </div>
                
                <div id="location-status" class="location-status"></div>
            </div>
            
            <!-- Opções de Ordenação -->
            <div class="sort-options">
                <button class="sort-btn active" data-sort="distance">📍 Mais Próximas</button>
                <button class="sort-btn" data-sort="name">🔤 Ordem Alfabética</button>
                <button class="sort-btn" data-sort="medicines">💊 Mais Medicamentos</button>
            </div>

            <div class="stats-overview">
                <h2>Estatísticas do FarmApp</h2>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $total_farmacias; ?></div>
                        <div class="stat-label">Farmácias Cadastradas</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $total_medicamentos; ?></div>
                        <div class="stat-label">Medicamentos Disponíveis</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $farmacias_com_gps; ?></div>
                        <div class="stat-label">Com Localização</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Disponibilidade</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="results-section">
        <div class="container">
            <div id="results-container">
                <div class="no-results">
                    <h3>👆 Use os controles acima para encontrar farmácias próximas</h3>
                    <p>Clique em "Usar Minha Localização Atual" para ver farmácias perto de você.</p>
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
                    <a href="../farmacias/login.php">Área da Farmácia</a>
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

    <script src="js/geolocalizacao.js"></script>
</body>
</html>