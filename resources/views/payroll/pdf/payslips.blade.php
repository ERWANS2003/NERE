<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletins de paie</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9.5px; color: #1b2126; }
        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .head { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .head td { vertical-align: top; }
        .company { font-size: 15px; font-weight: bold; color: #c83530; }
        .title { font-size: 13px; font-weight: bold; text-align: right; }
        .info { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .info td { border: 1px solid #c9ccd1; padding: 4px 6px; }
        .info .k { background: #f4f5f6; color: #555; width: 18%; }
        table.lines { width: 100%; border-collapse: collapse; }
        .lines th { background: #c83530; color: #fff; padding: 4px 5px; text-align: left; font-size: 8.5px; }
        .lines td { border-bottom: 1px solid #e3e5e8; padding: 3px 5px; }
        .num, .lines th.num { text-align: right; white-space: nowrap; }
        .section td { background: #f1e3e2; font-weight: bold; }
        .total td { border-top: 1.5px solid #1b2126; font-weight: bold; }
        .net { margin-top: 10px; width: 100%; border-collapse: collapse; }
        .net td { padding: 8px; font-size: 12px; font-weight: bold; background: #1b2126; color: #fff; }
        .foot { margin-top: 12px; color: #777; font-size: 8px; }
    </style>
</head>
<body>
@php($fmt = fn ($v) => \App\Support\Fcfa::format($v))
@foreach($payslips as $payslip)
    @php($e = $payslip->employee)
    @php($lines = $payslip->lines->groupBy('kind'))
    <div class="page">
        <table class="head">
            <tr>
                <td><div class="company">NÉRÉ Mining</div><div>Bulletin de paie</div></td>
                <td class="title">Période : {{ $payslip->period->label() }}<br><span style="font-weight: normal; font-size: 9px;">{{ $payslip->period->isClosed() ? 'Paie clôturée le '.$payslip->period->closed_at->format('d/m/Y') : 'Document provisoire (paie non clôturée)' }}</span></td>
            </tr>
        </table>

        <table class="info">
            <tr><td class="k">Nom</td><td>{{ $e['name'] ?? '' }}</td><td class="k">Matricule</td><td>{{ $e['matricule'] ?? '' }}</td></tr>
            <tr><td class="k">Emploi</td><td>{{ $e['poste'] ?? '' }}</td><td class="k">Catégorie</td><td>{{ $e['category'] ?? '' }}</td></tr>
            <tr><td class="k">Contrat</td><td>{{ $e['contract_type'] ?? '' }}</td><td class="k">Date d’embauche</td><td>{{ $e['hire_date'] ?? '' }}</td></tr>
            <tr><td class="k">N° sécurité sociale</td><td>{{ $e['social_security_number'] ?? '' }}</td><td class="k">Charges de famille</td><td>{{ $e['dependents'] ?? 0 }}</td></tr>
        </table>

        <table class="lines">
            <thead>
                <tr><th>Rubrique</th><th class="num">Base</th><th class="num">Taux sal.</th><th class="num">Gains</th><th class="num">Retenues</th><th class="num">Taux pat.</th><th class="num">Part patronale</th></tr>
            </thead>
            <tbody>
                @foreach(['gain' => 'Rémunération', 'contribution' => 'Cotisations sociales', 'tax' => 'Impôt sur salaire', 'deduction' => 'Autres retenues'] as $kind => $title)
                    @continue(! $lines->has($kind))
                    <tr class="section"><td colspan="7">{{ $title }}</td></tr>
                    @foreach($lines[$kind] as $line)
                        <tr>
                            <td>{{ $line->label }}</td>
                            <td class="num">{{ $line->base !== null && $kind !== 'gain' ? $fmt($line->base) : '' }}</td>
                            <td class="num">{{ \App\Support\Fcfa::rate($line->rate) }}</td>
                            <td class="num">{{ $kind === 'gain' ? $fmt($line->amount) : '' }}</td>
                            <td class="num">{{ $kind !== 'gain' ? $fmt($line->amount) : '' }}</td>
                            <td class="num">{{ \App\Support\Fcfa::rate($line->employer_rate) }}</td>
                            <td class="num">{{ $line->employer_amount ? $fmt($line->employer_amount) : '' }}</td>
                        </tr>
                    @endforeach
                @endforeach
                <tr class="total">
                    <td>Totaux</td><td></td><td></td>
                    <td class="num">{{ $fmt($payslip->gross) }}</td>
                    <td class="num">{{ $fmt($payslip->employee_contributions + $payslip->tax + $payslip->deductions) }}</td>
                    <td></td>
                    <td class="num">{{ $fmt($payslip->employer_contributions) }}</td>
                </tr>
            </tbody>
        </table>

        <table class="info" style="margin-top: 10px;">
            <tr><td class="k">Brut cotisable</td><td class="num">{{ $fmt($payslip->contributory_base) }}</td><td class="k">Brut imposable</td><td class="num">{{ $fmt($payslip->taxable_base) }}</td></tr>
            <tr><td class="k">Mode de paiement</td><td>{{ \App\Models\PayrollProfile::PAYMENT_METHODS[$e['payment_method'] ?? ''] ?? '' }}</td><td class="k">Banque / compte</td><td>{{ collect([$e['bank_name'] ?? null, $e['bank_account'] ?? null])->filter()->join(' · ') }}</td></tr>
        </table>

        <table class="net"><tr><td>Net à payer</td><td class="num">{{ $fmt($payslip->net) }} FCFA</td></tr></table>

        <p class="foot">Montants en francs CFA. Conservez ce bulletin sans limitation de durée.</p>
    </div>
@endforeach
</body>
</html>
