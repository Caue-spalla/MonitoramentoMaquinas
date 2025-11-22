<?php
header('Content-Type: application/json; charset=utf-8');
include("../conexao.php");

// Parâmetros
$tipo = $_GET['tipo'] ?? 'diario';
$maquina_id = intval($_GET['maquina_id'] ?? 0);
$data_inicio = $_GET['inicio'] ?? null;
$data_fim = $_GET['fim'] ?? null;

// Valida as datas
if (!$data_inicio || !$data_fim) {
    echo json_encode([]);
    exit;
}

$saida = [];

switch ($tipo) {

    // ------------------------------------------------------
    // 🔹 CONSOLIDADO DIÁRIO
    // ------------------------------------------------------
    case 'diario':
        $sql = "
            SELECT 
                data,
                percentual_atividade
            FROM consolidado_diario
            WHERE data BETWEEN '$data_inicio' AND '$data_fim'
        ";

        if ($maquina_id > 0) {
            $sql .= " AND maquina_id = $maquina_id";
        }

        $sql .= " ORDER BY data ASC";

        $result = $conn->query($sql);

        while ($row = $result->fetch_assoc()) {
            $saida[] = [
                "data" => $row["data"],
                "percentual_atividade" => floatval($row["percentual_atividade"])
            ];
        }
        break;


    // ------------------------------------------------------
    // 🔹 CONSOLIDADO MENSAL
    // ------------------------------------------------------
    case 'mensal':
        // Ano-mês início e fim
        // (Ex: 2025-01)
        $ano_inicio = intval(substr($data_inicio, 0, 4));
        $mes_inicio = intval(substr($data_inicio, 5, 2));

        $ano_fim = intval(substr($data_fim, 0, 4));
        $mes_fim = intval(substr($data_fim, 5, 2));

        $sql = "
            SELECT 
                ano,
                mes,
                percentual_atividade
            FROM consolidado_mensal
            WHERE (ano > $ano_inicio OR (ano = $ano_inicio AND mes >= $mes_inicio))
            AND (ano < $ano_fim OR (ano = $ano_fim AND mes <= $mes_fim))
        ";

        if ($maquina_id > 0) {
            $sql .= " AND maquina_id = $maquina_id";
        }

        $sql .= " ORDER BY ano ASC, mes ASC";

        $result = $conn->query($sql);

        while ($row = $result->fetch_assoc()) {
            $saida[] = [
                "data" => $row["ano"] . "-" . str_pad($row["mes"], 2, "0", STR_PAD_LEFT),
                "percentual_atividade" => floatval($row["percentual_atividade"])
            ];
        }
        break;


    // ------------------------------------------------------
    // 🔹 CONSOLIDADO ANUAL
    // ------------------------------------------------------
    case 'anual':
        $ano_inicio = intval(substr($data_inicio, 0, 4));
        $ano_fim = intval(substr($data_fim, 0, 4));

        $sql = "
            SELECT 
                ano,
                percentual_atividade
            FROM consolidado_anual
            WHERE ano BETWEEN $ano_inicio AND $ano_fim
        ";

        if ($maquina_id > 0) {
            $sql .= " AND maquina_id = $maquina_id";
        }

        $sql .= " ORDER BY ano ASC";

        $result = $conn->query($sql);

        while ($row = $result->fetch_assoc()) {
            $saida[] = [
                "data" => strval($row["ano"]),
                "percentual_atividade" => floatval($row["percentual_atividade"])
            ];
        }
        break;
}

// Saída final
echo json_encode($saida, JSON_UNESCAPED_UNICODE);
$conn->close();
