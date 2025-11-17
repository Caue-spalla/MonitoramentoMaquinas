<?php
include("conexao.php");
date_default_timezone_set('America/Sao_Paulo');

// Só permite POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Método não permitido
    echo json_encode([
        'status' => 'error',
        'message' => 'Use POST com JSON.'
    ]);
    exit;
}

// Lê JSON do ESP
$data = json_decode(file_get_contents('php://input'), true);

$maquina_id = $data['maquina_id'] ?? null;
$vibrando   = $data['vibrando'] ?? null;

// Valida campos obrigatórios
if (!$maquina_id || $vibrando === null) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Campos obrigatórios: maquina_id, vibrando.'
    ]);
    exit;
}

// Insere no banco
$sql = "INSERT INTO leituras (maquina_id, vibrando, data_hora)
        VALUES (?, ?, NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $maquina_id, $vibrando);
$stmt->execute();

echo json_encode([
    'status' => 'ok',
    'maquina_id' => $maquina_id,
    'vibrando' => $vibrando
]);

$conn->close();
?>
