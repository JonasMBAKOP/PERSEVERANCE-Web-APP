<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Registre des consultations - {{ $school->short_name ?? 'Etablissement' }}</title>
    @include('students.documents.partials.base-styles')
    <style>
        @page { size: A4 portrait; margin: 5mm; }
        .infirmary-report { max-width: 287mm; margin: 0 auto; padding: 1mm; }
        .bordereau-header { display: grid; grid-template-columns: 1.45fr .9fr; gap: 18px; align-items: start; padding-bottom: 8px; margin-bottom: 8px; border-bottom: 1px solid #CBD5E1; }
        .bordereau-header__brand { display: flex; gap: 12px; align-items: center; }
        .bordereau-header__logo { width: 68px; min-width: 68px; display: flex; align-items: center; justify-content: center; }
        .bordereau-header__logo img { max-width: 64px; max-height: 64px; object-fit: contain; }
        .bordereau-header__logo-placeholder { width: 62px; height: 62px; display: grid; place-items: center; background: #1A3A6B; color: #fff; font-size: 24px; font-weight: 800; }
        .bordereau-header__school-info { display: grid; gap: 2px; }
        .bordereau-header__school { font-size: 11px; font-weight: 800; color: #1A3A6B; }
        .bordereau-header__meta { font-size: 8px; color: #475569; }
        .bordereau-header__doc { display: grid; gap: 2px; justify-items: end; text-align: right; }
        .bordereau-header__doc-title { font-size: 11px; font-weight: 800; color: #1A3A6B; }
        .bordereau-header__doc-copy { font-size: 9px; font-weight: 700; color: #A35200; }
        .bordereau-header__doc-year { font-size: 9px; color: #1A3A6B; font-weight: 700; }
        .bordereau-header__title-row { margin: 7px 0 10px; text-align: center; }
        .bordereau-header__title { font-size: 17px; font-weight: 800; color: #1A3A6B; text-decoration: underline; }
        .bordereau-header__subtitle { margin-top: 2px; font-size: 9px; color: #475569; }
        .report-subtitle { margin: 0 0 10px; text-align: center; color: #475569; font-size: 10px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; font-size: 8px; }
        th, td { border: 1px solid #94A3B8; padding: 5px 4px; vertical-align: middle; }
        th { background: #EFF6FF; color: #172554; text-align: center; font-size: 8px; font-weight: 800; text-transform: uppercase; }
        td { color: #111827; line-height: 1.25; }
        .center { text-align: center; }
        .temperature-column { width: 34px; }
        .empty { padding: 14px; text-align: center; color: #64748B; font-style: italic; }
        @media print { .infirmary-report { max-width: none; padding: 0; } }
    </style>
</head>
<body>
@include('students.documents.partials.print-toolbar')
<main class="page infirmary-report">
    @include('grades.partials.bordereau-header', [
        'docTitleFr' => 'REGISTRE DES CONSULTATIONS INFIRMERIE',
        'docSubtitle' => $filterLabel,
        'forPdf' => false,
    ])
    <p class="report-subtitle">Annee scolaire : {{ $academicYearLabel }} - {{ $visits->count() }} consultation(s)</p>
    <table>
        <thead><tr><th>Date</th><th>Eleve</th><th>Sexe</th><th>Classe</th><th>Age</th><th>Parent</th><th class="temperature-column">T°</th><th>Motif</th><th>Traitement</th><th>Enregistre par</th></tr></thead>
        <tbody>
            @forelse($visits as $visit)
                <tr><td class="center">{{ $visit->visit_date?->format('d/m/Y') }}<br><span style="font-size: 7px; color: #64748B;">{{ substr((string) $visit->visit_time, 0, 5) }}</span></td><td>{{ $visit->student_name }}</td><td class="center">{{ $visit->student_gender ?: '-' }}</td><td>{{ $visit->class_name ?? '-' }}</td><td class="center">{{ $visit->student_age !== null ? $visit->student_age . ' ans' : '-' }}</td>@php($parentPhones = collect([$visit->student?->father_phone, $visit->student?->mother_phone, $visit->student?->guardian_phone, $visit->parent_phone])->filter(fn ($phone) => filled($phone))->map(fn ($phone) => trim($phone))->unique()->values())<td>@forelse($parentPhones as $phone)<div>{{ $phone }}</div>@empty-@endforelse</td><td class="center temperature-column">{{ $visit->temperature !== null ? number_format((float) $visit->temperature, 1, ',', ' ') . ' C' : '-' }}</td><td>{{ $visit->visit_reason }}</td><td>{{ $visit->treatment ?: '-' }}</td><td>{{ $visit->recorder_name }}</td></tr>
            @empty
                <tr><td colspan="10" class="empty">Aucune consultation pour les filtres selectionnes.</td></tr>
            @endforelse
        </tbody>
    </table>
</main>
</body>
</html>
