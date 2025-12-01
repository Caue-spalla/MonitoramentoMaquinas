<?php
ini_set('session.cookie_lifetime', 0);
ini_set('session.gc_maxlifetime', 3600);
session_start();

$login_esperado = "admin";
$senha_esperada = "admin";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $login_enviado = $_POST['login'] ?? '';
    $senha_enviada = $_POST['senha'] ?? '';

    if ($login_enviado === $login_esperado && $senha_enviada === $senha_esperada) {
        $_SESSION['logado'] = true;
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $erro_login = "Login ou senha incorretos.";
    }
}

$esta_logado = $_SESSION['logado'] ?? false;

include("conexao.php");

$maquinas_result = $conn->query("SELECT id, nome FROM maquinas");
$maquinas = [];
while ($row = $maquinas_result->fetch_assoc()) {
    $maquinas[] = $row;
}

$range_result = $conn->query("
    SELECT 
        MIN(DATE(data_hora)) AS inicio,
        MAX(DATE(data_hora)) AS fim
    FROM leituras
");

$range = $range_result->fetch_assoc();

$dataInicioDefault = $range["inicio"] ?? date("Y-m-d");
$dataFimDefault = $range["fim"] ?? date("Y-m-d");

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title>Dashboard de Monitoramento</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
<script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<script src="https://code.highcharts.com/highcharts.js"></script>
<script src="https://code.highcharts.com/modules/exporting.js"></script>
<script src="https://code.highcharts.com/modules/accessibility.js"></script>
<script src="https://code.highcharts.com/modules/stock.js"></script>

<style>
body { background-color: #f8f9fa; }
.card { margin-bottom: 20px; }
.help-circle {
    width: 36px; height: 36px; border-radius: 50%;
    background: #6c757d; color: #fff; border: none;
    display: inline-flex; align-items: center; justify-content: center;
    cursor: pointer; font-weight: 600; font-size: 16px;
}
.help-circle:focus { outline: none; box-shadow: 0 0 0 0.15rem rgba(108,117,125,0.25); }
.chart-container { 
    height: 400px; 
    width: 100%; 
    overflow: hidden; 
}
</style>
</head>
<body class="container mt-4">

<div class="modal fade" id="loginModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loginModalLabel">Login Necessário</h5>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <?php if (isset($erro_login)): ?>
                        <div class="alert alert-danger" role="alert"><?= $erro_login ?></div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label for="login" class="form-label">Usuário</label>
                        <input type="text" class="form-control" id="login" name="login" required>
                    </div>
                    <div class="mb-3">
                        <label for="senha" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="senha" name="senha" required>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="submit" name="login_submit" class="btn btn-primary">Entrar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<h2 class="mb-4">Dashboard de Monitoramento de Máquinas</h2>

<div class="row mb-4">
<div class="col-md-4">
    <label class="form-label">Selecione a máquina:</label>
    <select id="maquinaSelect" class="form-select">
        <?php 
        $primeira_maquina_id = !empty($maquinas) ? $maquinas[0]['id'] : null;
        foreach ($maquinas as $m): 
        ?>
            <option value="<?= $m['id'] ?>" <?= $m['id'] == $primeira_maquina_id ? 'selected' : '' ?>>
                <?= $m['nome'] ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

<div class="col-md-4">
    <label class="form-label">Período:</label>
    <div class="d-flex align-items-end gap-2">
        <input id="rangeData" class="form-control" placeholder="Selecione o intervalo">
        <button class="help-circle" type="button" data-bs-toggle="tooltip" data-bs-placement="right"
            title="Use o scroll do mouse para dar zoom e navegar pelo gráfico na horizontal.
Clique e arraste para mover o gráfico e selecionar partes dele.
No celular, use pinça para zoom.">?</button>
    </div>
</div>

<div class="col-md-4">
    <label class="form-label">Percentual mínimo (%):</label>
    <input id="minPercent" type="number" class="form-control" value="50" min="0" max="100">
</div>
</div>

<ul class="nav nav-tabs">
<li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#diario">Diário</button></li>
<li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#mensal">Mensal</button></li>
<li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#anual">Anual</button></li>
</ul>

<div class="tab-content mt-3">
<div class="tab-pane fade show active" id="diario">
    <div class="card"><div class="card-body">
        <div id="chartDiario" class="chart-container"></div>
    </div></div>
</div>
<div class="tab-pane fade" id="mensal">
    <div class="card"><div class="card-body">
        <h5 class="card-title">Atividade Mensal (%)</h5>
        <div id="chartMensal" class="chart-container"></div>
    </div></div>
</div>
<div class="tab-pane fade" id="anual">
    <div class="card"><div class="card-body">
        <h5 class="card-title">Atividade Anual (%)</h5>
        <div id="chartAnual" class="chart-container"></div>
    </div></div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const corBase = '#2496BF';
const corAlerta = '#FF3333';
let dateStart = "<?= $dataInicioDefault ?>";
let dateEnd = "<?= $dataFimDefault ?>";
let minPercent = 50;

const IS_LOGGED_IN = <?= $esta_logado ? 'true' : 'false' ?>;

function isUserLoggedIn() {
    if (!IS_LOGGED_IN) {
        console.warn("Acesso negado: Usuário não está logado.");
        return false;
    }
    return true;
}

$(function () {
    if (!IS_LOGGED_IN) {
        const loginModal = new bootstrap.Modal(document.getElementById('loginModal'));
        loginModal.show();
    }

    $('#rangeData').daterangepicker({
        locale: { format: 'YYYY-MM-DD', separator: ' até ', applyLabel: "Aplicar", cancelLabel: "Cancelar",
            daysOfWeek: ["Dom","Seg","Ter","Qua","Qui","Sex","Sáb"],
            monthNames: ["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"]
        },
        linkedCalendars: false,
        alwaysShowCalendars: true,
        startDate: dateStart,
        endDate: dateEnd
    }, function(start,end){
        dateStart = start.format('YYYY-MM-DD');
        dateEnd = end.format('YYYY-MM-DD');
        atualizarGraficos();
    });

    [...document.querySelectorAll('[data-bs-toggle="tooltip"]')]
        .map(el => new bootstrap.Tooltip(el));

    if (IS_LOGGED_IN) {
        atualizarGraficos();
    }
});

function criarHighcharts(containerId, tipoAtividade) {
    const plotLineConfig = {
        id: 'min-limit', 
        value: minPercent,
        color: corAlerta + '80',
        width: 2,
        dashStyle: 'Dash',
        zIndex: 5
    };
    
    const maquinaNomeInicial = $('#maquinaSelect option:selected').text();
    const tituloInicial = `${tipoAtividade} (%) - ${maquinaNomeInicial}`;

    return Highcharts.chart(containerId, {
        chart: {
            zoomType: 'x',
            panning: true,
            panKey: 'shift',
            scrollablePlotArea: { 
                minWidth: 1000,
                scrollPositionX: 1
            }
        },
        title: { text: tituloInicial },
        xAxis: { type: 'category' },
        yAxis: { 
            min: 0, 
            max: 100, 
            title: { text: 'Percentual (%)' },
            plotLines: [plotLineConfig]
        },
        tooltip: { shared: true, valueSuffix: '%' },
        series: [{
            name: 'Percentual de atividade da máquina',
            data: [],
            color: corBase,
            marker: { enabled: true, radius: 4 }
        }]
    });
}

const chartDiario = criarHighcharts('chartDiario', 'Atividade Diária');
const chartMensal = criarHighcharts('chartMensal', 'Atividade Mensal');
const chartAnual = criarHighcharts('chartAnual', 'Atividade Anual');

function atualizarPlotLine(chart, novoMinPercent) {
    if (chart.yAxis && chart.yAxis[0]) {
        chart.yAxis[0].removePlotLine('min-limit'); 
        chart.yAxis[0].addPlotLine({
            id: 'min-limit',
            value: novoMinPercent,
            color: corAlerta + '80',
            width: 2,
            dashStyle: 'Dash',
            zIndex: 5
        });
    }
}

function fetchChartData(tipo, chart, tituloBase) {
    if (!isUserLoggedIn()) {
        chart.setTitle({ text: 'Faça o login para visualizar os dados.' });
        chart.series[0].setData([], true);
        atualizarPlotLine(chart, minPercent);
        return; 
    }
    
    const maquina_id = $('#maquinaSelect').val();
    const maquinaNome = $('#maquinaSelect option:selected').text();
    chart.setTitle({ text: `${tituloBase} (%) - ${maquinaNome}` });

    fetch(`api/consolidado.php?tipo=${tipo}&maquina_id=${maquina_id}&inicio=${dateStart}&fim=${dateEnd}`)
    .then(res => res.json())
    .then(data => {
        if (data.error && data.error === 'Login required') {
            chart.setTitle({ text: 'Sessão expirada. Faça o login novamente.' });
            chart.series[0].setData([], true);
            return;
        }

        const parsedData = data.map(d => ({
            name: d.data,
            y: parseFloat(d.percentual_atividade)
        }));

        // Monta array de valores
        const values = parsedData.map(d => d.y);

        // Atualiza série com zones para linha antes do ponto vermelho
        chart.series[0].update({
            data: values,
            color: corBase,
            zones: [{
                value: minPercent,
                color: corAlerta
            }],
            zoneAxis: 'y'
        });

        // Ajusta marcadores individualmente
        chart.series[0].points.forEach((point, idx) => {
            point.update({
                marker: {
                    fillColor: (point.y < minPercent) ? corAlerta : corBase
                }
            }, false);
        });

        chart.redraw();
        atualizarPlotLine(chart, minPercent); 
    })
    .catch(error => {
        console.error("Erro ao buscar dados:", error);
        chart.setTitle({ text: 'Erro ao carregar dados.' });
        chart.series[0].setData([], true);
    });
}

function atualizarGraficos() {
    if (!isUserLoggedIn()) return; 
    minPercent = parseFloat($('#minPercent').val() || 0); 
    fetchChartData('diario', chartDiario, 'Atividade Diária');
    fetchChartData('mensal', chartMensal, 'Atividade Mensal');
    fetchChartData('anual', chartAnual, 'Atividade Anual');
}

$('#minPercent').on('input', atualizarGraficos);
$('#maquinaSelect').on('change', atualizarGraficos);

</script>
</body>
</html>
