<?php
include("conexao.php");
date_default_timezone_set('America/Sao_Paulo');

$sql_anual = "INSERT INTO consolidado_anual(maquina_id,ano,percentual_atividade)
SELECT maquina_id, YEAR(timestamp) as ano, AVG(vibrando)*100
FROM leituras
GROUP BY maquina_id, YEAR(timestamp)
ON DUPLICATE KEY UPDATE percentual_atividade=VALUES(percentual_atividade)";

$conn->query($sql_anual);
$conn->close();
echo "ETL anual concluído.\n";
?>
