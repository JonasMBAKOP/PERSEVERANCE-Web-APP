<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Liste des élèves — {{ $year->label ?? '' }}</title>
@include('students.documents.partials.base-styles')
<style>
@page { size: A4 portrait; margin: 2mm 4mm; }
body { margin: 0; padding: 0; }
.student-list-page {
    max-width: 196mm;
    padding: 3mm 4mm;
    color: #111827;
}
.student-list-page .cert-official-header {
    margin-bottom: 10px;
}
.student-list-title {
    background: #E5E7EB;
    border: 1px solid #4B5563;
    padding: 10px 12px;
    margin-bottom: 16px;
    text-align: center;
    font-family: Georgia, 'Times New Roman', serif;
    font-size: 26px;
    font-weight: 900;
    line-height: 1.1;
    text-transform: uppercase;
}
.student-list-subtitle {
    margin-top: 4px;
    font-family: Arial, Helvetica, sans-serif;
    font-size: 12px;
    font-weight: 700;
    text-transform: none;
}
.section-banner {
    background: #1A3A6B; color: #fff; padding: 10px 12px;
    font-size: 13px; font-weight: 900; text-transform: uppercase;
    margin: 18px 0 10px; border-radius: 4px;
}
.section-banner:first-of-type { margin-top: 0; }
.class-title {
    font-size: 14px; font-weight: 900; color: #9c4005;
    border-bottom: 1px solid #E5E7EB; padding-bottom: 6px; margin: 14px 0 8px;
}
.list-table { width: 100%; border-collapse: collapse; font-size: 10px; margin-bottom: 16px; }
.list-table th, .list-table td { border: 1px solid #D1D5DB; padding: 6px 8px; }
.list-table th { background: #F3F4F6; font-weight: 700; text-align: left; font-size: 10px; }
.list-table td.num { width: 32px; text-align: center; color: #6B7280; font-size: 10px; }
.list-table td.mat { font-family: 'Courier New', monospace; font-size: 10px; }
.list-summary { font-size: 10px; color: #6B7280; margin-bottom: 8px; }
.footer-signature-right {
    margin-top: 60px;
    margin-right: 18px;
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
    .section-banner { page-break-before: auto; }
    .class-block { page-break-inside: avoid; }
}
</style>
</head>
<body>
@include('students.documents.partials.print-toolbar')

<div class="page student-list-page">
    @php
        $totalStudents = 0;
        $isSingleClass = ($filters['scope'] ?? '') === 'class';
        $listSubtitle = $year?->label ? 'Année scolaire ' . $year->label : '';
        if ($isSingleClass && !empty($groups[0]['classes'][0]['class'])) {
            $listSubtitle .= ' — Classe ' . $groups[0]['classes'][0]['class']->full_name;
        }
        $listSubtitle .= ' — ' . now()->format('d/m/Y');
    @endphp

    @include('students.documents.partials.certificate-official-header', [
        'showCertificateTitle' => false,
    ])

    <div class="student-list-title">
        <div>Liste des élèves</div>
        <div class="student-list-subtitle">{{ $listSubtitle }}</div>
    </div>

    @foreach($groups as $group)
    @unless($isSingleClass)
    <div class="section-banner">
        Section : {{ $group['section']->name }}
        ({{ $group['section']->code }})
    </div>
    @endunless

    @foreach($group['classes'] as $block)
    @php $totalStudents += $block['students']->count(); @endphp
    <div class="class-block">
        <div class="class-title">
            Classe : {{ $block['class']->full_name }}
            @unless($isSingleClass)
            — Niveau {{ $block['class']->level?->name }}
            @endunless
        </div>
        <div class="list-summary">{{ $block['students']->count() }} élève(s) inscrit(s)</div>
        <table class="list-table">
            <thead>
                <tr>
                    <th class="num">N°</th>
                    <th>Matricule</th>
                    <th>Nom</th>
                    <th>Prénom(s)</th>
                    <th>Sexe</th>
                    <th>Date naiss.</th>
                    <th>Lieu de naissance</th>
                    <th>Inscrit(e) le</th>
                </tr>
            </thead>
            <tbody>
                @foreach($block['students'] as $i => $student)
                @php $printEnrollment = $student->printEnrollment ?? null; @endphp
                <tr>
                    <td class="num">{{ $i + 1 }}</td>
                    <td class="mat">{{ $student->matricule }}</td>
                    <td><strong>{{ $student->last_name }}</strong></td>
                    <td>{{ $student->first_name }}</td>
                    <td>{{ $student->gender === 'M' ? 'M' : 'F' }}</td>
                    <td>{{ $student->date_of_birth?->format('d/m/Y') ?? '—' }}</td>
                    <td>{{ strtoupper($student->place_of_birth ?? '—') }}</td>
                    <td>{{ $printEnrollment?->enrollment_date?->format('d/m/Y') ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach
    @endforeach

    <div class="footer-signature-right">
        <div>La Direction</div>
        @if($school->signature_seal)
            <img src="{{ asset('storage/' . $school->signature_seal) }}" alt="Cachet du Principal" class="footer-principal-seal">
        @endif
    </div>
    {{-- <div class="footer-note">
        Total général : {{ $totalStudents }} élève(s) — Document généré le {{ now()->format('d/m/Y à H:i') }}
    </div> --}}
</div>
</body>
</html>
