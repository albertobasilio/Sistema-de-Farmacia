<?php
session_start();
require_once '../config/database.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['farmacia_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $medicamento_id = $_POST['medicamento_id'];
    $quantidade = $_POST['quantidade'];
    $preco = $_POST['preco'];
    
    // Verificar se o medicamento existe
    $query = "SELECT id FROM medicamentos WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $medicamento_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // Criar novo medicamento
        $query = "INSERT INTO medicamentos (nome, principio_ativo) VALUES (:nome, :principio_ativo)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':nome', $_POST['nome']);
        $stmt->bindParam(':principio_ativo', $_POST['principio_ativo']);
        $stmt->execute();
        
        $medicamento_id = $db->lastInsertId();
    }
    
    // Atualizar estoque
    $query = "INSERT INTO estoque_farmacia (farmacia_id, medicamento_id, quantidade, preco) 
             VALUES (:farmacia_id, :medicamento_id, :quantidade, :preco)
             ON DUPLICATE KEY UPDATE quantidade = :quantidade, preco = :preco";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':farmacia_id', $_SESSION['farmacia_id']);
    $stmt->bindParam(':medicamento_id', $medicamento_id);
    $stmt->bindParam(':quantidade', $quantidade);
    $stmt->bindParam(':preco', $preco);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Estoque atualizado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar estoque']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
}
?>