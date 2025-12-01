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
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.1/dist/chartjs-plugin-zoom.min.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@2.2.1/dist/chartjs-plugin-annotation.min.js"></script>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <style>
        body { background-color: #f8f9fa; }
        .card { margin-bottom: 20px; }
        .chart-container { position: relative; height: 400px; width: 100%; }
        .help-circle {
            width: 36px; height: 36px; border-radius: 50%;
            background: #6c757d; color: #fff; border: none;
            display: inline-flex; align-items: center; justify-content: center;
            cursor: pointer; font-weight: 600; font-size: 16px;
        }
        .help-circle:focus { outline: none; box-shadow: 0 0 0 0.15rem rgba(108,117,125,0.25); }
    </style>
</head>
<body class="container mt-4">

<h2 class="mb-4">Dashboard de Monitoramento de Máquinas</h2>

<div class="row mb-4">
    <div class="col-md-4">
        <label class="form-label">Selecione a máquina:</label>
        <select id="maquinaSelect" class="form-select">
            <option value="0">Todas</option>
            <?php foreach ($maquinas as $m): ?>
                <option value="<?= $m['id'] ?>"><?= $m['nome'] ?></option>
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
            <div class="chart-container"><canvas id="chartDiario"></canvas></div>
        </div></div>
    </div>
    <div class="tab-pane fade" id="mensal">
        <div class="card"><div class="card-body">
            <h5 class="card-title">Atividade Mensal (%)</h5>
            <div class="chart-container"><canvas id="chartMensal"></canvas></div>
        </div></div>
    </div>
    <div class="tab-pane fade" id="anual">
        <div class="card"><div class="card-body">
            <h5 class="card-title">Atividade Anual (%)</h5>
            <div class="chart-container"><canvas id="chartAnual"></canvas></div>
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

// Criação de gráfico com Plugins (Zoom e Annotation)
function novoGrafico(ctx) {
    return new Chart(ctx, {
        type: 'line',
        data: { labels: [], datasets: [{
            label: 'Percentual de atividade',
            data: [],
            borderColor: corBase,
            backgroundColor: corBase + '33',
            tension: 0.3,
            pointRadius: 4
        }]},
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                zoom: {
                    pan: { enabled: true, mode: 'x' },
                    zoom: { wheel: { enabled: true }, pinch: { enabled: true }, mode: 'x' },
                    limits: { x: { minRange: 3 } }
                },
                // --- CONFIGURAÇÃO DA LINHA HORIZONTAL (ANNOTATION) ---
                annotation: {
                    annotations: {
                        linhaLimite: {
                            type: 'line',
                            yMin: minPercent, // Começa no valor do input
                            yMax: minPercent, // Termina no valor do input
                            borderColor: corAlerta + '50',
                            borderWidth: 2,
                            borderDash: [10, 5], // Linha tracejada
                        }
                    }
                }
            },
            scales: { y: { beginAtZero: true, max: 100 } }
        }
    });
}

// Fetch de dados e estilização dinâmica
function fetchChartData(tipo, maquina_id, chart) {
    fetch(`api/consolidado.php?tipo=${tipo}&maquina_id=${maquina_id}&inicio=${dateStart}&fim=${dateEnd}`)
    .then(res => res.json())
    .then(data => {
        const valores = data.map(d => parseFloat(d.percentual_atividade));
        chart.data.labels = data.map(d => d.data);
        chart.data.datasets[0].data = valores;

        // Linha vermelha SOMENTE se o PONTO FINAL (ctx.p1) do segmento estiver abaixo do mínimo
        chart.data.datasets[0].segment = {
            borderColor: ctx => ctx.p1.parsed.y < minPercent ? corAlerta : corBase
        };

        // Pontos: preenchimento e contorno
        chart.data.datasets[0].pointBackgroundColor = valores.map(v => v < minPercent ? corAlerta : corBase);
        chart.data.datasets[0].pointBorderColor = valores.map(v => v < minPercent ? corAlerta : corBase);

        chart.update();
    });
}

const chartDiario = novoGrafico(document.getElementById('chartDiario'));
const chartMensal = novoGrafico(document.getElementById('chartMensal'));
const chartAnual = novoGrafico(document.getElementById('chartAnual'));

// Helper para atualizar a linha horizontal sem precisar recarregar tudo
function atualizarLinhaLimite(chart, valor) {
    if (chart && chart.options.plugins.annotation.annotations.linhaLimite) {
        chart.options.plugins.annotation.annotations.linhaLimite.yMin = valor;
        chart.options.plugins.annotation.annotations.linhaLimite.yMax = valor;
        chart.update();
    }
}

function atualizarGraficos() {
    const maquina_id = document.getElementById('maquinaSelect').value;
    
    // Atualiza a posição da linha nos 3 gráficos antes de buscar dados
    atualizarLinhaLimite(chartDiario, minPercent);
    atualizarLinhaLimite(chartMensal, minPercent);
    atualizarLinhaLimite(chartAnual, minPercent);

    fetchChartData('diario', maquina_id, chartDiario);
    fetchChartData('mensal', maquina_id, chartMensal);
    fetchChartData('anual', maquina_id, chartAnual);
}

// Eventos
document.getElementById('minPercent').addEventListener('input', e => {
    minPercent = parseFloat(e.target.value || 0);
    // Chama atualizarGraficos para repintar as linhas (segment) E a barra horizontal
    atualizarGraficos();
});

document.getElementById('maquinaSelect').addEventListener('change', atualizarGraficos);
</script>
</body>
</html>
