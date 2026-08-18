@extends('layouts.app')

@section('title', 'Ajouter un insolvable')
@section('page-title', 'Ajouter un élève au registre des insolvables')

@section('content')
<div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
    <form method="POST" action="{{ route('finances.insolvables.store') }}" x-data="insolvablesForm()">
        @csrf
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold uppercase text-gray-500">Année scolaire</label>
                <select x-model="yearId" name="year_id" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm">
                    @foreach($years as $year)
                        <option value="{{ $year->id }}" {{ optional($selectedYear)->id === $year->id ? 'selected' : '' }}>{{ $year->label }}{{ $year->is_active ? ' (Active)' : '' }}</option>
                    @endforeach
                </select>
            </div>

            <div class="sm:col-span-2" x-cloak>
                <label class="block text-xs font-bold uppercase text-gray-500">Élève (recherche)</label>
                <div class="relative">
                    <input x-model="query" @input.debounce="search" type="text" placeholder="Tapez le nom ou le matricule" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm" />
                    <ul x-show="suggestions.length > 0" class="absolute z-50 mt-1 max-h-64 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg">
                        <template x-for="item in suggestions" :key="item.id">
                            <li @click.prevent="select(item)" class="cursor-pointer px-3 py-2 hover:bg-gray-50" x-text="item.label"></li>
                        </template>
                    </ul>
                </div>
                <input type="hidden" name="student_enrollment_id" x-model="selectedId" />
            </div>

            <div class="sm:col-span-2" x-show="selected" x-cloak>
                <label class="block text-xs font-bold uppercase text-gray-500">Infos élève</label>
                <div class="mt-2 grid grid-cols-1 gap-3 rounded-xl border border-gray-100 bg-gray-50 p-4 text-sm sm:grid-cols-2">
                    <div>
                        <div class="font-bold text-gray-800" x-text="selected.name"></div>
                        <div class="text-xs text-gray-400" x-text="selected.matricule"></div>
                        <div class="mt-2 text-xs text-gray-600">Classe : <span x-text="selected.class"></span></div>
                        <div class="text-xs text-gray-600">Section : <span x-text="selected.section ?? '—'"></span></div>
                    </div>
                    <div>
                        <div class="flex flex-col gap-2">
                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-500">Total frais</label>
                                <div class="mt-1 flex items-center gap-3">
                                    <input x-show="feeTotal==0" type="number" name="total_due" x-model.number="totalDue" min="0" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm" placeholder="Saisir manuellement si aucune config" />
                                    <div x-show="feeTotal>0" class="rounded-xl bg-white px-3 py-2.5 text-sm font-bold text-gray-800" x-text="feeTotal > 0 ? feeTotal + ' FCFA' : '—'"></div>
                                    <input type="hidden" name="total_due" :value="feeTotal>0 ? feeTotal : totalDue">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-500">Total payé</label>
                                <input type="number" name="total_paid" x-model.number="totalPaid" :max="(feeTotal>0 ? feeTotal : (totalDue || 0))" min="0" class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-sm" />
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-gray-500">Reste à payer</label>
                                <div class="mt-1 rounded-xl bg-white px-3 py-2.5 text-sm font-black text-red-600" x-text="remaining + ' FCFA'"></div>
                                <input type="hidden" name="remaining" :value="remaining">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-bold uppercase text-gray-500">Tranches (sélectionnez)</label>
                    <div class="mt-2 grid gap-2">
                        <template x-if="installments.length === 0">
                            <div class="text-xs text-gray-500">Aucune tranche configurée pour la classe. Vous pouvez remplir manuellement le total frais ci-dessus.</div>
                        </template>
                        <template x-for="inst in installments" :key="inst.id">
                            <label class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm">
                                <input type="checkbox" :value="inst.id" name="selected_installments[]" x-model="selectedInstallments" class="h-4 w-4" />
                                <div class="flex flex-col">
                                    <span class="font-semibold" x-text="inst.label"></span>
                                    <span class="text-xs text-gray-500" x-text="inst.amount + ' FCFA'"></span>
                                </div>
                            </label>
                        </template>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-4 flex gap-2">
            <button type="submit" class="rounded-xl bg-[#1A3A6B] px-4 py-2 text-white font-bold">Ajouter</button>
            <a href="{{ route('finances.insolvables') }}" class="rounded-xl border border-gray-200 px-4 py-2">Annuler</a>
        </div>
    </form>
</div>

<script>
function insolvablesForm(){
    return {
        yearId: '{{ optional($selectedYear)->id }}' || '{{ $years->first()->id ?? '' }}',
        query: '',
        suggestions: [],
        selected: null,
        selectedId: '',
        searchTimer: null,
        installments: [],
        feeTotal: 0,
        totalDue: null,
        totalPaid: 0,
        selectedInstallments: [],
        remaining: 0,
        async search(){
            this.selected = null; this.selectedId = '';
            this.installments = [];
            this.feeTotal = 0;
            if (! this.yearId) return this.suggestions = [];
            if (this.query.length < 2) { this.suggestions = []; return; }
            clearTimeout(this.searchTimer);
            this.searchTimer = setTimeout(async () => {
                try {
                    const res = await fetch('{{ route('finances.insolvables.enrollment-search') }}?year_id=' + encodeURIComponent(this.yearId) + '&q=' + encodeURIComponent(this.query));
                    if (! res.ok) { this.suggestions = []; return; }
                    this.suggestions = await res.json();
                } catch (e) { this.suggestions = []; }
            }, 250);
        },
        async select(item){
            this.selected = item;
            this.selectedId = item.id;
            this.query = item.label;
            this.suggestions = [];
            // fetch installments and fee total for this enrollment's class
            try {
                const res = await fetch('/finances/insolvables/enrollment/' + encodeURIComponent(item.id) + '/installments');
                if (! res.ok) { this.installments = []; this.feeTotal = 0; return; }
                const data = await res.json();
                this.installments = data.installments || [];
                this.feeTotal = data.fee_total || 0;
                // if there's a configured fee total, prefill totalDue
                if (this.feeTotal > 0) {
                    this.totalDue = this.feeTotal;
                }
                this.computeRemaining();
            } catch (e) {
                this.installments = []; this.feeTotal = 0;
            }
        },
        computeRemaining(){
            const due = this.feeTotal > 0 ? this.feeTotal : (this.totalDue || 0);
            const paid = Number(this.totalPaid || 0);
            this.remaining = Math.max(0, due - paid);
        },
        init(){
            // reactive watchers (Alpine v3+)
            try {
                this.$watch('totalPaid', value => this.computeRemaining());
                this.$watch('totalDue', value => this.computeRemaining());
                this.$watch('feeTotal', value => this.computeRemaining());
            } catch (e) {
                // ignore if $watch not available
            }
        }
    }
}
</script>

@endsection
