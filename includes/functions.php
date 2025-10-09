<?php
require_once __DIR__ . '/../config/db.php';

/**
 * Fetch list of professionals with aggregated hours for a given month.
 */
function fetch_professionals_with_hours(string $selectedMonth): array
{
    $mysqli = get_db_connection();

    $sql = "SELECT p.id, p.name, p.company, p.workload_hours, p.cbo, p.hourly_rate, p.unit,
                   IFNULL(SUM(CASE WHEN o.hour_change > 0 THEN o.hour_change ELSE 0 END), 0) AS extra_hours,
                   IFNULL(SUM(CASE WHEN o.hour_change < 0 THEN ABS(o.hour_change) ELSE 0 END), 0) AS missing_hours
            FROM professionals p
            LEFT JOIN professional_observations o
              ON o.professional_id = p.id AND o.observation_month = ?
            GROUP BY p.id
            ORDER BY p.name";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Erro ao preparar consulta: ' . $mysqli->error);
    }

    $stmt->bind_param('s', $selectedMonth);
    $stmt->execute();
    $result = $stmt->get_result();

    $professionals = [];
    while ($row = $result->fetch_assoc()) {
        $row['total_hours'] = (float)$row['workload_hours'] + (float)$row['extra_hours'] - (float)$row['missing_hours'];
        $row['payment_status'] = fetch_payment_status((int)$row['id'], $selectedMonth, $mysqli);
        $professionals[] = $row;
    }

    $stmt->close();
    $mysqli->close();

    return $professionals;
}

function fetch_payment_status(int $professionalId, string $selectedMonth, ?mysqli $mysqli = null): string
{
    $closeConnection = false;
    if ($mysqli === null) {
        $mysqli = get_db_connection();
        $closeConnection = true;
    }

    $sql = "SELECT is_paid FROM monthly_controls WHERE professional_id = ? AND reference_month = ?";
    $stmt = $mysqli->prepare($sql);

    if (!$stmt) {
        throw new RuntimeException('Erro ao preparar consulta: ' . $mysqli->error);
    }

    $stmt->bind_param('is', $professionalId, $selectedMonth);
    $stmt->execute();
    $stmt->bind_result($isPaid);

    $status = 'pendente';
    if ($stmt->fetch()) {
        $status = $isPaid ? 'pago' : 'pendente';
    }

    $stmt->close();
    if ($closeConnection) {
        $mysqli->close();
    }

    return $status;
}

function add_professional(array $data): void
{
    $mysqli = get_db_connection();

    $sql = "INSERT INTO professionals (name, company, workload_hours, cbo, hourly_rate, unit)
            VALUES (?, ?, ?, ?, ?, ?)";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Erro ao preparar consulta: ' . $mysqli->error);
    }

    $stmt->bind_param(
        'ssdsds',
        $data['name'],
        $data['company'],
        $data['workload_hours'],
        $data['cbo'],
        $data['hourly_rate'],
        $data['unit']
    );

    if (!$stmt->execute()) {
        throw new RuntimeException('Erro ao salvar profissional: ' . $stmt->error);
    }

    $stmt->close();
    $mysqli->close();
}

function fetch_all_professionals(): array
{
    $mysqli = get_db_connection();

    $sql = "SELECT id, name, company, workload_hours, cbo, hourly_rate, unit
            FROM professionals
            ORDER BY name";

    $result = $mysqli->query($sql);
    if ($result === false) {
        throw new RuntimeException('Erro ao buscar profissionais: ' . $mysqli->error);
    }

    $professionals = $result->fetch_all(MYSQLI_ASSOC);

    $mysqli->close();

    return $professionals;
}

function add_observation(array $data): void
{
    $mysqli = get_db_connection();
    $sql = "INSERT INTO professional_observations (professional_id, observation, hour_change, observation_month)
            VALUES (?, ?, ?, ?)";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Erro ao preparar consulta: ' . $mysqli->error);
    }

    $stmt->bind_param('isds', $data['professional_id'], $data['observation'], $data['hour_change'], $data['observation_month']);

    if (!$stmt->execute()) {
        throw new RuntimeException('Erro ao salvar observação: ' . $stmt->error);
    }

    $stmt->close();
    $mysqli->close();
}

function update_payment_status(int $professionalId, string $selectedMonth, bool $isPaid): void
{
    $mysqli = get_db_connection();

    $sql = "INSERT INTO monthly_controls (professional_id, reference_month, is_paid)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE is_paid = VALUES(is_paid)";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Erro ao preparar consulta: ' . $mysqli->error);
    }

    $paidValue = $isPaid ? 1 : 0;
    $stmt->bind_param('isi', $professionalId, $selectedMonth, $paidValue);

    if (!$stmt->execute()) {
        throw new RuntimeException('Erro ao atualizar pagamento: ' . $stmt->error);
    }

    $stmt->close();
    $mysqli->close();
}

function fetch_observations(int $professionalId, string $selectedMonth): array
{
    $mysqli = get_db_connection();

    $sql = "SELECT id, observation, hour_change, created_at
            FROM professional_observations
            WHERE professional_id = ? AND observation_month = ?
            ORDER BY created_at DESC";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Erro ao preparar consulta: ' . $mysqli->error);
    }

    $stmt->bind_param('is', $professionalId, $selectedMonth);
    $stmt->execute();
    $result = $stmt->get_result();

    $observations = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
    $mysqli->close();

    return $observations;
}

function fetch_recent_observations(string $selectedMonth, int $limit = 10): array
{
    $mysqli = get_db_connection();

    $sql = "SELECT o.id, o.observation, o.hour_change, o.created_at, p.name AS professional_name
            FROM professional_observations o
            INNER JOIN professionals p ON p.id = o.professional_id
            WHERE o.observation_month = ?
            ORDER BY o.created_at DESC
            LIMIT ?";

    $stmt = $mysqli->prepare($sql);
    if (!$stmt) {
        throw new RuntimeException('Erro ao preparar consulta: ' . $mysqli->error);
    }

    $stmt->bind_param('si', $selectedMonth, $limit);
    $stmt->execute();

    $result = $stmt->get_result();
    $observations = $result->fetch_all(MYSQLI_ASSOC);

    $stmt->close();
    $mysqli->close();

    return $observations;
}

function get_month_options(int $months = 6): array
{
    $months = max(1, $months);
    $options = [];
    $current = new DateTime('first day of this month');

    $useIntl = class_exists('IntlDateFormatter');
    $formatter = null;
    if ($useIntl) {
        $formatter = new IntlDateFormatter(
            'pt_BR',
            IntlDateFormatter::LONG,
            IntlDateFormatter::NONE,
            'America/Sao_Paulo',
            IntlDateFormatter::GREGORIAN,
            'LLLL/yyyy'
        );
    } else {
        setlocale(LC_TIME, 'pt_BR.UTF-8', 'pt_BR', 'Portuguese_Brazil');
    }

    $manualMonths = [
        1 => 'janeiro',
        2 => 'fevereiro',
        3 => 'março',
        4 => 'abril',
        5 => 'maio',
        6 => 'junho',
        7 => 'julho',
        8 => 'agosto',
        9 => 'setembro',
        10 => 'outubro',
        11 => 'novembro',
        12 => 'dezembro',
    ];

    for ($i = 0; $i < $months; $i++) {
        $monthKey = $current->format('Y-m');

        if ($useIntl && $formatter instanceof IntlDateFormatter) {
            $label = $formatter->format($current);
        } else {
            $monthNumber = (int)$current->format('n');
            $label = sprintf('%s %s', $manualMonths[$monthNumber], $current->format('Y'));
        }

        $options[$monthKey] = ucfirst($label);
        $current->modify('-1 month');
    }

    return $options;
}
