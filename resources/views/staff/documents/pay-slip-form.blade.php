<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preparation du bulletin de paie - {{ $staff->full_name }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; } body { font-family: Arial, Helvetica, sans-serif; margin: 0; color: #111827; background: #f8fafc; }
        .panel { max-width: 720px; margin: 24px auto; border: 1px solid #cbd5e1; border-radius: 10px; padding: 22px; background: #fff; }
        .title { font-size: 22px; font-weight: 900; color: #1A3A6B; text-transform: uppercase; margin-bottom: 8px; } .subtitle { font-size: 12px; color: #475569; margin-bottom: 16px; }
        label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: #0f172a; } input, select { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box; } input[readonly] { background: #f8fafc; }
        .btn { display: inline-flex; align-items: center; justify-content: center; margin-top: 16px; padding: 10px 16px; border: none; border-radius: 6px; background: #1A3A6B; color: #fff; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; }
        .meta { margin-bottom: 14px; font-size: 14px; font-weight: 500; color: #000; } .meta-value { font-weight: 700; color: #000; font-size: 16px; line-height: 2; }
        .fields { display: grid; grid-template-columns: 1fr 220px; gap: 12px; align-items: end; margin-bottom: 12px; } .amount-preview { margin-top: 14px; padding: 12px 14px; border: 1px solid #bbf7d0; border-radius: 8px; background: #f0fdf4; color: #166534; font-size: 15px; font-weight: 800; } .actions { display: flex; justify-content: flex-end; gap: 12px; }
        @media (max-width: 640px) { .panel { margin: 10px; padding: 16px; } .fields { grid-template-columns: 1fr; } .actions { justify-content: stretch; flex-direction: column; } .actions .btn { width: 100%; } }
    </style>
</head>
<body>
@php($isVacataire = $isVacataire ?? $staff->contract_type === 'vacataire')
<div class="panel">
    <div class="title">Preparation du bulletin de paie</div>
    <div class="subtitle">{{ $isVacataire ? 'Renseignez les heures effectuees. Le salaire est calcule automatiquement.' : 'Le salaire mensuel configure sera utilise pour la periode choisie.' }}</div>
    <div class="meta"><div><strong>Employe :</strong> <span class="meta-value">{{ $staff->full_name }}</span></div><div><strong>Contrat :</strong> <span class="meta-value">{{ $staff->contract_label }}</span></div></div>
    <form method="POST" action="{{ route('staff.pay-slip.store', $staff) }}" target="_blank">
        @csrf
        @if($isVacataire)
            <div class="fields"><div><label for="hourly_rate">Tarif horaire</label><input id="hourly_rate" type="text" value="{{ number_format((float) ($hourlyRate ?? $staff->hourly_rate ?? 0), 0, ',', ' ') }} FCFA / heure" readonly></div><div><label for="hours_worked">Heures effectuees</label><input id="hours_worked" name="hours_worked" type="number" step="0.01" min="0" required value="{{ old('hours_worked', $hoursWorked ?? '') }}"></div></div>
            <div class="amount-preview">Salaire a percevoir : <span id="calculated-amount">0 FCFA</span></div>
        @else
            <div class="fields">
                <div>
                    <label for="monthly_salary">Salaire mensuel</label>
                    <input id="monthly_salary" type="text" value="{{ number_format((float) ($monthlySalary ?? $staff->monthly_salary ?? 0), 0, ',', ' ') }} FCFA / mois" readonly>
                </div>
                <div>
                    <label for="period">Periode</label>
                    @php
                        $periodStart = $activeYear && $activeYear->start_date
                            ? \Carbon\Carbon::parse($activeYear->start_date)->startOfMonth()
                            : now()->startOfMonth();
                        $periodEnd = $activeYear && $activeYear->end_date
                            ? \Carbon\Carbon::parse($activeYear->end_date)->endOfMonth()
                            : now()->endOfMonth();
                        $periodCursor = $periodStart->copy();
                        $periodOptions = collect();
                        while ($periodCursor->lte($periodEnd)) {
                            $periodOptions->push([
                                'value' => $periodCursor->format('Y-m'),
                                'label' => $periodCursor->locale('fr')->translatedFormat('F Y'),
                            ]);
                            $periodCursor->addMonth();
                        }
                        $selectedPeriod = old('period', now()->format('Y-m'));
                    @endphp
                    <select id="period" name="period" required>
                        @foreach($periodOptions as $option)
                            <option value="{{ $option['value'] }}" {{ $selectedPeriod === $option['value'] ? 'selected' : '' }}>
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="amount-preview">Salaire a percevoir : <span>{{ number_format((float) ($monthlySalary ?? $staff->monthly_salary ?? 0), 0, ',', ' ') }} FCFA / mois</span></div>
        @endif
        <div class="actions"><button type="submit" class="btn" style="background:#0b5f3a;">Enregistrer et afficher</button><a href="{{ route('staff.pay-slip.annual', $staff) }}" target="_blank" class="btn" style="background:#0b5f3a;">Recapitulatif annuel</a></div>
    </form>
</div>
@if($isVacataire)<script>const hoursInput=document.getElementById('hours_worked');const amountOutput=document.getElementById('calculated-amount');const hourlyRate={{ (float) ($hourlyRate ?? $staff->hourly_rate ?? 0) }};const refreshAmount=()=>{amountOutput.textContent=new Intl.NumberFormat('fr-FR').format((Number(hoursInput.value)||0)*hourlyRate)+' FCFA';};hoursInput.addEventListener('input',refreshAmount);refreshAmount();</script>@endif
</body>
</html>
