<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Rapport des effectifs totaux — {{ $year->label }}</title>
@include('students.documents.partials.base-styles')
<style>
@page { size: A4 portrait; margin: 4mm 5mm; }
.enrollment-report-page {
    max-width: 192mm;
    padding: 5mm 6mm;
    color: #111827;
}
.enrollment-report-page .cert-official-header {
    margin-bottom: 10px;
}
.enrollment-report-title {
    background: #E5E7EB;
    border: 1px solid #4B5563;
    padding: 10px 12px;
    margin-bottom: 16px;
    text-align: center;
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 26px;
    font-weight: 900;
    line-height: 1.05;
    text-transform: uppercase;
}
.enrollment-report-subtitle {
    margin-top: 4px;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 12px;
    font-weight: 700;
    text-transform: none;
}
.enrollment-report-block {
    margin-top: 18px;
    page-break-inside: avoid;
}
.enrollment-report-block:first-of-type {
    margin-top: 0;
}
.enrollment-report-block__title {
    margin-bottom: 0;
    padding: 10px 12px;
    background: #fff;
    color: #111827;
    border: 1px solid #4B5563;
    border-bottom: 0;
    text-align: left;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 15px;
    font-weight: 900;
    text-transform: uppercase;
}
.enrollment-report-block__meta {
    margin-top: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: none;
    opacity: 0.85;
}
.enrollment-report-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 12px;
}
.enrollment-report-table th,
.enrollment-report-table td {
    border: 1px solid #4B5563;
    padding: 8px 10px;
    text-align: center;
    vertical-align: middle;
}
.enrollment-report-table th {
    font-weight: 900;
    background: #f7f7f7;
    color: #111827;
    font-size: 11px;
}
.enrollment-report-table td:first-child {
    text-align: left;
    font-weight: 700;
}
.enrollment-report-table .total-row td {
    font-weight: 900;
    background: #fff;
    border-top: 2px solid #111827;
}
.enrollment-report-summary {
    margin-top: 0;
    page-break-inside: avoid;
}
.enrollment-report-table .number-col {
    width: 24%;
}
.enrollment-report-table .name-col {
    width: 28%;
}
.footer-signature-right {
    margin-top: 60px;
    margin-right: 20px;
    padding-right: 10px;
    text-align: center;
    font-size: 13px;
    font-weight: 900;
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    float: right;
}
.footer-principal-seal {
    max-width: 100px;
    max-height: 100px;
    margin-top: 6px;
    display: block;
}
@media print {
    body { background: #fff !important; }
}
</style>
</head>
<body>
@include('students.documents.partials.print-toolbar')

<div class="page enrollment-report-page">
    @php
        $isSectionScope = ($filters['scope'] ?? 'school') === 'section';
    @endphp

    @include('students.documents.partials.certificate-official-header', [
        'showCertificateTitle' => false,
    ])

    <div class="enrollment-report-title">
        <div>RAPPORT DES EFFECTIFS TOTAUX</div>
        <div class="enrollment-report-subtitle">Année scolaire {{ $year->label }}</div>
    </div>

    @unless($isSectionScope)
    <section class="enrollment-report-block enrollment-report-summary">
        <div class="enrollment-report-block__title" style="text-align: center;">
            Synthèse générale par section
            <div class="enrollment-report-block__meta">Tableau du rapport des effectifs totaux pour tout l'établissement</div>
        </div>
        <table class="enrollment-report-table">
            <thead>
                <tr>
                    <th rowspan="2" class="name-col">SECTIONS</th>
                    <th colspan="3">EFFECTIFS</th>
                </tr>
                <tr>
                    <th class="number-col">Filles</th>
                    <th class="number-col">Garçons</th>
                    <th class="number-col">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($report['sections'] as $sectionReport)
                    <tr>
                        <td>{{ $sectionReport['section']->name }}</td>
                        <td>{{ $sectionReport['totals']['girls'] }}</td>
                        <td>{{ $sectionReport['totals']['boys'] }}</td>
                        <td>{{ $sectionReport['totals']['total'] }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td>TOTAL</td>
                    <td>{{ $report['totals']['girls'] }}</td>
                    <td>{{ $report['totals']['boys'] }}</td>
                    <td>{{ $report['totals']['total'] }}</td>
                </tr>
            </tbody>
        </table>
    </section>
    @endunless

    @foreach($report['sections'] as $sectionReport)
        <section class="enrollment-report-block">
            <div class="enrollment-report-block__title" style="text-align: center;" >
                Détail par classe — {{ $sectionReport['section']->name }}
                <div class="enrollment-report-block__meta">Tableau du rapport des effectifs totaux pour une section précise</div>
            </div>
            <table class="enrollment-report-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="name-col">CLASSES</th>
                        <th colspan="3">EFFECTIFS</th>
                    </tr>
                    <tr>
                        <th class="number-col">Filles</th>
                        <th class="number-col">Garçons</th>
                        <th class="number-col">TOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sectionReport['rows'] as $row)
                        <tr>
                            <td>{{ $row['class']->full_name }}</td>
                            <td>{{ $row['girls'] }}</td>
                            <td>{{ $row['boys'] }}</td>
                            <td>{{ $row['total'] }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td>TOTAL</td>
                        <td>{{ $sectionReport['totals']['girls'] }}</td>
                        <td>{{ $sectionReport['totals']['boys'] }}</td>
                        <td>{{ $sectionReport['totals']['total'] }}</td>
                    </tr>
                </tbody>
            </table>
        </section>
    @endforeach

    <div class="footer-signature-right">
        <div>La Direction</div>
        @if($school->signature_seal)
            <img src="{{ asset('storage/' . $school->signature_seal) }}" alt="Cachet du Principal" class="footer-principal-seal">
        @endif
    </div>
    {{-- <div class="footer-note">
        Document généré le {{ now()->format('d/m/Y à H:i') }} — {{ $school->short_name ?? 'COPTAN' }}
    </div> --}}
</div>
</body>
</html>
