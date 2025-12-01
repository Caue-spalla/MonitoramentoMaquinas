<?php
// Configurações de sessão para segurança
ini_set('session.cookie_lifetime', 0);
ini_set('session.gc_maxlifetime', 3600);
session_start();

$login_esperado = "admin";
$senha_esperada = "admin";

// Lógica de processamento do login
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
    
    <!-- Dependências do Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@2.0.1/dist/chartjs-plugin-zoom.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@2.2.1/dist/chartjs-plugin-annotation.min.js"></script>

    <!-- Dependências de Data -->
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

<!-- Modal de Login -->
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
<!-- Fim Modal de Login -->


<h2 class="mb-4">Dashboard de Monitoramento de Máquinas</h2>

<div class="row mb-4">
    <div class="col-md-4">
        <label class="form-label">Selecione a máquina:</label>
        <select id="maquinaSelect" class="form-select">
            <?php 
            // Determina a primeira máquina para ser selecionada por padrão
            $primeira_maquina_id = !empty($maquinas) ? $maquinas[0]['id'] : null;
            foreach ($maquinas as $m): 
            ?>
                <!-- Remove a opção "Todas" e garante que a primeira máquina esteja selecionada -->
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

const IS_LOGGED_IN = <?= $esta_logado ? 'true' : 'false' ?>;

// Função para verificar o status de login
function isUserLoggedIn() {
    if (!IS_LOGGED_IN) {
        console.warn("Acesso negado: Usuário não está logado.");
        return false;
    }
    return true;
}

// Inicializa daterangepicker e tooltip
$(function () {
    // Exibe o modal de login se não estiver logado
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

// Criação de gráfico com Plugins (Zoom e Annotation)
let chartDiario, chartMensal, chartAnual;

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

// Inicializa os gráficos (apenas uma vez)
document.addEventListener('DOMContentLoaded', () => {
    // Verifica se os elementos existem antes de inicializar
    if (document.getElementById('chartDiario')) {
        chartDiario = novoGrafico(document.getElementById('chartDiario'));
    }
    if (document.getElementById('chartMensal')) {
        chartMensal = novoGrafico(document.getElementById('chartMensal'));
    }
    if (document.getElementById('chartAnual')) {
        chartAnual = novoGrafico(document.getElementById('chartAnual'));
    }

    // Chama a função de atualização após a inicialização dos gráficos, se logado
    if (IS_LOGGED_IN) {
        // Pequeno timeout para garantir que todos os elementos Chart.js foram renderizados
        setTimeout(atualizarGraficos, 100); 
    }
});


// Fetch de dados e estilização dinâmica
function fetchChartData(tipo, maquina_id, chart) {
    if (!isUserLoggedIn() || !chart) {
        // Limpa o gráfico se não estiver logado ou se o chart for nulo
        if (chart) {
            chart.data.labels = [];
            chart.data.datasets[0].data = [];
            chart.update();
        }
        return;
    }

    fetch(`api/consolidado.php?tipo=${tipo}&maquina_id=${maquina_id}&inicio=${dateStart}&fim=${dateEnd}`)
    .then(res => res.json())
    .then(data => {
        const valores = data.map(d => parseFloat(d.percentual_atividade));
        chart.data.labels = data.map(d => d.data);
        chart.data.datasets[0].data = valores;

        // --- LÓGICA CHAVE: Segmento da linha (P_i -> P_{i+1}) usa a cor do ponto de CHEGADA (P_{i+1}) ---
        chart.data.datasets[0].segment = {
            // ctx.p1 é o ponto de chegada (o ponto mais à direita no segmento)
            borderColor: ctx => ctx.p1.parsed.y < minPercent ? corAlerta : corBase
        };

        // Pontos: preenchimento e contorno usam a cor do ponto atual
        chart.data.datasets[0].pointBackgroundColor = valores.map(v => v < minPercent ? corAlerta : corBase);
        chart.data.datasets[0].pointBorderColor = valores.map(v => v < minPercent ? corAlerta : corBase);

        chart.update();
    })
    .catch(error => {
        console.error("Erro ao buscar dados:", error);
    });
}

// Helper para atualizar a linha horizontal sem precisar recarregar tudo
function atualizarLinhaLimite(chart, valor) {
    if (chart && chart.options.plugins.annotation.annotations.linhaLimite) {
        chart.options.plugins.annotation.annotations.linhaLimite.yMin = valor;
        chart.options.plugins.annotation.annotations.linhaLimite.yMax = valor;
        chart.update();
    }
}

function atualizarGraficos() {
    if (!isUserLoggedIn()) {
        return; 
    }

    const maquina_id = document.getElementById('maquinaSelect').value;
    
    // Atualiza o valor de minPercent
    minPercent = parseFloat(document.getElementById('minPercent').value || 0);

    // Atualiza a posição da linha nos 3 gráficos antes de buscar dados
    atualizarLinhaLimite(chartDiario, minPercent);
    atualizarLinhaLimite(chartMensal, minPercent);
    atualizarLinhaLimite(chartAnual, minPercent);

    // Verifica se os objetos Chart existem antes de buscar dados
    if (chartDiario) fetchChartData('diario', maquina_id, chartDiario);
    if (chartMensal) fetchChartData('mensal', maquina_id, chartMensal);
    if (chartAnual) fetchChartData('anual', maquina_id, chartAnual);
}

// Eventos
document.getElementById('minPercent').addEventListener('input', atualizarGraficos);
document.getElementById('maquinaSelect').addEventListener('change', atualizarGraficos);
</script>
</body>
</html>