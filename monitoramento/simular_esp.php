<?php

$url = "http://localhost/monitoramento/salvar.php";

// Dados simulados (m/s²)
$dados = [
    "ax" => 1.23,
    "ay" => 0.45,
    "az" => 9.80
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
