<?php
include("conexao.php");
date_default_timezone_set('America/Sao_Paulo');

$sql_mensal = "INSERT INTO consolidado_mensal(maquina_id, ano, mes, percentual_atividade)
SELECT 
    maquina_id,
    YEAR(data_hora) AS ano,
    MONTH(data_hora) AS mes,
    AVG(vibrando) * 100 AS percentual_atividade
FROM leituras
GROUP BY maquina_id, YEAR(data_hora), MONTH(data_hora)
ON DUPLICATE KEY UPDATE percentual_atividade = VALUES(percentual_atividade)";

$conn->query($sql_mensal);
$conn->close();

echo 'ETL mensal concluído.';
?>
