<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

// Função para calcular distância entre duas coordenadas (Haversine)
function calcularDistancia($lat1, $lon1, $lat2, $lon2) {
    $raioTerra = 6371; // Raio da Terra em quilômetros
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) + 
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
         sin($dLon/2) * sin($dLon/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $raioTerra * $c; // Distância em km
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $database = new Database();
    $db = $database->getConnection();
    
    // Obter parâmetros
    $userLat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
    $userLng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;
    $raio = isset($_GET['raio']) ? floatval($_GET['raio']) : 10; // Raio padrão 10km
    $medicamento = isset($_GET['medicamento']) ? $_GET['medicamento'] : '';
    
    // Buscar farmácias com estoque
    if (!empty($medicamento)) {
        // Busca específica por medicamento
        $query = "SELECT 
                    f.id,
                    f.nome,
                    f.endereco,
                    f.telefone,
                    f.latitude,
                    f.longitude,
                    f.horario_funcionamento,
                    f.aberta_24h,
                    m.nome as medicamento_nome,
                    m.principio_ativo,
                    e.quantidade,
                    e.preco,
                    e.ultima_atualizacao
                  FROM farmacias f
                  JOIN estoque_farmacia e ON f.id = e.farmacia_id
                  JOIN medicamentos m ON e.medicamento_id = m.id
                  WHERE (m.nome LIKE :medicamento OR m.principio_ativo LIKE :medicamento)
                    AND e.quantidade > 0
                    AND f.ativo = 1
                    AND f.latitude IS NOT NULL 
                    AND f.longitude IS NOT NULL";
    } else {
        // Todas as farmácias com estoque
        $query = "SELECT 
                    f.id,
                    f.nome,
                    f.endereco,
                    f.telefone,
                    f.latitude,
                    f.longitude,
                    f.horario_funcionamento,
                    f.aberta_24h,
                    COUNT(e.id) as total_medicamentos,
                    SUM(e.quantidade) as total_estoque
                  FROM farmacias f
                  LEFT JOIN estoque_farmacia e ON f.id = e.farmacia_id AND e.quantidade > 0
                  WHERE f.ativo = 1 
                    AND f.latitude IS NOT NULL 
                    AND f.longitude IS NOT NULL
                  GROUP BY f.id";
    }
    
    $stmt = $db->prepare($query);
    if (!empty($medicamento)) {
        $termo = '%' . $medicamento . '%';
        $stmt->bindParam(':medicamento', $termo);
    }
    $stmt->execute();
    
    $farmacias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular distâncias se coordenadas do usuário foram fornecidas
    if ($userLat && $userLng) {
        foreach ($farmacias as &$farmacia) {
            $distancia = calcularDistancia(
                $userLat, 
                $userLng, 
                floatval($farmacia['latitude']), 
                floatval($farmacia['longitude'])
            );
            
            $farmacia['distancia_km'] = round($distancia, 2);
            $farmacia['distancia_texto'] = $distancia < 1 ? 
                round($distancia * 1000) . ' m' : 
                round($distancia, 1) . ' km';
        }
        
        // Ordenar por distância
        usort($farmacias, function($a, $b) {
            return $a['distancia_km'] <=> $b['distancia_km'];
        });
        
        // Filtrar por raio
        $farmacias = array_filter($farmacias, function($farmacia) use ($raio) {
            return $farmacia['distancia_km'] <= $raio;
        });
        
        $farmacias = array_values($farmacias); // Reindexar array
    }
    
    echo json_encode([
        'success' => true,
        'farmacias' => $farmacias,
        'total' => count($farmacias),
        'user_location' => $userLat && $userLng ? [
            'latitude' => $userLat,
            'longitude' => $userLng
        ] : null,
        'filters' => [
            'raio_km' => $raio,
            'medicamento' => $medicamento
        ]
    ]);
    
} elseif ($method === 'POST') {
    // Endpoint para obter localização do usuário via POST
    $data = json_decode(file_get_contents('php://input'), true);
    
    $userLat = isset($data['latitude']) ? floatval($data['latitude']) : null;
    $userLng = isset($data['longitude']) ? floatval($data['longitude']) : null;
    
    if ($userLat && $userLng) {
        echo json_encode([
            'success' => true,
            'message' => 'Localização recebida',
            'location' => [
                'latitude' => $userLat,
                'longitude' => $userLng
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Coordenadas inválidas'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido'
    ]);
}
?>