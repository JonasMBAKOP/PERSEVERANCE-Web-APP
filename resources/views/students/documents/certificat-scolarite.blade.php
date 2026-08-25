<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Certificat de scolarité — {{ $student->full_name }}</title>
@include('students.documents.partials.base-styles')
<style>
@page { size: A4 portrait; margin: 2mm 3mm; }
.page.cert-page { max-width: none; padding: 1mm 2mm; }
</style>
</head>
<body>
@include('students.documents.partials.print-toolbar')

<div class="page cert-page">
    @include('students.documents.partials.certificate-official-header')
    @include('students.documents.partials.certificate-body', [
        'student' => $student,
        'enrollment' => $enrollment,
    ])
</div>
</body>
</html>
