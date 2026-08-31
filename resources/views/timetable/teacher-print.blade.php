<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Emploi du temps individuel - {{ $selectedStaff->full_name }}</title>
@include('students.documents.partials.base-styles')
<style>
@page { size: A4 landscape; margin: 2.5mm; }
body { background:#fff; margin:0; padding:0; color:#111827; }
.timetable-page { width:100%; max-width:none; padding:0; background:#fff; }
.no-print { margin:0 0 4px; text-align:center; }
.print-btn { border:0; border-radius:8px; background:#1A3A6B; color:#fff; cursor:pointer; font-weight:800; padding:8px 18px; }
.cert-official-header { margin-bottom:1px; }
.cert-official-header__columns { grid-template-columns:1fr 28mm 1fr; gap:4mm; }
.cert-official-header__side { min-height:28mm; }
.cert-official-header__republic { font-size:9.2px; }
.cert-official-header__motto { font-size:8.3px; margin:1px 0; }
.cert-official-header__stars { font-size:8.3px; line-height:1; margin:1px 0; }
.cert-official-header__ministry,
.cert-official-header__school { font-size:8.3px; }
.cert-official-header__meta,
.cert-official-header__email { font-size:7px; line-height:1.2; }
.cert-official-header__logo img,
.cert-official-header__logo-placeholder { width:22mm; height:22mm; }
.cert-official-header__agreements { display:none; }
.report-title { border-top:2px solid #1A3A6B; border-bottom:1px solid #D1D5DB; margin:1px 0 2px; padding:2px 0; text-align:center; }
.report-title h1 { color:#1A3A6B; font-size:17px; font-weight:900; text-transform:uppercase; }
.report-title p { color:#4B5563; font-size:11px; margin-top:1px; }
.teacher-meta { width:100%; border-collapse:collapse; margin:0 0 8px; }
.teacher-meta td { border:1px solid #D1D5DB; padding:4px 6px; font-size:10.5px; line-height:1.4; vertical-align:top; }
.teacher-meta .label { width:24%; background:#F8FAFC; color:#1A3A6B; font-size:11px; font-weight:900; }
.teacher-meta .value { font-size:11.5px; font-weight:800; color:#111827; }
.timetable-print { width:100%; border-collapse:collapse; table-layout:fixed; font-size:12.5px; }
.timetable-print th,
.timetable-print td { border:1px solid #CBD5E1; padding:5px 4px; vertical-align:middle; text-align:center; }
.timetable-print th { background:#EEF2F7; color:#1A3A6B; font-size:12.5px; font-weight:900; text-transform:uppercase; }
.timetable-print .period { width:20mm; color:#4B5563; font-size:10.5px; font-weight:800; text-align:center; white-space:nowrap; }
.timetable-print td { min-height:9mm; height:9mm; text-align:center; vertical-align:middle; }
.break-row td { background:#FFF7ED; color:#9A3412; font-size:11.5px; font-weight:900; text-align:center; }
.slot { border-left:3px solid #1A5C2A; background:#F8FAFC; padding:4px 3px; min-height:8mm; text-align:center; display:flex; flex-direction:column; justify-content:center; align-items:center; height:100%; }
.slot strong { color:#0F5132; display:block; font-size:12.5px; line-height:1.25; }
.slot span { color:#374151; display:block; font-size:10.5px; line-height:1.3; }
.teacher-slot-block { width:100%; }
.teacher-slot-block strong { display:block; color:#0F5132; font-size:12.5px; line-height:1.25; }
.teacher-slot-block span { display:block; color:#374151; font-size:10.5px; line-height:1.3; }
.footer-area { margin-top:14px; }
.footer-line { display:flex; justify-content:flex-end; gap:14px; color:#6B7280; font-size:9px; }
.footer-line span { min-width:230px; text-align:right; }
.signatures { margin-top:10px; display:flex; justify-content:space-between; gap:12px; flex-wrap:wrap; }
.signatures__box { width:48%; min-width:170px; border-top:1px solid #CBD5E1; padding-top:6px; display:flex; flex-direction:column; gap:6px; align-items:center; text-align:center; }
.signatures__label { font-size:8.5px; color:#4B5563; font-weight:700; width:100%; }
.signatures__line { width:100%; border-bottom:1px solid #9CA3AF; height:0; margin-top:10px; }
.signatures__seal { max-height:14mm; width:auto; max-width:100%; object-fit:contain; display:block; margin:6px auto 0; }
@media print { .no-print { display:none !important; } }
</style>
</head>
<body>
<div class="no-print"><button class="print-btn" onclick="window.print()">Imprimer</button></div>

<div class="timetable-page">
    @php
        $currentUser = auth()->user();
        $censeurSeal = $currentUser?->hasRole('censeur') ? $currentUser->signature_seal : null;
        $principalSeal = $school->signature_seal;
        $footerCity = $school->city ?: '________________';
    @endphp

    @include('students.documents.partials.certificate-official-header', [
        'school' => $school,
        'phones' => $phones,
        'agreements' => $agreements,
        'showCertificateTitle' => false,
        'forPdf' => false,
    ])

    <div class="report-title">
        <h1>Emploi du Temps Individuel de l'Enseignant / Teacher's Individual Timetable</h1>
        {{-- <p>{{ $selectedStaff->full_name }} - {{ $activeYear?->label }}</p> --}}
    </div>

    <table class="teacher-meta">
        <tr>
            <td class="label">Nom de l'enseignant / Teacher's Name</td>
            <td class="value">{{ $selectedStaff->full_name }}</td>
            <td class="label">Année Scolaire / School Year</td>
            <td class="value">{{ $activeYear?->label ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Discipline(s) Enseignée(s) / Subject(s) Taught</td>
            <td class="value">{{ $teacherSubjects->join(' / ') ?: '' }}</td>
            <td class="label">Classes Tenues / Classes Taught</td>
            <td class="value">{{ $teacherClasses->join(' / ') ?: '' }}</td>
        </tr>
        <tr>
            <td class="label">Nombre d'Heures / Hours Done</td>
            <td class="value" colspan="3">{{ number_format($totalHours, 1, ',', ' ') }} h</td>
        </tr>
    </table>

    @include('timetable.partials.grid', [
        'mode' => 'teacher',
        'printable' => true,
        'days' => $days,
        'gridRows' => $gridRows,
        'slots' => $slots,
        'teacherSubjectCount' => $teacherSubjectCount,
    ])

    <div class="footer-area">
        <div class="footer-line">
            <span>Fait à {{ $footerCity }} le ________________________</span>
        </div>
        <div class="signatures">
            <div class="signatures__box">
                <span class="signatures__label">Le Préfet des Etudes / The Dean</span>
                @if($censeurSeal)
                    <img src="{{ asset('storage/' . $censeurSeal) }}" alt="Cachet du censeur" class="signatures__seal">
                @else
                    <div class="signatures__line"></div>
                @endif
            </div>
            <div class="signatures__box">
                <span class="signatures__label">La Direction / The Direction</span>
                @if($principalSeal)
                    <img src="{{ asset('storage/' . $principalSeal) }}" alt="Cachet du principal" class="signatures__seal">
                @else
                    <div class="signatures__line"></div>
                @endif
            </div>
        </div>
    </div>
</div>
</body>
</html>
