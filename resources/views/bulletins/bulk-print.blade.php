<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Bulletins — {{ $classGroup->full_name }}</title>
@include('bulletins.partials.pdf-styles')
<style>
@media print {
    .no-print { display: none !important; }
    body { background: #fff !important; padding: 0 !important; }
    .bulletin-page { page-break-after: always; margin: 0 !important; box-shadow: none !important; }
    .bulletin-page:last-child { page-break-after: auto; }
}
.bulletin-page { margin: 20px auto; border: 4px solid var(--bleu); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
</style>
</head>
<body>
@include('students.documents.partials.print-toolbar')

@foreach($bulletins as $b)
    @include('bulletins.partials.pdf-page', $b)
@endforeach

</body>
</html>
