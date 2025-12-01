<?php
include("conexao.php");

// Pega todas as máquinas
$maquinas_result = $conn->query("SELECT id, nome FROM maquinas");
$maquinas = [];
while ($row = $maquinas_result->fetch_assoc()) {
    $maquinas[] = $row;
}

// Obter menor e maior data de leituras
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
    /* Adicionando overflow para garantir que a barra de rolagem horizontal funcione no Highcharts */
    overflow: hidden; 
}
</style>
</head>
<body class="container mt-4">

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
            title="Use o scroll do mouse para dar zoom horizontal.
Clique e arraste para mover o gráfico.
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
        <h5 class="card-title">Atividade Diária (%)</h5>
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

// Inicializa daterangepicker e tooltip
$(function () {
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

    atualizarGraficos();
});

function criarHighcharts(containerId) {
    // Configuração completa da plotLine, AGORA INCLUINDO O ID 'min-limit' (Correção do bug de dupla linha)
    const plotLineConfig = {
        id: 'min-limit', 
        value: minPercent, // Valor inicial da linha
        color: corAlerta + '80', // Cor vermelha com 50% de opacidade
        width: 2,
        dashStyle: 'Dash',
        zIndex: 5
    };

    return Highcharts.chart(containerId, {
        chart: {
            zoomType: 'x',
            panning: true,
            panKey: 'shift',
            // Configuração para barra de rolagem horizontal (Highcharts Stock/Module)
            scrollablePlotArea: { 
                minWidth: 1000,
                scrollPositionX: 1
            }
        },
        title: { text: null },
        xAxis: { type: 'category' },
        yAxis: { 
            min: 0, 
            max: 100, 
            title: { text: 'Percentual (%)' },
            plotLines: [plotLineConfig] // Usa a configuração completa
        },
        tooltip: { shared: true, valueSuffix: '%' },
        series: [{
            name: 'Percentual de atividade',
            data: [],
            color: corBase
        }],
        plotOptions: {
            series: {
                marker: { enabled: true, radius: 4 }
            }
        }
    });
}

const chartDiario = criarHighcharts('chartDiario');
const chartMensal = criarHighcharts('chartMensal');
const chartAnual = criarHighcharts('chartAnual');

/**
 * Atualiza o plotLine (linha horizontal) do gráfico.
 * Deve ser chamada sempre que o input minPercent mudar.
 */
function atualizarPlotLine(chart, novoMinPercent) {
    if (chart.yAxis && chart.yAxis[0]) {
        // Remove a linha antiga, que agora SEMPRE terá o ID 'min-limit'
        chart.yAxis[0].removePlotLine('min-limit'); 
        
        // Adiciona a nova linha com o novo valor
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

function fetchChartData(tipo, chart) {
    const maquina_id = $('#maquinaSelect').val();
    fetch(`api/consolidado.php?tipo=${tipo}&maquina_id=${maquina_id}&inicio=${dateStart}&fim=${dateEnd}`)
    .then(res => res.json())
    .then(data => {
        const seriesData = data.map(d => ({
            name: d.data,
            y: parseFloat(d.percentual_atividade),
            color: parseFloat(d.percentual_atividade) < minPercent ? corAlerta : corBase
        }));
        
        chart.series[0].setData(seriesData, true);
        // Chama a função de atualização da linha com o valor atualizado
        atualizarPlotLine(chart, minPercent); 
    });
}

function atualizarGraficos() {
    // Atualiza a variável global
    minPercent = parseFloat($('#minPercent').val() || 0); 

    fetchChartData('diario', chartDiario);
    fetchChartData('mensal', chartMensal);
    fetchChartData('anual', chartAnual);
}

// Eventos
$('#minPercent').on('input', atualizarGraficos);
$('#maquinaSelect').on('change', atualizarGraficos);

</script>
</body>
</html>
