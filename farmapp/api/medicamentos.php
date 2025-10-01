<?php
// api/medicamentos.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';

$method = $_SERVER['REQUEST_METHOD'];

// Conexão com a base de dados
$database = new Database();
$db = $database->getConnection();

switch($method) {
    case 'GET':
        // Buscar medicamentos
        if(isset($_GET['search']) && !empty($_GET['search'])) {
            $search = $_GET['search'];
            $query = "SELECT m.*, e.quantidade, e.preco, f.nome as farmacia_nome, f.endereco as farmacia_endereco 
                     FROM medicamentos m 
                     JOIN estoque_farmacia e ON m.id = e.medicamento_id 
                     JOIN farmacias f ON e.farmacia_id = f.id 
                     WHERE m.nome LIKE :search AND e.quantidade > 0 AND f.ativo = 1";
            
            $stmt = $db->prepare($query);
            $stmt->bindValue(':search', '%' . $search . '%');
            $stmt->execute();
            
            $medicamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($medicamentos);
        }
        break;
        
    case 'POST':
        // Adicionar medicamento ao estoque (para farmácias)
        $data = json_decode(file_get_contents('php://input'), true);
        
        if(isset($data['farmacia_id']) && isset($data['medicamento_id']) && isset($data['quantidade'])) {
            $query = "INSERT INTO estoque_farmacia (farmacia_id, medicamento_id, quantidade, preco) 
                     VALUES (:farmacia_id, :medicamento_id, :quantidade, :preco)
                     ON DUPLICATE KEY UPDATE quantidade = :quantidade, preco = :preco";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':farmacia_id', $data['farmacia_id']);
            $stmt->bindParam(':medicamento_id', $data['medicamento_id']);
            $stmt->bindParam(':quantidade', $data['quantidade']);
            $stmt->bindParam(':preco', $data['preco']);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Estoque atualizado com sucesso']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao atualizar estoque']);
            }
        }
        break;
}
?>