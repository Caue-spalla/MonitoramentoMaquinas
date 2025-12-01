<?php
// Se o usuário clicou em iniciar
if (isset($_POST["iniciar"])) {

    // Config banco
    $con = new mysqli("localhost", "root", "", "monitoramento_maquinas");
    if ($con->connect_error) {
        die("Erro ao conectar: " . $con->connect_error);
    }

    // Data recebida
    $dataInicio = new DateTime($_POST["data_inicio"]);
    $dataFim = new DateTime();
    $dataFim->setTime(0,0);

    $dias = $dataInicio->diff($dataFim)->days + 1;
    $totalReg = $dias * 300;
    $feitos = 0;

    // ENVIA CABEÇALHOS PARA STREAMING
    header("Content-Type: text/html; charset=UTF-8");
    header("Cache-Control: no-cache");
    header("X-Accel-Buffering: no"); // desativa buffering no NGINX (não prejudica no XAMPP)

    // Força flush
    ob_implicit_flush(true);
    ob_end_flush();

    ?>

    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
    <meta charset="UTF-8">
    <title>Gerando Histórico...</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body { background:#f6f8fa; color:#24292e; }
        .card { background:#ffffff; border:1px solid #d0d7de; }
        #log { background:#f1f3f5; height:300px; overflow-y:auto; padding:10px; border-radius:6px; }
        .progress { height:25px; }
    </style>
    </head>

    <body class="p-4">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">

                <div class="card p-4 shadow-sm">

                    <h3 class="mb-3">Gerando Histórico...</h3>

                    <p id="status">Iniciando...</p>

                    <div class="progress mb-3">
                        <div id="bar" class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%;">0%</div>
                    </div>

                    <h5>Logs:</h5>
                    <div id="log"></div>

                </div>

            </div>
        </div>
    </div>

    <?php

    // Funções para enviar log, atualizar status e barra
    function enviar($msg) {
        echo "<script>
                document.getElementById('log').innerHTML += '$msg<br>';
                document.getElementById('log').scrollTop = document.getElementById('log').scrollHeight;
              </script>";
        flush();
    }

    function atualizarStatus($texto) {
        echo "<script>document.getElementById('status').innerHTML = '$texto';</script>";
        flush();
    }

    function atualizarBarra($porc) {
        echo "<script>
                document.getElementById('bar').style.width = '$porc%';
                document.getElementById('bar').innerHTML = '$porc%';
              </script>";
        flush();
    }

    enviar("▶ Iniciando geração...");
    flush();

    // LOOP PRINCIPAL
    for ($d = 0; $d < $dias; $d++) {

        $dataAtual = clone $dataInicio;
        $dataAtual->modify("+$d day");
        $dataStr = $dataAtual->format("Y-m-d");

        atualizarStatus("Gerando dia <b>$dataStr</b>");
        enviar("📅 Gerando registros de $dataStr");

        for ($i = 0; $i < 300; $i++) {

            $id = rand(1,3);

            // Mais chance de vibrando: 70% vibrando, 30% parado
            $vib = rand(1,10) <= 7 ? 1 : 0;

            $h = rand(0,23);
            $m = rand(0,59);
            $s = rand(0,59);

            $dataHora = "$dataStr $h:$m:$s";

            $con->query("INSERT INTO leituras (maquina_id, vibrando, data_hora)
                         VALUES ($id, $vib, '$dataHora')");

            $feitos++;
            $porc = round(($feitos / $totalReg) * 100, 2);

            atualizarBarra($porc);
        }

        enviar("✔ Finalizado dia $dataStr");
    }

    atualizarStatus("<b>Finalizado! 🎉</b>");
    enviar("🎉 Processo concluído!");

    echo "</body></html>";
    exit;
}
?>

<!-- ================================
     FORMULÁRIO PRINCIPAL
================================= -->

<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Gerador de Histórico</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body { background:#f6f8fa; color:#24292e; }
.card { background:#ffffff; border:1px solid #d0d7de; }
</style>

</head>
<body class="p-4">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card p-4 shadow-sm">

                <h3 class="mb-3">Gerador de Histórico</h3>

                <form method="POST">

                    <label class="fw-bold">Data inicial:</label>
                    <input type="date" name="data_inicio" class="form-control mb-3" required>

                    <button name="iniciar" class="btn btn-primary w-100">
                        Iniciar geração
                    </button>

                </form>

            </div>

        </div>
    </div>
</div>

</body>
</html>
