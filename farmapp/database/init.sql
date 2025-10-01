-- Criar base de dados
CREATE DATABASE IF NOT EXISTS farmapp;
USE farmapp;

-- Tabela de farmácias
CREATE TABLE IF NOT EXISTS farmacias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    endereco TEXT NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    telefone VARCHAR(20),
    email VARCHAR(255) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    horario_funcionamento TEXT,
    aberta_24h BOOLEAN DEFAULT FALSE,
    ativo BOOLEAN DEFAULT TRUE,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de medicamentos
CREATE TABLE IF NOT EXISTS medicamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL,
    principio_ativo VARCHAR(255),
    categoria VARCHAR(100),
    receita_obrigatoria BOOLEAN DEFAULT FALSE
);

-- Tabela de estoque das farmácias
CREATE TABLE IF NOT EXISTS estoque_farmacia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmacia_id INT,
    medicamento_id INT,
    quantidade INT DEFAULT 0,
    preco DECIMAL(10, 2),
    ultima_atualizacao TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id) ON DELETE CASCADE,
    FOREIGN KEY (medicamento_id) REFERENCES medicamentos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_estoque (farmacia_id, medicamento_id)
);

-- Tabela de horários especiais
CREATE TABLE IF NOT EXISTS horarios_especiais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    farmacia_id INT,
    data DATE NOT NULL,
    horario_abertura TIME,
    horario_fechamento TIME,
    fechado BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (farmacia_id) REFERENCES farmacias(id) ON DELETE CASCADE
);

-- Inserir dados de exemplo
INSERT INTO farmacias (nome, endereco, telefone, email, senha, horario_funcionamento) VALUES 
('Farmácia Central', 'Av. 25 de Setembro, 123 - Maputo', '+258 84 123 4567', 'central@farmacia.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Seg-Sex: 8h-20h, Sáb: 8h-18h'),
('Farmácia Popular', 'Rua da Sé, 456 - Maputo', '+258 85 234 5678', 'popular@farmacia.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Seg-Dom: 7h-23h'),
('Farmácia 24 Horas', 'Av. Eduardo Mondlane, 789 - Maputo', '+258 86 345 6789', '24horas@farmacia.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '24 horas');

-- Inserir medicamentos de exemplofarmappfarmaciasinformation_schemamysqlperformance_schemasyswordpress
INSERT INTO medicamentos (nome, principio_ativo, categoria) VALUES 
('Paracetamol', 'Paracetamol', 'Analgésico'),
('Ibuprofeno', 'Ibuprofeno', 'Anti-inflamatório'),
('Amoxicilina', 'Amoxicilina', 'Antibiótico'),
('Dipirona', 'Dipirona', 'Analgésico'),
('Omeprazol', 'Omeprazol', 'Gastrointestinal'),
('Losartana', 'Losartana', 'Cardiovascular'),
('Metformina', 'Metformina', 'Diabetes'),
('Sinvastatina', 'Sinvastatina', 'Colesterol'),
('Clonazepam', 'Clonazepam', 'Ansiolítico'),
('Sertralina', 'Sertralina', 'Antidepressivo');

-- Inserir estoque de exemplo
INSERT INTO estoque_farmacia (farmacia_id, medicamento_id, quantidade, preco) VALUES 
(1, 1, 50, 25.00),
(1, 2, 30, 35.50),
(1, 3, 20, 120.00),
(1, 4, 40, 18.00),
(2, 1, 25, 24.50),
(2, 3, 15, 115.00),
(2, 5, 10, 45.00),
(2, 6, 8, 85.00),
(3, 1, 100, 26.00),
(3, 2, 60, 36.00),
(3, 7, 12, 55.00),
(3, 8, 5, 95.00);farmacias