<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche de présences du personnel - {{ $date->format('d/m/Y') }}</title>
    @include('students.documents.partials.base-styles')
    <style>
        @page { size: A4 portrait; margin: 8mm; }
        body { background: #eef1f5; color: #1f2937; }
        .page { max-width: 210mm; margin: 0 auto; padding: 6mm 7mm; background: #fff; }
        .bordereau-header { display:grid; grid-template-columns:1.5fr 1fr; gap:18px; align-items:start; margin-bottom:12px; padding-bottom:12px; border-bottom:1px solid #CBD5E1; }
        .bordereau-header__brand { display:flex; gap:14px; align-items:center; }
        .bordereau-header__logo { width:88px; min-width:88px; display:flex; align-items:center; justify-content:center; }
        .bordereau-header__logo img { max-height:84px; max-width:84px; object-fit:contain; }
        .bordereau-header__logo-placeholder { width:84px; height:84px; border-radius:18px; background:#1A3A6B; color:#fff; display:grid; place-items:center; font-size:32px; font-weight:900; }
        .bordereau-header__school-info { display:grid; gap:4px; }
        .bordereau-header__school { font-size:14px; font-weight:900; color:#1A3A6B; text-transform:uppercase; letter-spacing:.03em; }
        .bordereau-header__meta { font-size:10px; color:#475569; }
        .bordereau-header__doc { display:grid; gap:4px; justify-items:end; text-align:right; }
        .bordereau-header__doc-title { font-size:13px; font-weight:900; text-transform:uppercase; color:#1A3A6B; letter-spacing:.05em; }
        .bordereau-header__doc-copy { font-size:12px; font-weight:700; color:#CC6000; }
        .bordereau-header__doc-year { font-size:12px; color:#1A3A6B; font-weight:700; }
        .bordereau-header__title-row { margin-top:14px; text-align:center; grid-column:1/-1; }
        .bordereau-header__title { font-size:22px; font-weight:900; color:#1A3A6B; text-transform:uppercase; letter-spacing:.06em; text-decoration:underline; }
        .bordereau-header__subtitle { font-size:14px; color:#475569; margin-top:4px; }
        .attendance-table { width:100%; border-collapse:collapse; table-layout:fixed; font-size:11px; margin-top:0; }
        .attendance-table th, .attendance-table td { border:1px solid #CBD5E1; padding:6px 6px; }
        .attendance-table th { background:#F8FAFC; color:#1A3A6B; font-size:10px; font-weight:900; text-transform:uppercase; }
        .attendance-table td { color:#1F2937; vertical-align:middle; }
        .attendance-table th:nth-child(1), .attendance-table td:nth-child(1) { width:5%; text-align:center; }
        .attendance-table th:nth-child(2), .attendance-table td:nth-child(2) { width:26%; text-align:left; font-weight:700; text-transform:uppercase; }
        .attendance-table th:nth-child(3), .attendance-table td:nth-child(3) { width:15%; }
        .attendance-table th:nth-child(4), .attendance-table td:nth-child(4) { width:11%; text-align:center; }
        .attendance-table th:nth-child(5), .attendance-table td:nth-child(5) { width:11%; text-align:center; }
        .attendance-table th:nth-child(6), .attendance-table td:nth-child(6) { width:13%; text-align:center; }
        .attendance-table th:nth-child(7), .attendance-table td:nth-child(7) { width:19%; }
        .status-present { color:#166534; font-weight:800; }
        .status-absent { color:#991B1B; font-weight:800; }
        .print-meta { display:flex; justify-content:space-between; gap:10px; margin:10px 0 8px; color:#475569; font-size:12px; }
        .no-print { display:block; }
        @media print { .no-print { display:none !important; } body { background:#fff; } .page { padding:0; max-width:none; } }
    </style>
</head>
<body>
@include('students.documents.partials.print-toolbar')
<main class="page">
    @include('grades.partials.bordereau-header', [
        'forPdf' => false,
        'docTitle' => 'Fiche de Présences du Personnel',
        // 'docSubtitle' => 'Personnel Attendance Sheet / Fiche de Présences'
    ])

    <div class="print-meta">
        <span>Date : <strong>{{ $date->format('d/m/Y') }}</strong></span>
        <span>Contrat : <strong>{{ $contractType === 'all' ? 'Tous les contrats' : ucfirst(str_replace('_', ' ', $contractType)) }}</strong></span>
        @php
            $staffCount = $staff->count();
            $presentCount = $presences->where('status', 'present')->count();
            $absentCount = $staffCount - $presentCount;
        @endphp
        <span>Effectif : <strong>{{ $staffCount }}</strong></span>
        <span>Présents : <strong>{{ $presentCount }}</strong></span>
        <span>Absents : <strong>{{ $absentCount }}</strong></span>
    </div>

    <table class="attendance-table">
        <thead>
            <tr>
                <th>N°</th>
                <th>Noms et Prénoms / Names</th>
                <th>Type contrat / Contract</th>
                <th>Arrivée / Arrival</th>
                <th>Départ / Departure</th>
                <th>Statut / Status</th>
                <th>Observations / Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($staff as $index => $member)
                @php $presence = $presences->get($member->id); @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $member->full_name }}</td>
                    <td>{{ $member->contract_label }}</td>
                    <td>{{ $presence?->arrival_time ? substr((string) $presence->arrival_time, 0, 5) : '—' }}</td>
                    <td>{{ $presence?->departure_time ? substr((string) $presence->departure_time, 0, 5) : '—' }}</td>
                    <td class="{{ $presence?->status === 'present' ? 'status-present' : 'status-absent' }}">
                        {{ $presence?->status === 'present' ? 'Présent' : 'Absent' }}
                    </td>
                    <td>{{ $presence?->note ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="7" style="text-align:center; padding:18px;">Aucun personnel pour ces filtres.</td></tr>
            @endforelse
        </tbody>
    </table>
</main>
</body>
</html>
