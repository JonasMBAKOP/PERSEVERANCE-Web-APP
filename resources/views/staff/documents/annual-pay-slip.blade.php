<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Récapitulatif annuel de paie — {{ $staff->full_name }}</title>
    <style>
        @page { size: A4 portrait; margin: 8mm; }
        body { font-family: Arial, Helvetica, sans-serif; margin: 0; color: #000; background: #fff; }
        .toolbar { width: 100%; padding: 8px 16px 0; box-sizing: border-box; display: flex; justify-content: center; }
        .print-button { display: inline-flex; text-align: center; align-items: center; justify-content: center; padding: 10px 16px; border: 1px solid #1A3A6B; background: #1A3A6B; color: #fff; border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer; text-decoration: none; }
        .sheet { padding: 8px 18px 18px; }
        .bordereau-header { display: grid; grid-template-columns: 1.3fr 1fr; gap: 18px; align-items: start; padding-bottom: 12px; margin-bottom: 16px; border-bottom: 1px solid #CBD5E1; color: #1A3A6B; }
        .bordereau-header__brand { display: flex; gap: 12px; align-items: center; }
        .bordereau-header__logo { width: 88px; min-width: 88px; height: 88px; display: flex; align-items: center; justify-content: center; overflow: hidden; background: transparent; }
        .bordereau-header__logo img { max-width: 84px; max-height: 84px; object-fit: contain; }
        .bordereau-header__logo-placeholder { width: 84px; height: 84px; display: grid; place-items: center; font-size: 32px; font-weight: 900; color: #1A3A6B; background: transparent; }
        .bordereau-header__school-info { display: grid; gap: 3px; }
        .bordereau-header__school { font-size: 16px; font-weight: 900; text-transform: uppercase; color: #1A3A6B; }
        .bordereau-header__meta { font-size: 11px; color: #1A3A6B; }
        .bordereau-header__doc { display: grid; gap: 4px; justify-items: end; text-align: right; margin-top: 10px; }
        .bordereau-header__doc-title { font-size: 14px; font-weight: 900; text-transform: uppercase; color: #1A3A6B; }
        .bordereau-header__doc-copy { font-size: 13px; font-weight: 700; color: #CC6000; }
        .bordereau-header__doc-year { font-size: 13px; font-weight: 700; color: #1A3A6B; }
        .bordereau-header__title-row { text-align: center; margin-bottom: 14px; }
        .bordereau-header__title { font-size: 26px; font-weight: 900; text-transform: uppercase; text-decoration: underline; color: #1A3A6B; }
        .table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .table th, .table td { border: 1px solid #000; font-size: 14px; padding: 10px 12px; vertical-align: top; }
        .table th { text-align: left; font-weight: 700; background: #F8FAFC; }
        .table td { color: #000; }
        .table tfoot td { font-weight: 900; }
        .signature-row { margin-top: 30px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .signature-block { min-height: 20px; padding-top: 0; font-weight: 700; display: flex; align-items: center; justify-content: center; font-size: 15px; text-transform: uppercase; color: #000; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
<div class="toolbar no-print">
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
            <div class="bordereau-header__doc-year">Année scolaire : {{ $yearLabel }}</div>
        </div>
    </div>
    <div class="bordereau-header__title-row">
        <div class="bordereau-header__title">RÉCAPITULATIF ANNUEL DE PAIE</div>
    </div>

    <table class="table" style="margin-bottom: 20px;">
        <tr><th>Nom et prénoms</th><td>{{ $staff->full_name }}</td></tr>
        <tr><th>Poste</th><td>{{ $staff->primaryPosition?->position_label ?? 'Personnel' }}</td></tr>
        <tr><th>Type de contrat</th><td>{{ $staff->contract_label }}</td></tr>
        <tr><th>Numéro de contact</th><td>{{ $staff->phone ?? '—' }}</td></tr>
        <tr><th>Email</th><td>{{ $staff->email ?? '—' }}</td></tr>
        <tr>
            <th>Salaire convenu</th>
            <td>
                @if(in_array($staff->contract_type, ['permanent', 'semi_permanent'], true))
                    {{ $staff->monthly_salary ? number_format($staff->monthly_salary) . ' FCFA / mois' : 'À renseigner' }}
                @else
                    {{ $staff->hourly_rate ? number_format($staff->hourly_rate) . ' FCFA / h' : 'À renseigner' }}
                @endif
            </td>
        </tr>
    </table>

    <table class="table">
        <thead>
            <tr>
                <th style="text-align: center; font-size: 16px;">Période</th>
                <th style="text-align: center; font-size: 16px;">Montant perçu</th>
            </tr>
        </thead>
        <tbody>
            @foreach($rows as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td style="font-weight: 700;">{{ $row['amount_received'] !== null ? number_format((float) $row['amount_received'], 0, ',', ' ') . ' FCFA' : '---' }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th style="text-align: center; font-size: 16px; font-weight: 700;">Total perçu</th>
                <th style="text-align: center; font-size: 16px; font-weight: 700;">{{ $totalReceived ? number_format((float) $totalReceived, 0, ',', ' ') . ' FCFA' : '---' }}</th>
            </tr>
        </tfoot>
    </table>

    <div class="signature-row">
        <div class="signature-block">Personnel</div>
        <div class="signature-block">Économe</div>
    </div>
</div>
</body>
</html>
