@php
    $bilingualDays = [
        1 => 'LUNDI / MONDAY',
        2 => 'MARDI / TUESDAY',
        3 => 'MERCREDI / WEDNESDAY',
        4 => 'JEUDI / THURSDAY',
        5 => 'VENDREDI / FRIDAY',
    ];
    $mode = $mode ?? 'class';
    $printable = $printable ?? false;
    $conflicts = $conflicts ?? collect();
    $teacherSubjectCount = $teacherSubjectCount ?? 0;
    $renderedUntil = [];
    $rowCount = count($gridRows);

    $rowInterval = function (array $row): string {
        $cell = collect($row['times'] ?? [])->first();
        return $cell ? $cell['start'] . ' - ' . $cell['end'] : '';
    };

    $slotsStartingAt = function (int $dayNumber, int $periodIndex) use ($slots) {
        return $slots->filter(fn ($slot) =>
            (int) $slot->day_of_week === $dayNumber
            && (int) $slot->period_index === $periodIndex
        );
    };

    $slotCoveringAfterBreak = function (int $dayNumber, int $periodIndex) use ($slots) {
        return $slots->filter(function ($slot) use ($dayNumber, $periodIndex) {
            $start = (int) $slot->period_index;
            $end = $start + (int) $slot->periods_count - 1;
            return (int) $slot->day_of_week === $dayNumber && $start < $periodIndex && $end >= $periodIndex;
        });
    };

    $segmentSpan = function ($slot, int $rowIndex, int $dayNumber) use ($gridRows, $rowCount): int {
        $endPeriod = (int) $slot->period_index + (int) $slot->periods_count - 1;
        $span = 0;

        for ($i = $rowIndex; $i < $rowCount; $i++) {
            $candidate = $gridRows[$i];
            if (($candidate['type'] ?? null) !== 'period') {
                break;
            }

            $period = (int) $candidate['period_index'];
            if ($period > $endPeriod) {
                break;
            }

            $cell = $candidate['times'][$dayNumber] ?? null;
            if (! $cell || ! ($cell['is_active'] ?? false)) {
                break;
            }

            $span++;
        }

        return max($span, 1);
    };

    $cellSlots = function (int $dayNumber, int $periodIndex, bool $includeAfterBreak = true) use ($slots, $slotsStartingAt, $slotCoveringAfterBreak) {
        $slotsAtStart = $slotsStartingAt($dayNumber, $periodIndex);

        if ($slotsAtStart->isNotEmpty()) {
            return $slotsAtStart;
        }

        if ($includeAfterBreak) {
            return $slotCoveringAfterBreak($dayNumber, $periodIndex);
        }

        return collect();
    };
@endphp

<table class="{{ $printable ? 'timetable-print' : 'w-full min-w-[1080px] border-separate border-spacing-0 text-[15px]' }}" style="table-layout: fixed;">
    <colgroup>
        <col style="width: 220px; min-width: 220px;">
        @foreach($days as $dayNumber => $dayName)
            <col style="width: {{ round((100 - 22) / count($days), 2) }}%;">
        @endforeach
    </colgroup>
    <thead>
        <tr class="{{ $printable ? '' : 'bg-gray-50' }}">
            <th class="{{ $printable ? 'period' : 'sticky left-0 z-10 border-b bg-[#F8FBFE] px-3 py-5 text-center text-[15px] font-black uppercase tracking-wide text-slate-600' }}" style="width: 220px; white-space: nowrap;">HORAIRES / PERIODS</th>
            @foreach($days as $dayNumber => $dayName)
                <th class="{{ $printable ? '' : 'border-b px-2 py-5 text-center text-[15px] font-black uppercase tracking-wide text-slate-600' }}" style="width: {{ round((100 - 22) / count($days), 2) }}%; white-space: nowrap;">{{ $bilingualDays[$dayNumber] ?? strtoupper($dayName) }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($gridRows as $rowIndex => $row)
            @if($row['type'] === 'break')
                <tr class="{{ $printable ? 'break-row' : 'bg-amber-50/60' }}">
                    <td class="{{ $printable ? 'period' : 'sticky left-0 z-10 border-b border-amber-100 bg-amber-50 px-5 py-4 text-[15px] font-black text-amber-700' }}">{{ $rowInterval($row) }}</td>
                    <td colspan="{{ count($days) }}" class="{{ $printable ? '' : 'border-b border-amber-100 px-4 py-4 text-center text-[15px] font-black uppercase tracking-wide text-amber-700' }}">PAUSE / BREAK TIME</td>
                </tr>
            @else
                <tr>
                    <td class="{{ $printable ? 'period' : 'sticky left-0 z-10 border-b bg-white px-5 py-4 text-center align-middle text-[15px] font-black text-slate-700' }}">{{ $rowInterval($row) }}</td>
                    @foreach($days as $dayNumber => $dayName)
                        @php
                            $periodIndex = (int) $row['period_index'];
                            $skipCell = ($renderedUntil[$dayNumber] ?? 0) >= $periodIndex;
                        @endphp
                        @continue($skipCell)

                        @php
                            $cell = $row['times'][$dayNumber] ?? null;
                            $slotsForCell = $cellSlots($dayNumber, $periodIndex, true);
                            $slot = $slotsForCell->first();
                            $rowspan = $slot ? max($slotsForCell->max(fn ($item) => (int) $item->periods_count) ?: 1, $segmentSpan($slot, $rowIndex, $dayNumber)) : 1;

                            if ($slot) {
                                $renderedUntil[$dayNumber] = $periodIndex + $rowspan - 1;
                            }
                        @endphp
                        <td rowspan="{{ $rowspan }}" class="{{ $printable ? '' : 'border-b px-2 py-3 text-center align-middle ' . (($cell && !($cell['is_active'] ?? false)) ? 'bg-gray-50' : '') }}" @if(!$printable) style="height:64px; vertical-align:middle; text-align:center;" @endif>
                            @if($slot)
                                @if($mode === 'teacher')
                                    @php
                                        $blockMinHeight = $rowspan * 62;
                                        $teacherSlotSubjects = $slotsForCell->map(fn ($teacherSlot) => $teacherSlot->classSubject?->subject?->id)->filter()->unique()->values();
                                        $teacherDisplaySubject = $teacherSlotSubjects->count() === 1 ? $slotsForCell->first()->classSubject?->subject?->name_fr : null;
                                    @endphp
                                    <div class="{{ $printable ? 'slot slot-teacher' : 'flex h-full min-h-[40px] flex-col items-center justify-center rounded-xl border border-green-100 bg-green-50 p-2 text-center shadow-sm' }}" @if(!$printable) style="min-height: {{ $blockMinHeight }}px;" @endif>
                                        @foreach($slotsForCell as $teacherSlot)
                                            @php
                                                $teacherSubject = $teacherSlot->classSubject?->subject;
                                                $teacherClass = $teacherSlot->classGroup?->full_name;
                                            @endphp
                                            <div class="{{ $printable ? 'teacher-slot-block' : 'w-full rounded-lg bg-white/80 px-2 py-1.5 shadow-sm ring-1 ring-green-100' }} {{ $loop->last ? '' : 'mb-2' }}">
                                                <strong class="{{ $printable ? '' : 'block text-[15px] font-black text-[#1A5C2A]' }}">{{ $teacherClass }}</strong>
                                                @if($teacherDisplaySubject === null && $teacherSubjectCount !== 1)
                                                    <span class="{{ $printable ? '' : 'mt-1 block text-[13px] font-semibold text-gray-600' }}">{{ $teacherSubject?->name_fr }}</span>
                                                @endif
                                                @if(!$printable && $teacherSlot->room)
                                                    <p class="mt-1 text-[12px] font-bold text-gray-500">{{ $teacherSlot->room }}</p>
                                                @endif
                                            </div>
                                        @endforeach
                                        @if($teacherDisplaySubject !== null && $teacherSubjectCount !== 1)
                                            <div class="{{ $printable ? 'teacher-slot-block' : 'mt-2 w-full rounded-lg bg-white/80 px-2 py-1.5 shadow-sm ring-1 ring-green-100' }}">
                                                <span class="{{ $printable ? '' : 'block text-[13px] font-semibold text-gray-600' }}">{{ $teacherDisplaySubject }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    @php
                                        $slotToRender = $slotsForCell->first();
                                        $isConflict = $conflicts->contains($slotToRender?->id);
                                        $teacher = $slotToRender?->classSubject?->teacherAssignments?->first()?->staff;
                                        $subject = $slotToRender?->classSubject?->subject;
                                        $blockMinHeight = $rowspan * 62;
                                    @endphp
                                    <div class="{{ $printable ? 'slot' : 'flex h-full min-h-[40px] flex-col items-center justify-center rounded-xl border p-2 text-center shadow-sm ' . ($isConflict ? 'border-red-300 bg-red-50' : 'border-blue-100 bg-blue-50') }}" @if(!$printable) style="min-height: {{ $blockMinHeight }}px;" @can('manage-timetable') role="button" @click="openEdit({{ $slotToRender?->id }}, {{ $slotToRender?->class_subject_id }}, {{ $slotToRender?->day_of_week }}, {{ $slotToRender?->period_index }}, {{ $slotToRender?->periods_count }}, @js($slotToRender?->room))" @endcan @endif>
                                        <strong class="{{ $printable ? '' : 'block text-[15px] font-black ' . ($isConflict ? 'text-red-800' : 'text-[#1A3A6B]') }}">{{ $subject?->name_fr ?? 'Matière' }}</strong>
                                        <span class="{{ $printable ? '' : 'mt-1 block text-[13px] font-semibold text-gray-600' }}">{{ $teacher?->honorific_full_name ?? 'Enseignant non assigné' }}</span>
                                        @if(!$printable && $slotToRender?->room)<p class="mt-1 text-[12px] font-bold text-gray-500">{{ $slotToRender->room }}</p>@endif
                                    </div>
                                @endif
                            @endif
                        </td>
                    @endforeach
                </tr>
            @endif
        @endforeach
    </tbody>
</table>