<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Liste du personnel</title>
@include('students.documents.partials.base-styles')
<style>
@page { size: A4 portrait; margin: 0 auto; }
body { margin: 0; padding: 0; color: #111827; }
.staff-list-page { max-width: 200mm; margin: 0 auto; padding: 2mm 1mm; }
.staff-list-page .cert-official-header { margin-bottom: 9px; }
.staff-list-title { background: #E5E7EB; color: #000; border: 1px solid #4B5563; padding: 8px 10px; margin-bottom: 12px; text-align: center; font-family: Georgia, 'Times New Roman', serif; font-size: 21px; font-weight: 900; text-transform: uppercase; }
.staff-list-subtitle { margin-top: 3px; font-family: Arial, Helvetica, sans-serif; font-size: 11px; font-weight: 700; text-transform: none; }
.staff-table { width: 100%; border-collapse: collapse; font-size: 9.5px; }
.staff-table th, .staff-table td { border: 1px solid #9CA3AF; padding: 6px 7px; vertical-align: middle; color: #000; }
.staff-table th { background: #E5E7EB; text-transform: uppercase; font-size: 12px; font-weight: 900; text-align: center; }
.staff-table td { font-size: 10px; font-weight: 600; text-transform: uppercase; }
.staff-table td.num { text-align: center; width: 8mm; }
.staff-table .center { text-align: center; }
@media print { body { background: #fff !important; } .no-print { display: none !important; } }
</style>
</head>
<body>
@include('students.documents.partials.print-toolbar')
<div class="page staff-list-page">
    @include('students.documents.partials.certificate-official-header', ['showCertificateTitle' => false])
    <div class="staff-list-title">
        <div>Liste du personnel</div>
        <div class="staff-list-subtitle">Année scolaire {{ $academicYear?->label ?? '—' }} — {{ $staff->count() }} membre(s)</div>
    </div>
    <table class="staff-table">
        <thead><tr>
            <th>#</th>
            <th>Noms et Prénoms</th>
            <th>Date de Naissance</th>
            <th>Genre</th>
            <th>Poste</th>
            <th>Grade</th>
            <th>Numéro</th>
            {{-- <th>Type de Contrat</th> --}}
        </tr></thead>
        <tbody>
        @forelse($staff as $index => $member)
            @php
                $positions = $member->positions->sortByDesc('is_primary')->map->position_label->filter()->implode(' / ');
                $gender = in_array(strtolower((string) $member->gender), ['f', 'female', 'femme'], true) ? 'Féminin' : (in_array(strtolower((string) $member->gender), ['m', 'male', 'homme'], true) ? 'Masculin' : '—');
            @endphp
            <tr>
                <td class="num">{{ $index + 1 }}</td>
                <td>{{ $member->full_name }}</td>
                <td class="center">{{ $member->date_of_birth?->format('d/m/Y') ?? '—' }}</td>
                <td class="center">{{ $gender }}</td>
                <td>{{ $positions ?: '—' }}</td>
                <td>{{ $member->diploma_label ?: '—' }}</td>
                <td>{{ $member->phone ?: '—' }}</td>
                {{-- <td>{{ $member->contract_label ?: '—' }}</td> --}}
            </tr>
        @empty
            <tr><td colspan="8" class="center">Aucun personnel ne correspond aux critères sélectionnés.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
</body>
</html>