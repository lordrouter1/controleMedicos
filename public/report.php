<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../lib/fpdf.php';

$selectedMonth = $_GET['month'] ?? (new DateTime())->format('Y-m');
$monthDate = DateTime::createFromFormat('Y-m', $selectedMonth) ?: new DateTime('first day of this month');
$formatter = new IntlDateFormatter('pt_BR', IntlDateFormatter::LONG, IntlDateFormatter::NONE, 'America/Sao_Paulo', IntlDateFormatter::GREGORIAN, "LLLL 'de' yyyy");
$monthLabel = ucfirst($formatter->format($monthDate));

$professionals = fetch_professionals_with_hours($selectedMonth);

class ReportPDF extends FPDF
{
    protected $title;
    protected $subtitle;

    public function __construct($title, $subtitle)
    {
        parent::__construct('P', 'mm', 'A4');
        $this->title = $title;
        $this->subtitle = $subtitle;
    }

    function Header()
    {
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 7, $this->utf('Associação dos Profissionais de Saúde'), 0, 1, 'C');
        $this->SetFont('helvetica', '', 12);
        $this->Cell(0, 6, $this->utf($this->title), 0, 1, 'C');
        $this->SetFont('helvetica', '', 10);
        $this->Cell(0, 5, $this->utf($this->subtitle), 0, 1, 'C');
        $this->Ln(5);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, $this->utf('Gerado em '.date('d/m/Y H:i').' - Página '.$this->PageNo()), 0, 0, 'C');
    }

    public function utf(string $text): string
    {
        return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
    }
}

$pdf = new ReportPDF('Relatório de carga horária de médicos', 'Referente a '.$monthLabel);
$pdf->SetTitle('Relatório de carga horária');
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 10);

$headers = ['Profissional', 'Empresa', 'Unidade', 'CBO', 'Carga Base', 'Extras', 'Faltas', 'Total'];
$widths = [45, 30, 30, 20, 20, 15, 15, 20];

foreach ($headers as $index => $header) {
    $pdf->Cell($widths[$index], 8, $pdf->utf($header), 1, 0, 'C');
}
$pdf->Ln();

$pdf->SetFont('helvetica', '', 9);

if (empty($professionals)) {
    $pdf->Cell(array_sum($widths), 8, $pdf->utf('Nenhum profissional cadastrado para o período.'), 1, 1, 'C');
} else {
    foreach ($professionals as $professional) {
        $row = [
            $professional['name'],
            $professional['company'],
            $professional['unit'],
            $professional['cbo'],
            number_format((float)$professional['workload_hours'], 2, ',', '.').' h',
            number_format((float)$professional['extra_hours'], 2, ',', '.').' h',
            number_format((float)$professional['missing_hours'], 2, ',', '.').' h',
            number_format((float)$professional['total_hours'], 2, ',', '.').' h',
        ];

        foreach ($row as $index => $column) {
            $align = $index >= 4 ? 'R' : 'L';
            $pdf->Cell($widths[$index], 8, $pdf->utf($column), 1, 0, $align);
        }
        $pdf->Ln();
    }
}

$pdf->Ln(5);
$pdf->SetFont('helvetica', 'I', 8);
$pdf->Cell(0, 5, $pdf->utf('Relatório emitido automaticamente pelo sistema de controle de carga horária.'), 0, 1, 'L');

$pdf->Output('I', 'relatorio_carga_horaria.pdf');
