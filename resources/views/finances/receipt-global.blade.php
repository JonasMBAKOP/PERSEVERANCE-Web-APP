<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Reçu Global — {{ $enrollment->student->full_name }}</title>
<style>
    /* ── PRINT ──────────────────────────────────────────────────────────── */
@page { size: A4 portrait; margin: 4mm; }
@media print { .no-print { display:none!important; } }

/* ── BASE ───────────────────────────────────────────────────────────── */
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 12.5px;
    font-weight: 700;
    color: #334155;
    background: #F7FAFC;
    padding: 8px;
    }

    /* ── PAGE ────────────────────────────────────────────────────────────── */
    .page {
        max-width: 277mm;
        margin: 0 auto;
        background: #fff;
        border: 3px solid #7FA6C4;
        border-radius: 4px;
        overflow: hidden;
    }

    /* ── HEADER ─────────────────────────────────────────────────────────── */
    .header {
        background: #F4F9FD;
        display: flex;
        align-items: stretch;
        border-bottom: 2px solid #7FA6C4;
    }
    .header-left {
        flex: 3;                    /* ← proportionnel */
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border-right: 2px solid #7FA6C4;
        min-width: 0;
    }
    .logo-circle {
        width: 46px; height: 46px;
        background: #DDECF6;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 17px; font-weight: 900; color: #A87B24;
        flex-shrink: 0;
    }
    .school-name { font-size: 13.5px; font-weight: 900; color: black; }
    .school-sub  { font-size: 9.5px; font-weight: 700; color: black; margin-top: 2px; }
    .header-right {
        flex: 1.85;
        padding: 10px 8px 10px 6px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
        gap: 3px;
        min-width: 110px;
        text-align: left;
    }
    .doc-title {
        font-size: 13px; font-weight: 900;
        color: #A87B24; text-transform: uppercase; letter-spacing: 1px;
    }
    .doc-sub  { font-size: 9.5px; font-weight: 700; color: black; }
    .doc-date { font-size: 10.5px; font-weight: 900; color: black; }

    /* ── BLOC ÉLÈVE ──────────────────────────────────────────────────────── */
    .student-block {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        border-bottom: 2.5px solid #7FA6C4;
    }
    .student-col {
        padding: 8px 13px;
        border-right: 1.5px solid #E4EEF7;
    }
    .student-col:last-child { border-right: none; }
    .col-badge {
        font-size: 9.5px; font-weight: 900; text-transform: uppercase;
        letter-spacing: 1.5px; color: #7FA6C4; background: #F4F9FD;
        padding: 3px 7px; border-radius: 2px;
        display: inline-block; margin-bottom: 5px;
    }
    .col-row { display: flex; gap: 6px; margin-bottom: 4px; align-items: baseline; }
    .col-lbl { font-size: 10.5px; font-weight: 900; color: #64748B; min-width: 75px; }
    .col-val { font-size: 12px; font-weight: 900; color: #334155; }

    /* ── SECTION TITRE ──────────────────────────────────────────────────── */
    .section-title {
        background: #7FA6C4;
        color: #fff;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
        letter-spacing: 1.5px;
        padding: 7px 13px;
    }

    /* ── TABLEAU VERSEMENTS ─────────────────────────────────────────────── */
    .t-versements {
        width: 100%;
        border-collapse: collapse;
        font-size: 11.5px;
    }
    .t-versements thead tr {
        background: #F4F9FD;
    }
    .t-versements thead th {
        padding: 7px 8px;
        text-align: left;
        font-size: 10.5px;
        font-weight: 900;
        color: #7FA6C4;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #7FA6C4;
        border-right: 1px solid #E4EEF7;
    }
    .t-versements thead th:last-child { border-right: none; }
    .t-versements tbody tr { border-bottom: 1px solid #D8E4F0; }
    .t-versements tbody tr:nth-child(even) { background: #F7FAFE; }
    .t-versements tbody td {
        padding: 6px 8px;
        font-weight: 800;
        color: #334155;
        border-right: 1px solid #D8E4F0;
        vertical-align: middle;
    }
    .t-versements tbody td:last-child {
        border-right: none;
        text-align: right;
        font-weight: 900;
        font-family: 'Courier New', monospace;
        font-size: 12px;
        color: #7FA6C4;
    }
    .receipt-id {
        font-family: 'Courier New', monospace;
        font-size: 9.5px;
        font-weight: 900;
        color: #1A5090;
        background: #EAF2FF;
        padding: 2px 5px;
        border-radius: 3px;
    }
    .mode-badge {
        font-size: 9px; font-weight: 900;
        padding: 2px 6px; border-radius: 3px;
        background: #F4F9FD; color: #7FA6C4;
    }
    .t-versements tfoot tr {
        background: #7FA6C4;
        border-top: 2px solid #7FA6C4;
    }
    .t-versements tfoot td {
        padding: 6px 8px;
        font-size: 11px;
        font-weight: 900;
        color: #fff;
        border-right: 1px solid #DDECF6;
    }
    .t-versements tfoot td:last-child {
        border-right: none;
        text-align: right;
        color: #A87B24;
        font-size: 13px;
        font-family: 'Courier New', monospace;
    }

    /* ── TABLEAU ÉTAT GÉNÉRAL ────────────────────────────────────────────── */
    .t-etat {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }
    .t-etat thead tr { background: #F4F9FD; }
    .t-etat thead th {
        padding: 7px 10px;
        text-align: left;
        font-size: 11px;
        font-weight: 900;
        color: #7FA6C4;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 2px solid #7FA6C4;
        border-right: 1px solid #E4EEF7;
    }
    .t-etat thead th:last-child { border-right: none; }
    .t-etat thead th.right,
    .t-etat tbody td.right,
    .t-etat tfoot td.right { text-align: right; }
    .t-etat tbody tr { border-bottom: 1px solid #D8E4F0; }
    .t-etat tbody tr:nth-child(even) { background: #F7FAFE; }
    .t-etat tbody td {
        padding: 7px 10px;
        font-weight: 800;
        color: #334155;
        border-right: 1px solid #D8E4F0;
    }
    .t-etat tbody td.right {
        font-family: 'Courier New', monospace;
        font-size: 11px;
        font-weight: 900;
    }
    .t-etat tbody td:last-child { border-right: none; }
    .status-ok  { color: #60906F; font-weight: 900; }
    .status-due { color: #B76E79; font-weight: 900; }
    .t-etat tfoot tr {
        background: #7FA6C4;
        border-top: 2.5px solid #7FA6C4;
    }
    .t-etat tfoot td {
        padding: 7px 10px;
        font-size: 11.5px;
        font-weight: 900;
        color: #fff;
        border-right: 1px solid #DDECF6;
    }
    .t-etat tfoot td:last-child { border-right: none; }
    .t-etat tfoot td.right {
        font-family: 'Courier New', monospace;
        font-size: 13px;
        text-align: right;
    }
    .t-etat tfoot td.right.green { color: #D6F2DF; }
    .t-etat tfoot td.right.red   { color: #F5D5D9; }
    .t-etat tfoot td.right.gold  { color: #A87B24; }

    /* ── CACHET ──────────────────────────────────────────────────────── */
    .seal-section {
        padding: 12px 14px; text-align: center; background: #FAFBFC;
        border-top: 1px solid #E4EEF7; border-bottom: 1px solid #E4EEF7;
    }
    .seal-section img {
        height: 48px; width: 48px; object-fit: contain;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }

    /* ── FOOTER ──────────────────────────────────────────────────────────── */
    .footer-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #7FA6C4;
        padding: 7px 14px;
        border-top: 2px solid #7FA6C4;
    }
    .footer-cashier {
        font-size: 10px; font-weight: 900; color: #F6FAFD;
    }
    .footer-cashier span { color: #fff; font-size: 11px; }
    .footer-sig {
        font-size: 9.5px; font-weight: 800; color: #F6FAFD;
        border-top: 1px solid #F6FAFD;
        padding-top: 3px; min-width: 120px; text-align: center;
    }
    .nb-bar {
        background: #FFF9EC;
        border-top: 2.5px solid #E4C978;
        padding: 6px 14px;
        text-align: center;
        font-size: 10px;
        font-weight: 700;
        color: black;
        letter-spacing: 0.5px;
    }

    /* ── BOUTON ──────────────────────────────────────────────────────────── */
    .print-btn { text-align:center; margin-bottom: 14px; }
    .print-btn button {
        background: #7FA6C4; color: #fff; border: none;
        padding: 10px 28px; border-radius: 8px; cursor: pointer;
        font-size: 13px; font-weight: 800;
    }
</style>
</head>
<body>

<div class="print-btn no-print">
    <button onclick="window.print()"><svg style="width:14px;height:14px;vertical-align:-2px;margin-right:6px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V4h12v5M6 18H5a2 2 0 01-2-2v-5a2 2 0 012-2h14a2 2 0 012 2v5a2 2 0 01-2 2h-1M7 14h10v6H7z"/></svg>{{ $isEnglishReceipt ? 'Print Global Receipt' : 'Imprimer le reçu global' }}</button>
</div>

<div class="page">

    {{-- ── HEADER ──────────────────────────────────────────────────────── --}}
    <div class="header">
        <div class="header-left">
            @if($school->logo)
                <img src="{{ asset('storage/' . $school->logo) }}" alt="Logo de l'établissement"
                     style="height:44px;width:46px;
                            flex-shrink:0;">
                {{-- <img src="{{ asset('storage/' . $school->logo) }}"
                     style="height:46px;width:46px;object-fit:contain;
                            border-radius:50%;background:#DDECF6;flex-shrink:0;"> --}}
            @else
                <div class="logo-circle">
                    {{ strtoupper(substr($school->short_name ?? 'C', 0, 1)) }}
                </div>
            @endif
            <div>
                <div class="school-name">
                    {{ $isEnglishReceipt ? strtoupper($school->full_name_en) : strtoupper($school->full_name) }}
                </div>
                <div class="school-sub">
                    @if($phones->isNotEmpty())
                        {{ $isEnglishReceipt ? 'Phone' : 'Tél' }} :
                        @foreach($phones->take(2) as $phone)
                            {{ $phone->number }}{{ !$loop->last ? ' / ' : '' }}
                        @endforeach
                    @elseif($school->phone_1)
                        {{ $isEnglishReceipt ? 'Phone' : 'Tél' }} : {{ $school->phone_1 }}
                    @endif
                    @if($school->address) &nbsp;|&nbsp; {{ $school->address }} @endif
                </div>
                <div class="school-sub">
                    @if($school->postal_box) {{ $isEnglishReceipt ? 'P.O. Box' : 'BP' }} : {{ $school->postal_box }} @endif
                </div>
                <div class="school-sub">
                    @if($school->ministry) {{ $isEnglishReceipt ? $school->ministry_en : $school->ministry }} @endif
                </div>
            </div>
        </div>
        <div class="header-right">
            <div class="doc-title">{{ $isEnglishReceipt ? 'Official Payment Receipt' : 'Reçu de Versement Officiel' }}</div>
            <div class="doc-sub">{{ $isEnglishReceipt ? 'Student Copy' : 'Exemplaire Élève' }}</div>
            <div class="doc-date">
                {{ $isEnglishReceipt ? 'Academic Year' : 'Année Scolaire' }} : {{ $enrollment->academicYear->label }}
                &nbsp;&nbsp;{{ $isEnglishReceipt ? 'Date' : 'Date' }} : {{ now()->format('d/m/Y') }}
            </div>
        </div>
    </div>

    {{-- ── BLOC ÉLÈVE ───────────────────────────────────────────────────── --}}
    <div class="student-block">
        <div class="student-col">
            <div class="col-badge">{{ $isEnglishReceipt ? 'Student Identity' : 'Identité de l\'élève' }}</div>
            <div class="col-row">
                <span class="col-lbl">{{ $isEnglishReceipt ? 'Name' : 'Nom et prénom' }} :</span>
                <span class="col-val">
                    {{ strtoupper($enrollment->student->full_name) }}
                </span>
            </div>
            <div class="col-row">
                <span class="col-lbl">{{ $isEnglishReceipt ? 'Registration N°' : 'Matricule' }} :</span>
                <span class="col-val">{{ $enrollment->student->matricule }}</span>
            </div>
        </div>
        <div class="student-col">
            <div class="col-badge">{{ $isEnglishReceipt ? 'Born On' : 'Naissance' }}</div>
            <div class="col-row">
                <span class="col-lbl">{{ $isEnglishReceipt ? 'Date' : 'Date' }} :</span>
                <span class="col-val">
                    {{ $enrollment->student->date_of_birth?->format('d/m/Y') ?? '—' }}
                </span>
            </div>
            <div class="col-row">
                <span class="col-lbl">{{ $isEnglishReceipt ? 'Place of Birth' : 'Lieu' }} :</span>
                <span class="col-val">
                    {{ strtoupper($enrollment->student->place_of_birth ?? '—') }}
                </span>
            </div>
        </div>
        <div class="student-col">
            <div class="col-badge">{{ $isEnglishReceipt ? 'Schooling' : 'Scolarité' }}</div>
            <div class="col-row">
                <span class="col-lbl">{{ $isEnglishReceipt ? 'Class' : 'Classe' }} :</span>
                <span class="col-val">
                    {{ $enrollment->classGroup->full_name }}
                </span>
            </div>
            <div class="col-row">
                <span class="col-lbl">Section :</span>
                <span class="col-val">
                    {{ $enrollment->classGroup->level->section->name }}
                </span>
            </div>
        </div>
    </div>

    {{-- ── TOUS LES VERSEMENTS ──────────────────────────────────────────── --}}
<div class="section-title">{{ $isEnglishReceipt ? 'All Payments' : 'Tous les Versements' }}</div>

    @if($payments->isEmpty())
    <div style="padding:12px 14px; font-size:11px; color:#666; font-style:italic;">
        Aucun paiement enregistré.
    </div>
    @else
    <table class="t-versements">
        <thead>
            <tr>
                <th style="width:22%">{{ $isEnglishReceipt ? 'Receipt No.' : 'N° Reçu' }}</th>
                <th style="width:20%">{{ $isEnglishReceipt ? 'Purpose' : 'Objet' }}</th>
                <th style="width:13%">{{ $isEnglishReceipt ? 'Method' : 'Mode' }}</th>
                <th style="width:20%">{{ $isEnglishReceipt ? 'Date / Time' : 'Date / Heure' }}</th>
                <th style="width:16%">{{ $isEnglishReceipt ? 'Cashier' : 'Caissier(e)' }}</th>
                <th style="width:9%; text-align:right;">{{ $isEnglishReceipt ? 'Amount' : 'Montant' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $p)
            <tr>
                <td>
                    <span class="receipt-id">{{ $p->receipt_number }}</span>
                </td>
                <td style="font-weight:900;">
                    @php $isManualInsolvablePayment = $p->fee_installment_id === null && ! $p->is_bulk; @endphp
                    @if($isManualInsolvablePayment)
                        {{ $isEnglishReceipt ? 'Manual Insolvable' : 'Insolvable manuel' }}
                    @else
                        {{ $p->is_bulk ? ($p->allocation_summary ?: ($p->feeInstallment?->label ?? ($isEnglishReceipt ? 'Bulk Payment' : 'Paiement groupé'))) : ($p->feeInstallment?->label ?? '—') }}
                    @endif
                </td>
                <td>
                    <span class="mode-badge">{{ $p->payment_method_label }}</span>
                </td>
                <td>
                    {{ $p->payment_date->format('d/m/Y') }}
                    {{ $p->created_at->format('H:i:s') }}
                </td>
                <td>{{ $p->recordedBy?->name ?? '—' }}</td>
                <td>{{ number_format($p->amount_paid, 0, ',', ' ') }} FCFA</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" style="font-weight:900; letter-spacing:1px;">
                    {{ $isEnglishReceipt ? 'TOTAL PAYMENTS' : 'TOTAL DES VERSEMENTS' }}
                    @php $totalScholarships = $payments->sum('scholarship_amount'); @endphp
                    @if($totalScholarships > 0)
                        &nbsp;|&nbsp; {{ $isEnglishReceipt ? 'SCHOLARSHIP APPLIED' : 'BOURSE APPLIQUÉE' }}: {{ number_format($totalScholarships, 0, ',', ' ') }} FCFA
                    @endif
                </td>
                <td>{{ number_format($totalPaid, 0, ',', ' ') }} FCFA</td>
            </tr>
        </tfoot>
    </table>
    @endif

    {{-- ── CACHET ──────────────────────────────────────────────────────── --}}
    @php
        $sealPath = null;
        $currentUser = auth()->user();
        if ($currentUser && $currentUser->hasAnyRole(['directeur', 'fondateur'])) {
            $sealPath = $school->signature_seal;
        } else {
            $sealPath = $currentUser?->signature_seal;
        }
    @endphp
    @if($sealPath)
        <div class="seal-section">
            <img src="{{ asset('storage/' . $sealPath) }}" alt="Cachet">
        </div>
    @endif

    {{-- ── ÉTAT GÉNÉRAL DES FRAIS ──────────────────────────────────────── --}}
    <div class="section-title" style="margin-top:0; border-top:2px solid #DDECF6;">
        {{ $isEnglishReceipt ? 'Fee Summary' : 'État Général des Frais' }}
    </div>

    @if($installmentSummary->isEmpty())
    <div style="padding:10px 14px; font-size:11px; color:#666; font-style:italic;">
        Aucune structure de frais configurée.
    </div>
    @else
    <table class="t-etat">
        <thead>
            <tr>
                <th style="width:30%">{{ $isEnglishReceipt ? 'Description' : 'Désignation' }}</th>
                <th style="width:18%">{{ $isEnglishReceipt ? 'Due Date' : 'Échéance' }}</th>
                <th class="right" style="width:17%">{{ $isEnglishReceipt ? 'Total Amount' : 'Montant Total' }}</th>
                <th class="right" style="width:17%">{{ $isEnglishReceipt ? 'Already Paid' : 'Déjà Payé' }}</th>
                <th class="right" style="width:18%">{{ $isEnglishReceipt ? 'Remaining' : 'Restant' }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($installmentSummary as $row)
            <tr>
                <td style="font-weight:900;">{{ $row['label'] }}</td>
                <td>
                    {{ $row['due_date'] instanceof \Carbon\Carbon
                        ? $row['due_date']->format('d/m/Y')
                        : ($row['due_date'] ?? '—') }}
                </td>
                <td class="right">
                    {{ number_format($row['amount'], 0, ',', ' ') }} FCFA
                </td>
                <td class="right {{ $row['paid'] >= $row['amount'] ? 'status-ok' : '' }}">
                    {{ number_format($row['paid'], 0, ',', ' ') }} FCFA
                </td>
                <td class="right {{ $row['remaining'] > 0 ? 'status-due' : 'status-ok' }}">
                    {{ number_format($row['remaining'], 0, ',', ' ') }} FCFA
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="font-weight:900; letter-spacing:1px;">
                    {{ $isEnglishReceipt ? 'GRAND TOTAL' : 'TOTAL GÉNÉRAL' }}
                </td>
                <td class="right gold">
                    {{ number_format($totalDue, 0, ',', ' ') }} FCFA
                </td>
                <td class="right green">
                    {{ number_format($totalPaid, 0, ',', ' ') }} FCFA
                </td>
                <td class="right {{ $totalRemaining > 0 ? 'red' : 'green' }}">
                    {{ number_format($totalRemaining, 0, ',', ' ') }} FCFA
                </td>
            </tr>
        </tfoot>
    </table>
    @endif

    {{-- ── FOOTER ───────────────────────────────────────────────────────── --}}
    <div class="footer-row">
        <div class="footer-cashier">
            {{ $isEnglishReceipt ? 'Issued on' : 'Émis le' }} : <span>{{ now()->format('d/m/Y à H:i') }}</span>
        </div>
        <div class="footer-cashier">
            {{ $isEnglishReceipt ? 'By' : 'Par' }} : <span>{{ auth()->user()?->name ?? 'Système' }}</span>
        </div>
        <div class="footer-sig">Signature &amp; Cachet Établissement</div>
    </div>
    <div class="nb-bar">
        <svg style="width:13px;height:13px;vertical-align:-2px;margin-right:5px" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
        </svg>
        {{ $isEnglishReceipt ? 'NB : NO FEES ARE REFUNDABLE !' : 'NB : AUCUN FRAIS N\'EST REMBOURSABLE !' }}
    </div>

</div>
</body>
</html>