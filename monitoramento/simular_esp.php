<?php
include("conexao.php");
date_default_timezone_set('America/Sao_Paulo');

// IDs das máquinas existentes
$maquinas = [1,2]; 

foreach($maquinas as $m){
    $vibrando = rand(0,1); // aleatório
    $sql = "INSERT INTO leituras (maquina_id, vibrando, timestamp) 
            VALUES ($m, $vibrando, NOW())";
    $conn->query($sql);
    echo "Máquina $m: ".($vibrando ? "vibrando" : "parada")." inserida\\n";
}

$conn->close();
?>
