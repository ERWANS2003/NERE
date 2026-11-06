<?php

namespace App\Services\Payroll;

use App\Support\PdfRenderer;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;

class PayslipPdf
{
    public function download(Collection $payslips, string $filename): Response
    {
        $payslips = EloquentCollection::make($payslips->all())->loadMissing(['lines', 'period']);

        $pdf = PdfRenderer::render(view('payroll.pdf.payslips', ['payslips' => $payslips])->render());

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'.pdf"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
