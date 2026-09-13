<div id="recent-absences-list">
    @if($recentAbsences->isEmpty())
    <div class="px-5 py-8 text-center text-sm text-gray-400 italic">
        Aucune absence enregistrée.
    </div>
    @else
    <div class="max-h-[28rem] overflow-x-auto overflow-y-auto divide-y divide-gray-50 sm:max-h-none">
        @foreach($recentAbsences as $ab)
        <div class="min-w-[760px] px-5 py-3 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-8 h-8 rounded-full flex items-center justify-center
                            text-white text-xs font-bold flex-shrink-0"
                     style="background:#1A3A6B;">
                    {{ strtoupper(substr($ab->studentEnrollment?->student?->last_name??'?',0,1)) }}
                </div>
                <div class="min-w-0">
                    <p class="text-sm font-bold text-gray-800 truncate">
                        {{ $ab->studentEnrollment?->student?->full_name ?? 'Élève transféré ou supprimé' }}
                    </p>
                    <p class="text-xs text-gray-400">
                        {{ $ab->studentEnrollment?->classGroup?->full_name ?? 'Classe inconnue' }}
                        · {{ $ab->absence_date->format('d/m/Y') }}
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-4 flex-shrink-0">
                <div class="text-right">
                    <p class="text-sm font-black"
                       style="color:{{ $ab->is_justified ? '#1A5C2A' : '#EF4444' }};">
                        @if($ab->status === 'present' && $ab->delay_minutes > 0)
                            Retard : {{ $ab->delay_minutes }} min
                        @else
                            Absent(e) : {{ number_format(
                                $ab->timetable_slot_id === null && $ab->class_subject_id === null
                                    ? config('attendance.daily_absence_hours', 0)
                                    : (float) $ab->hours,
                                1, '.', ''
                            ) }}h
                        @endif
                    </p>
                </div>
                <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                      style="{{ $ab->is_justified
                          ? 'background:#D1FAE5;color:#065F46;'
                          : 'background:#FEE2E2;color:#991B1B;' }}">
                    {{ $ab->is_justified ? 'Justifiée' : 'Injustifiée' }}
                </span>
                @can('manage-absences')
                <form method="POST"
                      action="{{ route('absences.justify', $ab) }}">
                    @csrf @method('PATCH')
                    <button type="submit"
                            class="text-xs font-medium px-2.5 py-1 rounded-lg
                                   border border-gray-200 text-gray-600
                                   hover:bg-gray-50 transition-colors">
                        {{ $ab->is_justified ? 'Injustifier' : 'Justifier' }}
                    </button>
                </form>
                @endcan
            </div>
        </div>
        @endforeach
    </div>
    @endif

    @if($recentAbsences->hasPages())
    <div class="border-t border-gray-100 px-5 py-4">
        {{ $recentAbsences->onEachSide(1)->links('vendor.pagination.custom') }}
    </div>
    @endif
</div>
