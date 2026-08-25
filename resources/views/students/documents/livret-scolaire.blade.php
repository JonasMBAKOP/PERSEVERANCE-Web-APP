<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Livret scolaire — {{ $student->full_name }}</title>
@include('students.documents.partials.base-styles')
<style>
@page { size: A4 portrait; margin: 4mm 5mm; }
.livret-meta { font-size: 12px; margin-bottom: 14px; }
.livret-table { width: 100%; border-collapse: collapse; font-size: 10px; }
.livret-table th, .livret-table td {
    border: 1px solid #9CA3AF; padding: 6px 7px; text-align: center;
}
.livret-table th { background: #1A3A6B; color: #fff; font-weight: 700; font-size: 10px; }
.livret-table td.subject { text-align: left; font-weight: 600; font-size: 10px; }
.livret-table td.coef { width: 42px; }
.livret-placeholder {
    background: #FEF3C7; border: 1px solid #FCD34D; padding: 10px;
    font-size: 10px; color: #92400E; margin-bottom: 12px; border-radius: 4px;
}
.livret-seal {
    display: block;
    max-width: 60px;
    max-height: 60px;
    margin: 6px auto 0;
}
</style>
</head>
<body>
@include('students.documents.partials.print-toolbar')

@php
    $subjects = $subjectsByEnrollment[$student->id] ?? collect();
    $section  = $enrollment?->classGroup?->level?->section;
    $seqCols  = $sequences->isNotEmpty() ? $sequences : collect(range(1, 6))->map(fn ($n) => (object)['number' => $n, 'label' => "Séq. $n"]);
@endphp

<div class="page">
    @include('students.documents.partials.school-header', [
        'docTitle' => 'Livret scolaire',
        'docSubtitle' => $student->full_name . ' — ' . ($year?->label ?? ''),
    ])

    <div class="livret-placeholder">
        <strong>Module notes en cours de déploiement.</strong>
        Le tableau ci-dessous présente la structure du livret (matières et séquences).
        Les notes seront renseignées automatiquement dès l'activation du module d'évaluation.
    </div>

    <div class="livret-meta info-grid">
        <div class="info-row"><span class="label">Élève</span><span class="value">{{ $student->full_name }}</span></div>
        <div class="info-row"><span class="label">Matricule</span><span class="value">{{ $student->matricule }}</span></div>
        @if($enrollment)
        <div class="info-row"><span class="label">Classe</span><span class="value">{{ $enrollment->classGroup->full_name }}</span></div>
        <div class="info-row"><span class="label">Section</span><span class="value">{{ $section?->name }}</span></div>
        @endif
    </div>

    <table class="livret-table">
        <thead>
            <tr>
                <th style="text-align:left;min-width:120px;">Matière</th>
                <th class="coef">Coef.</th>
                @foreach($seqCols as $seq)
                <th>{{ $seq->label ?? ('S' . $seq->number) }}</th>
                @endforeach
                <th>Moy.</th>
                <th>Appréciation</th>
            </tr>
        </thead>
        <tbody>
            @forelse($subjects as $cs)
            <tr>
                <td class="subject">{{ app(\App\Services\StudentDocumentService::class)->subjectLabel($cs, $section) }}</td>
                <td>{{ $cs->coefficient }}</td>
                @foreach($seqCols as $seq)
                <td>—</td>
                @endforeach
                <td>—</td>
                <td>—</td>
            </tr>
            @empty
            <tr>
                <td colspan="{{ 4 + $seqCols->count() }}" style="padding:12px;color:#6B7280;">
                    Aucune matière assignée à cette classe.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="signature-block signature-block--tight">
        <div class="signature-box">
            <div>La Direction / The Direction</div>
            <div class="signature-line">Signature</div>
        </div>
        <div class="signature-box">
            <div>Le/La Chef(fe) d'établissement</div>
            <div class="signature-line">Signature et cachet</div>
            @if($school->signature_seal)
                <img src="{{ asset('storage/' . $school->signature_seal) }}" alt="Cachet du Principal" class="livret-seal">
            @endif
        </div>
    </div>
</div>
</body>
</html>
