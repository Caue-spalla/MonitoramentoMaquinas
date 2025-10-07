<?php
include("conexao.php");
date_default_timezone_set('America/Sao_Paulo');

$sql_diario = "INSERT INTO consolidado_diario(maquina_id,data,percentual_atividade)
SELECT maquina_id, DATE(timestamp) as data, AVG(vibrando)*100
FROM leituras
GROUP BY maquina_id, DATE(timestamp)
ON DUPLICATE KEY UPDATE percentual_atividade=VALUES(percentual_atividade)";

$conn->query($sql_diario);
$conn->close();
echo "ETL diário concluído.\n";
?>
