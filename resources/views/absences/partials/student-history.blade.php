<div id="student-absence-history">
    @if($absences->isEmpty())
    <div class="px-5 py-10 text-center text-sm text-gray-400 italic">
        Aucune absence ou retard enregistré.
    </div>
    @else
    <div class="overflow-x-auto">
        <table class="w-full min-w-[980px]">
            <thead>
                <tr style="background:#F8FAFC;border-bottom:1px solid #E5E7EB;">
                    <th class="text-left px-5 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Date</th>
                    <th class="text-center px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Justification</th>
                    <th class="text-center px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Heure d'arrivée</th>
                    <th class="text-center px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Heures</th>
                    <th class="text-left px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Observations</th>
                    <th class="text-center px-4 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Statut</th>
                    @can('manage-absences')
                    <th class="text-right px-5 py-3 text-xs font-bold text-gray-400 uppercase tracking-wider">Actions</th>
                    @endcan
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($absences as $ab)
                <tr class="hover:bg-gray-50/50 transition-colors">
                    <td class="px-5 py-3.5">
                        <p class="text-sm font-bold text-gray-800">{{ $ab->absence_date->format('d/m/Y') }}</p>
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        @if($ab->status === 'present' && $ab->delay_minutes > 0)
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold" style="background:#FEF3C7;color:#92400E;">Retard</span>
                        @else
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold" style="background:#FEE2E2;color:#991B1B;">Absent</span>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 text-center text-sm font-semibold text-gray-700">
                        {{ $ab->arrival_time ? substr((string) $ab->arrival_time, 0, 5) : '—' }}
                    </td>
                    <td class="px-4 py-3.5 text-center text-sm font-black" style="color:{{ $ab->is_justified ? '#1A5C2A' : '#EF4444' }};">
                        @php
                            $totalMinutes = $ab->effective_minutes;
                            $displayHours = intdiv($totalMinutes, 60);
                            $displayMinutes = $totalMinutes % 60;
                        @endphp
                        {{ $displayHours }}h{{ str_pad((string) $displayMinutes, 2, '0', STR_PAD_LEFT) }}min
                    </td>
                    <td class="px-4 py-3.5 text-sm text-gray-600">
                        {{ $ab->observation ?: '—' }}
                    </td>
                    <td class="px-4 py-3.5 text-center">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                              style="{{ $ab->is_justified ? 'background:#D1FAE5;color:#065F46;' : 'background:#FEE2E2;color:#991B1B;' }}">
                            {{ $ab->is_justified ? 'Justifiée' : 'Injustifiée' }}
                        </span>
                        @if($ab->justification)
                        <p class="mt-1 text-xs text-gray-400 italic">{{ Str::limit($ab->justification, 30) }}</p>
                        @endif
                    </td>
                    @can('manage-absences')
                    <td class="px-5 py-3.5 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <form method="POST" action="{{ route('absences.justify', $ab) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-xs font-medium px-2.5 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">
                                    {{ $ab->is_justified ? 'Injustifier' : 'Justifier' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('absences.destroy', $ab) }}" onsubmit="return confirm('Supprimer cette absence ?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50" aria-label="Supprimer">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                    @endcan
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    @if($absences->hasPages())
    <div class="border-t border-gray-100 px-5 py-4">
        {{ $absences->onEachSide(1)->links('vendor.pagination.custom') }}
    </div>
    @endif
</div>
