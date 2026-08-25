@php
    $classGroup = $enrollment?->classGroup;
    $academicYear = $enrollment?->academicYear ?? $year ?? null;
    $birthDate = $student->date_of_birth?->format('d-m-Y') ?? '—';
    $birthPlace = $student->place_of_birth ?: '—';
    $fatherName = $student->father_name ?: ($student->guardian_name ?: '—');
    $motherName = $student->mother_name ?: '—';
    $registrationNumber = $student->matricule ?: '—';
    $className = $classGroup?->full_name ?? '—';
    $speciality = $classGroup?->series ?: '—';
    $yearLabel = $academicYear?->label ?? '—';
    $city = $school->city ?: '………………';
@endphp

<section class="cert-attestation">
    <div class="cert-line">
        <div>
            <span class="cert-label">Nous soussignés,</span>
            <span class="cert-value cert-value--school">{{ strtoupper($school->full_name) }}</span>
        </div>
        <div class="cert-translation">We the undersigned,</div>
    </div>

    <div class="cert-line">
        <div>
            <span class="cert-label">Certifions que l'élève :</span>
            <span class="cert-value">{{ strtoupper($student->full_name) }}</span>
        </div>
        <div class="cert-translation">Certify that student :</div>
    </div>

    <div class="cert-grid cert-grid--birth">
        <div class="cert-line">
            <div>
                <span class="cert-label">Né(e) le :</span>
                <span class="cert-value">{{ $birthDate }}</span>
            </div>
            <div class="cert-translation">Born on the :</div>
        </div>
        <div class="cert-line">
            <div>
                <span class="cert-label">à :</span>
                <span class="cert-value">{{ strtoupper($birthPlace) }}</span>
            </div>
            <div class="cert-translation">At :</div>
        </div>
    </div>

    <div class="cert-line">
        <div>
            <span class="cert-label">Fils ou Fille de :</span>
            <span class="cert-value">{{ strtoupper($fatherName) }}</span>
        </div>
        <div class="cert-translation">Son or daughter of :</div>
    </div>

    <div class="cert-line">
        <div>
            <span class="cert-label">Et de :</span>
            <span class="cert-value">{{ strtoupper($motherName) }}</span>
        </div>
        <div class="cert-translation">And of :</div>
    </div>

    {{-- <div class="cert-registration"> --}}
        <div class="cert-line">
            <div>
                <span class="cert-label">Est régulièrement inscrit(e) dans notre établissement sous le numéro matricule :</span>
                <span class="cert-value">{{ $registrationNumber }}</span>
            </div>
            <div class="cert-translation">Is registered in our establishment under the registration number :</div>
        </div>
        {{-- <div class="cert-registration__number">{{ $registrationNumber }}</div> --}}
    {{-- </div> --}}

    <div class="cert-grid cert-grid--class">
        <div class="cert-line">
            <div>
                <span class="cert-label">En classe de :</span>
                <span class="cert-value">{{ strtoupper($className) }}</span>
            </div>
            <div class="cert-translation">In class :</div>
        </div>
        <div class="cert-line">
            <div>
                <span class="cert-label">Spécialité :</span>
                <span class="cert-value">{{ strtoupper($speciality) }}</span>
            </div>
            <div class="cert-translation">Speciality :</div>
        </div>
    </div>

    <div class="cert-line">
        <div>
            <span class="cert-label">Pour le compte de l'année académique :</span>
            <span class="cert-value">{{ $yearLabel }}</span>
        </div>
        <div class="cert-translation">For the academic year :</div>
    </div>

    <div class="cert-line cert-line--statement">
        <div>En foi de quoi le présent certificat est délivré pour servir et valoir ce que de droit.</div>
        <div class="cert-translation">In witness whereof, this certificate is issued to serve the purpose for which it is used.</div>
    </div>

    <div class="cert-signature">
        <div class="cert-signature__date">
            <div>
                <span>Fait à {{ $city }}, le</span>
                <strong class="cert-generated-date">{{ now()->format('d/m/Y') }}</strong>
            </div>
            <div class="cert-translation">Done in {{ $city }}, on</div>
        </div>
        <div>
            <div class="cert-signature__principal">
                <div>La Direction</div>
                <div class="cert-translation">The Direction</div>
            </div>
            @if($school->signature_seal)
                <div class="cert-signature__seal">
                    <img src="{{ asset('storage/' . $school->signature_seal) }}" alt="Cachet du Principal">
                </div>
            @endif
        </div>
    </div>

    <div class="cert-note">
        <div>NB: Il n'est délivré qu'un seul certificat de scolarité pour une année académique.</div>
        <div class="cert-translation">NB: Only one certificate of school attendance shall be issued in a year.</div>
    </div>
</section>
