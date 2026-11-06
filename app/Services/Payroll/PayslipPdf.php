<?php

namespace App\Services\Payroll;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class PayslipPdf
{
    public function download(Collection $payslips, string $filename): Response
    {
        $payslips = EloquentCollection::make($payslips->all())->loadMissing(['lines', 'period']);

        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view('payroll.pdf.payslips', ['payslips' => $payslips])->render(), 'UTF-8');
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
