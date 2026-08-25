@php
    $schoolNameFr = strtoupper($school->full_name);
    $schoolNameEn = strtoupper($school->full_name_en ?: 'NTANKEU POLYVALENT COLLEGE');
    $ministryFr = strtoupper($school->ministry ?: 'MINISTERE DES ENSEIGNEMENTS SECONDAIRES');
    $ministryEn = strtoupper($school->ministry_en ?: 'MINISTRY OF SECONDARY EDUCATION');
    $phoneLine = $phones->isNotEmpty() ? $phones->pluck('number')->join(' / ') : null;
    $agreementLines = isset($agreements) && $agreements->isNotEmpty()
        ? $agreements->map(fn ($a) => 'N° ' . $a->number)
        : collect();
    $logoSrc = null;
    if ($school->logo) {
        $storagePath = public_path('storage/' . $school->logo);
        if (file_exists($storagePath)) {
            $logoSrc = $forPdf ?? false ? $storagePath : asset('storage/' . $school->logo);
        }
    }
    if (!$logoSrc && file_exists(public_path('images/logo.jpg'))) {
        $logoSrc = $forPdf ?? false ? public_path('images/logo.jpg') : asset('images/logo.jpg');
    }

    $categoryLetters = ['A', 'B', 'C', 'D', 'E'];
    $catIdx = 0;
@endphp

<div class="livret-page">

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
                <div class="cert-official-header__meta" style="font-weight: 800; color: #111827; margin-top: 1px;">Année Scolaire : {{ $academicYear->label ?? '—' }}</div>
            </div>

            <div class="cert-official-header__logo">
                @if($logoSrc)
                    <img src="{{ $logoSrc }}" alt="Logo {{ $school->short_name }}">
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
                <div class="cert-official-header__meta" style="font-weight: 800; color: #111827; margin-top: 1px;">School Year: {{ $academicYear->label ?? '—' }}</div>
            </div>
        </div>

        {{-- @if($agreementLines->isNotEmpty())
            <div class="cert-official-header__agreements">
                @foreach($agreementLines as $agreementLine)
                    <div>{{ $agreementLine }}</div>
                @endforeach
            </div>
        @endif --}}

        <div style="text-align:center;">
            <div class="cert-official-header__title">
                LIVRET SCOLAIRE
                <div style="font-size: 8px; font-weight: 600; font-style: italic; text-transform: uppercase; color: #6B7280; margin-top: 1px;">School Record Book</div>
            </div>
        </div>
    </header>

    {{-- ── INFORMATIONS ÉLÈVE ── --}}
    <div class="student-info-block">
        {{-- Photo --}}
        <div style="width: 62px; padding-right: 6px; text-align: center;">
            @if($studentPhoto)
                <img src="{{ $studentPhoto }}" alt="Photo" style="width: 56px; height: 70px; object-fit: cover; border: 1px solid #CBD5E1; border-radius: 3px;">
            @else
                <div style="width: 56px; height: 70px; border: 1px dashed #CBD5E1; color: #9CA3AF; display: flex; flex-direction: column; align-items: center; justify-content: center; font-size: 6px; text-align: center; background: white; border-radius: 3px;">
                    <svg width="20" height="20" fill="none" stroke="#CBD5E1" stroke-width="1.5" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                    <span>Photo</span>
                </div>
            @endif
        </div>
        {{-- Info --}}
        <div style="font-size: 7.5px;">
            {{-- Ligne 1 --}}
            <div style="display: flex; gap: 20px; margin-bottom: 2px;">
                <div style="display: inline-flex; align-items: center; flex: 2;">
                    <div style="margin-right: 3px; line-height: 1.1;">
                        <span style="font-weight: 800; font-size: 7px;">Nom et Prénom(s)</span><br>
                        <span style="font-size: 5.5px; font-style: italic; color: #6B7280;">Student name</span>
                    </div>
                    <span style="font-weight: 800; font-size: 8.5px;">: {{ strtoupper($enrollment->student->last_name . ' ' . $enrollment->student->first_name) }}</span>
                </div>
                <div style="display: inline-flex; align-items: center; flex: 1;">
                    <div style="margin-right: 3px; line-height: 1.1;">
                        <span style="font-weight: 800; font-size: 7px;">Matricule</span><br>
                        <span style="font-size: 5.5px; font-style: italic; color: #6B7280;">Mat No</span>
                    </div>
                    <span style="font-weight: 800; font-size: 8.5px;">: {{ $enrollment->student->matricule ?? '—' }}</span>
                </div>
            </div>
            {{-- Ligne 2 --}}
            <div style="display: flex; gap: 20px; margin-bottom: 2px;">
                <div style="display: inline-flex; align-items: center; flex: 2;">
                    <div style="margin-right: 3px; line-height: 1.1;">
                        <span style="font-weight: 800; font-size: 7px;">Né(e) le / À</span><br>
                        <span style="font-size: 5.5px; font-style: italic; color: #6B7280;">Born on / At</span>
                    </div>
                    <span style="font-weight: 800; font-size: 8.5px;">: {{ $enrollment->student->date_of_birth?->format('d/m/Y') ?? '—' }} à {{ strtoupper($enrollment->student->place_of_birth ?? '—') }}</span>
                </div>
                <div style="display: inline-flex; align-items: center; flex: 1;">
                    <div style="margin-right: 3px; line-height: 1.1;">
                        <span style="font-weight: 800; font-size: 7px;">Sexe</span><br>
                        <span style="font-size: 5.5px; font-style: italic; color: #6B7280;">Sex</span>
                    </div>
                    <span style="font-weight: 800; font-size: 8.5px;">: {{ $enrollment->student->gender ?? '—' }}</span>
                </div>
            </div>
            {{-- Ligne 3 --}}
            <div style="display: flex; gap: 20px; margin-bottom: 2px;">
                <div style="display: inline-flex; align-items: center;">
                    <div style="margin-right: 3px; line-height: 1.1;">
                        <span style="font-weight: 800; font-size: 7px;">Classe</span><br>
                        <span style="font-size: 5.5px; font-style: italic; color: #6B7280;">Class</span>
                    </div>
                    <span style="font-weight: 800; font-size: 8.5px;">: {{ $classGroup->full_name }}</span>
                </div>
                <div style="display: inline-flex; align-items: center;">
                    <div style="margin-right: 3px; line-height: 1.1;">
                        <span style="font-weight: 800; font-size: 7px;">Effectif</span><br>
                        <span style="font-size: 5.5px; font-style: italic; color: #6B7280;">Class size</span>
                    </div>
                    <span style="font-weight: 800; font-size: 8.5px;">: {{ $classSize }}</span>
                </div>
                <div style="display: inline-flex; align-items: center;">
                    <div style="margin-right: 3px; line-height: 1.1;">
                        <span style="font-weight: 800; font-size: 7px;">Redoublant(e)</span><br>
                        <span style="font-size: 5.5px; font-style: italic; color: #6B7280;">Repeater</span>
                    </div>
                    <span style="font-weight: 800; font-size: 8.5px;">: {{ $isRepeating ? 'Oui' : 'Non' }}</span>
                </div>
            </div>
            {{-- Ligne 4 --}}
            <div style="display: flex; gap: 20px; margin-bottom: 2px;">
                <div style="display: inline-flex; align-items: center; flex: 1;">
                    <div style="margin-right: 3px; line-height: 1.1;">
                        <span style="font-weight: 800; font-size: 7px;">Prof. Titulaire</span><br>
                        <span style="font-size: 5.5px; font-style: italic; color: #6B7280;">Class Master/Mistress</span>
                    </div>
                    <span style="font-weight: 800; font-size: 8.5px;">: {{ $classGroup->titularStaff?->full_name ?? '—' }}</span>
                </div>
                <div style="display: inline-flex; align-items: center; flex: 1;">
                    <div style="margin-right: 3px; line-height: 1.1;">
                        <span style="font-weight: 800; font-size: 7px;">Chef d'Établissement</span><br>
                        <span style="font-size: 5.5px; font-style: italic; color: #6B7280;">Principal</span>
                    </div>
                    <span style="font-weight: 800; font-size: 8.5px;">: {{ $principalName }}</span>
                </div>
            </div>
            {{-- Ligne 5 --}}
            <div style="display: inline-flex; align-items: center; margin-bottom: 2px;">
                <div style="margin-right: 3px; line-height: 1.1;">
                    <span style="font-weight: 800; font-size: 7px;">Contact Parent</span><br>
                    <span style="font-size: 5.5px; font-style: italic; color: #6B7280;">Parent/tutor contact</span>
                </div>
                <span style="font-weight: 800; font-size: 8.5px;">: {{ $parentContacts }}</span>
            </div>
        </div>
    </div>

    {{-- ── TABLEAU DES NOTES ── --}}
    <table class="livret-table">
        <thead>
            <tr>
                <th rowspan="2" style="text-align: left; min-width: 80px;">MATIERES / SUBJECTS</th>
                @foreach($trimesters as $trimester)
                    @php $seqCount = $trimester->sequences->count(); @endphp
                    <th colspan="{{ $seqCount }}" class="th-trim">TRIMESTRE {{ $trimester->number }}</th>
                    <th rowspan="1" class="th-trim">MOY.<br>TRIM {{ $trimester->number }}</th>
                @endforeach
                <th rowspan="2" class="th-annual">ANNUELLE<br><span style="font-size: 5.5px; font-weight: 600;">Annual</span></th>
            </tr>
            <tr>
                @foreach($trimesters as $trimester)
                    @foreach($trimester->sequences as $seq)
                        <th style="font-size: 6px;">{{ strtoupper($seq->label) }}</th>
                    @endforeach
                    <th style="background: rgba(74,134,200,0.12); color: #1A3A6B; font-size: 6px;"></th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($groupedDetails as $catRows)
                @foreach($catRows as $row)
                    @php
                        $annualAvg = $row['annual_avg'];
                        $annualClass = $annualAvg === null ? '' : (
                            $annualAvg >= 12 ? 'grade-good' : ($annualAvg >= 10 ? 'grade-avg' : 'grade-bad')
                        );
                    @endphp
                    <tr>
                        <td class="subject-cell">
                            <span class="subject-name">{{ $row['subject']->name_fr }}</span>
                            <span class="subject-coef">Coef. {{ $row['coefficient'] }}</span>
                        </td>
                        @foreach($trimesters as $trimester)
                            @foreach($trimester->sequences as $seq)
                                @php
                                    $val = $row['col_values'][$seq->id] ?? null;
                                    $cellClass = $val === 'ABS' ? 'grade-absent' : (
                                        is_numeric($val) ? ($val >= 12 ? 'grade-good' : ($val >= 10 ? 'grade-avg' : 'grade-bad')) : ''
                                    );
                                @endphp
                                <td class="{{ $cellClass }}">
                                    @if($val === 'ABS') ABS
                                    @elseif(is_numeric($val)) {{ number_format($val, 2) }}
                                    @else —
                                    @endif
                                </td>
                            @endforeach
                            @php
                                $trimVal = $row['col_values']['trim_' . $trimester->id] ?? null;
                                $trimClass = is_numeric($trimVal) ? ($trimVal >= 12 ? 'grade-good' : ($trimVal >= 10 ? 'grade-avg' : 'grade-bad')) : '';
                            @endphp
                            <td class="{{ $trimClass }}" style="background: rgba(74,134,200,0.08); font-weight: 700;">
                                {{ is_numeric($trimVal) ? number_format($trimVal, 2) : '—' }}
                            </td>
                        @endforeach
                        <td class="{{ $annualClass }}" style="font-weight: 800; background: rgba(74,134,200,0.06);">
                            {{ $annualAvg !== null ? number_format($annualAvg, 2) : '—' }}
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr class="footer-row">
                <td style="text-align: right; font-weight: 900; text-transform: uppercase; padding: 2px 6px; font-size: 6.5px;">
                    MOYENNE GÉNÉRALE / Average
                </td>
                @foreach($trimesters as $trimester)
                    @foreach($trimester->sequences as $seq)
                        @php $fVal = $footerValues[$seq->id] ?? null; @endphp
                        <td style="font-weight: 900; color: #1A3A6B;">
                            {{ $fVal !== null ? number_format($fVal, 2) : '—' }}
                        </td>
                    @endforeach
                    @php $fTrim = $footerValues['trim_' . $trimester->id] ?? null; @endphp
                    <td style="font-weight: 900; color: #1A3A6B; background: rgba(74,134,200,0.12);">
                        {{ $fTrim !== null ? number_format($fTrim, 2) : '—' }}
                    </td>
                @endforeach
                <td style="font-weight: 900; color: #1A3A6B; background: rgba(74,134,200,0.16); font-size: 8px;">
                    {{ $yearAverage !== null ? number_format($yearAverage, 2) : '—' }}
                </td>
            </tr>
            <tr class="footer-row">
                <td style="text-align: right; font-weight: 900; text-transform: uppercase; padding: 2px 6px; font-size: 6.5px;">
                    RANG / Rank
                </td>
                @foreach($trimesters as $trimester)
                    @foreach($trimester->sequences as $seq)
                        @php $rankValue = $footerRanks[$seq->id] ?? null; @endphp
                        <td style="font-weight: 900; color: #1A3A6B;">
                            {{ $rankValue !== null ? $rankValue : '—' }}
                        </td>
                    @endforeach
                    @php $trimRank = $footerRanks['trim_' . $trimester->id] ?? null; @endphp
                    <td style="font-weight: 900; color: #1A3A6B; background: rgba(74,134,200,0.12);">
                        {{ $trimRank !== null ? $trimRank : '—' }}
                    </td>
                @endforeach
                <td style="font-weight: 900; color: #1A3A6B;">{{ $yearRank ?? '—' }}</td>
            </tr>
            <tr class="footer-row">
                <td style="text-align: right; font-weight: 900; text-transform: uppercase; padding: 2px 6px; font-size: 6.5px;">
                    MENTION / Mention
                </td>
                @foreach($trimesters as $trimester)
                    @foreach($trimester->sequences as $seq)
                        @php $mention = $footerMentions[$seq->id] ?? null; @endphp
                        <td style="font-weight: 900; color: #1A3A6B;">
                            {{ $mention?->code ?? '—' }}
                        </td>
                    @endforeach
                    @php $trimMention = $footerMentions['trim_' . $trimester->id] ?? null; @endphp
                    <td style="font-weight: 900; color: #1A3A6B; background: rgba(74,134,200,0.12);">
                        {{ $trimMention?->code ?? '—' }}
                    </td>
                @endforeach
                <td style="font-weight: 900; color: #1A3A6B;">{{ $yearMention?->code ?? '—' }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- ── BAS DE PAGE : STATISTIQUES + APPRÉCIATIONS + CONDUITE + SIGNATURES ── --}}
    <div class="livret-bottom">
        {{-- Bloc 1 : Statistiques --}}
        <div class="stat-box">
            <div class="stat-box-title">Résultats / Results</div>
            <div class="stat-row">
                <span class="stat-label">Moyenne annuelle <em>Annual average</em></span>
                <span class="stat-value">{{ $yearAverage !== null ? number_format($yearAverage, 2) : '—' }}<span style="font-size: 6px; font-weight: 400; color: #9CA3AF;">/20</span></span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Rang <em>Position</em></span>
                <span class="stat-value">{{ $yearRank ?? '—' }}</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Moy. de classe <em>Class average</em></span>
                <span class="stat-value">{{ $classYearAvg !== null ? number_format($classYearAvg, 2) : '—' }}<span style="font-size: 6px; font-weight: 400; color: #9CA3AF;">/20</span></span>
            </div>
            {{-- Rappel des moyennes trimestrielles --}}
            @foreach($trimesters as $tri)
                @php $triAvg = $footerValues['trim_' . $tri->id] ?? null; @endphp
                <div class="stat-row">
                    <span class="stat-label">Moy. Trim {{ $tri->number }} <em>Trimester {{ $tri->number }}</em></span>
                    <span class="stat-value">{{ $triAvg !== null ? number_format($triAvg, 2) : '—' }}<span style="font-size: 6px; font-weight: 400; color: #9CA3AF;">/20</span></span>
                </div>
            @endforeach
        </div>

        {{-- Bloc 2 : Absences --}}
        <div class="stat-box">
            <div class="stat-box-title">Absences (h) / Absences (hrs)</div>
            <div class="stat-row">
                <span class="stat-label">Justifiées <em>Justified</em></span>
                <span class="stat-value">{{ $absences['justified'] ?? 0 }}</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Non justifiées <em>Unjustified</em></span>
                <span class="stat-value">{{ $absences['unjustified'] ?? 0 }}</span>
            </div>
            <div class="stat-row">
                <span class="stat-label">Total</span>
                <span class="stat-value">{{ $absences['total'] ?? 0 }}</span>
            </div>
        </div>

        {{-- Bloc 3 : Travail et conduite --}}
        <div class="stat-box" style="display: flex; flex-direction: column; gap: 6px;">
            <div class="stat-box-title">Travail / Academic Work</div>
            <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 4px;">
                <div style="border: 1px solid #CBD5E1; border-radius: 4px; padding: 4px; text-align: center; background: #F8FAFC; font-size: 6.5px; color: #1A3A6B;">
                    Tableau d'honneur<br><em style="font-size: 5.5px; color: #6B7280; font-style: italic;">Honor Roll</em>
                </div>
                <div style="border: 1px solid #CBD5E1; border-radius: 4px; padding: 4px; text-align: center; background: #F8FAFC; font-size: 6.5px; color: #1A3A6B;">
                    Encouragement<br><em style="font-size: 5.5px; color: #6B7280; font-style: italic;">Encouragement</em>
                </div>
                <div style="border: 1px solid #CBD5E1; border-radius: 4px; padding: 4px; text-align: center; background: #F8FAFC; font-size: 6.5px; color: #1A3A6B;">
                    Félicitation<br><em style="font-size: 5.5px; color: #6B7280; font-style: italic;">Congratulation</em>
                </div>
                <div style="border: 1px solid #CBD5E1; border-radius: 4px; padding: 4px; text-align: center; background: #F8FAFC; font-size: 6.5px; color: #1A3A6B;">
                    Avertissement<br><em style="font-size: 5.5px; color: #6B7280; font-style: italic;">Warning</em>
                </div>
                <div style="border: 1px solid #CBD5E1; border-radius: 4px; padding: 4px; text-align: center; background: #F8FAFC; font-size: 6.5px; color: #1A3A6B;">
                    Blâme<br><em style="font-size: 5.5px; color: #6B7280; font-style: italic;">Serious Warning</em>
                </div>
                <div style="border: 1px solid #CBD5E1; border-radius: 4px; padding: 4px; text-align: center; background: #F8FAFC; font-size: 6.5px; color: #1A3A6B;">
                    Consigne / Exclusion<br><em style="font-size: 5.5px; color: #6B7280; font-style: italic;">Suspension / Exclusion</em>
                </div>
            </div>
        </div>
    </div>

    <div style="margin-top: 50px; padding-top: 10px; display: flex; justify-content: flex-end;">
        <div style="width: 50%; text-align: center;">
            <div style="font-size: 10px; color: #6B7280; margin-bottom: 8px;">
                Fait à {{ $school->city ?? 'Douala' }} le : _________________
            </div>
            <div style="font-size: 10px; font-weight: 900; color: #1A3A6B; text-transform: uppercase; margin-top: 20px">
                La Direction
            </div>
            @if($school->signature_seal)
                <img
                    src="{{ $forPdf ? public_path('storage/' . $school->signature_seal) : asset('storage/' . $school->signature_seal) }}"
                    alt="Cachet du Principal"
                    class="livret-signature-seal"
                >
            @endif
        </div>
    </div>

    {{-- Pied de page --}}
    {{-- <div class="livret-footer">
        <div>
            <span>{{ $school->full_name ?? 'COPTAN' }}</span> · Livret généré le {{ now()->format('d/m/Y à H:i') }}
        </div>
        <div>
            @if($phones->isNotEmpty())
                Tél : {{ $phones->pluck('number')->join(' / ') }}
            @endif
        </div>
    </div> --}}

</div>
