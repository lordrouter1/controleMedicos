<?php
require_once __DIR__ . '/../includes/functions.php';

setlocale(LC_TIME, 'pt_BR.UTF-8', 'pt_BR', 'pt_BR.utf8', 'portuguese');

date_default_timezone_set('America/Sao_Paulo');

$monthOptions = get_month_options(6);
$selectedMonth = $_GET['month'] ?? array_key_first($monthOptions);
if (!isset($monthOptions[$selectedMonth])) {
    $selectedMonth = array_key_first($monthOptions);
}

$alerts = [];
$currentMonthLabel = $monthOptions[$selectedMonth] ?? '';
$currentMonthDescription = function_exists('mb_strtolower')
    ? mb_strtolower($currentMonthLabel, 'UTF-8')
    : strtolower($currentMonthLabel);

try {
    $professionals = fetch_all_professionals();
} catch (Throwable $exception) {
    $alerts[] = ['type' => 'danger', 'message' => $exception->getMessage()];
    $professionals = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $observationMonth = $_POST['observation_month'] ?? $selectedMonth;
    if (!isset($monthOptions[$observationMonth])) {
        $monthOptions = get_month_options(6);
        $observationMonth = array_key_first($monthOptions);
    }
    $selectedMonth = $observationMonth;
    $currentMonthLabel = $monthOptions[$selectedMonth] ?? $currentMonthLabel;
    $currentMonthDescription = function_exists('mb_strtolower')
        ? mb_strtolower($currentMonthLabel, 'UTF-8')
        : strtolower($currentMonthLabel);

    try {
        $data = [
            'professional_id' => (int)($_POST['professional_id'] ?? 0),
            'observation' => trim($_POST['observation'] ?? ''),
            'hour_change' => (float)($_POST['hour_change'] ?? 0),
            'observation_month' => $observationMonth,
        ];

        add_observation($data);
        $alerts[] = ['type' => 'success', 'message' => 'Registro de horas lançado com sucesso.'];
    } catch (Throwable $exception) {
        $alerts[] = ['type' => 'danger', 'message' => $exception->getMessage()];
    }
}

try {
    $recentObservations = fetch_recent_observations($selectedMonth, 15);
} catch (Throwable $exception) {
    $alerts[] = ['type' => 'danger', 'message' => $exception->getMessage()];
    $recentObservations = [];
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registro de Horas - Controle de Médicos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand" href="index.php">Controle de Médicos</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="Alternar navegação">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="index.php?month=<?= urlencode($selectedMonth) ?>">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="professionals.php">Cadastro de profissionais</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="lancamentos.php?month=<?= urlencode($selectedMonth) ?>">Registro de horas</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="container pb-5">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Registro de horas</h1>
            <p class="text-muted mb-0">Lance observações, horas extras e ausências para manter o histórico mensal atualizado.</p>
        </div>
        <form method="get" class="row g-2 align-items-end">
            <div class="col-auto">
                <label for="month" class="form-label mb-0 small text-muted">Mês de referência</label>
                <select class="form-select" id="month" name="month" onchange="this.form.submit()">
                    <?php foreach ($monthOptions as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>" <?= $value === $selectedMonth ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <?php foreach ($alerts as $alert): ?>
        <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($alert['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endforeach; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Novo lançamento</h5>
                </div>
                <div class="card-body">
                    <form method="post" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="professional_id" class="form-label">Profissional</label>
                            <select class="form-select" id="professional_id" name="professional_id" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($professionals as $professional): ?>
                                    <option value="<?= (int)$professional['id'] ?>"><?= htmlspecialchars($professional['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Escolha o profissional para o lançamento.</div>
                        </div>
                        <div class="mb-3">
                            <label for="observation_month" class="form-label">Mês de referência</label>
                            <select class="form-select" id="observation_month" name="observation_month" required>
                                <?php foreach ($monthOptions as $value => $label): ?>
                                    <option value="<?= htmlspecialchars($value) ?>" <?= $value === $selectedMonth ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="hour_change" class="form-label">Horas (+ / -)</label>
                            <input type="number" step="0.01" class="form-control" id="hour_change" name="hour_change" required>
                            <div class="form-text">Use valores positivos para extras e negativos para faltas.</div>
                            <div class="invalid-feedback">Informe o total de horas do lançamento.</div>
                        </div>
                        <div class="mb-3">
                            <label for="observation" class="form-label">Descrição</label>
                            <textarea class="form-control" id="observation" name="observation" rows="3" required></textarea>
                            <div class="invalid-feedback">Descreva o motivo do ajuste de horas.</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Registrar horas</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between">
                        <h5 class="card-title mb-0">Últimos lançamentos de <?= htmlspecialchars($currentMonthDescription) ?></h5>
                        <small class="text-muted">Mostrando os 15 registros mais recentes</small>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Data</th>
                            <th>Profissional</th>
                            <th class="text-end">Horas</th>
                            <th>Descrição</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($recentObservations)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">Nenhum lançamento registrado para o período selecionado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentObservations as $observation): ?>
                                <?php $hours = (float)$observation['hour_change']; ?>
                                <tr>
                                    <td><?= (new DateTime($observation['created_at']))->format('d/m/Y H:i') ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($observation['professional_name']) ?></td>
                                    <td class="text-end fw-semibold <?= $hours >= 0 ? 'text-success' : 'text-danger' ?>">
                                        <?= $hours >= 0 ? '+' : '' ?><?= number_format($hours, 2, ',', '.') ?> h
                                    </td>
                                    <td><?= htmlspecialchars($observation['observation']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(() => {
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>
</body>
</html>
