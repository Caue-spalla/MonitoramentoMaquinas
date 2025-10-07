<?php
include("conexao.php");
date_default_timezone_set('America/Sao_Paulo');

$sql_mensal = "INSERT INTO consolidado_mensal(maquina_id,ano,mes,percentual_atividade)
SELECT maquina_id, YEAR(timestamp) as ano, MONTH(timestamp) as mes, AVG(vibrando)*100
FROM leituras
GROUP BY maquina_id, YEAR(timestamp), MONTH(timestamp)
ON DUPLICATE KEY UPDATE percentual_atividade=VALUES(percentual_atividade)";

$conn->query($sql_mensal);
$conn->close();
echo "ETL mensal concluído.\n";
?>
