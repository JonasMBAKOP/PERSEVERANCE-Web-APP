<!DOCTYPE html>
<html lang="{{ $isEnglishReceipt ? 'en' : 'fr' }}">
<head>
<meta charset="UTF-8">
<title>{{ $isEnglishReceipt ? 'Batch Receipt Printing' : 'Impression groupée' }} — {{ $receiptsData->count() }} {{ $isEnglishReceipt ? 'receipt(s)' : 'reçu(s)' }}</title>
<style>
/* ── PRINT ──────────────────────────────────────────────────────────── */
@page {
    size: A4 portrait;     /* ← papier portrait */
    margin: 4mm;
}
@media print { .no-print { display: none !important; } }

* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 11.5px;
    font-weight: 700;
    color: black;
    background: #F7FAFC;
    padding: 6px;
}

/*
 * UNE PAGE A4 PORTRAIT = DEUX REÇUS EMPILÉS
 * A4 portrait utile : 190mm × 277mm
 * Chaque reçu : ~190mm large × ~132mm haut  (forme paysage)
 * 2 reçus + gap 5mm = 132 + 5 + 132 = 269mm  OK (< 277mm)
 */

/* ── CONTENEUR D'UNE PAGE (2 reçus empilés) ─────────────────────────── */
.receipt-page {
    display: flex;
    flex-direction: column;
    gap: 10mm;
    page-break-after: always;
    page-break-inside: avoid;
    margin-bottom: 10mm;
    margin-top: 10mm;
}
.receipt-page:last-child { page-break-after: auto; }

/* ── CARTE REÇU ─────────────────────────────────────────────────────── */
.receipt-card {
    width: 100%;
    background: #fff;
    border: 3px solid #7FA6C4;
    border-radius: 4px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    page-break-inside: avoid;
    break-inside: avoid;
}
.receipt-card.empty {
    height: 132mm;
    border: 2px dashed #E4EEF7;
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #E4EEF7;
    font-size: 11px;
    font-style: italic;
}

/* ── HEADER ───────────────────────────────────────────────────────────── */
.card-header,
.header {
    background: #F4F9FD;
    color: black;
    display: flex;
    align-items: stretch;
    flex-shrink: 0;
    border-bottom: 2px solid #7FA6C4;
}
.card-header-left,
.header-school {
    flex: 3;
    padding: 10px 14px;
    border-right: 2px solid #7FA6C4;
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
}
.logo-sm,
.header-school img {
    width: 44px;
    height: 44px;
    object-fit: contain;
    flex-shrink: 0;
}
.logo-sm {
    background: #EDF6FC;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    font-weight: 900;
    color: #A87B24;
}
.school-nm,
.school-name { font-size: 13px; font-weight: 900; line-height: 1.3; }
.school-sm,
.school-sub { font-size: 9.5px; font-weight: 700; color: black; margin-top: 3px; }

.card-header-right,
.header-right {
    flex: 1.85;
    padding: 10px 8px 10px 6px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    text-align: center;
    gap: 4px;
    min-width: 110px;
}
.card-title,
.receipt-title { font-size: 12px; font-weight: 900; letter-spacing: 0.8px; color: #A87B24; text-transform: uppercase; }
.card-num,
.receipt-num { font-size: 11px; font-weight: 900; color: black; font-family: 'Courier New', monospace; }
.card-year,
.receipt-date-head { font-size: 9.5px; font-weight: 700; color: black; }

/* ── INFORMATIONS ÉLÈVE ──────────────────────────────────────────────── */
.card-student,
.student-section {
    display: flex;
    border-bottom: 2px solid #7FA6C4;
    flex-shrink: 0;
}
.card-stu-col,
.student-left {
    flex: 1;
    padding: 9px 14px;
    border-right: 2px solid #7FA6C4;
    min-width: 0;
}
.card-stu-col:last-child,
.student-right { border-right: none; }
.student-right {
    flex: 1;
    padding: 9px 12px;
    min-width: 0;
}

.mini-badge,
.section-label {
    font-size: 10px; font-weight: 900; text-transform: uppercase;
    letter-spacing: 1.5px; color: #7FA6C4; background: #F4F9FD;
    padding: 3px 7px; border-radius: 2px;
    display: inline-block; margin-bottom: 6px;
}
.mini-row,
.info-row { display: flex; gap: 5px; margin-bottom: 4px; align-items: baseline; }
.mini-lbl,
.info-label { font-size: 10.5px; font-weight: 900; color: #64748B; min-width: 80px; text-transform: uppercase; white-space: nowrap; }
.mini-val,
.info-value { font-size: 12px; font-weight: 900; color: #334155; }

/* ── OBJET ────────────────────────────────────────────────────────────── */
.card-object,
.object-bar {
    background: #F4F9FD; border-bottom: 2px solid #7FA6C4;
    padding: 7px 14px; display: flex; align-items: center; gap: 12px;
    flex-shrink: 0;
}
.obj-lbl,
.object-label { font-size: 10.5px; font-weight: 900; text-transform: uppercase; color: #64748B; }
.obj-val,
.object-value { font-size: 13px; font-weight: 900; color: #7FA6C4; text-transform: uppercase; }
.mode-sm,
.mode-pill {
    margin-left: auto; background: #7FA6C4; color: #fff;
    font-size: 11px; font-weight: 900; padding: 4px 11px;
    border-radius: 3px; text-transform: uppercase; letter-spacing: 0.5px;
}

/* ── CACHET ────────────────────────────────────────────────────────── */
.card-amounts-wrapper {
    position: relative;
    margin-top: 4px;
}
.card-seal {
    position: absolute;
    top: 72px;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 120px;
    height: 120px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    border-radius: 50%;
    z-index: 2;
}
.card-seal img,
.card-seal .seal-placeholder {
    width: 96px;
    height: 96px;
}
.card-seal img {
    object-fit: contain;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
}
.card-seal .seal-placeholder svg {
    width: 100%;
    height: 100%;
    display: block;
}

/* ── MONTANTS ────────────────────────────────────────────────────────── */
.card-amounts { flex: 1; }
.amt-row {
    display: flex; justify-content: space-between;
    align-items: center;
    padding: 7px 14px;
    border-bottom: 1.5px solid #E4EEF7;
}
.amt-row:last-child { border-bottom: none; }
.amt-lbl { font-size: 12px; font-weight: 800; color: #374151; }
.amt-val {
    font-size: 14px; font-weight: 900;
    font-family: 'Courier New', monospace;
    color: #334155;
}
.amt-row.highlight { background: #F4F9FD; }
.amt-row.highlight .amt-lbl { color: black; font-size: 12px; }
.amt-row.highlight .amt-lbl::before { content: '▶ '; }
.amt-row.highlight .amt-val { color: #A87B24; font-size: 16px; }

/* ── PIED DE CARTE ───────────────────────────────────────────────────── */
.card-footer,
.footer-bar {
    background: #F4F9FD;
    padding: 7px 14px;
    display: flex; align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
    border-top: 2px solid #7FA6C4;
}
.foot-cashier,
.footer-cashier { font-size: 10px; font-weight: 900; color: black; }
.foot-cashier span,
.footer-cashier span { color: black; font-size: 11px; }
.foot-sig,
.footer-signature {
    font-size: 9.5px; font-weight: 800; color: black;
    border-top: 1px solid #F6FAFD; padding-top: 2px;
    min-width: 120px; text-align: center;
}
.card-nb,
.nb-bar {
    background: #FFF9EC; border-top: 2.5px solid #E4C978;
    padding: 6px 14px; text-align: center;
    font-size: 10px; font-weight: 700; color: black; letter-spacing: 0.5px;
    flex-shrink: 0;
}

/* ── SÉPARATEUR ENTRE LES 2 REÇUS (visible à l'écran, invisible à l'impression) */
.receipt-separator {
    height: 14px;
    background: repeating-linear-gradient(
        to right, #000 0, #000 6px, transparent 6px, transparent 12px
    );
    margin: 6px 0;
}
@media print { .receipt-separator { display: none; } }

/* ── BOUTON IMPRIMER ─────────────────────────────────────────────────── */
.print-btn { text-align: center; margin-bottom: 12px; }
.print-btn button {
    background: #7FA6C4; color: #fff; border: none;
    padding: 10px 28px; border-radius: 8px;
    cursor: pointer; font-size: 13px; font-weight: 800;
}
.info-bar {
    text-align: center; margin-bottom: 8px;
    font-size: 12px; font-weight: 700; color: #374151;
}
</style>
</head>
<body>

<div class="print-btn no-print">
    <div class="info-bar">
        {{ $receiptsData->count() }} {{ $isEnglishReceipt ? 'receipt(s)' : 'reçu(s)' }} —
        {{ ceil($receiptsData->count() / 2) }} {{ $isEnglishReceipt ? 'A4 portrait page(s)' : 'page(s) A4 portrait' }}
        ({{ $isEnglishReceipt ? '2 receipts per page, stacked' : '2 reçus par page, empilés' }})
    </div>
    <button onclick="window.print()">
        <svg style="width:14px;height:14px;vertical-align:-2px;margin-right:6px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V4h12v5M6 18H5a2 2 0 01-2-2v-5a2 2 0 012-2h14a2 2 0 012 2v5a2 2 0 01-2 2h-1M7 14h10v6H7z"/></svg>{{ $isEnglishReceipt ? 'Print' : 'Imprimer' }} {{ $receiptsData->count() }} {{ $isEnglishReceipt ? 'receipt(s)' : 'reçu(s)' }}
    </button>
</div>

{{-- ── GROUPER PAR PAIRES (un en haut, un en bas) ─────────────────────── --}}
@foreach($receiptsData->chunk(2) as $pair)
<div class="receipt-page">

    @foreach($pair as $index => $item)
    @php
        $p   = $item['payment'];
        $enr = $p->studentEnrollment;
        $rem = $item['totalRemaining'];
    @endphp

    {{-- Ligne pointillée de coupe entre les 2 reçus --}}
    @if(!$loop->first)
    <div class="receipt-separator no-print"></div>
    @endif

    <div class="receipt-card">

        {{-- ── HEADER ──────────────────────────────────────────────────── --}}
        <div class="card-header">
            <div class="card-header-left">
                @if($school->logo)
                    <img src="{{ asset('storage/' . $school->logo) }}" alt="Logo" style="width:44px;height:44px;flex-shrink:0;">
                @else
                    <div class="header-school-placeholder">
                        {{ strtoupper(substr($school->short_name ?? 'C', 0, 1)) }}
                    </div>
                @endif
                <div>
                    <div class="school-nm">
                        {{ strtoupper($school->full_name ?? 'COLLÈGE POLYVALENT NTANKEU') }}
                    </div>
                    <div class="school-sm">
                        @if($phones->isNotEmpty())
                            {{ $isEnglishReceipt ? 'Phone' : 'Tél' }} :
                            @foreach($phones->take(2) as $ph)
                                {{ $ph->number }}{{ !$loop->last ? ' / ' : '' }}
                            @endforeach
                        @elseif($school->phone_1)
                            {{ $isEnglishReceipt ? 'Phone' : 'Tél' }} : {{ $school->phone_1 }}
                        @endif
                        @if($school->address) &nbsp;|&nbsp; {{ $school->address }} @endif
                    </div>
                    <div class="school-sm">
                        @if($school->postal_box) {{ $isEnglishReceipt ? 'P.O. Box' : 'BP' }} : {{ $school->postal_box }} @endif
                    </div>
                    <div class="school-sm">
                        @if($school->ministry) {{ $school->ministry }} @endif
                    </div>
                </div>
            </div>
            <div class="card-header-right" style="text-align: center;">
                <div class="card-title">{{ $isEnglishReceipt ? 'Official Payment Receipt' : 'Reçu de Paiement Officiel' }}</div>
                <div class="card-num">{{ $isEnglishReceipt ? 'No.' : 'N°' }} {{ $p->receipt_number }}</div>
                <div class="card-year">
                    {{ $isEnglishReceipt ? 'Student Copy' : 'Exemplaire Élève' }}
                </div>
                <div class="card-year">
                    {{ $isEnglishReceipt ? 'Academic Year' : 'Année Scolaire' }} : {{ $enr->academicYear->label }}
                    &nbsp;|&nbsp;
                    {{ $isEnglishReceipt ? 'Date' : 'Date' }} : {{ now()->format('d/m/Y') }}
                </div>
            </div>
        </div>

        {{-- ── INFOS ÉLÈVE (2 colonnes) ────────────────────────────────── --}}
        <div class="card-student">
            <div class="card-stu-col">
                <div class="mini-badge">{{ $isEnglishReceipt ? 'Student Identity' : 'Identité Élève' }}</div>
                <div class="mini-row">
                    <span class="mini-lbl">{{ $isEnglishReceipt ? 'Name' : 'Nom & prénom' }} :</span>
                    <span class="mini-val">
                        {{ strtoupper($enr->student->full_name) }}
                    </span>
                </div>
                <div class="mini-row">
                    <span class="mini-lbl">{{ $isEnglishReceipt ? 'Registration N°' : 'Matricule' }} :</span>
                    <span class="mini-val">{{ $enr->student->matricule }}</span>
                </div>
                <div class="mini-row">
                    <span class="mini-lbl">{{ $isEnglishReceipt ? 'Born On' : 'Né(e) le' }} :</span>
                    <span class="mini-val">
                        {{ $enr->student->date_of_birth?->format('d/m/Y') ?? '—' }}
                        @if($enr->student->place_of_birth)
                            &nbsp;à&nbsp;
                            {{ strtoupper($enr->student->place_of_birth) }}
                        @endif
                    </span>
                </div>
            </div>
            <div class="card-stu-col">
                <div class="mini-badge">{{ $isEnglishReceipt ? 'Schooling' : 'Scolarité' }}</div>
                <div class="mini-row">
                    <span class="mini-lbl">{{ $isEnglishReceipt ? 'Class' : 'Classe' }} :</span>
                    <span class="mini-val">
                        {{ $enr->classGroup->full_name }}
                    </span>
                </div>
                <div class="mini-row">
                    <span class="mini-lbl">{{ $isEnglishReceipt ? 'Section' : 'Section' }} :</span>
                    <span class="mini-val" style="font-size:8.5px;">
                        {{ $enr->classGroup->level->section->name }}
                    </span>
                </div>
                <div class="mini-row">
                    <span class="mini-lbl">{{ $isEnglishReceipt ? 'Payment Date' : 'Date paiement' }} :</span>
                    <span class="mini-val">
                        {{ $p->payment_date->format('d/m/Y') }}
                        {{ $p->created_at->format('H:i') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- ── OBJET / MODE ────────────────────────────────────────────────── --}}
        @php $isManualInsolvablePayment = $p->fee_installment_id === null && ! $p->is_bulk; @endphp
        <div class="card-object">
            @if(!$isManualInsolvablePayment && $p->receipt_payment_subject)
                <span class="obj-lbl">{{ $isEnglishReceipt ? 'Purpose' : 'Objet' }} :</span>
                <span class="obj-val">
                    {{ $p->receipt_payment_subject }}
                </span>
            @else
                <span class="obj-lbl">{{ $isEnglishReceipt ? 'Payment Method' : 'Mode de paiement' }} :</span>
                <span class="obj-val" style="font-size:11px;">{{ $p->payment_method_label }}</span>
            @endif
            @if($p->reference && ! $isManualInsolvablePayment)
                <span class="obj-lbl">{{ $isEnglishReceipt ? 'Ref.' : 'Réf' }} :</span>
                <span class="obj-val" style="font-size:9px;">
                    {{ $p->reference }}
                </span>
            @endif
            <span class="mode-sm">{{ $p->payment_method_label }}</span>
        </div>

        {{-- ── CACHET ────────────────────────────────────────────────── --}}
        @php
            $sealPath = null;
            $recordedByUser = $p->recordedBy;
            if ($recordedByUser) {
                $sealPath = $recordedByUser->hasAnyRole(['directeur', 'fondateur'])
                    ? $school->signature_seal
                    : $recordedByUser->signature_seal;
            }
        @endphp
        <div class="card-amounts-wrapper">
            @if($sealPath)
                <div class="card-seal">
                    <img src="{{ asset('storage/' . $sealPath) }}" alt="Cachet">
                </div>
            @endif
            <div class="card-amounts">
                <div class="amt-row">
                    <span class="amt-lbl">{{ $isEnglishReceipt ? 'Total school fees' : 'Total des frais scolaires' }}</span>
                    <span class="amt-val">
                        {{ number_format($item['totalDue'], 0, ',', ' ') }} FCFA
                    </span>
                </div>
            <div class="amt-row highlight">
                <span class="amt-lbl">{{ $isEnglishReceipt ? 'AMOUNT OF THIS PAYMENT' : 'MONTANT DU PRÉSENT PAIEMENT' }}</span>
                <span class="amt-val">
                    {{ number_format($p->amount_paid, 0, ',', ' ') }} FCFA
                </span>
            </div>
            @if($p->scholarship_amount > 0)
            <div class="amt-row">
                <span class="amt-lbl" style="color:#000;">{{ $isEnglishReceipt ? 'SCHOLARSHIP APPLIED' : 'BOURSE APPLIQUÉE' }}</span>
                <span class="amt-val" style="color:#000;">
                    {{ number_format($p->scholarship_amount, 0, ',', ' ') }} FCFA
                </span>
            </div>
            @endif
            <div class="amt-row">
                <span class="amt-lbl"
                      style="color:black;">
                    {{ $isEnglishReceipt ? 'Balance after this payment' : 'Reste à payer après ce paiement' }}
                </span>
                <span class="amt-val"
                      style="color:{{ $rem > 0 ? '#B76E79' : '#60906F' }};">
                    {{ number_format($rem, 0, ',', ' ') }} FCFA
                </span>
            </div>
        </div>
    </div>

        {{-- ── PIED DE CARTE ────────────────────────────────────────────── --}}
        <div class="card-footer">
            <div class="foot-cashier">
                {{ $isEnglishReceipt ? 'Cashier' : 'Caissier(e)' }} :
                <span>{{ $p->recordedBy?->name ?? 'Système' }}</span>
            </div>
            {{-- <div class="foot-sig">{{ $isEnglishReceipt ? 'Signature & Seal' : 'Signature & Cachet' }}</div> --}}
        </div>
        <div class="card-nb">
            <svg style="width:13px;height:13px;vertical-align:-2px;margin-right:5px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg> {{ $isEnglishReceipt ? 'NB : NO FEES ARE REFUNDABLE !' : 'NB : AUCUN FRAIS N\'EST REMBOURSABLE !' }}
        </div>

    </div>{{-- fin receipt-card --}}

    @endforeach

    {{-- Cellule vide si nombre impair --}}
    {{-- @if($pair->count() === 1)
    <div class="receipt-card empty">
        — Page libre —
    </div>
    @endif --}}

</div>{{-- fin receipt-page --}}
@endforeach

</body>
</html>