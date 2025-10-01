<?php
// Coloque este arquivo na pasta principal do projeto
echo "<h2>Teste de Conexão com Base de Dados</h2>";

try {
    require_once 'config/database.php';
    
    $database = new Database();
    $conn = $database->getConnection();
    
    if ($conn) {
        echo "<p style='color: green;'>✅ Conexão com MySQL estabelecida com sucesso!</p>";
        
        // Testar se consegue acessar a base farmapp
        $stmt = $conn->query("SELECT DATABASE() as db_name");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Base de dados conectada: <strong>" . $result['db_name'] . "</strong></p>";
        
        // Contar tabelas
        $stmt = $conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Número de tabelas: <strong>" . count($tables) . "</strong></p>";
        
        echo "<h3>Tabelas encontradas:</h3>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
        
        // Testar dados das farmácias
        $stmt = $conn->query("SELECT COUNT(*) as total FROM farmacias");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Farmácias cadastradas: <strong>" . $result['total'] . "</strong></p>";
        
        // Listar farmácias
        $stmt = $conn->query("SELECT id, nome, email FROM farmacias");
        $farmacias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Farmácias:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Email</th></tr>";
        foreach ($farmacias as $farmacia) {
            echo "<tr>";
            echo "<td>" . $farmacia['id'] . "</td>";
            echo "<td>" . $farmacia['nome'] . "</td>";
            echo "<td>" . $farmacia['email'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>❌ Falha na conexão com a base de dados</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erro de conexão: " . $e->getMessage() . "</p>";
    echo "<p>Verifique se:</p>";
    echo "<ul>";
    echo "<li>MySQL está rodando no Laragon</li>";
    echo "<li>A base 'farmapp' existe</li>";
    echo "<li>As credenciais em config/database.php estão corretas</li>";
    echo "</ul>";
}
?>