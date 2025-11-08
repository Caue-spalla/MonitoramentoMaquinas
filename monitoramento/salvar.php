<?php

// Permite requisições do ESP32
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Confirma que o método é POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["erro" => "Método não permitido. Use POST."]);
    exit;
}

// Conecta ao banco
include_once "conexao.php";

// Lê o corpo bruto da requisição (JSON enviado pelo ESP)
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Valida o JSON
if (!$data || !isset($data["ax"]) || !isset($data["ay"]) || !isset($data["az"])) {
    http_response_code(400);
    echo json_encode(["erro" => "JSON inválido ou campos ausentes."]);
    exit;
}

// Extrai e sanitiza os dados
$ax = floatval($data["ax"]);
$ay = floatval($data["ay"]);
$az = floatval($data["az"]);

// Prepara a query (uso seguro de prepared statement)
$stmt = $conn->prepare("INSERT INTO dados (ax, ay, az, data_hora) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("ddd", $ax, $ay, $az);

if ($stmt->execute()) {
    http_response_code(200);
    echo json_encode(["status" => "ok", "mensagem" => "Dados salvos com sucesso!"]);
} else {
    http_response_code(500);
    echo json_encode(["erro" => "Falha ao inserir dados no banco."]);
}

$stmt->close();
$conn->close();
?>
