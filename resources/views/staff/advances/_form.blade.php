<div class="grid gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <label for="staff_id" class="mb-1.5 block text-sm font-semibold text-slate-700">Personnel <span class="text-red-600">*</span></label>
        <select id="staff_id" name="staff_id" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-blue-100">
            <option value="">Sélectionner un membre du personnel</option>
            @foreach($staff as $member)
                <option value="{{ $member->id }}" @selected((string) old('staff_id', $advance->staff_id) === (string) $member->id)>{{ $member->full_name }} — {{ $member->contract_label }}</option>
            @endforeach
        </select>
        @error('staff_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="issued_on" class="mb-1.5 block text-sm font-semibold text-slate-700">Date du bon <span class="text-red-600">*</span></label>
        <input id="issued_on" name="issued_on" type="date" max="{{ now()->toDateString() }}" required value="{{ old('issued_on', $advance->issued_on?->format('Y-m-d')) }}" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-blue-100">
        @error('issued_on')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="amount" class="mb-1.5 block text-sm font-semibold text-slate-700">Montant (FCFA) <span class="text-red-600">*</span></label>
        <input id="amount" name="amount" type="number" min="1" step="1" required value="{{ old('amount', $advance->amount) }}" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-blue-100">
        @error('amount')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="md:col-span-2">
        <label for="purpose" class="mb-1.5 block text-sm font-semibold text-slate-700">Motif</label>
        <input id="purpose" name="purpose" maxlength="255" value="{{ old('purpose', $advance->purpose) }}" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-blue-100">
        @error('purpose')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="md:col-span-2">
        <label for="notes" class="mb-1.5 block text-sm font-semibold text-slate-700">Notes</label>
        <textarea id="notes" name="notes" rows="3" maxlength="5000" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-[#1A3A6B] focus:outline-none focus:ring-2 focus:ring-blue-100">{{ old('notes', $advance->notes) }}</textarea>
        @error('notes')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>
