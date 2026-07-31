@extends('layouts.app')

@section('title', 'Nouvelle matiere')
@section('page-title', 'Nouvelle Matiere')
@section('page-subtitle', 'Ajouter au catalogue')

@section('breadcrumb')
    <a href="{{ route('subjects.index') }}" class="hover:text-gray-700">Matieres</a>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    <span class="font-medium" style="color: #1A3A6B;">Nouvelle matiere</span>
@endsection

@section('content')
@php
    $categoryOptions = $categories->map(fn ($category) => [
        'id' => $category->id,
        'section_id' => $category->section_id,
        'code' => $category->code,
        'name' => $category->name,
    ])->values();
@endphp
<div class="w-full px-4 lg:px-8" x-data="{
    sectionId: '{{ old('section_id') }}',
    categoryId: '{{ old('subject_category_id') }}',
    categories: @js($categoryOptions),
    filteredCategories() { return this.categories.filter(category => String(category.section_id) === String(this.sectionId)); }
}">
    <form method="POST" action="{{ route('subjects.store') }}">
        @csrf
        <div class="max-w-4xl rounded-2xl border border-gray-100 bg-white p-6 shadow-sm sm:p-8">
            <div class="mb-7 border-b border-gray-100 pb-4">
                <h3 class="text-base font-semibold text-gray-900">Informations de la matiere</h3>
                <p class="mt-1 text-sm text-gray-500">Choisissez d'abord la section, puis sa categorie.</p>
            </div>

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Section <span class="text-red-500">*</span></label>
                    <select name="section_id" x-model="sectionId" @change="categoryId = ''" class="w-full rounded-xl border px-4 py-3 text-sm outline-none focus:border-[#1A3A6B] focus:ring-2 focus:ring-blue-100 {{ $errors->has('section_id') ? 'border-red-400' : 'border-gray-200' }}">
                        <option value="">Selectionner une section</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}">{{ $section->name }}</option>
                        @endforeach
                    </select>
                    @error('section_id')<p class="mt-2 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Categorie <span class="text-red-500">*</span></label>
                    <select name="subject_category_id" x-model="categoryId" :disabled="!sectionId" class="w-full rounded-xl border px-4 py-3 text-sm outline-none focus:border-[#1A3A6B] focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-gray-50 {{ $errors->has('subject_category_id') ? 'border-red-400' : 'border-gray-200' }}">
                        <option value="">Selectionner une categorie</option>
                        <template x-for="category in filteredCategories()" :key="category.id">
                            <option :value="category.id" x-text="category.code + ' - ' + category.name"></option>
                        </template>
                    </select>
                    @error('subject_category_id')<p class="mt-2 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700">Nom de la matiere <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" maxlength="100" placeholder="Ex: Mathematiques" class="w-full rounded-xl border px-4 py-3 text-sm outline-none focus:border-[#1A3A6B] focus:ring-2 focus:ring-blue-100 {{ $errors->has('name') ? 'border-red-400' : 'border-gray-200' }}">
                    @error('name')<p class="mt-2 text-xs text-red-500">{{ $message }}</p>@enderror
                </div>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('subjects.index') }}" class="rounded-xl border border-gray-200 px-5 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50">Annuler</a>
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-[#0F766E] px-5 py-2.5 text-sm font-semibold text-white hover:bg-[#0B625B]">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Creer la matiere
            </button>
        </div>
    </form>
</div>
@endsection
