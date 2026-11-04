<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Liste des matières</title>
    @include('students.documents.partials.base-styles')
    <style>
        @page { size: A4 portrait; margin: 10mm; }
        body { background: #eef1f5; color: #111827; }
        .page { width: 100%; max-width: 900px; margin: 0 auto; padding: 24px; background: #fff; box-sizing: border-box; }
        .no-print { margin-bottom: 14px; }
        .report-title { margin: 18px 0 4px; text-align: center; font-size: 22px; font-weight: 900; color: #1A3A6B; text-transform: uppercase; text-decoration: underline; }
        .report-subtitle { margin-bottom: 18px; text-align: center; font-size: 12px; color: #475569; font-weight: 700; }
        .class-block { margin: 26px 0 22px; page-break-inside: avoid; }
        .class-title { margin: 0 0 7px; padding: 7px 10px; background: #F1F5F9; border: 1px solid #CBD5E1; color: #1A3A6B; text-align: center; font-size: 14px; font-weight: 900; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 12px; }
        th, td { border: 1px solid #94A3B8; padding: 6px 7px; vertical-align: middle; }
        th { background: #E2E8F0; color: #111827; font-weight: 900; text-transform: uppercase; }
        th:nth-child(1), td:nth-child(1) { width: 52%; text-align: left; }
        th:nth-child(2), td:nth-child(2) { width: 18%; text-align: center; }
        th:nth-child(3), td:nth-child(3) { width: 30%; text-align: left; }
        .empty { padding: 12px; text-align: center; color: #64748B; }
        .page-number { margin-top: 14px; text-align: right; font-size: 9px; color: #64748B; }
        @media print {
            html, body { margin: 0; padding: 0; background: #fff; }
            .page { max-width: none; padding: 0; }
            .no-print { display: none !important; }
            .class-block { page-break-inside: avoid; }
            .page-number { position: fixed; right: 0; bottom: 0; }
            .page-number::after { content: "Page " counter(page) " / " counter(pages); }
            .page-number { color: transparent; }
        }
    </style>
</head>
<body>
    @if(!$forPdf)
        @include('students.documents.partials.print-toolbar')
    @endif
    @php($classGroup = $classes->first())
    <main class="page">
        @if($classGroup)
            @include('grades.partials.bordereau-header', ['forPdf' => $forPdf, 'docTitleFr' => 'Liste des matières', 'docSubtitle' => $docSubtitle])
        @else
            <h1 class="report-title">Liste des matières</h1>
            <div class="report-subtitle">{{ $docSubtitle }} — Année scolaire : {{ $academicYear->label }}</div>
        @endif
        @forelse($classes as $class)
            <section class="class-block">
                <h2 class="class-title">{{ $class->full_name }}</h2>
                <table>
                    <thead><tr><th>Matière</th><th>Barème</th><th>Enseignant</th></tr></thead>
                    <tbody>
                    @forelse($class->classSubjects as $classSubject)
                        <tr><td>{{ $classSubject->subject?->name_fr ?? '—' }}</td><td>{{ rtrim(rtrim(number_format((float) $classSubject->grading_scale, 2, '.', ''), '0'), '.') }}</td><td>{{ $classSubject->teacherAssignments->first()?->staff?->full_name ?? 'Non affecté' }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="empty">Aucune matière assignée.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </section>
        @empty
            <p class="empty">Aucune classe ne correspond aux filtres choisis.</p>
        @endforelse
        <div class="page-number">&nbsp;</div>
    </main>
</body>
</html>
