<?php
include("conexao.php");

// Pega todas as máquinas para o filtro
$maquinas_result = $conn->query("SELECT id, nome FROM maquinas");
$maquinas = [];
while($row = $maquinas_result->fetch_assoc()) {
    $maquinas[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Dashboard de Monitoramento</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Flatpickr -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/pt.js"></script>

    <style>
        body { background-color: #f8f9fa; }
        .card { margin-bottom: 20px; }
        .chart-container { position: relative; height:400px; }
    </style>
</head>

<body class="container mt-4">

    <h2 class="mb-4">Dashboard de Monitoramento de Máquinas</h2>

    <!-- Filtros -->
    <div class="row mb-3">
        <div class="col-md-6">
            <label for="maquinaSelect" class="form-label">Selecione a máquina:</label>
            <select id="maquinaSelect" class="form-select">
                <option value="0">Todas</option>
                <?php foreach($maquinas as $m): ?>
                    <option value="<?= $m['id'] ?>"><?= $m['nome'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-6">
            <label for="rangeData" class="form-label">Período:</label>
            <input id="rangeData" class="form-control" placeholder="Selecione o intervalo de datas">
        </div>
    </div>

    <!-- Abas -->
    <ul class="nav nav-tabs" id="viewTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="diario-tab" data-bs-toggle="tab" data-bs-target="#diario" type="button">Diário</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="mensal-tab" data-bs-toggle="tab" data-bs-target="#mensal" type="button">Mensal</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="anual-tab" data-bs-toggle="tab" data-bs-target="#anual" type="button">Anual</button>
        </li>
    </ul>

    <div class="tab-content mt-3">

        <div class="tab-pane fade show active" id="diario">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Atividade Diária (%)</h5>
                    <div class="chart-container">
                        <canvas id="chartDiario"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="mensal">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Atividade Mensal (%)</h5>
                    <div class="chart-container">
                        <canvas id="chartMensal"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="anual">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Atividade Anual (%)</h5>
                    <div class="chart-container">
                        <canvas id="chartAnual"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const corBase = '#2496BF';

let dataInicio = null;
let dataFim = null;

// Inicializa calendário de intervalo
flatpickr("#rangeData", {
    mode: "range",
    locale: "pt",
    dateFormat: "Y-m-d",
    onChange: function(selectedDates) {
        if (selectedDates.length === 2) {
            dataInicio = selectedDates[0].toISOString().split('T')[0];
            dataFim = selectedDates[1].toISOString().split('T')[0];
            atualizarGraficos();
        }
    }
});

// Função de busca
function fetchChartData(tipo, maquina_id, chart) {
    if (!dataInicio || !dataFim) return;

    fetch(`api/consolidado.php?tipo=${tipo}&maquina_id=${maquina_id}&inicio=${dataInicio}&fim=${dataFim}`)
        .then(res => res.json())
        .then(data => {
            const labels = data.map(d => d.data);
            const valores = data.map(d => parseFloat(d.percentual_atividade));

            chart.data.labels = labels;
            chart.data.datasets[0].data = valores;
            chart.update();
        });
}

// Configuração base dos gráficos
const baseConfig = {
    type: 'line',
    data: { labels: [], datasets: [{
        label: 'Percentual de atividade',
        data: [],
        borderColor: corBase,
        backgroundColor: corBase + '33',
        tension: 0.3
    }]},
    options: { responsive:true, scales:{ y:{ beginAtZero:true, max:100 } } }
};

const chartDiario = new Chart(document.getElementById('chartDiario'), JSON.parse(JSON.stringify(baseConfig)));
const chartMensal = new Chart(document.getElementById('chartMensal'), JSON.parse(JSON.stringify(baseConfig)));
const chartAnual = new Chart(document.getElementById('chartAnual'), JSON.parse(JSON.stringify(baseConfig)));

function atualizarGraficos() {
    const maquina_id = document.getElementById('maquinaSelect').value;

    if (!dataInicio || !dataFim) return;

    fetchChartData('diario', maquina_id, chartDiario);
    fetchChartData('mensal', maquina_id, chartMensal);
    fetchChartData('anual', maquina_id, chartAnual);
}

document.getElementById('maquinaSelect').addEventListener('change', atualizarGraficos);

</script>

</body>
</html>
