<?php
require_once __DIR__ . '/../includes/functions.php';

$selectedMonth = $_GET['month'] ?? (new DateTime())->format('Y-m');
$monthDate = DateTime::createFromFormat('Y-m', $selectedMonth) ?: new DateTime('first day of this month');
$monthLabel = format_month_label($monthDate);

$professionals = fetch_professionals_with_hours($selectedMonth);

$totalExtra = 0.0;
$totalMissing = 0.0;
$totalHours = 0.0;

foreach ($professionals as $professional) {
    $totalExtra += (float)$professional['extra_hours'];
    $totalMissing += (float)$professional['missing_hours'];
    $totalHours += (float)$professional['total_hours'];
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relatório de carga horária - <?= htmlspecialchars($monthLabel) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/theme.css" rel="stylesheet">
    <style>
        body {
            background: #fff;
        }
        .report-container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 2rem 1.5rem 3rem;
        }
        .report-table th,
        .report-table td {
            vertical-align: middle;
            font-size: 0.9rem;
        }
        .report-table thead th {
            background-color: var(--bs-primary-bg-subtle, #e8f0ff);
            color: var(--bs-primary-text, #0d3b66);
            font-weight: 600;
        }
        .report-meta {
            font-size: 0.95rem;
        }
        .print-actions {
            position: sticky;
            top: 0;
            background: #fff;
            padding: 1rem 0 0.75rem;
            z-index: 1020;
        }
        @media print {
            body {
                background: #fff;
            }
            .print-actions {
                display: none;
            }
            .report-container {
                padding-top: 0;
            }
            a[href]:after {
                display: none;
            }
        }
    </style>
</head>
<body>
<div class="report-container">
    <div class="print-actions d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.close()">Fechar</button>
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">Imprimir</button>
    </div>
    <header class="mb-4 border-bottom pb-3">
        <h1 class="h4 mb-1">Associação dos Profissionais de Saúde</h1>
        <p class="report-meta text-muted mb-0">Relatório de carga horária &middot; Referência <?= htmlspecialchars($monthLabel) ?></p>
        <small class="text-muted">Gerado em <?= (new DateTime())->format('d/m/Y H:i') ?></small>
    </header>

    <section class="mb-4">
        <div class="row g-3">
            <div class="col-sm-4">
                <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                    <p class="text-muted mb-1">Profissionais listados</p>
                    <p class="fs-4 fw-semibold mb-0"><?= count($professionals) ?></p>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                    <p class="text-muted mb-1">Horas extras</p>
                    <p class="fs-5 fw-semibold text-success mb-0">+<?= number_format($totalExtra, 2, ',', '.') ?> h</p>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="border rounded-3 p-3 h-100 bg-light-subtle">
                    <p class="text-muted mb-1">Saldo total</p>
                    <p class="fs-5 fw-semibold mb-0"><?= number_format($totalHours, 2, ',', '.') ?> h</p>
                    <small class="text-muted">Horas extras - faltantes (<?= number_format($totalMissing, 2, ',', '.') ?> h em faltas)</small>
                </div>
            </div>
        </div>
    </section>

    <section>
        <div class="table-responsive">
            <table class="table table-bordered report-table">
                <thead>
                <tr>
                    <th>Profissional</th>
                    <th>Empresa</th>
                    <th>Unidade</th>
                    <th>CBO</th>
                    <th class="text-end">Carga base</th>
                    <th class="text-end">Horas extras</th>
                    <th class="text-end">Horas faltas</th>
                    <th class="text-end">Total</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($professionals)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">Nenhum profissional cadastrado para o período informado.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($professionals as $professional): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($professional['name']) ?></div>
                                <div class="small text-muted">Valor hora: R$ <?= number_format((float)$professional['hourly_rate'], 2, ',', '.') ?></div>
                            </td>
                            <td><?= htmlspecialchars($professional['company']) ?></td>
                            <td><?= htmlspecialchars($professional['unit']) ?></td>
                            <td><?= htmlspecialchars($professional['cbo']) ?></td>
                            <td class="text-end"><?= number_format((float)$professional['workload_hours'], 2, ',', '.') ?> h</td>
                            <td class="text-end text-success">+<?= number_format((float)$professional['extra_hours'], 2, ',', '.') ?> h</td>
                            <td class="text-end text-danger">-<?= number_format(abs((float)$professional['missing_hours']), 2, ',', '.') ?> h</td>
                            <td class="text-end fw-semibold"><?= number_format((float)$professional['total_hours'], 2, ',', '.') ?> h</td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<script>
    window.addEventListener('load', () => {
        setTimeout(() => window.print(), 300);
    });
</script>
</body>
</html>
