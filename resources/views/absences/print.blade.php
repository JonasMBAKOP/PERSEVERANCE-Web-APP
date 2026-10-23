<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste des absences - {{ $filterLabel }}</title>
    <style>
        @page { size: A4 portrait; margin: 8mm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; color: #111827; font-family: Arial, sans-serif; }
        .no-print { text-align: center; padding: 12px; margin-bottom: 12px; background: #f3f4f6; }
        .no-print button { border: 0; border-radius: 6px; padding: 8px 18px; background: #1A3A6B; color: #fff; font-weight: 700; cursor: pointer; }
        .document { width: 100%; }
        .bordereau-header { display: grid; grid-template-columns: 1.5fr 1fr; gap: 18px; align-items: start; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #CBD5E1; }
        .bordereau-header__brand { display: flex; gap: 14px; align-items: center; }
        .bordereau-header__logo { width: 88px; min-width: 88px; display: flex; align-items: center; justify-content: center; }
        .bordereau-header__logo img { max-height: 84px; max-width: 84px; object-fit: contain; }
        .bordereau-header__logo-placeholder { width: 84px; height: 84px; border-radius: 18px; background: #1A3A6B; color: #fff; display: grid; place-items: center; font-size: 32px; font-weight: 900; }
        .bordereau-header__school-info { display: grid; gap: 4px; }
        .bordereau-header__school { font-size: 14px; font-weight: 900; color: #1A3A6B; text-transform: uppercase; letter-spacing: .03em; }
        .bordereau-header__meta { font-size: 10px; color: #475569; }
        .bordereau-header__doc { display: grid; gap: 4px; justify-items: end; text-align: right; }
        .bordereau-header__doc-title { font-size: 13px; font-weight: 900; text-transform: uppercase; color: #1A3A6B; letter-spacing: .05em; }
        .bordereau-header__doc-copy { font-size: 12px; font-weight: 700; color: #CC6000; }
        .bordereau-header__doc-year { font-size: 12px; color: #1A3A6B; font-weight: 700; }
        .bordereau-header__title-row { margin-top: 14px; margin-bottom: 14px; text-align: center; }
        .bordereau-header__title { font-size: 22px; font-weight: 900; color: #1A3A6B; text-transform: uppercase; letter-spacing: .06em; text-decoration: underline; }
        .bordereau-header__subtitle { font-size: 12px; color: #475569; margin-top: 4px; }
        .filter-note { margin: 0 0 12px; text-align: center; font-size: 11px; color: #475569; }
        .class-block { margin-top: 16px; }
        .class-title { margin: 0 0 6px; padding: 7px 10px; text-align: center; background: #F2F4F7; border: 1.5px solid #9CA3AF; color: #1A3A6B; font-size: 14px; font-weight: 900; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 10px; }
        th, td { border: 1px solid #9CA3AF; padding: 5px 4px; vertical-align: middle; }
        th { background: #F8FAFC; color: #1A3A6B; font-size: 9px; font-weight: 900; text-transform: uppercase; text-align: center; }
        td { text-align: center; }
        td.student, td.subject, td.observation { text-align: left; }
        th:nth-child(1) { width: 5%; } th:nth-child(2) { width: 32%; } th:nth-child(3) { width: 18%; }
        th:nth-child(4) { width: 15%; } th:nth-child(5) { width: 15%; } th:nth-child(6) { width: 15%; }
        .empty { padding: 22px; border: 1px solid #D1D5DB; text-align: center; color: #6B7280; font-style: italic; }
        @media print { .no-print { display: none !important; } .class-block { break-inside: auto; } tr { break-inside: avoid; } }
    </style>
</head>
<body>
    @include('students.documents.partials.print-toolbar')
    @php
        $classGroup = $headerClassGroup;
        $forPdf = false;
    @endphp
    <main class="document">
        @include('grades.partials.bordereau-header', ['forPdf' => false, 'docTitle' => 'Liste des absences', 'docSubtitle' => $filterLabel . ' — ' . $periodLabel])
        @forelse($groups as $group)
            @php $class = $group->first()['classGroup']; @endphp
            <section class="class-block">
                <h2 class="class-title">{{ $class->full_name }}</h2>
                <table>
                    <thead><tr>
                        <th>#</th><th>Élève</th><th>Matricule</th><th>Total</th>
                        <th>Justifiées</th><th>Non justifiées</th>
                    </tr></thead>
                    <tbody>
                    @foreach($group as $index => $row)
                        @php
                            $enrollment = $row['enrollment'];
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="student">{{ $enrollment->student?->full_name ?? 'Élève inconnu' }}</td>
                            <td>{{ $enrollment->student?->matricule ?? '—' }}</td>
                            <td>{{ number_format($row['hours'], 1, ',', '') }} h</td>
                            <td>{{ number_format($row['justified_hours'], 1, ',', '') }} h</td>
                            <td>{{ number_format($row['unjustified_hours'], 1, ',', '') }} h</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </section>
        @empty
            <div class="empty">Aucun élève ne possède au moins une heure d’absence pour les filtres sélectionnés.</div>
        @endforelse
    </main>
</body>
</html>
