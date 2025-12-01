<?php
header('Content-Type: application/json; charset=utf-8');

ini_set('session.cookie_lifetime', 0); 
ini_set('session.gc_maxlifetime', 3600);
session_start();

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    http_response_code(401); 
    
    echo json_encode([
        "error" => "Login required", 
        "message" => "Sua sessão expirou ou o acesso não foi autorizado."
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

include("../conexao.php");

$tipo = $_GET['tipo'] ?? 'diario';
$maquina_id = intval($_GET['maquina_id'] ?? 0);
$data_inicio = $_GET['inicio'] ?? null;
$data_fim = $_GET['fim'] ?? null;

if (!$data_inicio || !$data_fim) {
    echo json_encode([]);
    exit;
}

$saida = [];

switch ($tipo) {

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


    case 'mensal':
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

echo json_encode($saida, JSON_UNESCAPED_UNICODE);
$conn->close();