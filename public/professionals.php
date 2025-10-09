<?php
require_once __DIR__ . '/../includes/functions.php';

setlocale(LC_TIME, 'pt_BR.UTF-8', 'pt_BR', 'pt_BR.utf8', 'portuguese');

date_default_timezone_set('America/Sao_Paulo');

$alerts = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
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
    } catch (Throwable $exception) {
        $alerts[] = ['type' => 'danger', 'message' => $exception->getMessage()];
    }
}

try {
    $professionals = fetch_all_professionals();
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
    <title>Cadastro de Profissionais - Controle de Médicos</title>
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
                    <a class="nav-link" href="index.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" aria-current="page" href="professionals.php">Cadastro de profissionais</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="lancamentos.php">Registro de horas</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="container pb-5">
    <div class="row align-items-center mb-4">
        <div class="col-lg-8">
            <h1 class="h3 mb-2">Cadastro de profissionais</h1>
            <p class="text-muted mb-0">Mantenha os dados de médicos e empresas atualizados para garantir um controle confiável.</p>
        </div>
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
                    <h5 class="card-title mb-0">Novo profissional</h5>
                </div>
                <div class="card-body">
                    <form method="post" class="needs-validation" novalidate>
                        <div class="mb-3">
                            <label for="name" class="form-label">Nome completo</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback">Informe o nome do profissional.</div>
                        </div>
                        <div class="mb-3">
                            <label for="company" class="form-label">Empresa</label>
                            <input type="text" class="form-control" id="company" name="company" required>
                            <div class="invalid-feedback">Informe a empresa vinculada.</div>
                        </div>
                        <div class="mb-3">
                            <label for="unit" class="form-label">Unidade de atuação</label>
                            <input type="text" class="form-control" id="unit" name="unit" required>
                            <div class="invalid-feedback">Informe a unidade de trabalho.</div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="cbo" class="form-label">CBO</label>
                                <input type="text" class="form-control" id="cbo" name="cbo" required>
                                <div class="invalid-feedback">Informe o código CBO.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="workload_hours" class="form-label">Carga horária mensal (h)</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="workload_hours" name="workload_hours" required>
                                <div class="invalid-feedback">Informe a carga mensal prevista.</div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <label for="hourly_rate" class="form-label">Valor hora (R$)</label>
                            <input type="number" step="0.01" min="0" class="form-control" id="hourly_rate" name="hourly_rate" required>
                            <div class="invalid-feedback">Informe o valor pago por hora.</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mt-4">Salvar cadastro</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0">Profissionais cadastrados</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>Empresa</th>
                            <th>Unidade</th>
                            <th>CBO</th>
                            <th class="text-end">Carga mensal</th>
                            <th class="text-end">Valor hora</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($professionals)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">Nenhum profissional cadastrado ainda.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($professionals as $professional): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($professional['name']) ?></td>
                                    <td><?= htmlspecialchars($professional['company']) ?></td>
                                    <td><?= htmlspecialchars($professional['unit']) ?></td>
                                    <td><?= htmlspecialchars($professional['cbo']) ?></td>
                                    <td class="text-end"><?= number_format((float)$professional['workload_hours'], 2, ',', '.') ?> h</td>
                                    <td class="text-end">R$ <?= number_format((float)$professional['hourly_rate'], 2, ',', '.') ?></td>
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
