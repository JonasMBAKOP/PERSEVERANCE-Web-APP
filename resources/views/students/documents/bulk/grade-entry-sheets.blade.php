<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Bordereaux de saisie — {{ $classGroup->full_name }}</title>
@include('students.documents.partials.base-styles')
<style>
    :root{--primary:#1A3A6B;--border:#9CA3AF;--muted:#475569}
    @page{size:A4 portrait; margin:5mm 5mm 5mm 5mm}
    html,body{margin:0;padding:0;font-family:Inter,system-ui,-apple-system,Segoe UI,sans-serif;color:#111827;background:#ffffff}
    body{padding:0 !important; margin:0 !important; background:#ffffff !important;}
    .page{width:100%;margin:0;padding:0;background:white;box-sizing:border-box;page-break-after:always;display:block;}
    .page-content{width:100%;margin:0;padding:0}
    /* reuse bordereau styles for header */
    .data-table{width:100%;border-collapse:collapse;font-size:11px;margin:0}
    .data-table th,.data-table td{border:1.5px solid var(--border);padding:6px 6px;text-align:center;vertical-align:middle}
    .data-table th.evaluation-column,
    .data-table td.evaluation-cell{box-sizing:border-box;overflow:hidden;white-space:nowrap}
    .data-table th{background:#F2F4F7;font-weight:900;color:var(--primary);font-size:13px}
    .data-table td.name{text-align:left;font-weight:700;text-transform:uppercase;padding-left:8px}
    .bordereau-header__title{font-size:21px;font-weight:900;color:var(--primary);text-transform:uppercase;letter-spacing:.06em}
    .meta-line{display:flex;justify-content:space-between;align-items:center;margin:0px;font-size:15px}
    .meta-left{flex:1;text-align:left;margin-left:2rem;}
    .meta-right{flex:1;text-align:right;margin-right:17rem;}
    .small-muted{font-size:11px;color:var(--muted)}
    .signature-box{border:1px dashed var(--border);min-height:48px;display:flex;align-items:center;justify-content:center;font-size:9px;color:var(--muted)}
    /* make name header span two rows visually balanced */
    .data-table th.name-header{vertical-align:middle}
    @media print {
        /* Reserve space for a left binding without changing the screen preview. */
        .page{padding-left:8mm !important;}
        .data-table{table-layout:fixed;}
        .data-table th:first-child,
        .data-table td:first-child{width:28px;min-width:28px;max-width:28px;}
        .data-table th.evaluation-column,
        .data-table td.evaluation-cell{box-sizing:border-box;width:18px;min-width:18px;max-width:18px;padding-left:0;padding-right:0;}
        .data-table th:nth-child(2),
        .data-table td:nth-child(2){width:45%;min-width:0;max-width:none;}
    }
</style>
</head>
<body>
@include('students.documents.partials.print-toolbar')

@php
    // load trimesters and sequences for the year
    $trimesters = \App\Models\Trimester::where('academic_year_id', $year->id)
        ->with(['sequences' => fn($q) => $q->orderBy('number')])
        ->orderBy('number')
        ->get();

    $classGroup->loadMissing('level.section');
    $isCoptan = ! str_contains(strtolower((string) ($school->full_name ?? '')), 'perseverance');
    $isAnglophone = $classGroup->level?->section?->isAnglophone() ?? false;
    if ($isCoptan) {
        $trimesters->each(function ($trimester) use ($isAnglophone) {
            $trimester->setRelation('sequences', $trimester->sequences->take($isAnglophone ? 2 : 3)->values());
        });
    }

    $studentsList = $students->values();
@endphp

@foreach($subjects as $cs)
    @php
        $subject = $cs->subject;
        $teacher = $cs->teacherFor($year->id)?->full_name ?? ($classGroup->titularStaff?->full_name ?? '—');
        $discipline = $subject->name_fr ?? $subject->name ?? ($subject->name_en ?? '—');
    @endphp

    <div class="page" style="margin:0; width:100%; padding:0;">
        @include('grades.partials.bordereau-header', ['forPdf' => false, 'docTitle' => 'Bordereau de notes', 'docSubtitle' => $classGroup->full_name])
        <div class="page-content" style="margin-top:6px;">
            {{-- <div class="bordereau-header__title-row">
                <div class="bordereau-header__title">Bordereau de notes</div>
            </div> --}}

            <div class="meta-line" style="margin:0;">
                <div class="meta-left"><strong>Discipline :</strong></div>
                {{-- {{ $discipline }} --}}
                <div class="meta-right"><strong>Enseignant :</strong></div>
                 {{-- <span style="font-weight:700">{{ $teacher }}</span> --}}
            </div>

            {{-- <div class="meta-line" style="margin-bottom:6px;">
                <div class="meta-left small-muted">Classe : <strong>{{ $classGroup->full_name }}</strong></div>
                <div class="meta-right small-muted">Année scolaire : <strong>{{ $year->label ?? '—' }}</strong></div>
            </div> --}}

            <div style="overflow-x:auto;margin-top:6px;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th class="name-header" rowspan="2">N°</th>
                            <th class="name-header" rowspan="2">Noms et Prénoms</th>
                            @foreach($trimesters as $tri)
                                @php $seqCount = $tri->sequences->count(); @endphp
                                <th colspan="{{ $seqCount }}">TRIMESTRE {{ $tri->number }}</th>
                            @endforeach
                        </tr>
                        <tr>
                            @foreach($trimesters as $tri)
                                @php $seqCount = $tri->sequences->count(); $i = 1; @endphp
                                @foreach($tri->sequences as $seq)
                                    <th class="evaluation-column">E{{ $i }}</th>
                                    @php $i++; @endphp
                                @endforeach
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php $idx = 1; @endphp
                        @foreach($studentsList as $s)
                            @php $full = strtoupper($s->last_name . ' ' . $s->first_name); @endphp
                            <tr>
                                <td class="name" style="text-align: center;">{{ $idx }}</td>
                                <td class="name">{{ $full }}</td>
                                @foreach($trimesters as $tri)
                                    @foreach($tri->sequences as $seq)
                                        <td class="evaluation-cell"></td>
                                    @endforeach
                                @endforeach
                            </tr>
                            @php $idx++; @endphp
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- <div style="display:flex;justify-content:space-between;margin-top:8px;align-items:flex-end;">
                <div class="small-muted">Page générée pour la classe <strong>{{ $classGroup->full_name }}</strong></div>
                <div class="signature-box">Cachet et Signature de la Direction</div>
            </div> --}}
        </div>
    </div>
@endforeach

</body>
</html>
