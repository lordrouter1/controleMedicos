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

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_payment_status') {
            $professionalId = (int)($_POST['professional_id'] ?? 0);
            $isPaid = isset($_POST['is_paid']);
            update_payment_status($professionalId, $selectedMonth, $isPaid);
            $alerts[] = ['type' => 'success', 'message' => 'Status de pagamento atualizado.'];
        }
    }
} catch (Throwable $exception) {
    $alerts[] = ['type' => 'danger', 'message' => $exception->getMessage()];
}

try {
    $professionals = fetch_professionals_with_hours($selectedMonth);
} catch (Throwable $exception) {
    $alerts[] = ['type' => 'danger', 'message' => $exception->getMessage()];
    $professionals = [];
}

$totalProfessionals = count($professionals);
$totalExtraHours = 0.0;
$totalMissingHours = 0.0;
$paidCount = 0;

foreach ($professionals as $professional) {
    $extra = (float)$professional['extra_hours'];
    $missing = (float)$professional['missing_hours'];
    $total = (float)$professional['total_hours'];

    $totalExtraHours += $extra;
    $totalMissingHours += $missing;

    if ($professional['payment_status'] === 'pago') {
        $paidCount++;
    }
}

$pendingCount = $totalProfessionals - $paidCount;
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Controle de Carga Horária - Associação</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/theme.css" rel="stylesheet">
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
                    <a class="nav-link active" aria-current="page" href="index.php?month=<?= urlencode($selectedMonth) ?>">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="professionals.php">Cadastro de profissionais</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="lancamentos.php?month=<?= urlencode($selectedMonth) ?>">Registro de horas</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="container pb-5">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between gap-3 mb-4">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-auto">
                <label for="month" class="form-label small text-muted mb-1">Mês de referência</label>
                <select class="form-select" id="month" name="month" onchange="this.form.submit()">
                    <?php foreach ($monthOptions as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>" <?= $value === $selectedMonth ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
        <a class="btn btn-outline-primary" href="report.php?month=<?= htmlspecialchars($selectedMonth) ?>" target="_blank">Gerar relatório PDF</a>
    </div>

    <?php foreach ($alerts as $alert): ?>
        <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($alert['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
        </div>
    <?php endforeach; ?>

    <div class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted fw-semibold mb-2">Profissionais</h6>
                    <p class="display-6 fw-bold mb-0"><?= $totalProfessionals ?></p>
                    <small class="text-muted">Contratos cadastrados</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted fw-semibold mb-2">Horas extras</h6>
                    <p class="display-6 text-success fw-bold mb-0">+<?= number_format($totalExtraHours, 2, ',', '.') ?>h</p>
                    <small class="text-muted">Acumuladas no mês</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted fw-semibold mb-2">Horas faltantes</h6>
                    <p class="display-6 text-danger fw-bold mb-0">-<?= number_format($totalMissingHours, 2, ',', '.') ?>h</p>
                    <small class="text-muted">A compensar</small>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted fw-semibold mb-2">Pagamentos</h6>
                    <p class="display-6 fw-bold mb-0"><?= $paidCount ?> / <?= $totalProfessionals ?></p>
                    <small class="text-muted">Contratos pagos · <?= $pendingCount ?> pendente<?= $pendingCount === 1 ? '' : 's' ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between gap-2">
                <div class="fw-semibold">Profissionais cadastrados</div>
                <div class="text-md-end">
                    <span class="badge bg-success-subtle text-success me-2">Pago</span>
                    <span class="badge bg-warning-subtle text-warning">Pendente</span>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Profissional</th>
                    <th>Empresa</th>
                    <th>Unidade</th>
                    <th>CBO</th>
                    <th class="text-end">Valor hora</th>
                    <th class="text-end">Carga base</th>
                    <th class="text-end">Horas extras</th>
                    <th class="text-end">Horas faltas</th>
                    <th class="text-end">Total mês</th>
                    <th class="text-center">Pagamento</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($professionals)): ?>
                    <tr>
                        <td colspan="11" class="text-center py-4">Nenhum profissional cadastrado até o momento.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($professionals as $professional): ?>
                        <?php $isPaid = $professional['payment_status'] === 'pago'; ?>
                        <tr class="<?= $isPaid ? 'table-success table-paid' : '' ?>">
                            <td class="fw-semibold"><?= htmlspecialchars($professional['name']) ?></td>
                            <td><?= htmlspecialchars($professional['company']) ?></td>
                            <td><?= htmlspecialchars($professional['unit']) ?></td>
                            <td><?= htmlspecialchars($professional['cbo']) ?></td>
                            <td class="text-end">R$ <?= number_format((float)$professional['hourly_rate'], 2, ',', '.') ?></td>
                            <td class="text-end"><?= number_format((float)$professional['workload_hours'], 2, ',', '.') ?> h</td>
                            <td class="text-end text-success">+<?= number_format((float)$professional['extra_hours'], 2, ',', '.') ?> h</td>
                            <td class="text-end text-danger">-<?= number_format((float)$professional['missing_hours'], 2, ',', '.') ?> h</td>
                            <td class="text-end fw-semibold"><?= number_format((float)$professional['total_hours'], 2, ',', '.') ?> h</td>
                            <td class="text-center">
                                <form method="post" class="d-inline-flex align-items-center gap-2">
                                    <input type="hidden" name="action" value="update_payment_status">
                                    <input type="hidden" name="professional_id" value="<?= (int)$professional['id'] ?>">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="paidSwitch<?= (int)$professional['id'] ?>"
                                               name="is_paid" <?= $professional['payment_status'] === 'pago' ? 'checked' : '' ?>
                                               onchange="this.form.submit()">
                                        <label class="form-check-label small" for="paidSwitch<?= (int)$professional['id'] ?>">
                                            <?= $isPaid ? 'Pago' : 'Pendente' ?>
                                        </label>
                                    </div>
                                </form>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                        data-bs-target="#observationsModal"
                                        data-professional-id="<?= (int)$professional['id'] ?>"
                                        data-professional-name="<?= htmlspecialchars($professional['name']) ?>">
                                    Observações
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="observationsModal" tabindex="-1" aria-labelledby="observationsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="observationsModalLabel">Observações</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div id="observationsContent" class="small text-muted">Selecione um profissional para carregar as observações.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const observationsModal = document.getElementById('observationsModal');
if (observationsModal) {
    observationsModal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        const professionalId = button.getAttribute('data-professional-id');
        const professionalName = button.getAttribute('data-professional-name');
        const content = document.getElementById('observationsContent');
        const modalTitle = document.getElementById('observationsModalLabel');

        modalTitle.textContent = `Observações - ${professionalName}`;
        content.textContent = 'Carregando...';

        fetch(`observations.php?professional_id=${professionalId}&month=<?= urlencode($selectedMonth) ?>`)
            .then(response => response.text())
            .then(html => {
                content.innerHTML = html;
            })
            .catch(() => {
                content.textContent = 'Não foi possível carregar as observações.';
            });
    });
}
</script>
</body>
</html>
