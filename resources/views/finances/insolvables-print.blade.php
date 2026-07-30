<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><title>Liste des élèves insolvables - {{ $selectedYear?->label }}</title>
@include('students.documents.partials.base-styles')
@include('finances.partials.pdf-document-styles')
<style>
@page { size: A4 portrait; margin: 6mm; }
.bordereau-header { display:grid; grid-template-columns:1.45fr .9fr; gap:18px; align-items:start; padding-bottom:10px; margin-bottom:10px; border-bottom:1px solid #CBD5E1; }
.bordereau-header__brand { display:flex; gap:12px; align-items:center; }
.bordereau-header__logo { width:86px; min-width:86px; display:flex; justify-content:center; }
.bordereau-header__logo img { max-width:82px; max-height:82px; object-fit:contain; }
.bordereau-header__logo-placeholder { width:78px; height:78px; border-radius:12px; background:#1A3A6B; color:#fff; display:grid; place-items:center; font-size:28px; font-weight:900; }
.bordereau-header__school-info { display:grid; gap:2px; }
.bordereau-header__school { font-size:14px; font-weight:900; color:#1A3A6B; }
.bordereau-header__meta { font-size:10px; color:#475569; }
.bordereau-header__doc { display:grid; gap:2px; justify-items:end; text-align:right; }
.bordereau-header__doc-title { font-size:14px; font-weight:900; color:#1A3A6B; }
.bordereau-header__doc-copy { font-size:11px; font-weight:700; color:#A35200; }
.bordereau-header__doc-year { font-size:11px; color:#1A3A6B; font-weight:700; }
.bordereau-header__title-row { margin:10px 0 13px; text-align:center; }
.bordereau-header__title { font-size:21px; font-weight:900; color:#1A3A6B; text-decoration:underline; }
.bordereau-header__subtitle { margin-top:4px; font-size:12px; color:#475569; font-weight:700; }
.summary { display:grid; grid-template-columns:repeat(4,1fr); gap:9px; margin-bottom:12px; }
.box { border:1px solid #D1D5DB; background:#F8FAFC; padding:10px 12px; color:#000; }
.box b { display:block; margin-top:5px; color:#000; font-size:15px; }
.box span { color:#4B5563; font-size:10px; font-weight:800; text-transform:uppercase; }
table { width:100%; border-collapse:collapse; table-layout:fixed; font-size:10.5px; } 
th,td { border:1px solid #D1D5DB; color:#000; padding:7px 6px; vertical-align:top; word-wrap:break-word; } 
th { background:#F3F4F6; font-size:12px; font-weight:900; text-transform:uppercase; } 
td { font-size: 11px; }
.right{text-align:right}
.name{font-weight:900}
.muted{font-size:10px; color:#374151;}
.fees{font-size:9px;line-height:1.5}
.footer{margin-top:11px;text-align:center;font-size:10px;color:#6B7280}
</style>
</head>

<body>
@include('students.documents.partials.print-toolbar')
<div class="page cert-page pdf-document">
@php($classGroup = (object) ['academicYear' => $selectedYear])
@include('grades.partials.bordereau-header', [
    'docTitleFr' => 'LISTE DES ELEVES INSOLVABLES',
    'docSubtitle' => $installmentLabel . ' - Liste générée le ' . now()->format('d/m/Y'),
    'forPdf' => false,
])
<div class="summary">
    <div class="box">
        <span>Élèves concernés</span>
        <b>{{ number_format($summary['count']) }}</b>
    </div>
    <div class="box">
        <span>Total frais</span>
        <b>{{ number_format($summary['due']) }} FCFA</b>
    </div>
    <div class="box">
        <span>Total payé</span>
        <b>{{ number_format($summary['paid']) }} FCFA</b>
    </div>
    <div class="box">
        <span>Reste à payer</span>
        <b>{{ number_format($summary['remaining']) }} FCFA</b>
    </div>
</div>
<table>
    <thead>
        <tr>
            <th style="width:3%">#</th>
            <th style="width:20%">Élève</th>
            <th style="width:15%">Classe</th>
            <th class="right" style="width:11%">Frais</th>
            <th class="right" style="width:11%">Payé</th>
            <th class="right" style="width:12%">Reste</th>
            @if(!$selectedInstallmentLabel)
            <th style="width:28%">Tranche(s) restante(s)</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $index => $row) 
            @php($enrollment = $row['enrollment'])
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>
                    <div class="name">{{ $enrollment->student->full_name }}</div>
                    <div class="muted">{{ $enrollment->student->matricule }}</div>
                </td>
                <td>
                    {{ $enrollment->classGroup->full_name }}
                    <div class="muted">{{ $enrollment->classGroup->level?->section?->name }}</div>
                </td>
                <td class="right">{{ number_format($row['total_due']) }}</td>
                <td class="right">{{ number_format($row['total_paid']) }}</td>
                <td class="right name">{{ number_format($row['remaining']) }}</td>
                @if(!$selectedInstallmentLabel)
                <td class="fees">
                    @foreach($row['remaining_fees'] as $fee)
                        <div>{{ $fee['label'] }} : {{ number_format($fee['remaining']) }} FCFA</div>
                    @endforeach
                </td>
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="{{ $selectedInstallmentLabel ? 6 : 7 }}" style="text-align:center;padding:12px">Aucun élève insolvable ne correspond aux filtres.</td>
            </tr>
        @endforelse
    </tbody>
</table>

<div class="footer">Les montants payés incluent les bourses accordées. Les frais restants regroupent les tranches non réglées ou réglées partiellement.</div>
</div>
</body>
</html>