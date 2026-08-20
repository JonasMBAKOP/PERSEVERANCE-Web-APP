@php
    $schoolNameFr = strtoupper($school->full_name);
    $schoolNameEn = strtoupper($school->full_name_en ?: 'NTANKEU POLYVALENT COLLEGE');
    $ministryFr = strtoupper($school->ministry ?: 'MINISTERE DES ENSEIGNEMENTS SECONDAIRES');
    $ministryEn = strtoupper($school->ministry_en ?: 'MINISTRY OF SECONDARY EDUCATION');
    $phones = collect($phones);
    $phoneLine = $phones->isNotEmpty() ? $phones->pluck('number')->join(' / ') : null;
    $agreementLines = isset($agreements) ? collect($agreements) : collect();
    $agreementLines = $agreementLines->isNotEmpty()
        ? $agreementLines->map(fn ($agreement) => 'N° ' . $agreement->number)
        : collect();
    $logoSrc = null;
    if ($school->logo) {
        $storagePath = public_path('storage/' . $school->logo);
        if (file_exists($storagePath)) {
            $logoSrc = $forPdf ?? false ? $storagePath : asset('storage/' . $school->logo);
        }
    }
    if (! $logoSrc && file_exists(public_path('images/logo.jpg'))) {
        $logoSrc = $forPdf ?? false ? public_path('images/logo.jpg') : asset('images/logo.jpg');
    }

    // Définition des colonnes de notes conditionnelles
    $cols = [];
    if ($type === 'sequentiel') {
        $cols[] = $periodLabel; // Ex: DS 5
    } else {
        foreach ($periodHeaders as $hdr) {
            $cols[] = $hdr; // Ex: DS1, DS2
        }
        $cols[] = 'Moy.';
    }
    $conditionalColsCount = count($cols);
    $totalColspan = 3 + $conditionalColsCount + 6; // Groupe + Matiere + Coef + Conditional + (Total + Rang + %Reussite + Max + Min + Mentions)

    // Groupement des matières par catégorie
    $groupedDetails = collect($details)
        ->groupBy(fn ($detail) => $detail['subject']->category?->id ?? 'general')
        ->sortBy(function ($catDetails) {
            $code = (string) ($catDetails->first()['subject']->category?->code ?? '');
            return $code === '' ? PHP_INT_MAX : (int) substr($code, 3);
        });
    $categoryCodes = $groupedDetails->map(fn ($catDetails) =>
        trim((string) ($catDetails->first()['subject']->category?->code ?? ''))
    )->filter()->values();
@endphp

<div class="bulletin-page">
    {{-- ── EN-TÊTE BILINGUE OFFICIEL ── --}}
    <header class="cert-official-header">
        <div class="cert-official-header__columns">
            <div class="cert-official-header__side cert-official-header__side--fr">
                <div class="cert-official-header__republic">REPUBLIQUE DU CAMEROUN</div>
                <div class="cert-official-header__motto">Paix-Travail-Patrie</div>
                <div class="cert-official-header__stars">********</div>
                <div class="cert-official-header__ministry">{{ $ministryFr }}</div>
                <div class="cert-official-header__stars">********</div>
                <div class="cert-official-header__school">{{ $schoolNameFr }}</div>
                <div class="cert-official-header__motto">{{ $school->motto ?: 'Paix-Travail-Patrie' }}</div>
                @if($phoneLine)
                    <div class="cert-official-header__meta">Tél. {{ $phoneLine }}</div>
                @endif
                <div class="cert-official-header__meta">
                    @if($school->postal_box) B.P. {{ $school->postal_box }} @endif
                </div>
                @if($school->email)
                    <div class="cert-official-header__email"><span>E-mail :</span> {{ $school->email }}</div>
                @endif
                <div class="cert-official-header__meta" style="font-weight: 800; color: #111827; margin-top: 2px;">Année Scolaire : {{ $academicYear->label ?? '—' }}</div>
            </div>

            <div class="cert-official-header__logo">
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="Logo {{ $school->short_name }}" class="school-logo" style="width: 32mm; height: 32mm; object-fit: contain;">
                @else
                    <div class="cert-official-header__logo-placeholder">
                        {{ strtoupper(substr($school->short_name ?? 'C', 0, 1)) }}
                    </div>
                @endif
            </div>

            <div class="cert-official-header__side cert-official-header__side--en">
                <div class="cert-official-header__republic">REPUBLIC OF CAMEROON</div>
                <div class="cert-official-header__motto">Peace-Work-Fatherland</div>
                <div class="cert-official-header__stars">********</div>
                <div class="cert-official-header__ministry">{{ $ministryEn }}</div>
                <div class="cert-official-header__stars">********</div>
                <div class="cert-official-header__school">{{ $schoolNameEn }}</div>
                <div class="cert-official-header__motto">{{ $school->motto_en ?: 'Peace-Work-Fatherland' }}</div>
                @if($phoneLine)
                    <div class="cert-official-header__meta">Phone. {{ $phoneLine }}</div>
                @endif
                <div class="cert-official-header__meta">
                    @if($school->postal_box) P.O. BOX {{ $school->postal_box }} @endif
                </div>
                @if($school->email)
                    <div class="cert-official-header__email"><span>Email :</span> {{ $school->email }}</div>
                @endif
                <div class="cert-official-header__meta" style="font-weight: 800; color: #111827; margin-top: 2px;">School Year: {{ $academicYear->label ?? '—' }}</div>
            </div>
        </div>

        {{-- @if($agreementLines->isNotEmpty())
            <div class="cert-official-header__agreements">
                @foreach($agreementLines as $agreementLine)
                    <div>{{ $agreementLine }}</div>
                @endforeach
            </div>
        @endif --}}

        <div class="cert-official-header__title">
        <div>{{ $documentTitle }} / {{ $documentTitleEn }}</div>
        </div>
    </header>

    {{-- ── INFORMATIONS ÉLÈVE (PHOTO À GAUCHE) ── --}}
    <table style="width: 100%; border-collapse: collapse; border: none; background: transparent; margin-bottom: 6px;">
        <tbody>
        <tr>
            {{-- Photo column --}}
            <td style="width: 82px; padding: 0 10px 0 0; vertical-align: middle; border: none; text-align: center;">
                @if($studentPhoto)
                    <img src="{{ $studentPhoto }}" alt="Photo" style="width: 72px; height: 88px; object-fit: cover; border: 1px solid #CBD5E1; border-radius: 4px;">
                @else
                    <div class="student-photo-fallback" style="width: 72px; height: 76px; border: 1px solid #D1D5DB; color: #111827; display: flex; align-items: center; justify-content: center; background: #E5E7EB; border-radius: 4px;">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="#111827" aria-label="Avatar eleve"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7H4z"/></svg>
                        <span>Photo élève</span>
                    </div>
                @endif
            </td>
            {{-- Info columns --}}
            <td style="border: none; padding: 0; vertical-align: top;">
                <table style="width: 100%; border-collapse: collapse; border: none; background: transparent; font-size: 8px;">
                    <tbody>
                    <tr>
                        {{-- Ligne 1 : Nom et Prénom(s) | Matricule --}}
                        <td style="border: none; padding: 2px 0; vertical-align: middle; width: 62%;">
                            <div style="display: inline-block; vertical-align: middle; margin-right: 4px; line-height: 1.1;">
                                <span class="info-label" style="font-weight: 800; font-size: 7.5px;">Nom et Prénom(s)</span><br>
                                <span class="info-sublabel" style="font-size: 5.5px; font-style: italic; color: #6B7280;">Student name</span>
                            </div>
                            <span class="info-value" style="font-weight: 800; font-size: 9px; vertical-align: middle;">: {{ strtoupper($enrollment->student->last_name . ' ' . $enrollment->student->first_name) }}</span>
                        </td>
                        <td style="border: none; padding: 2px 0; vertical-align: middle;">
                            <div style="display: inline-block; vertical-align: middle; margin-right: 4px; line-height: 1.1;">
                                <span class="info-label" style="font-weight: 800; font-size: 7.5px;">Matricule</span><br>
                                <span class="info-sublabel" style="font-size: 5.5px; font-style: italic; color: #6B7280;">Mat No</span>
                            </div>
                            <span class="info-value" style="font-weight: 800; font-size: 9px; vertical-align: middle;">: {{ $enrollment->student->matricule ?? '—' }}</span>
                        </td>
                    </tr>
                    <tr>
                        {{-- Ligne 2 : Né(e) le / A | Sexe --}}
                        <td style="border: none; padding: 2px 0; vertical-align: middle; width: 62%;">
                            {{-- <div style="display: inline-block; vertical-align: middle; margin-right: 4px; line-height: 1.1;">
                                <span class="info-label" style="font-weight: 800; font-size: 7.5px;">Né(e) le / A</span><br>
                                <span class="info-sublabel" style="font-size: 5.5px; font-style: italic; color: #6B7280;">Born on / At</span>
                            </div>
                            <span class="info-value" style="font-weight: 800; font-size: 9px; vertical-align: middle;">: {{ $enrollment->student->date_of_birth?->format('d/m/Y') ?? '—' }} à {{ strtoupper($enrollment->student->place_of_birth ?? '—') }}</span> --}}
                            <div style="display: flex; gap: 70px;">
                                <div style="display: inline-flex; align-items: center;">
                                    <div style="display: inline-block; vertical-align: middle; margin-right: 4px; line-height: 1.1;">
                                        <span class="info-label" style="font-weight: 800; font-size: 7.5px;">Né(e) le </span><br>
                                        <span class="info-sublabel" style="font-size: 5.5px; font-style: italic; color: #6B7280;">Born on </span>
                                    </div>
                                    <span class="info-value" style="font-weight: 800; font-size: 9px; vertical-align: middle;">: {{ $enrollment->student->date_of_birth?->format('d/m/Y') ?? '—' }}</span>
                                </div>
                                <div style="display: inline-flex; align-items: center;">
                                    <div style="display: inline-block; vertical-align: middle; margin-right: 4px; line-height: 1.1;">
                                        <span class="info-label" style="font-weight: 800; font-size: 7.5px;">A </span><br>
                                        <span class="info-sublabel" style="font-size: 5.5px; font-style: italic; color: #6B7280;">At </span>
                                    </div>
                                    <span class="info-value" style="font-weight: 800; font-size: 9px; vertical-align: middle;">: {{ strtoupper($enrollment->student->place_of_birth ?? '—') }}</span>
                                </div>
                            </div>  
                        </td>
                        <td style="border: none; padding: 2px 0; vertical-align: middle;">
                            <div style="display: inline-block; vertical-align: middle; margin-right: 4px; line-height: 1.1;">
                                <span class="info-label" style="font-weight: 800; font-size: 7.5px;">Sexe</span><br>
                                <span class="info-sublabel" style="font-size: 5.5px; font-style: italic; color: #6B7280;">Sex</span>
                            </div>
                            <span class="info-value" style="font-weight: 800; font-size: 9px; vertical-align: middle;">: {{ $enrollment->student->gender ?? '—' }}</span>
                        </td>
                    </tr>
                    <tr>
                        {{-- Ligne 3 : Classe | Effectif | Redoublant --}}
                        <td style="border: none; padding: 2px 0; vertical-align: middle;" colspan="2">
                            <div style="display: flex; gap: 70px;">
                                <div style="display: inline-flex; align-items: center;">
                                    <div style="display: inline-block; margin-right: 4px; line-height: 1.1;">
                                        <span class="info-label" style="font-weight: 800; font-size: 7.5px;">Classe</span><br>
                                        <span class="info-sublabel" style="font-size: 5.5px; font-style: italic; color: #6B7280;">Class</span>
                                    </div>
                                    <span class="info-value" style="font-weight: 800; font-size: 9px;">: {{ $classGroup->full_name }}</span>
                                </div>
                                <div style="display: inline-flex; align-items: center;">
                                    <div style="display: inline-block; margin-right: 4px; line-height: 1.1;">
                                        <span class="info-label" style="font-weight: 800; font-size: 7.5px;">Effectif</span><br>
                                        <span class="info-sublabel" style="font-size: 5.5px; font-style: italic; color: #6B7280;">Class size</span>
                                    </div>
                                    <span class="info-value" style="font-weight: 800; font-size: 9px;">: {{ $classSize }}</span>
                                </div>
                                <div style="display: inline-flex; align-items: center;">
                                    <div style="display: inline-block; margin-right: 4px; line-height: 1.1;">
                                        <span class="info-label" style="font-weight: 800; font-size: 7.5px;">Redoublant(e)</span><br>
                                        <span class="info-sublabel" style="font-size: 5.5px; font-style: italic; color: #6B7280;">Repeater</span>
                                    </div>
                                    <span class="info-value" style="font-weight: 800; font-size: 9px;">: {{ $isRepeating ? 'Oui' : 'Non' }}</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        {{-- Ligne 4 : Prof. Titulaire | Chef d'établissement --}}
                        <td style="border: none; padding: 2px 0; vertical-align: middle; width: 62%;">
                            <div style="display: inline-block; vertical-align: middle; margin-right: 4px; line-height: 1.1;">
                                <span class="info-label" style="font-weight: 800; font-size: 7.5px;">Prof. Titulaire</span><br>
                                <span class="info-sublabel" style="font-size: 5.5px; font-style: italic; color: #6B7280;">Class Master/Mistress</span>
                            </div>
                            <span class="info-value" style="font-weight: 800; font-size: 9px; vertical-align: middle;">: {{ $classGroup->titularStaff?->full_name ?? '—' }}</span>
                        </td>
                        <td style="border: none; padding: 2px 0; vertical-align: middle;">
                            <div style="display: inline-block; vertical-align: middle; margin-right: 4px; line-height: 1.1;">
                                <span class="info-label" style="font-weight: 800; font-size: 7.5px;">Chef d'Établissement</span><br>
                                <span class="info-sublabel" style="font-size: 5.5px; font-style: italic; color: #6B7280;">Principal</span>
                            </div>
                            <span class="info-value" style="font-weight: 800; font-size: 9px; vertical-align: middle;">: {{ $principalName }}</span>
                        </td>
                    </tr>
                    {{-- <tr> --}}
                        {{-- Ligne 5 : Contact Parent --}}
                        {{-- <td style="border: none; padding: 2px 0; vertical-align: middle;" colspan="2">
                            <div style="display: inline-block; vertical-align: middle; margin-right: 4px; line-height: 1.1;">
                                <span class="info-label" style="font-weight: 800; font-size: 7.5px;">Contact Parent</span><br>
                                <span class="info-sublabel" style="font-size: 5.5px; font-style: italic; color: #6B7280;">Parent/tutor contact</span>
                            </div>
                            <span class="info-value" style="font-weight: 800; font-size: 9px; vertical-align: middle;">: {{ $parentContacts }}</span>
                        </td> --}}
                    {{-- </tr> --}}
                    </tbody>
                </table>
            </td>
        </tr>
        </tbody>
    </table>

    {{-- ── TABLEAU DES NOTES DU BULLETIN ── --}}
    <table class="notes-table">
        <thead>
            <tr>
                <th class="col-group">GROUPES / GROUPS</th>
                <th class="col-subject">MATIERES / SUBJECTS</th>
                @foreach($cols as $colName)
                    <th style="text-align: center;">{{ $colName }}</th>
                @endforeach
                <th class="col-coef" style="text-align: center;">COEF.</th>
                <th style="text-align: center;">TOTAL</th>
                <th style="text-align: center;">RANG</th>
                <th style="text-align: center;">%REUSSITE</th>
                <th style="text-align: center;">MAX</th>
                <th style="text-align: center;">MIN</th>
                <th style="text-align: center;">MENTIONS</th>
            </tr>
        </thead>
        <tbody>
            @php $sumPoints = 0; $sumCoef = 0; @endphp

            @foreach($groupedDetails as $catDetails)
                @php
                    $category = $catDetails->first()['subject']->category;
                    $categoryCode = trim((string) ($category?->code ?? ''));
                    $categoryName = $category?->name_fr ?? 'AUTRES MATIERES';
                @endphp
                {{-- Ligne En-tête Catégorie --}}
                @if(false)
                <tr class="category-row" style="background: rgba(26,58,107,0.05); font-weight: bold;">
                    <td rowspan="{{ $catDetails->count() + 2 }}" style="width: 8%; text-align: center; vertical-align: middle; padding: 6px 4px; font-weight: 900; color: #1A3A6B;">
                        {{ $categoryCode ?: '—' }}
                    </td>
                    <td colspan="{{ $totalColspan - 1 }}" style="padding: 6px 8px; font-weight: 900; text-transform: uppercase; color: #1A3A6B;">
                        {{ $categoryCode ? $categoryCode . ' - ' : '' }}{{ $categoryName }}
                    </td>
                </tr>
                @endif

                @foreach($catDetails as $detail)
                    @php
                        $grade = $detail['grade'];
                        $coef = $detail['coefficient'];
                        $isAbsent = $detail['is_absent'] ?? false;
                        $pointTotal = $detail['total'] ?? ($grade !== null && !$isAbsent ? round($grade * $coef, 2) : null);
                        
                        if ($grade !== null && !$isAbsent) {
                            $sumPoints += $grade * $coef;
                            $sumCoef += $coef;
                        } elseif ($isAbsent) {
                            $sumCoef += $coef;
                        }

                        $gradeClass = $isAbsent ? 'grade-absent' : (
                            $grade === null ? '' : (
                            $grade >= 12 ? 'grade-good' : ($grade >= 10 ? 'grade-avg' : 'grade-bad')
                        ));
                    @endphp
                    <tr>
                        @if($loop->first)
                            <td rowspan="{{ $catDetails->count() + 1 }}" class="col-group" style="text-align: center; vertical-align: middle; padding: 6px 4px; font-weight: 900; color: #1A3A6B;">
                                {{ $categoryCode ? $categoryCode . ' - ' : '' }}{{ $categoryName }}
                            </td>
                        @endif
                        <td class="col-subject">
                            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                                <span style="font-weight: bold; color: #111827;">{{ $detail['subject']->name_fr }}</span>
                                <span style="font-size: 7.5px; color: #6B7280; font-style: italic;">{{ $detail['teacher'] ?? '—' }}</span>
                            </div>
                        </td>

                        @if($type === 'sequentiel')
                            {{-- Note séquence unique --}}
                            <td class="grade-cell {{ $gradeClass }}">
                                @if($isAbsent)
                                    ABS
                                @elseif($grade === null)
                                    —
                                @else
                                    {{ number_format($grade, 2) }}
                                @endif
                            </td>
                        @else
                            {{-- Évaluations de trimestres / moyennes de trimestres --}}
                            @foreach($periodHeaders as $index => $header)
                                @php
                                    $value = $type === 'trimestriel'
                                        ? ($detail['seq_grades'][$index] ?? null)
                                        : ($detail['trimester_averages'][$index] ?? null);
                                @endphp
                                <td class="grade-cell {{ is_numeric($value) ? ($value >= 12 ? 'grade-good' : ($value >= 10 ? 'grade-avg' : 'grade-bad')) : '' }}">
                                    @if($value === 'ABS')
                                        ABS
                                    @elseif($value !== null)
                                        {{ number_format($value, 2) }}
                                    @else
                                        —
                                    @endif
                                </td>
                            @endforeach

                            {{-- Moyenne générale de la matière --}}
                            <td class="grade-cell {{ $gradeClass }}">
                                @if($isAbsent)
                                    ABS
                                @elseif($grade === null)
                                    —
                                @else
                                    {{ number_format($grade, 2) }}
                                @endif
                            </td>
                        @endif

                        {{-- Coef (après les notes, avant le Total) --}}
                        <td class="col-coef" style="text-align: center;">{{ $coef }}</td>

                        {{-- Total Points --}}
                        <td class="col-total" style="text-align: center; font-weight: bold;">
                            {{ $pointTotal !== null ? number_format($pointTotal, 2) : '—' }}
                        </td>

                        {{-- Rang --}}
                        <td class="col-rank" style="text-align: center;">
                            {{ $detail['rank'] ?? '—' }}
                        </td>

                        {{-- % Réussite --}}
                        <td style="text-align: center;">
                            {{ isset($detail['success_rate']) ? number_format($detail['success_rate'], 1) . '%' : '—' }}
                        </td>

                        {{-- Max --}}
                        <td style="text-align: center;">
                            {{ isset($detail['max']) ? number_format($detail['max'], 2) : '—' }}
                        </td>

                        {{-- Min --}}
                        <td style="text-align: center;">
                            {{ isset($detail['min']) ? number_format($detail['min'], 2) : '—' }}
                        </td>

                        {{-- Mentions (Appréciation code) --}}
                        <td class="col-appr" style="text-align: center; font-weight: bold;">
                            {{ $detail['appreciation']?->code ?? '—' }}
                        </td>
                    </tr>
                @endforeach

                {{-- Ligne Total par Catégorie --}}
                @php
                    $catTotalPoints = 0;
                    $catTotalCoef = 0;
                    foreach ($catDetails as $d) {
                        $cGrade = $d['grade'];
                        $cCoef = $d['coefficient'];
                        $cAbsent = $d['is_absent'] ?? false;
                        if ($cGrade !== null && !$cAbsent) {
                            $catTotalPoints += $cGrade * $cCoef;
                            $catTotalCoef += $cCoef;
                        } elseif ($cAbsent) {
                            $catTotalCoef += $cCoef;
                        }
                    }
                    $catAvg = $catTotalCoef > 0 ? round($catTotalPoints / $catTotalCoef, 2) : null;
                @endphp
                <tr style="background: #F0F4FA; font-weight: bold; border: 1px solid #9CA3AF; font-size: 8px;">
                    <td style="text-align: right; font-weight: 900; text-transform: uppercase; padding: 4px 8px;">TOTAL SOUS-GROUPE</td>
                    @for ($i = 0; $i < $conditionalColsCount; $i++)
                        <td></td>
                    @endfor
                    <td style="text-align: center; font-weight: 900;">{{ $catTotalCoef }}</td>
                    <td style="text-align: center; font-weight: 900;">{{ number_format($catTotalPoints, 2) }}</td>
                    <td colspan="5" style="text-align: right; padding-right: 12px; font-weight: 900;">
                        MOYENNE : <span style="color: #1A3A6B; font-weight: 900;">{{ $catAvg !== null ? number_format($catAvg, 2) . '/20' : '—' }}</span>
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
                <tr style="background: #F1F5F9; color: #1F2937; font-weight: bold; font-size: 9px; border: 1px solid #9CA3AF;">
                <td colspan="2" style="font-weight: 900; text-transform: uppercase; padding: 5px 8px;">TOTAL {{ $categoryCodes->join(' + ') ?: 'GENERAL' }}</td>
                @foreach($periodAverages as $pAvg)
                    <td style="text-align: center; font-weight: 900; color: #1F2937;">
                        {{ $pAvg !== null ? number_format($pAvg, 2) : '—' }}
                    </td>
                @endforeach
                @if($type !== 'sequentiel')
                    <td style="text-align: center; font-weight: 900; color: #1F2937;">
                        {{ $average !== null ? number_format($average, 2) : '—' }}
                    </td>
                @endif
                <td style="text-align: center; font-weight: 900;">{{ $sumCoef }}</td>
                <td style="text-align: center; font-weight: 900;">{{ number_format($sumPoints, 2) }}</td>
                <td colspan="5" style="text-align: right; padding-right: 12px; font-weight: 900; color: #1F2937;">
                    @if($type === 'sequentiel')
                        MOYENNE : 
                    @else
                        MOYENNE GENERALE / AVERAGE : 
                    @endif
                    <span style="font-size: 10px; color: #1A3A6B; font-weight: 900;">{{ $average !== null ? number_format($average, 2) . '/20' : '—' }}</span>
                </td>
            </tr>
        </tfoot>
    </table>

    {{-- ── STATISTIQUES ET MOYENNES (DEUX BLOCS) ── --}}
    <div class="stats-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 6px; margin-bottom: 8px;">
        {{-- Bloc 1: Moyenne, Rang, Moy. de Classe --}}
        <div style="border: 1px solid #CBD5E1; border-radius: 4px; padding: 6px 10px; background: #F8FAFC; display: flex; flex-direction: column; gap: 5px; font-size: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                <span style="font-weight: 700; color: #374151;">Moyenne <span style="font-size: 6px; color: #9CA3AF; font-style: italic; font-weight: normal; margin-left: 2px;">/ Average</span></span>
                <span style="font-weight: 900; font-size: 10px; color: #1A3A6B;">{{ $average !== null ? number_format($average, 2) : '—' }}<span style="font-size: 7px; font-weight: normal; color: #9CA3AF;">/20</span></span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                <span style="font-weight: 700; color: #374151;">Rang <span style="font-size: 6px; color: #9CA3AF; font-style: italic; font-weight: normal; margin-left: 2px;">/ Position</span></span>
                <span style="font-weight: 900; font-size: 10px; color: #1A3A6B;">{{ $rankInfo['rank'] ?? '—' }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                <span style="font-weight: 700; color: #374151;">Moyenne de classe <span style="font-size: 6px; color: #9CA3AF; font-style: italic; font-weight: normal; margin-left: 2px;">/ Class average</span></span>
                <span style="font-weight: 900; font-size: 10px; color: #1A3A6B;">{{ isset($rankInfo['class_average']) ? number_format($rankInfo['class_average'], 2) : '—' }}<span style="font-size: 7px; font-weight: normal; color: #9CA3AF;">/20</span></span>
            </div>
        </div>

        {{-- Bloc 2: Moy. premier, Moy. dernier, Nbre moyennes, Taux réussite --}}
        <div style="border: 1px solid #CBD5E1; border-radius: 4px; padding: 6px 10px; background: #F8FAFC; display: flex; flex-direction: column; gap: 5px; font-size: 8px;">
            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                <span style="font-weight: 700; color: #374151;">Moyenne du premier <span style="font-size: 6px; color: #9CA3AF; font-style: italic; font-weight: normal; margin-left: 2px;">/ Highest average</span></span>
                <span style="font-weight: 900; font-size: 10px; color: #1A3A6B;">{{ isset($rankInfo['highest']) ? number_format($rankInfo['highest'], 2) : '—' }}<span style="font-size: 7px; font-weight: normal; color: #9CA3AF;">/20</span></span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                <span style="font-weight: 700; color: #374151;">Moyenne du dernier <span style="font-size: 6px; color: #9CA3AF; font-style: italic; font-weight: normal; margin-left: 2px;">/ Lowest average</span></span>
                <span style="font-weight: 900; font-size: 10px; color: #1A3A6B;">{{ isset($rankInfo['lowest']) ? number_format($rankInfo['lowest'], 2) : '—' }}<span style="font-size: 7px; font-weight: normal; color: #9CA3AF;">/20</span></span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                <span style="font-weight: 700; color: #374151;">Nbre de moyennes <span style="font-size: 6px; color: #9CA3AF; font-style: italic; font-weight: normal; margin-left: 2px;">/ Number of averages</span></span>
                <span style="font-weight: 900; font-size: 10px; color: #1A3A6B;">{{ $rankInfo['averages_count'] ?? '—' }}</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: baseline;">
                <span style="font-weight: 700; color: #374151;">Taux de réussite <span style="font-size: 6px; color: #9CA3AF; font-style: italic; font-weight: normal; margin-left: 2px;">/ Success rate</span></span>
                <span style="font-weight: 900; font-size: 10px; color: #1A3A6B;">{{ isset($rankInfo['success_rate']) ? number_format($rankInfo['success_rate'], 1) . '%' : '—' }}</span>
            </div>
        </div>
    </div>

    {{-- ── BLOC DE RAPPEL DES MOYENNES TRIMESTRIELLES (TRIMESTRES 2, 3 ET ANNUEL) ── --}}
    @if(isset($previousAverages) && count($previousAverages) > 0)
    <div style="margin-top: 6px; margin-bottom: 8px;">
        <div style="text-align:center; font-size:7.5px; font-weight:900; text-transform:uppercase; letter-spacing:.06em; color:#1A3A6B; margin-bottom:4px; border-top: 1px solid #CBD5E1; padding-top:4px;">
            RAPPEL DES MOYENNES TRIMESTRIELLES / <span style="font-style:italic; font-weight:600; color:#6B7280;">TRIMESTER AVERAGES RECALL</span>
        </div>
        <div style="display: flex; gap: 10px;">
            @foreach($previousAverages as $triLabel => $triVal)
            <div style="flex: 1; border: 1px solid #CBD5E1; padding: 4px 8px; border-radius: 4px; display: flex; justify-content: space-between; align-items: baseline; background: #F8FAFC;">
                <span style="font-weight: 800; font-size: 7.5px; color: #374151;">{{ $triLabel }}</span>
                <span style="font-weight: 900; font-size: 9px; color: #1A3A6B;">{{ $triVal !== null ? number_format($triVal, 2) . '/20' : '—' }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── APPRÉCIATIONS / APPRECIATIONS ── --}}
    <div style="margin-top: 6px;">
        <div style="text-align:center; font-size:7.5px; font-weight:900; text-transform:uppercase; letter-spacing:.06em; color:#1A3A6B; margin-bottom:4px; border-top: 1px solid #CBD5E1; padding-top:4px;">
            APPRÉCIATIONS / <span style="font-style:italic; font-weight:600; color:#6B7280;">APPRECIATIONS</span>
        </div>
        <div class="appr-codes-row">
            <div class="appr-code-cell">
                <div class="code">CNA</div>
                <div class="meaning">Compt. Non Acq.<br><em>Compt. Not Acq.</em></div>
            </div>
            <div class="appr-code-cell">
                <div class="code">CMA</div>
                <div class="meaning">Compt. Moy. Acq.<br><em>Compt. Avg. Acq.</em></div>
            </div>
            <div class="appr-code-cell">
                <div class="code">CA</div>
                <div class="meaning">Compétences Acquises<br><em>Competences Acquired</em></div>
            </div>
            <div class="appr-code-cell">
                <div class="code">CBA</div>
                <div class="meaning">Compt. Bien Acq.<br><em>Compt. Well Acq.</em></div>
            </div>
            <div class="appr-code-cell">
                <div class="code">CTBA</div>
                <div class="meaning">Compt. Très Bien Acq.<br><em>Compt. Very Well Acq.</em></div>
            </div>
        </div>
    </div>

    {{-- ── TRAVAIL & CONDUITE ── --}}
    <div class="bilan-bottom">
        <div>
            <div style="text-align:center; font-size:7px; font-weight:900; text-transform:uppercase; letter-spacing:.06em; color:#1A3A6B; margin-bottom:3px; border-bottom:1px solid #E5E7EB; padding-bottom:2px;">
                TRAVAIL / <span style="font-style:italic; font-weight:600; color:#6B7280;">ACADEMIC WORK</span>
            </div>
            <div class="work-badges">
                @php
                    $workItems = [
                        ['fr' => "Tableau d'honneur", 'en' => 'Honor Roll'],
                        ['fr' => 'Encouragement', 'en' => 'Encouragement'],
                        ['fr' => 'Félicitation', 'en' => 'Congratulation'],
                        ['fr' => 'Avertissement', 'en' => 'Warning'],
                        ['fr' => 'Blâme', 'en' => 'Serious Warning'],
                        ['fr' => 'Consigne', 'en' => 'Suspension'],
                        ['fr' => 'Exclusion', 'en' => 'Exclusion'],
                    ];
                    $councilCode = $council?->code ?? null;
                @endphp
                @foreach($workItems as $wi)
                <div class="work-badge" style="{{ ($councilCode && stripos($wi['fr'], $councilCode) !== false) ? 'background:#EFF6FF; border-color: #4A86C8;' : '' }}">
                    <div class="wbfr">{{ $wi['fr'] }}</div>
                    <div class="wben">{{ $wi['en'] }}</div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ── CONDUITE / CONDUCT ── --}}
        <div>
            <div style="text-align:center; font-size:7px; font-weight:900; text-transform:uppercase; letter-spacing:.06em; color:#1A3A6B; margin-bottom:3px; border-bottom:1px solid #E5E7EB; padding-bottom:2px;">
                CONDUITE / <span style="font-style:italic; font-weight:600; color:#6B7280;">CONDUCT</span>
            </div>
            <table class="conduct-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Justifiées / Exec<br><em>Justified or Exec</em></th>
                        <th>Non Just/Exécutées<br><em>Unjustified/Executed</em></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="font-weight:700; text-align:left;">Absences (h)</td>
                        <td>{{ $absences['justified'] ?? 0 }}</td>
                        <td>{{ $absences['unjustified'] ?? 0 }}</td>
                    </tr>
                </tbody>
            </table>
            @if($appreciation || $distinction)
            <div style="margin-top: 5px; padding: 3px 6px; background: #F0F4FA; border-radius: 3px; font-size: 7.5px; font-weight: 600; color: #374151;">
                @if($appreciation)
                    <span style="font-weight:900; color:#1A3A6B;">Appréciation :</span> {{ $appreciation->label_fr }}
                @endif
                @if($distinction)
                    <span style="font-weight:800; color: var(--vert); margin-left:4px;">— Distinction : {{ $distinction->label_fr }}</span>
                @endif
            </div>
            @endif
        </div>
    </div>

    {{-- ── SIGNATURES ── --}}
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; border-top: 1px dashed #D1D5DB; padding-top: 8px; margin-top: 6px;">
        <div style="text-align: center; font-size: 7.5px;">
            <div style="font-size: 7px; font-weight: 700; color: #6B7280; margin-bottom: 4px;">Date : ...............</div>
            <div style="font-size: 7.5px; font-weight: 900; text-transform: uppercase; color: #1A3A6B; margin-bottom: 18px;">
                Parent/Tuteur<br><span style="font-style:italic; font-weight:600; font-size:6.5px; color:#9CA3AF;">Parent/tutor</span>
            </div>
            {{-- <div style="border-top: 1px solid #D1D5DB; padding-top: 4px; color: #9CA3AF; font-size: 6.5px;">Signature</div> --}}
        </div>
        <div style="text-align: center; font-size: 7.5px;">
            <div style="font-size: 7px; font-weight: 700; color: #6B7280; margin-bottom: 4px;">Date : ...............</div>
            <div style="font-size: 7.5px; font-weight: 900; text-transform: uppercase; color: #1A3A6B; margin-bottom: 18px;">
                Le Principal<br><span style="font-style:italic; font-weight:600; font-size:6.5px; color:#9CA3AF;">The principal</span>
            </div>
            {{-- <div style="border-top: 1px solid #D1D5DB; padding-top: 4px; color: #9CA3AF; font-size: 6.5px;">Signature &amp; Cachet / <em>Stamp</em></div> --}}
        </div>
    </div>

    {{-- Pied de page --}}
    {{-- <div class="bulletin-footer">
        <div>
            <span>{{ $school->full_name ?? 'COPTAN' }}</span> · Bulletin généré le {{ now()->format('d/m/Y à H:i') }}
        </div>
        <div>
            @if($phones->isNotEmpty())
                Tél : {{ $phones->pluck('number')->join(' / ') }}
            @endif
        </div> --}}
    </div>
</div>
