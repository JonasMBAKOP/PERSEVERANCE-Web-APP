<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>{{ $documentTitle }} — {{ $enrollment->student->full_name }}</title>
@include('bulletins.partials.pdf-styles')
<style>
@media print {
    .no-print { display: none !important; }
    body { background: #fff !important; padding: 0 !important; }
    .bulletin-page { box-shadow: none !important; margin: 0 !important; }
}
body { background: #E5E7EB; }
.bulletin-page { width: 210mm; min-height: 297mm; max-width: calc(100vw - 32px); margin: 20px auto; border: 4px solid var(--bleu); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
</style>
</head>
<body>
@include('students.documents.partials.print-toolbar')

@if(false)
@php
    $phones = array_filter([
        $enrollment->student->father_phone ?? null,
        $enrollment->student->mother_phone ?? null,
        $enrollment->student->guardian_phone ?? null,
    ]);
@endphp
@if(!empty($phones))
<form method="POST" action="{{ route('communication.parents.bulletin.send', $enrollment) }}"
      class="inline">
    @csrf
    <input type="hidden" name="phone" value="all">
    <input type="hidden" name="pdf_url"
           value="{{ URL::temporarySignedRoute('bulletins.signed-pdf', now()->addMinutes(30), [
                'enrollment'   => $enrollment,
                'type'         => $type,
                'sequence_id'  => request('sequence_id'),
                'trimester_id' => request('trimester_id'),
           ]) }}">
    <button type="submit"
            class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-white
                   text-sm font-bold" style="background-color:#1A5C2A;">
        📱 Envoyer à tous les numéros parents/tuteurs par WhatsApp
    </button>
</form>
@endif
@endif

@include('bulletins.partials.pdf-page')

</body>
</html>
