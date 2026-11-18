<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Preparation du bulletin de paie - {{ $staff->full_name }}</title>
    <style>
        @page { size: A4 portrait; margin: 10mm; } body { font-family: Arial, Helvetica, sans-serif; margin: 0; color: #111827; background: #f8fafc; }
        .panel { max-width: 720px; margin: 24px auto; border: 1px solid #cbd5e1; border-radius: 10px; padding: 22px; background: #fff; transition: max-width .2s ease; }
        .panel.is-expanded { max-width: 960px; }
        .title { font-size: 22px; font-weight: 900; color: #1A3A6B; text-transform: uppercase; margin-bottom: 8px; } .subtitle { font-size: 12px; color: #475569; margin-bottom: 16px; }
        label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 6px; color: #0f172a; } input, select { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; box-sizing: border-box; } input[readonly] { background: #f8fafc; }
        .btn { display: inline-flex; align-items: center; justify-content: center; margin-top: 16px; padding: 10px 16px; border: none; border-radius: 6px; background: #1A3A6B; color: #fff; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; }
        .meta { margin-bottom: 14px; font-size: 14px; font-weight: 500; color: #000; } .meta-value { font-weight: 700; color: #000; font-size: 16px; line-height: 2; }
        .fields { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px; align-items: end; margin-bottom: 12px; } .fields.is-repaying { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        .repayment-toggle { min-height: 42px; display: flex; align-items: center; gap: 8px; } .repayment-toggle input { width: 18px; height: 18px; margin: 0; accent-color: #0b5f3a; } .repayment-toggle label { margin: 0; white-space: nowrap; }
        .repayment-amount[hidden] { display: none; }
        .amount-preview { margin-top: 14px; padding: 12px 14px; border: 1px solid #bbf7d0; border-radius: 8px; background: #f0fdf4; color: #166534; font-size: 15px; font-weight: 800; } .actions { display: flex; justify-content: flex-end; gap: 12px; }
        @media (max-width: 860px) { .fields, .fields.is-repaying { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (max-width: 640px) { .panel, .panel.is-expanded { max-width: none; margin: 10px; padding: 16px; } .fields, .fields.is-repaying { grid-template-columns: 1fr; } .actions { justify-content: stretch; flex-direction: column; } .actions .btn { width: 100%; } }
    </style>
</head>
<body>
@php
    $isVacataire = $isVacataire ?? ($staff->contract_type === 'vacataire');
    $hourlyRate = $hourlyRate ?? $staff->hourly_rate ?? 0;
    $monthlySalary = $monthlySalary ?? $staff->monthly_salary ?? 0;
    $hoursWorked = $hoursWorked ?? null;
    $remainingAdvanceAmount = (int) ($remainingAdvanceAmount ?? $staff->remaining_salary_advance_balance ?? 0);
    $repaymentAmount = old('repayment_amount', $repaymentAmount ?? '');
    $repayAdvance = old('repay_advance', false);
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
<div class="panel">
    <div class="title">Preparation du bulletin de paie</div>
    <div class="subtitle">{{ $isVacataire ? 'Renseignez les heures effectuees. Le salaire est calcule automatiquement.' : 'Le salaire mensuel configure sera utilise pour la periode choisie.' }}</div>
    <div class="meta"><div><strong>Employe :</strong> <span class="meta-value">{{ $staff->full_name }}</span></div><div><strong>Contrat :</strong> <span class="meta-value">{{ $staff->contract_label }}</span></div></div>
    <form method="POST" action="{{ route('staff.pay-slip.store', $staff) }}" target="_blank">
        @csrf
        @if($isVacataire)
            <div class="fields"><div><label for="hourly_rate">Tarif horaire</label><input id="hourly_rate" type="text" value="{{ number_format((float) ($hourlyRate ?? $staff->hourly_rate ?? 0), 0, ',', ' ') }} FCFA / heure" readonly></div><div><label for="hours_worked">Heures effectuees</label><input id="hours_worked" name="hours_worked" type="number" step="0.01" min="0" required value="{{ old('hours_worked', $hoursWorked ?? '') }}"></div><div><label for="remaining_advance">Bon à rembourser</label><input id="remaining_advance" type="text" value="{{ number_format($remainingAdvanceAmount, 0, ',', ' ') }} FCFA" readonly></div><div class="repayment-toggle"><input id="repay_advance" name="repay_advance" type="checkbox" value="1" @checked($repayAdvance) @disabled($remainingAdvanceAmount < 1)><label for="repay_advance">Rembourser</label></div><div id="repayment_amount_wrap" class="repayment-amount" @if(! $repayAdvance) hidden @endif><label for="repayment_amount">Montant à rembourser</label><input id="repayment_amount" name="repayment_amount" type="number" min="0" max="{{ $remainingAdvanceAmount }}" step="1" value="{{ $repaymentAmount }}" @if($repayAdvance) required @endif placeholder="Montant (FCFA)"></div></div>
            <div class="amount-preview">Salaire a percevoir : <span id="calculated-amount">0 FCFA</span></div>
        @else
            <div class="fields">
                <div>
                    <label for="monthly_salary">Salaire mensuel</label>
                    <input id="monthly_salary" type="text" value="{{ number_format((float) ($monthlySalary ?? $staff->monthly_salary ?? 0), 0, ',', ' ') }} FCFA / mois" readonly>
                </div>
                <div><label for="remaining_advance">Bon à rembourser</label><input id="remaining_advance" type="text" value="{{ number_format($remainingAdvanceAmount, 0, ',', ' ') }} FCFA" readonly></div>
                <div class="repayment-toggle"><input id="repay_advance" name="repay_advance" type="checkbox" value="1" @checked($repayAdvance) @disabled($remainingAdvanceAmount < 1)><label for="repay_advance">Rembourser</label></div>
                <div id="repayment_amount_wrap" class="repayment-amount" @if(! $repayAdvance) hidden @endif><label for="repayment_amount">Montant à rembourser</label><input id="repayment_amount" name="repayment_amount" type="number" min="0" max="{{ $remainingAdvanceAmount }}" step="1" value="{{ $repaymentAmount }}" @if($repayAdvance) required @endif placeholder="Montant (FCFA)"></div>
                <div>
                    <label for="period">Periode</label>
                    <select id="period" name="period" required>
                        @foreach($periodOptions as $option)
                            <option value="{{ $option['value'] }}" {{ $selectedPeriod === $option['value'] ? 'selected' : '' }}>
                                {{ $option['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="amount-preview">Salaire a percevoir : <span id="calculated-amount">{{ number_format((float) ($monthlySalary ?? $staff->monthly_salary ?? 0), 0, ',', ' ') }} FCFA</span></div>
        @endif
        <div class="actions"><button type="submit" class="btn" style="background:#0b5f3a;">Enregistrer et afficher</button><a href="{{ route('staff.pay-slip.annual', $staff) }}" target="_blank" class="btn" style="background:#0b5f3a;">Recapitulatif annuel</a></div>
    </form>
</div>
<script>
    const panel = document.querySelector('.panel');
    const fields = document.querySelector('.fields');
    const repayCheckbox = document.getElementById('repay_advance');
    const repaymentWrap = document.getElementById('repayment_amount_wrap');
    const repaymentInput = document.getElementById('repayment_amount');
    const hoursInput = document.getElementById('hours_worked');
    const amountOutput = document.getElementById('calculated-amount');
    const hourlyRate = {{ (float) $hourlyRate }};
    const monthlySalary = {{ (float) $monthlySalary }};
    const remainingAdvance = {{ (int) $remainingAdvanceAmount }};
    const getGrossAmount = () => hoursInput ? (Number(hoursInput.value) || 0) * hourlyRate : monthlySalary;
    const refreshAmount = () => {
        const gross = getGrossAmount();
        const net = Math.max(0, gross - (repayCheckbox.checked ? (Number(repaymentInput.value) || 0) : 0));
        repaymentInput.max = String(Math.min(remainingAdvance, Math.floor(Math.max(0, gross))));
        amountOutput.textContent = new Intl.NumberFormat('fr-FR').format(net) + ' FCFA';
    };
    const updateRepaymentVisibility = () => {
        const enabled = repayCheckbox.checked;
        repaymentWrap.hidden = !enabled;
        repaymentInput.required = enabled;
        panel.classList.toggle('is-expanded', enabled);
        fields.classList.toggle('is-repaying', enabled);
        refreshAmount();
    };
    repayCheckbox.addEventListener('change', updateRepaymentVisibility);
    repaymentInput.addEventListener('input', refreshAmount);
    if (hoursInput) hoursInput.addEventListener('input', refreshAmount);
    updateRepaymentVisibility();
</script>
</body>
</html>
