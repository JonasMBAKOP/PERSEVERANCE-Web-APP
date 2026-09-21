<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des bourses — {{ $selectedYear?->label ?? '' }}</title>
    @include('students.documents.partials.base-styles')
    @include('finances.partials.pdf-document-styles')
    <style>
        .scholar-doc-title {
            margin: 8px 0 10px;
            padding: 8px 10px;
            text-align: center;
            border-top: 1px solid #9CA3AF;
            border-bottom: 1px solid #9CA3AF;
            background: #F3F4F6;
            font-family: Georgia, 'Times New Roman', serif;
        }
        .scholar-doc-title .main {
            font-size: 18px;
            font-weight: 900;
            text-transform: uppercase;
            line-height: 1.15;
            color: #111827;
        }
        .scholar-doc-title .sub {
            margin-top: 5px;
            font: 700 9px Arial, Helvetica, sans-serif;
            color: #4B5563;
        }
        @page { margin: 3mm; }
        body { padding: 0 !important; }
        .cert-official-header { margin-bottom: 2px !important; padding-bottom: 2px !important; }
        .cert-official-header__agreements { margin: 0 auto 2px !important; }
        .cert-official-header__agreements div { padding: 2px 4px !important; }
        .summary-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 6px;
            margin-bottom: 8px;
        }
        .summary-box {
            border: 1px solid #E5E7EB;
            background: #F8FAFC;
            padding: 6px 8px;
            border-radius: 4px;
        }
        .summary-box:nth-child(2) { display: none; }
        .summary-box .label {
            font-size: 7.5px;
            font-weight: 800;
            color: #6B7280;
            text-transform: uppercase;
            letter-spacing: .35px;
        }
        .summary-box .value {
            margin-top: 3px;
            font-size: 10px;
            font-weight: 900;
            color: #1A3A6B;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5px;
            table-layout: fixed;
        }
        thead tr { background: #F3F4F6; }
        th {
            border: 1px solid #D1D5DB;
            padding: 3px 4px;
            text-align: left;
            font-weight: 900;
            text-transform: uppercase;
            color: #374151;
            word-wrap: break-word;
        }
        td {
            border: 1px solid #E5E7EB;
            padding: 3px 4px;
            vertical-align: top;
            word-wrap: break-word;
        }
        tbody tr:nth-child(even) { background: #FAFAFA; }
        .right { text-align: right; }
        .bold { font-weight: 900; }
        .muted { color: #6B7280; font-size: 7px; }
        tfoot td {
            background: #F3F4F6;
            font-weight: 900;
        }
        .footer-note {
            margin-top: 8px;
            border-top: 1px solid #E5E7EB;
            padding-top: 6px;
            text-align: center;
            font-size: 7.5px;
            color: #6B7280;
        }
    </style>
</head>
<body>
@include('students.documents.partials.print-toolbar')

<div class="page cert-page pdf-document">
    @include('students.documents.partials.certificate-official-header', [
        'showCertificateTitle' => false,
    ])

    <div class="scholar-doc-title">
        <div class="main">Liste des bourses accordées</div>
        <div class="sub">{{ $periodLabel }} · {{ $selectedYear?->label ?? '—' }}</div>
    </div>

    <div class="summary-row">
        <div class="summary-box">
            <div class="label">Responsable</div>
            <div class="value">{{ $cashierName }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Année scolaire</div>
            <div class="value">{{ $selectedYear?->label ?? '—' }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Période</div>
            <div class="value">{{ $periodLabel }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Nombre</div>
            <div class="value">{{ number_format($scholarshipCount) }}</div>
        </div>
        <div class="summary-box">
            <div class="label">Montant total</div>
            <div class="value">{{ number_format($totalScholarships) }} FCFA</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:11%;">Date</th>
                <th style="width:30%;">Élève</th>
                <th style="width:16%;">Classe</th>
                <th class="right" style="width:14%;">Montant</th>
                <th style="width:18%;">Responsable</th>
                <th style="width:11%;">Réf.</th>
            </tr>
        </thead>
        <tbody>
            @forelse($scholarships as $scholarship)
                <tr>
                    <td class="bold">{{ $scholarship->payment_date?->format('d/m/Y') }}</td>
                    <td>
                        <div class="bold">{{ $scholarship->studentEnrollment?->student?->full_name ?? '—' }}</div>
                        <div class="muted">{{ $scholarship->studentEnrollment?->student?->matricule ?? '—' }}</div>
                    </td>
                    <td>{{ $scholarship->studentEnrollment?->classGroup?->full_name ?? '—' }}</td>
                    <td class="right bold">{{ number_format($scholarship->scholarship_amount) }}</td>
                    <td>{{ $scholarship->recordedBy?->name ?? '—' }}</td>
                    <td>{{ $scholarship->reference ?: $scholarship->receipt_number ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:14px;">Aucune bourse ne correspond aux filtres.</td>
                </tr>
            @endforelse
        </tbody>
        @if($scholarships->isNotEmpty())
        <tfoot>
            <tr>
                <td colspan="3">Total</td>
                <td class="right">{{ number_format($totalScholarships) }} FCFA</td>
                <td colspan="2">{{ number_format($scholarshipCount) }} ligne(s)</td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer-note">
        {{ $school->full_name ?? 'COPTAN' }} · Document généré le {{ now()->format('d/m/Y à H:i') }}
    </div>
</div>
</body>
</html>
