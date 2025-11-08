<?php

$url = "http://localhost/monitoramento/salvar.php";

// Dados simulados
$dados = [
    "maquina_id" => 1,   // ID da máquina cadastrada no banco
    "vibrando" => rand(0, 1) // 0 = parado, 1 = vibrando
];

$options = [
    "http" => [
        "header"  => "Content-Type: application/json\r\n",
        "method"  => "POST",
        "content" => json_encode($dados)
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "Erro ao enviar requisição.\n";
} else {
    echo "Resposta do servidor: $result\n";
}
?>
