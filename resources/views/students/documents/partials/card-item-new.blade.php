@php
    $enr = $enrollment ?? $student->printEnrollment ?? null;
    $year_enrollment = $enr?->academicYear?->label ?? $year?->label ?? '2024-2025';
    $parentPhone = $student->father_phone ?? $student->mother_phone ?? $student->guardian_phone ?? '';
@endphp
<div class="card-container">
    <!-- En-tête Bilingue avec Drapeau -->
    <div class="card-header-bilingual">
        <div class="card-header-left">
            <div class="card-header-line">RÉPUBLIQUE DU CAMEROUN</div>
            <div class="card-header-line">PAIX - TRAVAIL - PATRIE</div>
            <div class="card-header-line">MINISTÈRE DES ENSEIGNEMENTS SECONDAIRES</div>
        </div>
        <div class="card-header-center">
            @if($school->cameroon_flag_image)
                <img src="{{ asset('storage/' . $school->cameroon_flag_image) }}" alt="Drapeau" class="card-flag">
            @else
                <div class="card-flag-placeholder">🇨🇲</div>
            @endif
        </div>
        <div class="card-header-right">
            <div class="card-header-line">REPUBLIC OF CAMEROON</div>
            <div class="card-header-line">PEACE - WORK - FATHERLAND</div>
            <div class="card-header-line">MINISTRY OF SECONDARY EDUCATION</div>
        </div>
    </div>

    <!-- Titre Principal -->
    <div class="card-school-info">
        <div class="card-school-name">{{ strtoupper($school->full_name) }}</div>
        <div class="card-school-acronym">{{ $school->short_name ?? 'COPTAN' }}</div>
    </div>

    <!-- Sous-titre Bilingual -->
    <div class="card-subtitle-bilingual">
        <div class="card-subtitle-left">CARTE D'IDENTITÉ SCOLAIRE</div>
        <div class="card-subtitle-right">STUDENT IDENTITY</div>
    </div>

    <div class="card-year-info">
        Année Scolaire / Academic Year : {{ $year_enrollment }}
    </div>

    <!-- Contenu Principal -->
    <div class="card-main-content">
        <!-- Colonne Photo -->
        <div class="card-photo-section">
            <div class="card-photo">
                @if($student->photo)
                    <img src="{{ asset('storage/' . $student->photo) }}" alt="Photo">
                @else
                    <div class="card-photo-placeholder">
                        {{ strtoupper(substr($student->last_name, 0, 1) . substr($student->first_name, 0, 1)) }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Colonne Informations -->
        <div class="card-info-section">
            <div class="card-info-row">
                <span class="card-info-label">Nom /</span>
                <span class="card-info-label-en">Lastname:</span>
                <span class="card-info-value">{{ strtoupper($student->last_name) }}</span>
            </div>
            <div class="card-info-row">
                <span class="card-info-label">Prénom /</span>
                <span class="card-info-label-en">Firstname:</span>
                <span class="card-info-value">{{ strtoupper($student->first_name) }}</span>
            </div>
            <div class="card-info-row">
                <span class="card-info-label">Né le /</span>
                <span class="card-info-label-en">Born on:</span>
                <span class="card-info-value">
                    @if($student->date_of_birth)
                        {{ $student->date_of_birth->format('d M Y') }}
                    @else
                        —
                    @endif
                </span>
            </div>
            <div class="card-info-row">
                <span class="card-info-label">à /</span>
                <span class="card-info-label-en">At:</span>
                <span class="card-info-value">{{ $student->place_of_birth ?? '—' }}</span>
            </div>
            <div class="card-info-row">
                <span class="card-info-label">Classe /</span>
                <span class="card-info-label-en">Class:</span>
                <span class="card-info-value card-class-highlight">{{ $enr?->classGroup?->full_name ?? '—' }}</span>
            </div>
            @if($parentPhone)
            <div class="card-info-row">
                <span class="card-info-label">Parent:</span>
                <span class="card-info-value">{{ $parentPhone }}</span>
            </div>
            @endif
        </div>

        <!-- Colonne Cachets/Logos -->
        <div class="card-stamps-section">
            @if($school->school_logo_for_card)
                <img src="{{ asset('storage/' . $school->school_logo_for_card) }}" alt="Logo" class="card-school-logo">
            @else
                <div class="card-school-logo-placeholder">
                    {{ strtoupper(substr($school->short_name ?? 'COPTAN', 0, 1)) }}
                </div>
            @endif

            @if($school->principal_seal_image)
                <div class="card-seal">
                    <img src="{{ asset('storage/' . $school->principal_seal_image) }}" alt="Cachet">
                </div>
            @else
                <div class="card-seal-placeholder">Cachet</div>
            @endif
        </div>
    </div>

    <!-- Matricule -->
    <div class="card-matricule">
        MLE: <strong>{{ $student->matricule }}</strong>
    </div>

    <!-- Signature Chef -->
    <div class="card-signature">
        <div class="card-signature-space">
            @if($school->principal_signature_image)
                <img src="{{ asset('storage/' . $school->principal_signature_image) }}" alt="Signature">
            @endif
        </div>
        <div class="card-signature-label">La Direction</div>
    </div>

    <!-- Bande Colorée (Drapeau) -->
    <div class="card-colored-stripe">
        <div class="card-stripe-green"></div>
        <div class="card-stripe-red"></div>
        <div class="card-stripe-yellow"></div>
    </div>
</div>
