<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
@php
    $documentBrand = str_contains(strtolower((string) ($school->full_name ?? '')), 'perseverance') ? 'PERSEVERANCE' : 'COPTAN';
    $documentRange = ($year?->start_date?->format('Y') ?? '') . '-' . ($year?->end_date?->format('Y') ?? '');
    $documentScope = (($filters['scope'] ?? null) === 'section' && ! empty($report['sections'][0]['section']))
        ? ($report['sections'][0]['section']->code ?: $report['sections'][0]['section']->name)
        : 'Complet';
    $documentFilename = 'Rapport des effectifs ' . $documentScope . ' ' . $documentBrand . ' ' . $documentRange;
@endphp
<title>{{ $documentFilename }}</title>
@include('students.documents.partials.base-styles')
<style>
@page {
    size: A4 portrait;
    margin: 2mm 4mm 9mm;
    @bottom-right {
        content: counter(page) " / " counter(pages);
        font-family: Arial, Helvetica, sans-serif;
        font-size: 9px;
        color: #111827;
    }
}
.enrollment-report-page {
    max-width: 196mm;
    margin: 0 auto;
    padding: 3mm 3mm;
    color: #111827;
}
.enrollment-report-page .cert-official-header {
    margin-bottom: 6px;
}
.enrollment-report-page .cert-official-header__side {
    min-height: 40mm;
}
.enrollment-report-page .cert-official-header__motto,
.enrollment-report-page .cert-official-header__stars,
.enrollment-report-page .cert-official-header__ministry,
.enrollment-report-page .cert-official-header__school {
    margin-top: 1px;
    margin-bottom: 1px;
}
.enrollment-report-page .cert-official-header__logo img,
.enrollment-report-page .cert-official-header__logo-placeholder {
    width: 30mm;
    height: 30mm;
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
        margin-bottom: 10mm;
    page-break-inside: auto;
    break-inside: auto;
}
.enrollment-report-block:first-of-type {
    margin-top: 0;
}
.enrollment-report-block--last { margin-bottom: 0; }
.enrollment-report-page,
.enrollment-report-page * { color: #000 !important; }
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
    max-width: 180px;
    max-height: 180px;
    margin-top: 6px;
    display: block;
}
.print-page-number { display: none; }
@media print {
    body { background: #fff !important; }
    .enrollment-report-block { page-break-inside: auto; break-inside: auto; }
    .enrollment-report-table thead { display: table-row-group; }
    .enrollment-report-table tr { page-break-inside: avoid; break-inside: avoid; }
    .print-page-number {
        display: block;
        position: fixed;
        right: 4mm;
        bottom: 2mm;
        font: 9px Arial, Helvetica, sans-serif;
        color: #111827;
    }
    .print-page-number::after { content: counter(page) " / " counter(pages); }
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
        <section class="enrollment-report-block {{ $loop->last ? 'enrollment-report-block--last' : '' }}">
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
    {{-- <div class="print-page-number" aria-hidden="true"></div> --}}
    {{-- <div class="footer-note">
        Document généré le {{ now()->format('d/m/Y à H:i') }} — {{ $school->short_name ?? 'COPTAN' }}
    </div> --}}
</div>
</body>
</html>
