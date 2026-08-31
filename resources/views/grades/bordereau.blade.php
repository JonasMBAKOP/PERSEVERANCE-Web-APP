<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bordereau Effectif de Notes — {{ $classGroup->full_name }}</title>
    @include('students.documents.partials.base-styles')
    <style>
        @page { size: A4 landscape; margin: 0; }
        body { background: #eef1f5; }
        .page { width: 100%; max-width: none; box-sizing: border-box; margin: 0 auto; padding: 1mm; background: #fff; }
        .print-toolbar { margin-bottom: 10px; }
        .document-card { border: 1px solid #CBD5E1; border-radius: 18px; padding: 18px; margin-bottom: 14px; }
        .header-row { display: grid; grid-template-columns: 1fr auto; gap: 16px; align-items: start; }
        .header-left { display: grid; gap: 4px; }
        .document-label { font-size: 10px; letter-spacing: .18em; text-transform: uppercase; color: #475569; font-weight: 700; }
        .document-title { font-size: 26px; font-weight: 900; color: #1A3A6B; letter-spacing: .03em; margin: 0; }
        .info-row { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 12px; margin-top: 18px; }
        .info-item { padding: 14px 16px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 14px; }
        .bordereau-table th:first-child,
        .bordereau-table td:first-child { width: 22px; }
        .bordereau-table th:nth-child(2),
        .bordereau-table td:nth-child(2) { min-width: 180px; }
        .bordereau-table th:nth-child(n+3):not(:nth-last-child(-n+2)) { width: 34px; min-width: 34px; max-width: 34px; }
        .bordereau-table th:nth-last-child(2),
        .bordereau-table td:nth-last-child(2),
        .bordereau-table th:last-child,
        .bordereau-table td:last-child { width: 30px; }
        .info-label { display: block; font-size: 9px; color: #475569; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 4px; }
        .info-value { font-size: 12px; color: #1A3A6B; font-weight: 900; }
        .bordereau-table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 11px; }
        .bordereau-table th, .bordereau-table td { border: 1px solid #CBD5E1; padding: 6px 6px; }
        .bordereau-table th { background: #F8FAFC; color: #1A3A6B; font-weight: 900; font-size: 9px; }
        .bordereau-table td { color: #1F2937; }
        .bordereau-table td.name-cell { text-align: left; font-weight: 700; text-transform: uppercase; }
        .subject-label { writing-mode: vertical-rl; transform: rotate(180deg); white-space: nowrap; font-size: 9px; line-height: 1.1; }
        .summary-note { margin-top: 14px; font-size: 9px; color: #64748B; }
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
        .bordereau-header__title-row { margin-top: 14px; text-align: center; grid-column: 1 / -1; }
        .bordereau-header__title { font-size: 24px; font-weight: 900; color: #1A3A6B; text-transform: uppercase; letter-spacing: .08em; text-decoration: underline; }
        .bordereau-header__subtitle { font-size: 12px; color: #475569; margin-top: 4px; }
        .page-number { margin-top: 12px; font-size: 10px; text-align: right; color: #475569; }
        @media print {
            html, body { width: 100%; margin: 0; padding: 0; background: #fff; }
            .page { width: 100%; max-width: none; margin: 0; padding: 1mm; }
            .page-number { color: transparent; }
            .page-number::before { content: "Page " counter(page) " sur " counter(pages); color: #475569; position: fixed; bottom: 8mm; right: 8mm; }
        }
        .summary-note { margin-top: 14px; font-size: 9px; color: #64748B; }
        .no-print { display: block; }
        @media print { .no-print { display: none !important; } }
        thead { display: table-row-group; }
    </style>
</head>
<body>
@include('students.documents.partials.print-toolbar')
<div class="page">
    @include('grades.partials.bordereau-header', ['forPdf' => false, 'docTitle' => 'Bordereau Effectif de Notes'])
    <div class="info-row">
        <div class="info-item"><span class="info-label">Classe</span><span class="info-value">{{ $classGroup->full_name }}</span></div>
        <div class="info-item"><span class="info-label">Examen</span><span class="info-value">{{ $sequence->label }}</span></div>
        <div class="info-item"><span class="info-label">Année Scolaire</span><span class="info-value">{{ $classGroup->academicYear->label ?? '—' }}</span></div>
        <div class="info-item"><span class="info-label">Effectif</span><span class="info-value">{{ $enrollments->count() }}</span></div>
        <div class="info-item"><span class="info-label">Garçons / Filles</span><span class="info-value">{{ $boys }} / {{ $girls }}</span></div>
    </div>
    <div style="overflow-x:auto;">
        <table class="bordereau-table">
            <thead>
                <tr>
                    <th style="min-width:18px;">N°</th>
                    <th style="min-width:160px; text-align:left;">Noms et Prénoms</th>
                    @foreach($subjects as $subject)
                        <th style="width: 34px; min-width: 34px; max-width: 34px;"><div class="subject-label">{{ $subject->subject->name_fr }} ({{ $subject->coefficient == (int) $subject->coefficient ? (int) $subject->coefficient : $subject->coefficient }})</div></th>
                    @endforeach
                    <th style="width: 34px; min-width: 34px; max-width: 34px;"><div class="subject-label">MOYENNE ({{ $totalCoefficient }})</div></th>
                    <th style="width: 30px; min-width: 30px; max-width: 30px;"><div class="subject-label">RANG</div></th>
                </tr>
            </thead>
            <tbody>
                @foreach($enrollments as $index => $enrollment)
                    @php
                        $grades = $allGrades->get($enrollment->id);
                        $row = $studentAverages->get($enrollment->id);
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="name-cell">{{ strtoupper($enrollment->student->last_name) }} {{ $enrollment->student->first_name }}</td>
                        @foreach($subjects as $subject)
                            @php $grade = $grades?->get($subject->id)?->grade; @endphp
                            <td>{{ $grade !== null ? number_format($grade, 2) : '—' }}</td>
                        @endforeach
                        <td>{{ $row && $row['average'] !== null ? number_format($row['average'], 2) : '—' }}</td>
                        <td>{{ $studentRanks[$enrollment->id] ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="tfoot-row">
                    <td colspan="2" class="text-left">Moyenne générale</td>
                    @foreach($subjects as $subject)
                        <td>{{ optional($subjectSummaries->get($subject->id))['average'] !== null ? number_format($subjectSummaries->get($subject->id)['average'], 2) : '—' }}</td>
                    @endforeach
                    <td class="font-bold">—</td>
                    <td>—</td>
                </tr>
                <tr>
                    <td colspan="2" class="text-left">Taux de réussite (%)</td>
                    @foreach($subjects as $subject)
                        <td>{{ optional($subjectSummaries->get($subject->id))['success_rate'] !== null ? number_format($subjectSummaries->get($subject->id)['success_rate'], 2) : '—' }}</td>
                    @endforeach
                    <td>—</td>
                    <td>—</td>
                </tr>
                <tr>
                    <td colspan="2" class="text-left">Taux réussite garçons (%)</td>
                    @foreach($subjects as $subject)
                        <td>{{ optional($subjectSummaries->get($subject->id))['success_rate_boys'] !== null ? number_format($subjectSummaries->get($subject->id)['success_rate_boys'], 2) : '—' }}</td>
                    @endforeach
                    <td>—</td>
                    <td>—</td>
                </tr>
                <tr>
                    <td colspan="2" class="text-left">Taux réussite filles (%)</td>
                    @foreach($subjects as $subject)
                        <td>{{ optional($subjectSummaries->get($subject->id))['success_rate_girls'] !== null ? number_format($subjectSummaries->get($subject->id)['success_rate_girls'], 2) : '—' }}</td>
                    @endforeach
                    <td>—</td>
                    <td>—</td>
                </tr>
                <tr class="tfoot-row">
                    <td colspan="2" class="text-left">Dernier</td>
                    @foreach($subjects as $subject)
                        <td>{{ optional($subjectSummaries->get($subject->id))['min'] !== null ? number_format($subjectSummaries->get($subject->id)['min'], 2) : '—' }}</td>
                    @endforeach
                    <td>—</td>
                    <td>—</td>
                </tr>
                <tr class="tfoot-row">
                    <td colspan="2" class="text-left">Premier</td>
                    @foreach($subjects as $subject)
                        <td>{{ optional($subjectSummaries->get($subject->id))['max'] !== null ? number_format($subjectSummaries->get($subject->id)['max'], 2) : '—' }}</td>
                    @endforeach
                    <td>—</td>
                    <td>—</td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="summary-note">Document imprimable : si la classe dépasse l'espace vertical, la pagination est normale.</div>
    <div class="page-number">Page 1 sur 1</div>
</div>
</body>
</html>
