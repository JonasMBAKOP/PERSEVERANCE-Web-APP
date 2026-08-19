<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\StaffPresence;
use App\Models\TimetableSlot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class PresenceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::today();
        $contractType = $request->query('contract_type');
        $dayNumber = (int) $date->isoWeekday(); // 1 = Monday

        // Staff from timetable slots that day
        $slots = TimetableSlot::with('classSubject.teacherAssignments.staff')
            ->where('day_of_week', $dayNumber)
            ->get();

        $scheduledStaffIds = $slots->flatMap(function ($slot) {
            return $slot->classSubject?->teacherAssignments?->pluck('staff_id') ?? [];
        })->unique()->values()->all();

        // Permanent staff are expected every day
        $permanentStaff = Staff::active()->where('contract_type', 'permanent')->pluck('id')->all();

        $expectedIds = collect(array_merge($scheduledStaffIds, $permanentStaff))->unique()->values()->all();

        $staffQuery = Staff::whereIn('id', $expectedIds)->with('positions')->orderBy('last_name');
        if ($contractType && $contractType !== 'all') {
            // map friendly names to stored values if needed
            $map = [
                'permanent' => 'permanent',
                'semi' => 'semi_permanent',
                'vacataire' => 'vacataire',
            ];
            $value = $map[$contractType] ?? $contractType;
            $staffQuery->where('contract_type', $value);
        }

        $staff = $staffQuery->get();

        $presences = StaffPresence::whereIn('staff_id', $staff->pluck('id'))
            ->where('date', $date->toDateString())
            ->get()
            ->keyBy('staff_id');

        // Determine absentees: expected but either no presence record or status != present
        $absentees = $staff->filter(function ($member) use ($presences) {
            $p = $presences->get($member->id);
            return !$p || $p->status !== 'present';
        });

        return view('staff.presences.index', compact('date', 'staff', 'presences', 'absentees', 'contractType'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'presences' => 'nullable|array',
            'presences.*.status' => 'required|in:present,absent',
            'presences.*.arrival_time' => 'nullable|date_format:H:i',
            'presences.*.departure_time' => 'nullable|date_format:H:i',
            'presences.*.note' => 'nullable|string',
        ]);

        $date = Carbon::parse($request->input('date'))->toDateString();
        $payload = $request->input('presences', []);

        $staffIds = collect(array_keys($payload))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        if ($staffIds->isEmpty()) {
            return response()->json(['ok' => true, 'saved' => []]);
        }

        $validStaffIds = Staff::query()
            ->whereIn('id', $staffIds)
            ->pluck('id');
        $now = now();
        $rows = [];

        foreach ($validStaffIds as $staffId) {
            $data = $payload[$staffId] ?? $payload[(string) $staffId] ?? [];
            $rows[] = [
                'staff_id' => $staffId,
                'date' => $date,
                'status' => $data['status'] ?? 'absent',
                'arrival_time' => $data['arrival_time'] ?? null,
                'departure_time' => $data['departure_time'] ?? null,
                'note' => $data['note'] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        try {
            // Atomic on the unique(staff_id, date) key: safe for autosave and
            // the global submit running at the same time.
            StaffPresence::upsert(
                $rows,
                ['staff_id', 'date'],
                ['status', 'arrival_time', 'departure_time', 'note', 'updated_at']
            );
        } catch (Throwable $exception) {
            Log::error('Staff presence save failed', [
                'date' => $date,
                'staff_ids' => $validStaffIds->all(),
                'exception' => $exception->getMessage(),
            ]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Les présences n’ont pas pu être enregistrées. Consultez les journaux du serveur.',
                ], 500);
            }

            return back()->withInput()->with('error', 'Les présences n’ont pas pu être enregistrées.');
        }

        $saved = StaffPresence::query()
            ->whereIn('staff_id', $validStaffIds)
            ->whereDate('date', $date)
            ->get()
            ->keyBy('staff_id');

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'ok' => true,
                'saved' => $saved->map(fn($p) => [
                    'id' => $p->id,
                    'staff_id' => $p->staff_id,
                    'date' => $p->date->toDateString(),
                    'status' => $p->status,
                    'arrival_time' => $p->arrival_time,
                    'departure_time' => $p->departure_time,
                    'note' => $p->note,
                ])->values()->all(),
            ]);
        }

        return back()->with('success', 'Présences sauvegardées.');
    }

    public function print(Request $request)
    {
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::today();
        $contractType = $request->query('contract_type', 'all');
        $dayNumber = (int) $date->isoWeekday();

        $slots = TimetableSlot::with('classSubject.teacherAssignments.staff')
            ->where('day_of_week', $dayNumber)
            ->get();
        $scheduledStaffIds = $slots->flatMap(fn ($slot) =>
            $slot->classSubject?->teacherAssignments?->pluck('staff_id') ?? []
        )->unique()->values()->all();
        $permanentStaffIds = Staff::active()->where('contract_type', 'permanent')->pluck('id')->all();
        $expectedIds = collect(array_merge($scheduledStaffIds, $permanentStaffIds))->unique()->values();

        $staffQuery = Staff::whereIn('id', $expectedIds)
            ->with('positions')
            ->orderBy('last_name')
            ->orderBy('first_name');
        if ($contractType !== 'all') {
            $contractValue = [
                'semi' => 'semi_permanent',
                'semi_permanent' => 'semi_permanent',
            ][$contractType] ?? $contractType;
            $staffQuery->where('contract_type', $contractValue);
        }

        $staff = $staffQuery->get();
        $presences = StaffPresence::whereIn('staff_id', $staff->pluck('id'))
            ->whereDate('date', $date->toDateString())
            ->get()
            ->keyBy('staff_id');
        $school = \App\Models\SchoolSetting::instance();
        $phones = \App\Models\SchoolPhone::orderByDesc('is_primary')->orderBy('id')->get();
        $activeYear = \App\Models\AcademicYear::active();
        $classGroup = (object) ['academicYear' => $activeYear];

        return view('staff.presences.print', compact(
            'date', 'contractType', 'staff', 'presences', 'school', 'phones', 'classGroup'
        ));
    }

    public function mark(Request $request)
    {
        $date = $request->query('date') ? Carbon::parse($request->query('date')) : Carbon::today();
        $contractType = $request->query('contract_type');

        // Reuse index filtering to get the staff list
        $dayNumber = (int) $date->isoWeekday();
        $slots = TimetableSlot::with('classSubject.teacherAssignments.staff')
            ->where('day_of_week', $dayNumber)
            ->get();

        $scheduledStaffIds = $slots->flatMap(function ($slot) {
            return $slot->classSubject?->teacherAssignments?->pluck('staff_id') ?? [];
        })->unique()->values()->all();

        $permanentStaff = Staff::active()->where('contract_type', 'permanent')->pluck('id')->all();
        $expectedIds = collect(array_merge($scheduledStaffIds, $permanentStaff))->unique()->values()->all();

        $staffQuery = Staff::whereIn('id', $expectedIds)->with('positions')->orderBy('last_name');
        if ($contractType && $contractType !== 'all') {
            $map = [
                'permanent' => 'permanent',
                'semi' => 'semi_permanent',
                'semi_permanent' => 'semi_permanent',
                'vacataire' => 'vacataire',
            ];
            $value = $map[$contractType] ?? $contractType;
            $staffQuery->where('contract_type', $value);
        }

        $staff = $staffQuery->get();

        $presences = StaffPresence::whereIn('staff_id', $staff->pluck('id'))
            ->where('date', $date->toDateString())
            ->get()
            ->keyBy('staff_id');

        return view('staff.presences.mark', compact('date', 'staff', 'presences', 'contractType'));
    }

    public function dossier(Request $request)
    {
        $contractType = $request->input('contract_type', 'all');
        $staffId = $request->input('staff_id');
        $dossierType = $request->input('dossier_type', 'month');
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $startDate = $request->input('start_date', Carbon::now()->subDays(6)->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->toDateString());

        $staffQuery = Staff::active()->orderBy('last_name')->orderBy('first_name');
        if ($contractType !== 'all') {
            $canon = match ($contractType) {
                'semi', 'semi_permanent' => 'semi_permanent',
                'vacataire' => 'vacataire',
                'permanent' => 'permanent',
                default => $contractType,
            };
            $staffQuery->where('contract_type', $canon);
        }

        $availableStaff = $staffQuery->get();
        $selectedStaff = $availableStaff->firstWhere('id', (int) $staffId) ?? $availableStaff->first();

        if ($dossierType === 'month') {
            $start = Carbon::parse($month . '-01')->startOfMonth();
            $end = $start->copy()->endOfMonth();
        } else {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();

            if ($start->greaterThan($end)) {
                [$start, $end] = [$end, $start];
            }
        }

        $attendanceRows = [];
        $cursor = $start->copy();

        while ($cursor->lte($end)) {
            if ($cursor->isSunday()) {
                $cursor->addDay();
                continue;
            }

            if ($selectedStaff) {
                $dateString = $cursor->toDateString();
                $isProgrammed = $this->isStaffScheduledOnDate($selectedStaff, $cursor);
                $presence = StaffPresence::where('staff_id', $selectedStaff->id)
                    ->where('date', $dateString)
                    ->first();

                if ($isProgrammed || $selectedStaff->contract_type === 'permanent') {
                    $attendanceRows[] = [
                        'date' => $dateString,
                        'day' => $cursor->translatedFormat('l'),
                        'arrival_time' => $presence?->arrival_time ?? '—',
                        'departure_time' => $presence?->departure_time ?? '—',
                        'status' => $presence?->status ?? 'absent',
                        'note' => $presence?->note ?? 'Aucune observation',
                        'is_present' => ($presence?->status ?? 'absent') === 'present',
                    ];
                }
            }

            $cursor->addDay();
        }

        $staffByContract = Staff::active()->orderBy('last_name')->orderBy('first_name')
            ->get()
            ->groupBy('contract_type')
            ->map(fn ($items) => $items->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->full_name,
            ])->values()->all())
            ->all();

        return view('staff.presences.dossier', compact(
            'contractType',
            'staffId',
            'dossierType',
            'month',
            'startDate',
            'endDate',
            'availableStaff',
            'selectedStaff',
            'attendanceRows',
            'staffByContract',
            'start',
            'end',
        ));
    }

    protected function isStaffScheduledOnDate(Staff $staff, Carbon $date): bool
    {
        $dayOfWeek = (int) $date->isoWeekday();

        $scheduled = TimetableSlot::where('day_of_week', $dayOfWeek)
            ->whereHas('classSubject.teacherAssignments', function ($query) use ($staff) {
                $query->where('staff_id', $staff->id);
            })
            ->exists();

        if ($scheduled) {
            return true;
        }

        return $staff->contract_type === 'permanent';
    }
}
