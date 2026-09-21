<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport financier — {{ $selectedYear?->label ?? '' }}</title>
    @include('students.documents.partials.base-styles')
    @include('finances.partials.pdf-document-styles')
    <style>
        .finance-doc-title {
            background: #d9d9d9;
            border-top: 1px solid #9CA3AF;
            border-bottom: 1px solid #9CA3AF;
            padding: 7px 8px;
            text-align: center;
            font-family: Georgia, 'Times New Roman', serif;
            margin: 7px 0 9px;
        }
        @page { margin: 3mm; }
        body { padding: 0 !important; }
        .cert-official-header { margin-bottom: 2px !important; padding-bottom: 2px !important; }
        .cert-official-header__agreements { margin: 0 auto 2px !important; }
        .cert-official-header__agreements div { padding: 2px 4px !important; }
        .finance-doc-title .main {
            font-size: 20px;
            font-weight: 900;
            text-transform: uppercase;
            line-height: 1.1;
            color: #111827;
        }
        .finance-doc-title .sub {
            font-size: 10px;
            color: #4B5563;
            margin-top: 6px;
            font-family: Arial, Helvetica, sans-serif;
            font-weight: 700;
        }

        .kpi-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            margin-bottom: 8px;
        }
        .kpi-box {
            background: #F8FAFC;
            border: 1px solid #E5E7EB;
            border-radius: 4px;
            padding: 7px 8px;
        }
        .kpi-box .label {
            font-size: 9px;
            font-weight: 700;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .kpi-box .value {
            font-size: 10px;
            font-weight: 900;
            color: #1A3A6B;
            margin-top: 4px;
        }

        .section-title {
            background: #F3F4F6;
            color: #374151;
            padding: 4px 6px;
            font-size: 10px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 7px 0 5px;
            border-radius: 3px;
            border: 1px solid #E5E7EB;
        }

        .bar-row { margin-bottom: 5px; }
        .bar-meta { display: flex; justify-content: space-between; margin-bottom: 2px; font-size: 8.5px; }
        .bar-label { font-weight: 700; }
        .bar-value { font-weight: 800; color: #1A3A6B; }
        .bar-track { height: 6px; background: #EEF2F7; border-radius: 3px; overflow: hidden; }
        .bar-fill { height: 6px; background: #4A6FA5; border-radius: 3px; }

        .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 7px; margin-bottom: 6px; }

        table { width: 100%; border-collapse: collapse; font-size: 7.6px; }
        thead tr { background: #F3F4F6; }
        thead th {
            padding: 3px 4px;
            text-align: left;
            font-weight: 700;
            font-size: 7.5px;
            text-transform: uppercase;
            border: 1px solid #E5E7EB;
        }
        thead th.right { text-align: right; }
        tbody td { padding: 2px 4px; border: 1px solid #E5E7EB; }
        tbody tr:nth-child(even) { background: #FAFAFA; }
        tbody td.right { text-align: right; font-weight: 700; }
        tfoot tr { background: #F3F4F6; }
        tfoot td { padding: 3px 4px; font-weight: 900; border: 1px solid #E5E7EB; }
        tfoot td.right { text-align: right; color: #1A3A6B; }

        .footer-note {
            text-align: center;
            margin-top: 7px;
            font-size: 8px;
            color: #9CA3AF;
            border-top: 1px solid #E5E7EB;
            padding-top: 6px;
        }
        .nb {
            background: #FFFBEB;
            border: 1px solid #E5E7EB;
            color: #78350F;
            font-weight: 700;
            font-size: 8.5px;
            padding: 5px 8px;
            border-radius: 3px;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
@include('students.documents.partials.print-toolbar')

<div class="page cert-page pdf-document">
    @include('students.documents.partials.certificate-official-header', [
        'showCertificateTitle' => false,
    ])

    @php
        $mainTitle = match($type) {
            'journalier' => 'Rapport Financier Journalier',
            'hebdomadaire' => 'Rapport Financier Hebdomadaire',
            'entre-2-dates' => 'Rapport Financier du ' . ($startDate ? \Carbon\Carbon::parse($startDate)->format('d/m/Y') : '—') . ' au ' . ($endDate ? \Carbon\Carbon::parse($endDate)->format('d/m/Y') : '—'),
            'mensuel' => 'Rapport Financier Mensuel',
            'annuel' => 'Rapport Financier Annuel',
            default => 'Rapport Financier',
        };

        $subtitleParts = [];
        if ($type === 'journalier') {
            $subtitleParts[] = 'Date : ' . ($date ? \Carbon\Carbon::parse($date)->format('d/m/Y') : '—');
        } elseif ($type === 'hebdomadaire') {
            try {
                $weekStart = \Carbon\Carbon::parse($week . '-1')->startOfWeek(\Carbon\Carbon::MONDAY);
            } catch (\Throwable $e) {
                $weekStart = \Carbon\Carbon::parse(now()->format('o-\WW') . '-1')->startOfWeek(\Carbon\Carbon::MONDAY);
            }
            $weekEnd = $weekStart->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);
            $subtitleParts[] = 'Période : ' . $weekStart->format('d/m/Y') . ' au ' . $weekEnd->format('d/m/Y');
        } elseif ($type === 'mensuel') {
            $subtitleParts[] = 'Mois : ' . (['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'][$month-1] ?? '—');
        } elseif ($type === 'entre-2-dates') {
            $subtitleParts[] = 'Du ' . ($startDate ? \Carbon\Carbon::parse($startDate)->format('d/m/Y') : '—');
            $subtitleParts[] = 'Au ' . ($endDate ? \Carbon\Carbon::parse($endDate)->format('d/m/Y') : '—');
        } elseif ($type === 'annuel') {
            $subtitleParts[] = 'Année scolaire : ' . ($selectedYear?->label ?? '—');
        }
        if ($selectedYear && $type !== 'annuel') {
            $subtitleParts[] = $selectedYear->label;
        }
        $subtitleParts[] = 'Généré le ' . now()->format('d/m/Y à H:i');
        $subtitleParts[] = $whoFilter === 'global' ? 'Tous les enregistrements' : $responsibleName;
    @endphp

    <div class="finance-doc-title">
        <div class="main">
            {{ $mainTitle }}
        </div>
        <div class="sub">
            {{ implode(' · ', $subtitleParts) }}
        </div>
    </div>

    <div class="kpi-row">
        <div class="kpi-box">
            <div class="label">Total collecté</div>
            @php $totalWithScholarships = $totalCollected + (int)$allPayments->sum('scholarship_amount'); @endphp
            <div class="value">{{ number_format($totalWithScholarships) }} FCFA</div>
        </div>
        <div class="kpi-box">
            <div class="label">Bourses Accordées</div>
            @php $scholarships = $allPayments->sum('scholarship_amount'); @endphp
            <div class="value">{{ number_format($scholarships) }} FCFA</div>
        </div>
        <div class="kpi-box">
            <div class="label">Nb paiements</div>
            <div class="value">{{ $allPayments->count() }}</div>
        </div>
        <div class="kpi-box">
            <div class="label">Espèces</div>
            @php $cash = $allPayments->where('payment_method','cash')->sum('amount_paid') + $allPayments->where('payment_method','cash')->sum('scholarship_amount'); @endphp
            <div class="value">{{ number_format($cash) }} FCFA</div>
        </div>
        <div class="kpi-box">
            <div class="label">Paiements Mobiles</div>
            @php $mm = $allPayments->whereIn('payment_method',['orange_money','mtn_momo'])->sum('amount_paid') + $allPayments->whereIn('payment_method',['orange_money','mtn_momo'])->sum('scholarship_amount'); @endphp
            <div class="value">{{ number_format($mm) }} FCFA</div>
        </div>
        <div class="kpi-box">
            <div class="label">Virement</div>
            @php $vir = $allPayments->where('payment_method','bank_transfer')->sum('amount_paid') + $allPayments->where('payment_method','bank_transfer')->sum('scholarship_amount'); @endphp
            <div class="value">{{ number_format($vir) }} FCFA</div>
        </div>
    </div>

    <div class="two-col">
        <div>
            <div class="section-title">Par tranche de paiement</div>
            @php $maxI = $byInstallment->max('total') ?: 1; @endphp
            @foreach($byInstallment as $inst)
            <div class="bar-row">
                <div class="bar-meta">
                    <span class="bar-label">{{ $inst['label'] }}</span>
                    <span class="bar-value">
                        {{ number_format($inst['total']) }} F
                        <span style="font-weight:400;color:#9CA3AF;">({{ $inst['count'] }})</span>
                    </span>
                </div>
                <div class="bar-track">
                    <div class="bar-fill" style="width:{{ round(($inst['total']/$maxI)*100) }}%;"></div>
                </div>
            </div>
            @endforeach
        </div>

        <div>
            <div class="section-title">Par mode de paiement</div>
            @php
                $byMethodWithScholarships = $byMethod->map(function($m) use ($allPayments) {
                    $methodScholarships = $allPayments->where('payment_method', $m['method'])->sum('scholarship_amount');
                    $m['total'] = $m['total'] + $methodScholarships;
                    return $m;
                });
                $maxM = $byMethodWithScholarships->max('total') ?: 1;
            @endphp
            @foreach($byMethodWithScholarships as $m)
            <div class="bar-row">
                <div class="bar-meta">
                    <span class="bar-label">{{ $m['label'] }}</span>
                    <span class="bar-value">
                        {{ number_format($m['total']) }} F
                        <span style="font-weight:400;color:#9CA3AF;">({{ $m['count'] }})</span>
                    </span>
                </div>
                <div class="bar-track">
                    <div class="bar-fill" style="width:{{ round(($m['total']/$maxM)*100) }}%; background:#6B7280;"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="section-title">Détail des paiements</div>
    <table>
        <thead>
            <tr>
                <th>Élève</th>
                <th>Classe</th>
                <th>Tranche</th>
                <th class="right">Montant</th>
                <th>Mode</th>
                <th>Date</th>
                <th>Caissier</th>
                <th>N° Reçu</th>
            </tr>
        </thead>
        <tbody>
            @foreach($allPayments as $p)
            <tr>
                <td>{{ $p->studentEnrollment?->student?->full_name }}</td>
                <td>{{ $p->studentEnrollment?->classGroup?->full_name }}</td>
                <td>{{ $p->is_bulk ? 'Paiement groupé' : ($p->feeInstallment?->label ?? '—') }}</td>
                <td class="right">{{ number_format($p->amount_paid + $p->scholarship_amount) }} FCFA</td>
                <td>{{ $p->payment_method_label }}</td>
                <td>{{ $p->payment_date->format('d/m/Y') }}</td>
                <td>{{ $p->recordedBy?->name ?? '—' }}</td>
                <td>{{ $p->receipt_number }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">TOTAL</td>
                <td class="right">{{ number_format($totalCollected + (int)$allPayments->sum('scholarship_amount')) }} FCFA</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
    </table>

    <div class="nb">NB : AUCUN FRAIS N'EST REMBOURSABLE.</div>

    <div class="footer-note">
        {{ $school->full_name ?? 'COPTAN' }} · Rapport généré le {{ now()->format('d/m/Y à H:i') }}
    </div>
</div>
</body>
</html>
