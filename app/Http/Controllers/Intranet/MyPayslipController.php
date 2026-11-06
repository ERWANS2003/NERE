<?php

namespace App\Http\Controllers\Intranet;

use App\Http\Controllers\Controller;
use App\Models\Payslip;
use App\Services\Payroll\PayslipPdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/** Bulletins du salarié connecté, visibles une fois la paie clôturée. */
class MyPayslipController extends Controller
{
    public function index(Request $request): View
    {
        return view('intranet.payslips.index', [
            'payslips' => $request->user()->payslips()
                ->whereHas('period', fn ($query) => $query->where('status', 'closed'))
                ->with('period')
                ->get()
                ->sortByDesc(fn (Payslip $payslip) => sprintf('%04d%02d', $payslip->period->year, $payslip->period->month)),
        ]);
    }

    public function download(Request $request, Payslip $payslip, PayslipPdf $pdf): Response
    {
        abort_unless($payslip->user_id === $request->user()->id && $payslip->period->isClosed(), 404);

        return $pdf->download(collect([$payslip]), sprintf('bulletin-%04d-%02d', $payslip->period->year, $payslip->period->month));
    }
}
