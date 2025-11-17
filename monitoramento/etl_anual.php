<?php
include("conexao.php");
date_default_timezone_set('America/Sao_Paulo');

$sql_anual = "INSERT INTO consolidado_anual(maquina_id, ano, percentual_atividade)
SELECT 
    maquina_id,
    YEAR(data_hora) AS ano,
    AVG(vibrando) * 100 AS percentual_atividade
FROM leituras
GROUP BY maquina_id, YEAR(data_hora)
ON DUPLICATE KEY UPDATE percentual_atividade = VALUES(percentual_atividade)";

$conn->query($sql_anual);
$conn->close();

echo "ETL anual concluído.\n";
?>
