<?php
include("conexao.php");
date_default_timezone_set('America/Sao_Paulo');

// Detecta GET ou POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $maquina_id = $data['maquina_id'] ?? 0;
    $vibrando = $data['vibrando'] ?? 0;
} else {
    $maquina_id = $_GET['maquina_id'] ?? 0;
    $vibrando = $_GET['vibrando'] ?? 0;
}

if($maquina_id > 0){
    $sql = "INSERT INTO leituras (maquina_id, vibrando, timestamp) 
            VALUES ($maquina_id, $vibrando, NOW())";
    $conn->query($sql);
    echo json_encode(['status'=>'ok','maquina_id'=>$maquina_id,'vibrando'=>$vibrando]);
} else {
    echo json_encode(['status'=>'error','message'=>'maquina_id inválido']);
}

$conn->close();
?>
