<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite requisições externas
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include 'conexao.php'; // garante que $conn existe

// Lê o corpo JSON
$data = json_decode(file_get_contents('php://input'), true);

// Verifica se os campos obrigatórios existem
if (!isset($data['maquina_id']) || !isset($data['vibrando'])) {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Campos obrigatórios ausentes: maquina_id e vibrando.'
    ]);
    exit;
}

$maquina_id = intval($data['maquina_id']);
$vibrando = intval($data['vibrando']);

// Prepara e executa o insert
$sql = "INSERT INTO leituras (maquina_id, vibrando, timestamp) VALUES (?, ?, NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $maquina_id, $vibrando);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'sucesso',
        'mensagem' => 'Leitura registrada com sucesso.'
    ]);
} else {
    echo json_encode([
        'status' => 'erro',
        'mensagem' => 'Falha ao inserir leitura: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
