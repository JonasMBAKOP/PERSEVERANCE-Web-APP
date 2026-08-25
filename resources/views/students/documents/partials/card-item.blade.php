@php
    $enr = $enrollment ?? $student->printEnrollment ?? null;
    $year = $year ?? $enr?->academicYear;

    $mottoFr = 'Paix - Travail - Patrie';
    $mottoEn = 'Peace - Work - Fatherland';

    $ministryFr = strtoupper($school->ministry ?: 'MINISTÈRE DES ENSEIGNEMENTS SECONDAIRES');
    $ministryEn = strtoupper($school->ministry_en ?: 'MINISTRY OF SECONDARY EDUCATION');

    $splitMinistry = function (string $text): array {
        $words = preg_split('/\s+/', trim($text));
        $mid   = (int) ceil(count($words) / 2);

        return [
            implode(' ', array_slice($words, 0, $mid)),
            implode(' ', array_slice($words, $mid)),
        ];
    };

    [$ministryFr1, $ministryFr2] = $splitMinistry($ministryFr);
    [$ministryEn1, $ministryEn2] = $splitMinistry($ministryEn);

    $primaryPhone = isset($phones)
        ? ($phones->firstWhere('is_primary', true) ?? $phones->first())
        : null;
@endphp

<!-- GUILLOCHE PATTERN BACKGROUND -->
<div class="id-card">
    <svg class="id-card__guilloche" viewBox="0 0 380 240" preserveAspectRatio="none">
        <defs>
            <pattern id="guilloche" x="0" y="0" width="20" height="20" patternUnits="userSpaceOnUse">
                <circle cx="10" cy="10" r="0.5" fill="#1a3a6b" opacity="0.03"/>
            </pattern>
        </defs>
        <rect width="380" height="240" fill="url(#guilloche)"/>
    </svg>

    <div class="id-card__content">
        <!-- HEADER BILINGUE -->
        <div class="id-card__header">
            <div class="id-card__header-section id-card__header-fr">
                <div class="id-card__header-text">RÉPUBLIQUE DU CAMEROUN</div>
                <div class="id-card__header-motto">{{ $mottoFr }}</div>
                <div class="id-card__header-text">{{ $ministryFr1 }}</div>
                @if($ministryFr2)
                <div class="id-card__header-text">{{ $ministryFr2 }}</div>
                @endif
            </div>

            <!-- FLAG SVG -->
            <div class="id-card__flag">
                <svg viewBox="0 0 100 60" width="24" height="15">
                    <rect width="33.33" height="60" fill="#007A5E"/>
                    <rect x="33.33" width="33.33" height="60" fill="#CE1126"/>
                    <g transform="translate(50, 30)">
                        <path d="M 0 -8 L 2.4 -2.4 L 8 -1.2 L 3.2 3.2 L 4.8 9.6 L 0 5.6 L -4.8 9.6 L -3.2 3.2 L -8 -1.2 L -2.4 -2.4 Z" fill="#FCD116"/>
                    </g>
                    <rect x="66.66" width="33.34" height="60" fill="#FCD116"/>
                </svg>
            </div>

            <div class="id-card__header-section id-card__header-en">
                <div class="id-card__header-text">REPUBLIC OF CAMEROON</div>
                <div class="id-card__header-motto">{{ $mottoEn }}</div>
                <div class="id-card__header-text">{{ $ministryEn1 }}</div>
                @if($ministryEn2)
                <div class="id-card__header-text">{{ $ministryEn2 }}</div>
                @endif
            </div>
        </div>

        <!-- ÉCOLE INFO & TITRE -->
        <div class="id-card__school-header">
            <div class="id-card__school-info">
                <div class="id-card__school-name">{{ strtoupper($school->full_name) }}</div>
                {{-- @if($school->short_name)
                    <div class="id-card__school-acronym">{{ strtoupper($school->short_name) }}</div>
                @endif --}}
            </div>
            <div class="id-card__title-section">
                <div class="id-card__title">CARTE D'IDENTITÉ SCOLAIRE / STUDENT IDENTITY CARD</div>
                @if($year)
                    <div class="id-card__subtitle">Année Scolaire / Academic Year : {{ $year->label }}</div>
                @endif
            </div>
        </div>

        <!-- CORPS PRINCIPAL -->
        <div class="id-card__body">
            <!-- PHOTO & MATRICULE -->
            <div class="id-card__photo-section">
                <div class="id-card__photo-box">
                    @if($student->photo)
                        <img src="{{ asset('storage/' . $student->photo) }}" alt="" class="id-card__photo">
                    @else
                        <div class="id-card__photo-placeholder">
                            {{ strtoupper(substr($student->last_name, 0, 1) . substr($student->first_name, 0, 1)) }}
                        </div>
                    @endif
                </div>
                <div class="id-card__matricule">MATRICULE: {{ $student->matricule }}</div>
            </div>

            <!-- INFO TABLE -->
            <div class="id-card__info-section">
                <table class="id-card__info-table">
                    <tr class="id-card__info-row">
                        <td class="id-card__info-label">
                            <span class="id-card__label-fr">Nom</span>
                            <span class="id-card__label-en">Lastname</span>
                        </td>
                        <td class="id-card__info-value">{{ strtoupper($student->last_name) }}</td>
                    </tr>
                    <tr class="id-card__info-row">
                        <td class="id-card__info-label">
                            <span class="id-card__label-fr">Prénom</span>
                            <span class="id-card__label-en">Firstname</span>
                        </td>
                        <td class="id-card__info-value">{{ $student->first_name }}</td>
                    </tr>
                    <tr class="id-card__info-row">
                        <td class="id-card__info-label">
                            <span class="id-card__label-fr">Né le</span>
                            <span class="id-card__label-en">Born on</span>
                        </td>
                        <td class="id-card__info-value">{{ $student->date_of_birth?->format('d M Y') ?? '—' }}</td>
                    </tr>
                    <tr class="id-card__info-row">
                        <td class="id-card__info-label">
                            <span class="id-card__label-fr">À</span>
                            <span class="id-card__label-en">At</span>
                        </td>
                        <td class="id-card__info-value">{{ $student->place_of_birth ?? '—' }}</td>
                    </tr>
                    <tr class="id-card__info-row">
                        <td class="id-card__info-label">
                            <span class="id-card__label-fr">Classe</span>
                            <span class="id-card__label-en">Class</span>
                        </td>
                        <td class="id-card__info-value id-card__info-value--highlight">{{ $enr?->classGroup?->full_name ?? '—' }}</td>
                    </tr>
                    <tr class="id-card__info-row">
                        <td class="id-card__info-label">
                            <span class="id-card__label-fr">Parent</span>
                            <span class="id-card__label-en">Parent</span>
                        </td>
                        <td class="id-card__info-value">{{ $student->father_phone ?? $student->mother_phone ?? $student->guardian_phone ?? '—' }}</td>
                    </tr>
                </table>

                <!-- LOGO & COORDONNÉES HAUT DROIT -->
                <div class="id-card__top-logo">
                    @if($school->logo)
                        <img src="{{ asset('storage/' . $school->logo) }}" alt="" class="id-card__top-logo-image" />
                    @endif
                    @if($school->postal_box)
                        <div class="id-card__top-logo-meta">B.P. {{ $school->postal_box }}</div>
                    @endif
                    @if($primaryPhone)
                        <div class="id-card__top-logo-meta">{{ $primaryPhone->number }}</div>
                    @endif
                </div>

                <!-- CACHET/SIGNATURE BAS DROIT -->
                <div class="id-card__seal-area">
                    @if($school->signature_seal)
                        <img src="{{ asset('storage/' . $school->signature_seal) }}" alt="Cachet" class="id-card__seal-image" />
                    @else
                        <!-- CACHET SVG PAR DÉFAUT -->
                        <svg class="id-card__seal-svg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <path id="sealCurve" d="M 20 50 A 30 30 0 1 1 80 50" fill="none"/>
                            </defs>
                            <circle cx="50" cy="50" r="45" fill="none" stroke="#1A3A6B" stroke-width="1.5" stroke-dasharray="2,1" opacity="0.4"/>
                            <circle cx="50" cy="50" r="38" fill="none" stroke="#1A3A6B" stroke-width="1" opacity="0.4"/>
                            <text font-size="6" font-weight="bold" fill="#1A3A6B" opacity="0.4">
                                <textPath href="#sealCurve" startOffset="50%" text-anchor="middle">LE CHEF D'ETABLISSEMENT</textPath>
                            </text>
                            <text font-size="5" font-weight="bold" fill="#1A3A6B" text-anchor="middle" x="50" y="55" opacity="0.4">COPTAN</text>
                            <text font-size="4" fill="#1A3A6B" text-anchor="middle" x="50" y="65" opacity="0.4">Bafoussam</text>
                        </svg>
                    @endif
                    <div class="id-card__seal-label">La Direction</div>
                </div>
            </div>
        </div>

        <!-- FOOTER STRIPE (DRAPEAU) -->
        <div class="id-card__footer-stripe" aria-hidden="true">
            <svg viewBox="0 0 950 25" preserveAspectRatio="none">
                <rect width="316.67" height="25" fill="#007A5E"/>
                <rect x="316.67" width="316.66" height="25" fill="#CE1126"/>
                <rect x="633.33" width="316.67" height="25" fill="#FCD116"/>
                <g transform="translate(475, 12.5) scale(0.75)">
                    <path d="M 0 -8 L 2.4 -2.4 L 8 -1.2 L 3.2 3.2 L 4.8 9.6 L 0 5.6 L -4.8 9.6 L -3.2 3.2 L -8 -1.2 L -2.4 -2.4 Z" fill="#FCD116"/>
                </g>
            </svg>
        </div>
    </div>
</div>
