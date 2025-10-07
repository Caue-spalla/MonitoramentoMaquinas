<?php
include("../conexao.php");
$type = $_GET['tipo'] ?? 'diario'; // diario, mensal, anual
$maquina_id = $_GET['maquina_id'] ?? 0;

switch($type){
    case 'diario':
        $table = 'consolidado_diario';
        $date_col = 'data';
        break;
    case 'mensal':
        $table = 'consolidado_mensal';
        $date_col = 'CONCAT(ano,"-",mes,"-01")';
        break;
    case 'anual':
        $table = 'consolidado_anual';
        $date_col = 'CONCAT(ano,"-01-01")';
        break;
    default:
        die(json_encode([]));
}

$where = $maquina_id > 0 ? "WHERE maquina_id=$maquina_id" : "";

$sql = "SELECT $date_col as data, percentual_atividade FROM $table $where ORDER BY $date_col ASC";
$result = $conn->query($sql);

$data = [];
while($row = $result->fetch_assoc()){
    $data[] = $row;
}

echo json_encode($data);
$conn->close();
?>
