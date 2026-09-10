@extends('layouts.app')

@section('title', 'Tableau de bord - Assistant(e) de Direction')
@section('page-title', 'Tableau de bord')
@section('page-subtitle')Bonjour, {{ auth()->user()->name }} - {{ now()->isoFormat('dddd D MMMM YYYY') }}@endsection

@push('styles')
<style>
.assistant-panel { background:#fff; border:1px solid #e5edf5; box-shadow:0 10px 26px rgba(26,58,107,.055); }
.assistant-action { transition:transform .18s ease, box-shadow .18s ease; }
.assistant-action:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(26,58,107,.10); }
</style>
@endpush

@section('content')
@if($noStaff)
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-center text-amber-700 font-semibold">Aucun dossier personnel associe a votre compte.</div>
@elseif(!$activeYear)
<div class="bg-amber-50 border border-amber-200 rounded-xl p-6 text-center text-amber-700 font-semibold">Aucune annee scolaire active.</div>
@else
<div class="rounded-2xl p-6 mb-6 relative overflow-hidden" style="background:linear-gradient(120deg,#1A3A6B,#28558e);"><div class="relative z-10"><p class="text-xs font-bold uppercase tracking-widest text-white/60 mb-2">Assistant(e) de Direction</p><h2 class="text-2xl font-black text-white mb-1">{{ now()->hour < 18 ? 'Bonjour' : 'Bonsoir' }}, {{ explode(' ', auth()->user()->name)[0] }}</h2><p class="text-white/75 text-sm">Coordination pedagogique et acces aux outils de suivi de l'etablissement.</p><div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-5"><div class="rounded-xl p-4 bg-white/10 border border-white/10"><p class="text-xs font-bold text-white/60 uppercase">Matieres</p><p class="text-2xl font-black text-white mt-1">{{ str_pad($totalSubjects,2,'0',STR_PAD_LEFT) }}</p></div><div class="rounded-xl p-4 bg-white/10 border border-white/10"><p class="text-xs font-bold text-white/60 uppercase">Classes</p><p class="text-2xl font-black text-white mt-1">{{ str_pad($totalClasses,2,'0',STR_PAD_LEFT) }}</p></div><div class="rounded-xl p-4 bg-white/10 border border-white/10"><p class="text-xs font-bold text-white/60 uppercase">Eleves suivis</p><p class="text-2xl font-black text-white mt-1">{{ $totalStudents }}</p></div></div></div></div>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-6"><a href="{{ route('grades.index') }}" class="assistant-action rounded-2xl bg-white border border-gray-100 p-4"><b class="block text-sm text-gray-900">Notes</b><small class="text-xs text-gray-500">Suivi des evaluations</small></a><a href="{{ route('classes.index') }}" class="assistant-action rounded-2xl bg-white border border-gray-100 p-4"><b class="block text-sm text-gray-900">Classes</b><small class="text-xs text-gray-500">Organisation des classes</small></a><a href="{{ route('timetable.teacher') }}" class="assistant-action rounded-2xl bg-white border border-gray-100 p-4"><b class="block text-sm text-gray-900">Emploi du temps</b><small class="text-xs text-gray-500">Planning de la semaine</small></a>@if(Route::has('staff.index'))<a href="{{ route('staff.index') }}" class="assistant-action rounded-2xl bg-white border border-gray-100 p-4"><b class="block text-sm text-gray-900">Personnel</b><small class="text-xs text-gray-500">Acces au suivi RH</small></a>@endif</div>
<div class="assistant-panel rounded-2xl overflow-hidden"><div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between"><h3 class="font-black text-base" style="color:#1A3A6B;">Mes classes et matieres</h3><span class="text-xs text-gray-500">Annee {{ $activeYear->label }}</span></div>@if($myClasses->isEmpty())<p class="p-8 text-center text-sm text-gray-500">Aucune classe ou matiere assignee pour cette annee.</p>@else<div class="overflow-x-auto"><table class="w-full min-w-[620px] text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="text-left px-5 py-3">Classe</th><th class="text-left px-5 py-3">Matiere</th><th class="text-center px-5 py-3">Coefficient</th><th class="text-center px-5 py-3">Eleves</th></tr></thead><tbody class="divide-y divide-gray-100">@foreach($myClasses as $item)<tr><td class="px-5 py-3 font-semibold text-gray-800">{{ $item['class']->full_name }}</td><td class="px-5 py-3 text-gray-600">{{ $item['subject']->name_fr }}</td><td class="px-5 py-3 text-center text-gray-600">{{ $item['coefficient'] }}</td><td class="px-5 py-3 text-center text-gray-600">{{ $item['enrolled'] }}</td></tr>@endforeach</tbody></table></div>@endif</div>
@endif
@endsection
