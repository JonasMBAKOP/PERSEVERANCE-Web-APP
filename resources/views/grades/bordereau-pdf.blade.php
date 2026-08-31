<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bordereau Effectif de Notes — {{ $classGroup->full_name }}</title>
    @include('students.documents.partials.base-styles')
    <style>
        :root {
            --primary: #1A3A6B;
            --secondary: #FF8934;
            --surface: #F7F9FC;
            --border: #D1D5DB;
            --text: #111827;
            --muted: #475569;
        }

        @page {
            size: A4 landscape;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text);
            background: #E5E7EB;
        }

        body {
            padding: 0;
        }

        .document-canvas {
            width: 100%;
            max-width: none;
            min-height: 210mm;
            margin: 0 auto;
            padding: 1mm;
            background: white;
        }

        .info-row {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 10px;
            margin: 18px 0 16px;
        }

        .info-item {
            display: grid;
            gap: 4px;
            padding: 10px 12px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--surface);
            font-size: 10px;
        }

        .info-item span {
            display: block;
        }

        .info-label {
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: 8px;
        }

        .info-value {
            font-weight: 900;
            color: var(--text);
            font-size: 11px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .data-table th,
        .data-table td {
            border: 1px solid var(--border);
            padding: 6px 8px;
            text-align: center;
            vertical-align: middle;
        }

        .data-table th {
            background: #F2F4F7;
            font-weight: 900;
            color: var(--primary);
            font-size: 9px;
        }

        .data-table td:first-child {
            width: 24px;
        }

        .data-table td.name {
            text-align: left;
            font-weight: 700;
            text-transform: uppercase;
        }

        .vertical-text {
            writing-mode: vertical-rl;
            transform: rotate(180deg);
            white-space: nowrap;
            font-size: 9px;
            font-weight: 700;
            line-height: 1.1;
        }

        .tfoot-row td {
            background: #F8FAFC;
            font-weight: 700;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr auto;
            gap: 10px;
            margin-top: 18px;
            align-items: end;
        }

        .page-number {
            font-size: 9px;
            font-weight: 700;
            text-align: right;
        }

        .page-number::before {
            content: "Page " counter(page) " sur " counter(pages);
        }

        .signature-box {
            border: 1px dashed var(--border);
            min-height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            color: var(--muted);
            text-align: center;
            padding: 10px;
        }

        .footer-note {
            font-size: 9px;
            color: var(--muted);
        }

        body {
            padding: 0;
            counter-reset: page;
        }

        .page-number {
            font-size: 9px;
            font-weight: 700;
            text-align: right;
            position: fixed;
            bottom: 8mm;
            right: 8mm;
        }

        .overflow-x-auto {
            overflow-x: auto;
        }

        .bordereau-header {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 18px;
            align-items: start;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .bordereau-header__brand {
            display: flex;
            gap: 14px;
            align-items: center;
        }

        .bordereau-header__logo {
            width: 108px;
            min-width: 108px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bordereau-header__logo img {
            max-height: 104px;
            max-width: 104px;
            object-fit: contain;
        }

        .bordereau-header__logo-placeholder {
            width: 104px;
            height: 104px;
            border-radius: 18px;
            background: var(--primary);
            color: #fff;
            display: grid;
            place-items: center;
            font-size: 36px;
            font-weight: 900;
        }

        .bordereau-header__school-info {
            display: grid;
            gap: 6px;
        }

        .bordereau-header__school {
            font-size: 18px;
            font-weight: 900;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .bordereau-header__meta {
            font-size: 12px;
            color: var(--muted);
        }

        .bordereau-header__doc {
            display: grid;
            gap: 5px;
            justify-items: end;
            text-align: right;
        }

        .bordereau-header__doc-title {
            font-size: 13px;
            font-weight: 900;
            text-transform: uppercase;
            color: var(--primary);
            letter-spacing: .05em;
        }

        .bordereau-header__doc-copy {
            font-size: 12px;
            font-weight: 700;
            color: #CC6000;
        }

        .bordereau-header__doc-year,
        .bordereau-header__doc-date {
            font-size: 12px;
            font-weight: 700;
            color: var(--primary);
        }

        .bordereau-header__title-row {
            margin-top: 14px;
            text-align: center;
            grid-column: 1 / -1;
        }

        .bordereau-header__title {
            font-size: 26px;
            font-weight: 900;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: .08em;
            text-decoration: underline;
        }

        .bordereau-header__subtitle {
            font-size: 13px;
            color: var(--muted);
            margin-top: 4px;
        }

        .data-table th:first-child,
        .data-table td:first-child { width: 22px; }
        .data-table th:nth-child(2),
        .data-table td.name { min-width: 180px; }
        .data-table th:nth-child(n+3):not(:nth-last-child(-n+2)) { width: 34px; min-width: 34px; max-width: 34px; }
        .data-table th:nth-last-child(2),
        .data-table td:nth-last-child(2),
        .data-table th:last-child,
        .data-table td:last-child { width: 30px; }
    </style>
</head>
<body>
<div class="document-canvas">
    @include('grades.partials.bordereau-header', ['forPdf' => true, 'docTitle' => 'Bordereau Effectif de Notes'])
    <div class="info-row">
        <div class="info-item"><span class="info-label">Classe</span><span class="info-value">{{ $classGroup->full_name }}</span></div>
        <div class="info-item"><span class="info-label">Examen</span><span class="info-value">{{ $sequence->label }}</span></div>
        <div class="info-item"><span class="info-label">Effectif</span><span class="info-value">{{ $enrollments->count() }}</span></div>
        <div class="info-item"><span class="info-label">Nombre de garçons</span><span class="info-value">{{ $boys }}</span></div>
        <div class="info-item"><span class="info-label">Nombre de filles</span><span class="info-value">{{ $girls }}</span></div>
    </div>

    <div class="overflow-x-auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>N°</th>
                    <th class="name">Noms et Prénoms</th>
                    @foreach($subjects as $subject)
                        <th class="vertical-text">{{ $subject->subject->name_fr }} ({{ $subject->coefficient == (int) $subject->coefficient ? (int) $subject->coefficient : $subject->coefficient }})</th>
                    @endforeach
                    <th class="vertical-text">MOYENNE</th>
                    <th class="vertical-text">RANG</th>
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
                        <td class="name">{{ strtoupper($enrollment->student->last_name) }} {{ $enrollment->student->first_name }}</td>
                        @foreach($subjects as $subject)
                            @php $grade = $grades?->get($subject->id)?->grade; @endphp
                            <td>
                                @if($grade === null)
                                    —
                                @else
                                    {{ number_format($grade, 2) }}
                                @endif
                            </td>
                        @endforeach
                        <td>
                            @if($row && $row['average'] !== null)
                                {{ number_format($row['average'], 2) }}
                            @else
                                —
                            @endif
                        </td>
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

    <div class="footer-grid">
        <div class="footer-note">
            Bordereau de notes de la classe {{ $classGroup->full_name }} — {{ $sequence->label }}.
        </div>
        <div class="signature-box">Cachet et Signature de la Direction</div>
        <div class="page-number"></div>
    </div>
</div>
</body>
</html>
