<?php
require_once __DIR__ . '/../includes/functions.php';

setlocale(LC_TIME, 'pt_BR.UTF-8', 'pt_BR', 'pt_BR.utf8', 'portuguese');

date_default_timezone_set('America/Sao_Paulo');

$monthOptions = get_month_options();
$selectedMonth = $_GET['month'] ?? array_key_first($monthOptions);
if (!isset($monthOptions[$selectedMonth])) {
    $selectedMonth = array_key_first($monthOptions);
}

$alerts = [];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_professional') {
            $data = [
                'name' => trim($_POST['name'] ?? ''),
                'company' => trim($_POST['company'] ?? ''),
                'workload_hours' => (float)($_POST['workload_hours'] ?? 0),
                'cbo' => trim($_POST['cbo'] ?? ''),
                'hourly_rate' => (float)($_POST['hourly_rate'] ?? 0),
                'unit' => trim($_POST['unit'] ?? ''),
            ];

            add_professional($data);
            $alerts[] = ['type' => 'success', 'message' => 'Profissional cadastrado com sucesso.'];
        } elseif ($action === 'add_observation') {
            $data = [
                'professional_id' => (int)($_POST['professional_id'] ?? 0),
                'observation' => trim($_POST['observation'] ?? ''),
                'hour_change' => (float)($_POST['hour_change'] ?? 0),
                'observation_month' => $selectedMonth,
            ];

            add_observation($data);
            $alerts[] = ['type' => 'success', 'message' => 'Observação registrada com sucesso.'];
        } elseif ($action === 'update_payment_status') {
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
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Controle de Carga Horária - Associação</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <span class="navbar-brand mb-0 h1">Controle de Médicos</span>
        <div>
            <a class="btn btn-outline-light" href="report.php?month=<?= htmlspecialchars($selectedMonth) ?>" target="_blank">Gerar Relatório PDF</a>
        </div>
    </div>
</nav>
<div class="container">
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="month" class="form-label">Mês de referência</label>
                    <select class="form-select" id="month" name="month" onchange="this.form.submit()">
                        <?php foreach ($monthOptions as $value => $label): ?>
                            <option value="<?= htmlspecialchars($value) ?>" <?= $value === $selectedMonth ? 'selected' : '' ?>>
                                <?= htmlspecialchars(ucfirst($label)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php foreach ($alerts as $alert): ?>
        <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($alert['message']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endforeach; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Profissionais cadastrados</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Empresa</th>
                            <th>Unidade</th>
                            <th>CBO</th>
                            <th>Valor Hora</th>
                            <th>Carga Base</th>
                            <th>Extras</th>
                            <th>Faltas</th>
                            <th>Total</th>
                            <th>Pagamento</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($professionals)): ?>
                            <tr>
                                <td colspan="11" class="text-center">Nenhum profissional cadastrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($professionals as $professional): ?>
                                <tr>
                                    <td><?= htmlspecialchars($professional['name']) ?></td>
                                    <td><?= htmlspecialchars($professional['company']) ?></td>
                                    <td><?= htmlspecialchars($professional['unit']) ?></td>
                                    <td><?= htmlspecialchars($professional['cbo']) ?></td>
                                    <td>R$ <?= number_format((float)$professional['hourly_rate'], 2, ',', '.') ?></td>
                                    <td><?= number_format((float)$professional['workload_hours'], 2, ',', '.') ?> h</td>
                                    <td class="text-success">+<?= number_format((float)$professional['extra_hours'], 2, ',', '.') ?> h</td>
                                    <td class="text-danger">-<?= number_format((float)$professional['missing_hours'], 2, ',', '.') ?> h</td>
                                    <td><?= number_format((float)$professional['total_hours'], 2, ',', '.') ?> h</td>
                                    <td>
                                        <form method="post" class="d-flex align-items-center gap-2">
                                            <input type="hidden" name="action" value="update_payment_status">
                                            <input type="hidden" name="professional_id" value="<?= (int)$professional['id'] ?>">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="paidSwitch<?= (int)$professional['id'] ?>" name="is_paid" <?= $professional['payment_status'] === 'pago' ? 'checked' : '' ?> onchange="this.form.submit()">
                                                <label class="form-check-label" for="paidSwitch<?= (int)$professional['id'] ?>">
                                                    <?= $professional['payment_status'] === 'pago' ? 'Pago' : 'Pendente' ?>
                                                </label>
                                            </div>
                                        </form>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#observationsModal" data-professional-id="<?= (int)$professional['id'] ?>" data-professional-name="<?= htmlspecialchars($professional['name']) ?>">Observações</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Novo profissional</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="add_professional">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="company" class="form-label">Empresa</label>
                            <input type="text" class="form-control" id="company" name="company" required>
                        </div>
                        <div class="mb-3">
                            <label for="unit" class="form-label">Unidade</label>
                            <input type="text" class="form-control" id="unit" name="unit" required>
                        </div>
                        <div class="mb-3">
                            <label for="cbo" class="form-label">CBO</label>
                            <input type="text" class="form-control" id="cbo" name="cbo" required>
                        </div>
                        <div class="mb-3">
                            <label for="workload_hours" class="form-label">Carga horária mensal (horas)</label>
                            <input type="number" step="0.01" class="form-control" id="workload_hours" name="workload_hours" required>
                        </div>
                        <div class="mb-3">
                            <label for="hourly_rate" class="form-label">Valor hora (R$)</label>
                            <input type="number" step="0.01" class="form-control" id="hourly_rate" name="hourly_rate" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Cadastrar profissional</button>
                    </form>
                </div>
            </div>
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Nova observação</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="add_observation">
                        <div class="mb-3">
                            <label for="professional_id" class="form-label">Profissional</label>
                            <select class="form-select" id="professional_id" name="professional_id" required>
                                <option value="">Selecione...</option>
                                <?php foreach ($professionals as $professional): ?>
                                    <option value="<?= (int)$professional['id'] ?>"><?= htmlspecialchars($professional['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="observation" class="form-label">Observação</label>
                            <textarea class="form-control" id="observation" name="observation" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="hour_change" class="form-label">Horas (+/-)</label>
                            <input type="number" step="0.01" class="form-control" id="hour_change" name="hour_change" required>
                        </div>
                        <button type="submit" class="btn btn-secondary w-100">Salvar observação</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="observationsModal" tabindex="-1" aria-labelledby="observationsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="observationsModalLabel">Observações</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
</script>
</body>
</html>
