<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $documentTitle }} — {{ $school->short_name ?? 'Établissement' }}</title>
    <style>
        @page { size: A4 portrait; margin: 4mm; }
        body { font-family: Arial, Helvetica, sans-serif; margin: 0; color: #000; background: #fff; }
        .toolbar { width: 100%; padding: 12px 16px; box-sizing: border-box; display: flex; justify-content: flex-end; }
        .print-button { display: inline-flex; text-align: center; align-items: center; justify-content: center; padding: 10px 16px; border: 1px solid #1A3A6B; background: #1A3A6B; color: #fff; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; text-decoration: none; }
        .no-print { display: block; }
        .sheet { width: 100%; }
        .bordereau-header { display: grid; grid-template-columns: 1.45fr 0.9fr; gap: 18px; align-items: start; padding-bottom: 10px; margin-bottom: 10px; border-bottom: 1px solid #CBD5E1; }
        .bordereau-header__brand { display: flex; gap: 14px; align-items: center; }
        .bordereau-header__logo { width: 88px; min-width: 88px; display: flex; align-items: center; justify-content: center; }
        .bordereau-header__logo img { max-height: 84px; max-width: 84px; object-fit: contain; }
        .bordereau-header__logo-placeholder { width: 84px; height: 84px; border-radius: 18px; background: #1A3A6B; color: #fff; display: grid; place-items: center; font-size: 32px; font-weight: 900; }
        .bordereau-header__school-info { display: grid; gap: 4px; }
        .bordereau-header__school { font-size: 14px; font-weight: 900; color: #1A3A6B; text-transform: uppercase; letter-spacing: .03em; }
        .bordereau-header__meta { font-size: 10px; color: #475569; }
        .bordereau-header__doc { display: grid; gap: 4px; justify-items: end; text-align: right; }
        .bordereau-header__doc-title { font-size: 15px; font-weight: 900; text-transform: uppercase; color: #1A3A6B; letter-spacing: .06em; }
        .bordereau-header__doc-copy { font-size: 13px; font-weight: 700; color: #CC6000; }
        .bordereau-header__doc-year { font-size: 13px; color: #1A3A6B; font-weight: 700; }
        .bordereau-header__title-row { margin-top: 12px; margin-bottom: 12px; text-align: center; grid-column: 1 / -1; }
        .bordereau-header__title { font-size: 28px; font-weight: 900; color: #1A3A6B; text-transform: uppercase; letter-spacing: .08em; text-decoration: underline; }
        .bordereau-header__subtitle { font-size: 13px; color: #475569; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th, td { border: 1px solid #CBD5E1; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #F8FAFC; font-weight: 700; color: #0F172A; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
<div class="toolbar no-print" style="text-align: center;">
    <button type="button" class="print-button" onclick="window.print()">Imprimer</button>
</div>
<div class="sheet">
    <div class="bordereau-header">
        <div class="bordereau-header__brand">
            <div class="bordereau-header__logo">
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="Logo {{ $school->short_name }}">
                @else
                    <div class="bordereau-header__logo-placeholder">{{ strtoupper(substr($school->short_name ?? 'C', 0, 1)) }}</div>
                @endif
            </div>
            <div class="bordereau-header__school-info">
                <div class="bordereau-header__school">{{ strtoupper($school->full_name) }}</div>
                @if($school->postal_box)
                    <div class="bordereau-header__meta">B.P. {{ $school->postal_box }}</div>
                @endif
                @if($phones->isNotEmpty())
                    <div class="bordereau-header__meta">Tél : {{ $phones->pluck('number')->join(' / ') }}</div>
                @endif
                <div class="bordereau-header__meta">{{ $school->ministry ?: 'MINISTERE DES ENSEIGNEMENTS SECONDAIRES' }}</div>
            </div>
        </div>
        <div class="bordereau-header__doc">
            <div class="bordereau-header__doc-title">DOCUMENT ADMINISTRATIF</div>
            <div class="bordereau-header__doc-copy">Exemplaire Scolarité</div>
            <div class="bordereau-header__doc-year">Année scolaire : {{ $activeYear?->label ?? now()->format('Y') }}</div>
        </div>
    </div>
    <div class="bordereau-header__title-row">
        <div class="bordereau-header__title">{{ $documentTitle }}</div>
        {{-- <div class="bordereau-header__subtitle">Liste du personnel</div> --}}
    </div>

    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Nom et prénoms</th>
            <th>Poste</th>
            <th>Type de contrat</th>
            <th>Numéro</th>
            <th>Salaire</th>
        </tr>
        </thead>
        <tbody>
        @foreach($staff as $index => $member)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $member->full_name }}</td>
                <td>{{ $member->primaryPosition?->position_label ?? 'Personnel' }}</td>
                <td>{{ $member->contract_label }}</td>
                <td>{{ $member->phone ?? '—' }}</td>
                <td>{{ $member->salary_display }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
</body>
</html>
