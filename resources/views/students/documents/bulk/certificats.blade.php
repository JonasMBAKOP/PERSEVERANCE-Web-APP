<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Certificats de scolarité — impression groupée</title>
@include('students.documents.partials.base-styles')
<style>
@page { size: A4 portrait; margin: 2mm 3mm; }
@media print {
    .cert-page { page-break-after: always; }
    .cert-page:last-child { page-break-after: auto; }
}
.cert-page { max-width: none; padding: 1mm 2mm; margin-bottom: 8px; }
</style>
</head>
<body>
@include('students.documents.partials.print-toolbar')

@foreach($students as $student)
@php $enrollment = $student->printEnrollment; @endphp
<div class="page cert-page">
    @include('students.documents.partials.certificate-official-header')
    @include('students.documents.partials.certificate-body', [
        'student' => $student,
        'enrollment' => $enrollment,
    ])
</div>
@endforeach
</body>
</html>
