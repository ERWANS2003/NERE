<?php

namespace App\Services\Payroll;

use App\Models\PayrollPeriod;
use App\Models\PayrollProfile;
use App\Models\PayslipLine;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Livre de paie, récapitulatif par rubrique (états déclaratifs) et état des paiements. */
class PayrollLedgerExporter
{
    private const MONEY = '#,##0';

    public function download(PayrollPeriod $period): StreamedResponse
    {
        $spreadsheet = $this->workbook($period);
        $filename = sprintf('livre-de-paie-%04d-%02d.xlsx', $period->year, $period->month);

        return response()->streamDownload(static function () use ($spreadsheet): void {
            try {
                (new Xlsx($spreadsheet))->save('php://output');
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    public function workbook(PayrollPeriod $period): Spreadsheet
    {
        $payslips = $period->payslips()->with('lines')->orderBy('employee->name')->get();
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()->setTitle('Livre de paie '.$period->label());

        // Livre de paie : une ligne par salarié.
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Livre de paie');
        $headers = ['Matricule', 'Nom', 'Catégorie', 'Brut', 'Brut cotisable', 'Brut imposable', 'Cotisations salariales', 'Impôt', 'Autres retenues', 'Net à payer', 'Cotisations patronales', 'Coût total employeur'];
        $this->header($sheet, 'Livre de paie · '.$period->label(), $headers);
        $row = 3;
        foreach ($payslips as $payslip) {
            $this->text($sheet, "A{$row}", $payslip->employee['matricule'] ?? '');
            $this->text($sheet, "B{$row}", $payslip->employee['name'] ?? '');
            $this->text($sheet, "C{$row}", $payslip->employee['category'] ?? '');
            $sheet->fromArray([[
                $payslip->gross, $payslip->contributory_base, $payslip->taxable_base, $payslip->employee_contributions,
                $payslip->tax, $payslip->deductions, $payslip->net, $payslip->employer_contributions,
                $payslip->gross + $payslip->employer_contributions,
            ]], null, "D{$row}");
            $row++;
        }
        $this->totals($sheet, $row, 'D', 'L', 3);

        // Récapitulatif par rubrique : montants à reporter sur les déclarations.
        $recap = $spreadsheet->createSheet()->setTitle('Récapitulatif');
        $this->header($recap, 'Récapitulatif par rubrique · '.$period->label(), ['Code', 'Rubrique', 'Type', 'Base', 'Part salariale', 'Part patronale', 'Salariés']);
        $kinds = ['gain' => 'Gain', 'deduction' => 'Retenue', 'contribution' => 'Cotisation', 'tax' => 'Impôt'];
        $row = 3;
        $lines = PayslipLine::query()->whereIn('payslip_id', $payslips->modelKeys())
            ->selectRaw('code, kind, MIN(label) as label, SUM(base) as base, SUM(amount) as amount, SUM(employer_amount) as employer_amount, COUNT(*) as people, MIN(position) as position')
            ->groupBy('code', 'kind')->orderBy('position')->get();
        foreach ($lines as $line) {
            $this->text($recap, "A{$row}", $line->code);
            $this->text($recap, "B{$row}", $line->label);
            $this->text($recap, "C{$row}", $kinds[$line->kind] ?? $line->kind);
            $recap->fromArray([[(float) $line->base, (float) $line->amount, (float) $line->employer_amount, (int) $line->people]], null, "D{$row}");
            $row++;
        }
        $recap->getStyle("D3:F{$row}")->getNumberFormat()->setFormatCode(self::MONEY);

        // État des paiements par mode de règlement.
        $pay = $spreadsheet->createSheet()->setTitle('Paiements');
        $this->header($pay, 'État des paiements · '.$period->label(), ['Mode', 'Matricule', 'Nom', 'Banque', 'Compte', 'Net à payer']);
        $row = 3;
        foreach ($payslips->sortBy(fn ($p) => [$p->employee['payment_method'] ?? '', $p->employee['name'] ?? '']) as $payslip) {
            $this->text($pay, "A{$row}", PayrollProfile::PAYMENT_METHODS[$payslip->employee['payment_method'] ?? ''] ?? '');
            $this->text($pay, "B{$row}", $payslip->employee['matricule'] ?? '');
            $this->text($pay, "C{$row}", $payslip->employee['name'] ?? '');
            $this->text($pay, "D{$row}", $payslip->employee['bank_name'] ?? '');
            $this->text($pay, "E{$row}", $payslip->employee['bank_account'] ?? '');
            $pay->setCellValue("F{$row}", $payslip->net);
            $row++;
        }
        $this->totals($pay, $row, 'F', 'F', 3);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function header(Worksheet $sheet, string $title, array $headers): void
    {
        $sheet->setCellValue('A1', $title);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->fromArray([$headers], null, 'A2');
        $last = $sheet->getHighestColumn(2);
        $sheet->getStyle("A2:{$last}2")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A2:{$last}2")->getFill()->setFillType('solid')->getStartColor()->setARGB('FFC83530');
        foreach (range('A', $last) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->freezePane('A3');
    }

    private function totals(Worksheet $sheet, int $row, string $from, string $to, int $firstRow): void
    {
        $this->text($sheet, "A{$row}", 'TOTAL');
        foreach (range($from, $to) as $column) {
            $sheet->setCellValue("{$column}{$row}", "=SUM({$column}{$firstRow}:{$column}".($row - 1).')');
        }
        $sheet->getStyle("A{$row}:{$to}{$row}")->getFont()->setBold(true);
        $sheet->getStyle("{$from}{$firstRow}:{$to}{$row}")->getNumberFormat()->setFormatCode(self::MONEY);
    }

    private function text(Worksheet $sheet, string $cell, string $value): void
    {
        $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);
    }
}
