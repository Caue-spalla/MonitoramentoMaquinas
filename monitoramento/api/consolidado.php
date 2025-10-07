<?php
include("../conexao.php");

$type = $_GET['tipo'] ?? 'diario'; // diario, mensal, anual
$maquina_id = $_GET['maquina_id'] ?? 0;
$periodo = $_GET['periodo'] ?? 30; // número de dias/meses/anos

// Garantir que seja inteiro
$periodo = intval($periodo);
if($periodo < 1) $periodo = 30;

$where = $maquina_id > 0 ? "AND maquina_id=$maquina_id" : "";

// SQL dinâmico dependendo do tipo
switch($type){
    case 'diario':
        // Pega os últimos $periodo dias
        $sql = "SELECT data, percentual_atividade
                FROM consolidado_diario
                WHERE data >= CURDATE() - INTERVAL $periodo DAY $where
                ORDER BY data ASC";
        break;

    case 'mensal':
        // Pega os últimos $periodo meses
        $sql = "SELECT CONCAT(ano,'-',LPAD(mes,2,'0'),'-01') as data, percentual_atividade
                FROM consolidado_mensal
                WHERE DATE(CONCAT(ano,'-',LPAD(mes,2,'0'),'-01')) >= DATE_SUB(CURDATE(), INTERVAL $periodo MONTH) $where
                ORDER BY ano, mes ASC";
        break;

    case 'anual':
        // Pega os últimos $periodo anos
        $sql = "SELECT CONCAT(ano,'-01-01') as data, percentual_atividade
                FROM consolidado_anual
                WHERE ano >= YEAR(CURDATE()) - ($periodo - 1) $where
                ORDER BY ano ASC";
        break;

    default:
        die(json_encode([]));
}

$result = $conn->query($sql);

$data = [];
while($row = $result->fetch_assoc()){
    $data[] = $row;
}

echo json_encode($data);
$conn->close();
?>
