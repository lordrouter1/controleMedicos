<?php
require_once __DIR__ . '/../includes/functions.php';

$professionalId = isset($_GET['professional_id']) ? (int)$_GET['professional_id'] : 0;
$selectedMonth = $_GET['month'] ?? (new DateTime())->format('Y-m');

if ($professionalId <= 0) {
    echo '<p class="text-danger">Profissional inválido.</p>';
    exit;
}

try {
    $observations = fetch_observations($professionalId, $selectedMonth);
} catch (Throwable $exception) {
    echo '<p class="text-danger">' . htmlspecialchars($exception->getMessage()) . '</p>';
    exit;
}

if (empty($observations)) {
    echo '<p>Nenhuma observação registrada para este mês.</p>';
    exit;
}
?>
<table class="table table-sm">
    <thead>
    <tr>
        <th>Data</th>
        <th>Horas</th>
        <th>Descrição</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($observations as $observation): ?>
        <tr>
            <td><?= (new DateTime($observation['created_at']))->format('d/m/Y H:i') ?></td>
            <td class="fw-semibold <?= $observation['hour_change'] >= 0 ? 'text-success' : 'text-danger' ?>">
                <?= $observation['hour_change'] >= 0 ? '+' : '' ?><?= number_format((float)$observation['hour_change'], 2, ',', '.') ?> h
            </td>
            <td><?= htmlspecialchars($observation['observation']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
