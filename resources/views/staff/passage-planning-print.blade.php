<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $docTitle }}</title>
    @include('students.documents.partials.base-styles')
    <style>
        @page { size: A4 portrait; margin: 2mm 3mm; }
        body {
            background: #f8fafc;
            color: #1A3A6B;
            font-size: 12px;
        }
        .page {
            max-width: 297mm;
            margin: 0 auto;
            padding: 2mm 3mm;
            background: #fff;
            color: #1A3A6B;
        }
        .print-toolbar { margin-bottom: 8px; }
        .bordereau-header { display: grid; grid-template-columns: 1.5fr 1fr; gap: 18px; align-items: start; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #CBD5E1; }
        .bordereau-header__brand { display: flex; gap: 14px; align-items: center; }
        .bordereau-header__logo { width: 88px; min-width: 88px; display: flex; align-items: center; justify-content: center; }
        .bordereau-header__logo img { max-height: 84px; max-width: 84px; object-fit: contain; }
        .bordereau-header__logo-placeholder { width: 84px; height: 84px; border-radius: 18px; background: #1A3A6B; color: #fff; display: grid; place-items: center; font-size: 32px; font-weight: 900; }
        .bordereau-header__school-info { display: grid; gap: 4px; }
        .bordereau-header__school { font-size: 14px; font-weight: 900; color: #1A3A6B; text-transform: uppercase; letter-spacing: .03em; }
        .bordereau-header__meta { font-size: 10px; color: #334155; }
        .bordereau-header__doc { display: grid; gap: 4px; justify-items: end; text-align: right; }
        .bordereau-header__doc-title { font-size: 15px; font-weight: 900; text-transform: uppercase; color: #1A3A6B; letter-spacing: .05em; }
        .bordereau-header__doc-copy { font-size: 12px; font-weight: 700; color: #CC6000; }
        .bordereau-header__doc-year { font-size: 12px; color: #1A3A6B; font-weight: 700; }
        .bordereau-header__title-row { margin-top: 14px; text-align: center; grid-column: 1 / -1; }
        .bordereau-header__title { font-size: 28px; font-weight: 900; color: #1A3A6B; text-transform: uppercase; letter-spacing: .08em; text-decoration: underline; }
        .bordereau-header__subtitle { font-size: 12px; color: #334155; margin-top: 4px; }
        .info-row { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 18px; }
        .info-item { padding: 12px 14px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; }
        .info-label { display: block; font-size: 12px; color: #475569; font-weight: 900; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 4px; }
        .info-value { font-size: 16px; color: #1A3A6B; font-weight: 900; }
        .bordereau-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .bordereau-table th, .bordereau-table td { border: 1px solid #cbd5e1; padding: 8px 10px; }
        .bordereau-table th { background: #f8fafc; color: #1A3A6B; font-weight: 900; text-align: left; }
        .bordereau-table td { color: #1f2937; vertical-align: top; }
        .bordereau-table td.name-cell { font-weight: 700; color: #1A3A6B; }
        .print-toolbar { display: flex; justify-content: flex-end; margin-bottom: 14px; }
        .print-button { background:#1A3A6B; border:none; border-radius:10px; color:#fff; cursor:pointer; font-size:12px; font-weight:900; padding:10px 16px; }
        @media print { .no-print { display:none !important; } }
    </style>
</head>
<body>
<div class="page">
    <div class="no-print print-toolbar">
        <button class="print-button" onclick="window.print()">Imprimer</button>
    </div>

    @include('grades.partials.bordereau-header', [
        'school' => $school,
        'phones' => $phones,
        'agreements' => $agreements,
        'classGroup' => (object)[ 'academicYear' => (object)[ 'label' => $activeYear?->label ?? '—' ] ],
        'forPdf' => false,
        'docTitle' => $docTitle,
    ])

    <div class="info-row">
        <div class="info-item"><span class="info-label">Jour</span><span class="info-value">{{ $days[$selectedDay] }}</span></div>
        <div class="info-item"><span class="info-label">Contrat</span><span class="info-value">{{ $contract ? $contracts[$contract] ?? ucfirst($contract) : 'Tous' }}</span></div>
        <div class="info-item"><span class="info-label">Personnels programmés</span><span class="info-value">{{ $scheduleItems->total() ?? 0 }}</span></div>
    </div>

    <div style="overflow-x:auto;">
        <table class="bordereau-table">
            <thead>
                <tr>
                    <th style="width: 32px;">#</th>
                    <th>Nom</th>
                    <th>Contrat</th>
                    <th>Téléphone</th>
                    <th>Poste(s)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($scheduleItems as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="name-cell">{{ $item['staff']->full_name }}</td>
                        <td>{{ $item['staff']->contract_label }}</td>
                        <td>{{ $item['staff']->phone ?: '—' }}</td>
                        <td>{{ $item['staff']->positions->pluck('position')->map(fn($p) => ucfirst(str_replace('_', ' ', $p)))->join(' • ') ?: 'Aucun' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
