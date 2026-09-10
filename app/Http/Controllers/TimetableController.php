<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTimetableSlotRequest;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassGroup;
use App\Models\ClassSubject;
use App\Models\SchoolAgreement;
use App\Models\SchoolPhone;
use App\Models\SchoolSetting;
use App\Models\Section;
use App\Models\Staff;
use App\Models\TeacherAssignment;
use App\Models\TimetableSetting;
use App\Models\TimetableSlot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class TimetableController extends Controller
{
    private const DAYS = [
        1 => 'Lundi',
        2 => 'Mardi',
        3 => 'Mercredi',
        4 => 'Jeudi',
        5 => 'Vendredi',
    ];

    public function index(Request $request)
    {
        $activeYear = AcademicYear::active();
        $setting = TimetableSetting::current();
        $grid = $this->buildGrid($setting);
        $sections = Section::orderBy('id')->get();

        $classes = $activeYear
            ? ClassGroup::where('academic_year_id', $activeYear->id)
                ->with(['level.section', 'academicYear'])
                ->withCount(['classSubjects', 'timetableSlots'])
                ->orderBy('name')
                ->get()
            : collect();

        $selectedClassId = $request->integer('class_id') ?: null;
        $selectedClass = null;

        if ($selectedClassId && $activeYear) {
            $selectedClass = ClassGroup::where('academic_year_id', $activeYear->id)
                ->with([
                    'level.section',
                    'academicYear',
                    'classSubjects' => fn ($query) => $query
                        ->where('is_active', true)
                        ->with(['subject', 'teacherAssignments.staff']),
                ])
                ->find($selectedClassId);
        }

        $slots = collect();
        $conflicts = collect();
        $summary = [
            'courses' => 0,
            'scheduled_hours' => 0,
            'expected_hours' => 0,
            'missing_hours' => 0,
        ];

        if ($selectedClass && $activeYear) {
            $slots = TimetableSlot::where('class_group_id', $selectedClass->id)
                ->where('academic_year_id', $activeYear->id)
                ->with([
                    'classGroup.level.section',
                    'classSubject.subject',
                    'classSubject.teacherAssignments.staff',
                ])
                ->orderBy('day_of_week')
                ->orderBy('period_index')
                ->orderBy('start_time')
                ->get();

            $conflicts = $this->detectTeacherConflicts($slots, $activeYear);
            $summary = $this->buildClassSummary($selectedClass, $slots, $setting);
        }

        return view('timetable.index', [
            'days' => self::DAYS,
            'gridRows' => $grid['rows'],
            'periodOptions' => $grid['periods'],
            'activeYear' => $activeYear,
            'setting' => $setting,
            'sections' => $sections,
            'classes' => $classes,
            'selectedClassId' => $selectedClassId,
            'selectedClass' => $selectedClass,
            'slots' => $slots,
            'conflicts' => $conflicts,
            'summary' => $summary,
        ]);
    }

    public function settings()
    {
        $setting = TimetableSetting::current();

        return view('timetable.settings', [
            'days' => self::DAYS,
            'setting' => $setting,
            'dayConfigs' => $this->normalizedDayConfigs($setting),
            'breaks' => collect($setting->breaks ?: [])->values()->all(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'period_duration_minutes' => ['required', 'integer', 'min:30', 'max:120'],
            'max_periods_per_day' => ['required', 'integer', 'min:1', 'max:14'],
            'days' => ['required', 'array'],
            'days.*.start_time' => ['required', 'date_format:H:i'],
            'days.*.active_periods' => ['required', 'integer', 'min:0', 'max:14'],
            'breaks' => ['nullable', 'array'],
            'breaks.*.start_time' => ['nullable', 'date_format:H:i'],
            'breaks.*.duration_minutes' => ['nullable', 'integer', 'min:5', 'max:120'],
        ]);

        $maxPeriods = (int) $validated['max_periods_per_day'];
        $dayConfigs = [];

        foreach (self::DAYS as $dayNumber => $dayName) {
            $dayInput = $validated['days'][$dayNumber] ?? [];
            $dayConfigs[(string) $dayNumber] = [
                'start_time' => $dayInput['start_time'] ?? '07:30',
                'active_periods' => min((int) ($dayInput['active_periods'] ?? $maxPeriods), $maxPeriods),
            ];
        }

        $breaks = collect($validated['breaks'] ?? [])
            ->filter(fn ($break) => filled($break['start_time'] ?? null) && filled($break['duration_minutes'] ?? null))
            ->map(fn ($break) => [
                'start_time' => $break['start_time'],
                'duration_minutes' => (int) $break['duration_minutes'],
            ])
            ->sortBy('start_time')
            ->values()
            ->all();

        $setting = TimetableSetting::current();
        $setting->update([
            'period_duration_minutes' => (int) $validated['period_duration_minutes'],
            'max_periods_per_day' => $maxPeriods,
            'day_configs' => $dayConfigs,
            'breaks' => $breaks,
        ]);

        return redirect()
            ->route('timetable.settings')
            ->with('success', 'Configuration de l’emploi du temps enregistrée.');
    }

    public function store(StoreTimetableSlotRequest $request)
    {
        $activeYear = AcademicYear::active();

        if (! $activeYear) {
            return back()->with('error', 'Aucune année scolaire active. Activez une année avant de créer un emploi du temps.');
        }

        $setting = TimetableSetting::current();
        $data = $request->validated();
        $classSubject = $this->resolveClassSubject($data, $activeYear);

        if (! $classSubject) {
            return back()->with('error', 'La matière choisie ne correspond pas à cette classe ou à l’année active.');
        }

        $periodWindow = $this->periodWindow($setting, (int) $data['day_of_week'], (int) $data['period_index'], (int) $data['periods_count']);

        if (! $periodWindow) {
            return back()->with('error', 'La période choisie dépasse la grille configurée pour ce jour.');
        }

        if ($this->classHasPeriodOverlap((int) $data['class_group_id'], (int) $data['day_of_week'], (int) $data['period_index'], (int) $data['periods_count'])) {
            return back()->with('error', 'Cette classe a déjà un cours programmé sur une ou plusieurs périodes sélectionnées.');
        }

        $teacherConflict = $this->findTeacherConflict(
            (int) $data['class_subject_id'],
            (int) $data['day_of_week'],
            (int) $data['period_index'],
            (int) $data['periods_count'],
            $activeYear
        );

        if ($teacherConflict) {
            return back()->with('error', $this->teacherConflictMessage($teacherConflict));
        }

        $slot = TimetableSlot::create([
            'academic_year_id' => $activeYear->id,
            'class_group_id' => $data['class_group_id'],
            'class_subject_id' => $data['class_subject_id'],
            'day_of_week' => $data['day_of_week'],
            'period_index' => $data['period_index'],
            'periods_count' => $data['periods_count'],
            'start_time' => $periodWindow['start'],
            'end_time' => $periodWindow['end'],
            'room' => $data['room'] ?? null,
        ]);

        AuditLog::log('timetable_slot_created', $slot);

        return redirect()
            ->route('timetable.index', ['class_id' => $slot->class_group_id])
            ->with('success', 'Créneau ajouté avec succès.');
    }

    public function update(StoreTimetableSlotRequest $request, TimetableSlot $slot)
    {
        $activeYear = AcademicYear::active();

        if (! $activeYear) {
            return back()->with('error', 'Aucune année scolaire active.');
        }

        $setting = TimetableSetting::current();
        $data = $request->validated();
        $classSubject = $this->resolveClassSubject($data, $activeYear);

        if (! $classSubject) {
            return back()->with('error', 'La matière choisie ne correspond pas à cette classe ou à l’année active.');
        }

        if ($slot->academic_year_id !== $activeYear->id) {
            return back()->with('error', 'Ce créneau n’appartient pas à l’année scolaire active.');
        }

        $periodWindow = $this->periodWindow($setting, (int) $data['day_of_week'], (int) $data['period_index'], (int) $data['periods_count']);

        if (! $periodWindow) {
            return back()->with('error', 'La période choisie dépasse la grille configurée pour ce jour.');
        }

        if ($this->classHasPeriodOverlap((int) $data['class_group_id'], (int) $data['day_of_week'], (int) $data['period_index'], (int) $data['periods_count'], $slot->id)) {
            return back()->with('error', 'Cette classe a déjà un cours programmé sur une ou plusieurs périodes sélectionnées.');
        }

        $teacherConflict = $this->findTeacherConflict(
            (int) $data['class_subject_id'],
            (int) $data['day_of_week'],
            (int) $data['period_index'],
            (int) $data['periods_count'],
            $activeYear,
            $slot->id
        );

        if ($teacherConflict) {
            return back()->with('error', $this->teacherConflictMessage($teacherConflict));
        }

        $slot->update([
            'class_group_id' => $data['class_group_id'],
            'class_subject_id' => $data['class_subject_id'],
            'day_of_week' => $data['day_of_week'],
            'period_index' => $data['period_index'],
            'periods_count' => $data['periods_count'],
            'start_time' => $periodWindow['start'],
            'end_time' => $periodWindow['end'],
            'room' => $data['room'] ?? null,
        ]);

        AuditLog::log('timetable_slot_updated', $slot);

        return redirect()
            ->route('timetable.index', ['class_id' => $slot->class_group_id])
            ->with('success', 'Créneau modifié avec succès.');
    }

    public function destroy(TimetableSlot $slot)
    {
        $classGroupId = $slot->class_group_id;
        $slot->delete();

        AuditLog::log('timetable_slot_deleted', null, ['id' => $slot->id]);

        return redirect()
            ->route('timetable.index', ['class_id' => $classGroupId])
            ->with('success', 'Créneau supprimé.');
    }

    public function teacher(Request $request)
    {
        $activeYear = AcademicYear::active();
        $setting = TimetableSetting::current();
        $grid = $this->buildGrid($setting);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isAdmin = $user->hasAnyRole(['super-admin', 'directeur', 'censeur', 'assistant-direction']);

        $staffList = $isAdmin
            ? Staff::teachers()->orderBy('last_name')->get()
            : collect($user->staff ? [$user->staff] : []);

        $selectedStaffId = $request->integer('staff_id') ?: $user->staff?->id;
        $selectedStaff = $staffList->firstWhere('id', $selectedStaffId)
            ?? ($isAdmin ? Staff::find($selectedStaffId) : null);

        $slots = collect();

        if ($selectedStaff && $activeYear) {
            $classSubjectIds = TeacherAssignment::where('staff_id', $selectedStaff->id)
                ->where('academic_year_id', $activeYear->id)
                ->pluck('class_subject_id');

            $slots = TimetableSlot::whereIn('class_subject_id', $classSubjectIds)
                ->where('academic_year_id', $activeYear->id)
                ->with(['classGroup.level.section', 'classSubject.subject'])
                ->orderBy('day_of_week')
                ->orderBy('period_index')
                ->orderBy('start_time')
                ->get();
        }

        $teacherSubjects = $this->teacherSubjectsFromSlots($slots);
        $teacherClasses = $this->teacherClassesFromSlots($slots);
        $totalHours = $this->hoursFromSlots($slots, $setting);

        return view('timetable.teacher', [
            'days' => self::DAYS,
            'gridRows' => $grid['rows'],
            'activeYear' => $activeYear,
            'setting' => $setting,
            'staffList' => $staffList,
            'selectedStaff' => $selectedStaff,
            'selectedStaffId' => $selectedStaffId,
            'slots' => $slots,
            'isAdmin' => $isAdmin,
            'teacherSubjects' => $teacherSubjects,
            'teacherClasses' => $teacherClasses,
            'teacherSubjectCount' => $teacherSubjects->count(),
            'totalHours' => $totalHours,
        ]);
    }

    public function printTeacher(Staff $staff)
    {
        $user = Auth::user();
        $canViewAnyStaff = $user->hasAnyRole(['super-admin', 'directeur', 'censeur', 'assistant-direction']);
        abort_unless($canViewAnyStaff || $user->staff?->is($staff), 403);

        $activeYear = AcademicYear::active();
        $setting = TimetableSetting::current();
        $grid = $this->buildGrid($setting);

        $slots = collect();

        if ($activeYear) {
            $classSubjectIds = TeacherAssignment::where('staff_id', $staff->id)
                ->where('academic_year_id', $activeYear->id)
                ->pluck('class_subject_id');

            $slots = TimetableSlot::whereIn('class_subject_id', $classSubjectIds)
                ->where('academic_year_id', $activeYear->id)
                ->with(['classGroup.level.section', 'classSubject.subject'])
                ->orderBy('day_of_week')
                ->orderBy('period_index')
                ->orderBy('start_time')
                ->get();
        }

        $teacherSubjects = $this->teacherSubjectsFromSlots($slots);
        $teacherClasses = $this->teacherClassesFromSlots($slots);

        return view('timetable.teacher-print', [
            'days' => self::DAYS,
            'gridRows' => $grid['rows'],
            'activeYear' => $activeYear,
            'setting' => $setting,
            'selectedStaff' => $staff,
            'slots' => $slots,
            'teacherSubjects' => $teacherSubjects,
            'teacherClasses' => $teacherClasses,
            'teacherSubjectCount' => $teacherSubjects->count(),
            'totalHours' => $this->hoursFromSlots($slots, $setting),
            'school' => SchoolSetting::instance(),
            'phones' => SchoolPhone::orderByDesc('is_primary')->get(),
            'agreements' => SchoolAgreement::orderBy('id')->get(),
        ]);
    }

    public function print(ClassGroup $classGroup)
    {
        $setting = TimetableSetting::current();
        $grid = $this->buildGrid($setting);
        $classGroup->load(['level.section', 'academicYear']);

        $slots = TimetableSlot::where('class_group_id', $classGroup->id)
            ->where('academic_year_id', $classGroup->academic_year_id)
            ->with(['classSubject.subject', 'classSubject.teacherAssignments.staff'])
            ->orderBy('day_of_week')
            ->orderBy('period_index')
            ->orderBy('start_time')
            ->get();

        return view('timetable.print', [
            'days' => self::DAYS,
            'gridRows' => $grid['rows'],
            'classGroup' => $classGroup,
            'slots' => $slots,
            'school' => SchoolSetting::instance(),
            'phones' => SchoolPhone::orderByDesc('is_primary')->get(),
            'agreements' => SchoolAgreement::orderBy('id')->get(),
        ]);
    }

    public function apiSubjects(Request $request)
    {
        $classGroup = ClassGroup::with([
            'classSubjects' => fn ($query) => $query
                ->where('is_active', true)
                ->with(['subject', 'teacherAssignments.staff']),
        ])->find($request->integer('class_id'));

        if (! $classGroup) {
            return response()->json(['subjects' => []]);
        }

        $subjects = $classGroup->classSubjects->map(fn ($classSubject) => [
            'id' => $classSubject->id,
            'label' => $classSubject->subject->code . ' - ' . $classSubject->subject->name_fr,
            'teacher' => $classSubject->teacherAssignments->first()?->staff?->full_name ?? 'Non assigné',
        ]);

        return response()->json(['subjects' => $subjects->values()]);
    }

    private function teacherSubjectsFromSlots(Collection $slots): Collection
    {
        return $slots
            ->map(fn (TimetableSlot $slot) => $slot->classSubject?->subject?->name_fr)
            ->filter()
            ->unique()
            ->values();
    }

    private function teacherClassesFromSlots(Collection $slots): Collection
    {
        return $slots
            ->map(fn (TimetableSlot $slot) => $slot->classGroup?->full_name)
            ->filter()
            ->unique()
            ->values();
    }

    private function hoursFromSlots(Collection $slots, TimetableSetting $setting): float
    {
        return round($slots->sum('periods_count'), 1);
    }

    private function resolveClassSubject(array $data, AcademicYear $activeYear): ?ClassSubject
    {
        return ClassSubject::where('id', $data['class_subject_id'])
            ->where('class_group_id', $data['class_group_id'])
            ->where('is_active', true)
            ->whereHas('classGroup', fn ($query) => $query->where('academic_year_id', $activeYear->id))
            ->first();
    }

    private function classHasPeriodOverlap(
        int $classGroupId,
        int $dayOfWeek,
        int $periodIndex,
        int $periodsCount,
        ?int $excludeSlotId = null
    ): bool {
        $endPeriod = $periodIndex + $periodsCount - 1;

        return TimetableSlot::where('class_group_id', $classGroupId)
            ->where('day_of_week', $dayOfWeek)
            ->when($excludeSlotId, fn ($query) => $query->where('id', '!=', $excludeSlotId))
            ->where('period_index', '<=', $endPeriod)
            ->whereRaw('(period_index + periods_count - 1) >= ?', [$periodIndex])
            ->exists();
    }

    private function findTeacherConflict(
        int $classSubjectId,
        int $dayOfWeek,
        int $periodIndex,
        int $periodsCount,
        AcademicYear $activeYear,
        ?int $excludeSlotId = null
    ): ?TimetableSlot {
        $teacherAssignment = TeacherAssignment::where('class_subject_id', $classSubjectId)
            ->where('academic_year_id', $activeYear->id)
            ->with('staff')
            ->first();

        if (! $teacherAssignment?->staff_id) {
            return null;
        }

        $teacherClassSubjectIds = TeacherAssignment::where('staff_id', $teacherAssignment->staff_id)
            ->where('academic_year_id', $activeYear->id)
            ->pluck('class_subject_id');

        $endPeriod = $periodIndex + $periodsCount - 1;

        return TimetableSlot::whereIn('class_subject_id', $teacherClassSubjectIds)
            ->where('academic_year_id', $activeYear->id)
            ->where('day_of_week', $dayOfWeek)
            ->when($excludeSlotId, fn ($query) => $query->where('id', '!=', $excludeSlotId))
            ->where('period_index', '<=', $endPeriod)
            ->whereRaw('(period_index + periods_count - 1) >= ?', [$periodIndex])
            ->with([
                'classGroup.level',
                'classSubject.subject',
                'classSubject.teacherAssignments.staff',
            ])
            ->first();
    }

    private function teacherConflictMessage(TimetableSlot $slot): string
    {
        $teacher = $slot->classSubject?->teacherAssignments?->first()?->staff?->full_name ?? 'Cet enseignant';
        $class = $slot->classGroup?->full_name ?? 'une autre classe';
        $subject = $slot->classSubject?->subject?->name_fr ?? 'une matière';
        $day = self::DAYS[$slot->day_of_week] ?? 'ce jour';
        $period = 'P' . $slot->period_index;
        $endPeriod = $slot->period_index + $slot->periods_count - 1;

        if ($endPeriod > $slot->period_index) {
            $period .= ' à P' . $endPeriod;
        }

        return "Conflit enseignant : {$teacher} est déjà programmé en {$class} ({$subject}) le {$day}, {$period}.";
    }

    private function detectTeacherConflicts(Collection $slots, ?AcademicYear $activeYear): Collection
    {
        if (! $activeYear) {
            return collect();
        }

        return $slots
            ->filter(fn (TimetableSlot $slot) => (bool) $this->findTeacherConflict(
                $slot->class_subject_id,
                $slot->day_of_week,
                (int) $slot->period_index,
                (int) $slot->periods_count,
                $activeYear,
                $slot->id
            ))
            ->pluck('id')
            ->values();
    }

    private function buildClassSummary(ClassGroup $classGroup, Collection $slots, TimetableSetting $setting): array
    {
        $expectedHours = (float) $classGroup->classSubjects->sum(fn ($classSubject) => (float) $classSubject->hours_per_week);
        $scheduledHours = round($slots->sum('periods_count'), 1);

        return [
            'courses' => $slots->count(),
            'scheduled_hours' => $scheduledHours,
            'expected_hours' => $expectedHours,
            'missing_hours' => max(round($expectedHours - $scheduledHours, 1), 0),
        ];
    }

    private function buildGrid(TimetableSetting $setting): array
    {
        $rows = [];
        $periods = [];

        for ($period = 1; $period <= $setting->max_periods_per_day; $period++) {
            $periods[$period] = 'P' . $period;
        }

        foreach (self::DAYS as $dayNumber => $dayName) {
            $dayRows = $this->buildDayRows($setting, $dayNumber);
            foreach ($dayRows as $row) {
                $key = $row['type'] === 'period' ? 'period_' . $row['period_index'] : 'break_' . $row['start'];
                $rows[$key]['type'] = $row['type'];
                $rows[$key]['label'] = $row['label'];
                $rows[$key]['period_index'] = $row['period_index'] ?? null;
                $rows[$key]['times'][$dayNumber] = $row;
            }
        }

        return [
            'rows' => array_values($rows),
            'periods' => $periods,
        ];
    }

    private function buildDayRows(TimetableSetting $setting, int $dayNumber): array
    {
        $dayConfig = $this->normalizedDayConfigs($setting)[$dayNumber];
        $cursor = $this->minutesFromTime($dayConfig['start_time']);
        $activePeriods = (int) $dayConfig['active_periods'];
        $breaks = collect($setting->breaks ?: [])
            ->map(fn ($break) => [
                'start' => $this->minutesFromTime($break['start_time']),
                'duration' => (int) $break['duration_minutes'],
            ])
            ->sortBy('start')
            ->values();
        $breakIndex = 0;
        $rows = [];

        for ($period = 1; $period <= $setting->max_periods_per_day; $period++) {
            while (isset($breaks[$breakIndex]) && $breaks[$breakIndex]['start'] <= $cursor) {
                $breakStart = $cursor;
                $breakEnd = $breakStart + $breaks[$breakIndex]['duration'];
                $rows[] = [
                    'type' => 'break',
                    'label' => 'Pause',
                    'start' => $this->timeFromMinutes($breakStart),
                    'end' => $this->timeFromMinutes($breakEnd),
                    'is_active' => $period <= $activePeriods,
                ];
                $cursor = $breakEnd;
                $breakIndex++;
            }

            $start = $cursor;
            $end = $cursor + $setting->period_duration_minutes;
            $rows[] = [
                'type' => 'period',
                'label' => 'P' . $period,
                'period_index' => $period,
                'start' => $this->timeFromMinutes($start),
                'end' => $this->timeFromMinutes($end),
                'is_active' => $period <= $activePeriods,
            ];
            $cursor = $end;
        }

        return $rows;
    }

    private function periodWindow(TimetableSetting $setting, int $dayNumber, int $periodIndex, int $periodsCount): ?array
    {
        $periodRows = collect($this->buildDayRows($setting, $dayNumber))
            ->where('type', 'period')
            ->keyBy('period_index');
        $lastPeriod = $periodIndex + $periodsCount - 1;

        if (! isset($periodRows[$periodIndex], $periodRows[$lastPeriod])) {
            return null;
        }

        if (! $periodRows[$periodIndex]['is_active'] || ! $periodRows[$lastPeriod]['is_active']) {
            return null;
        }

        return [
            'start' => $periodRows[$periodIndex]['start'],
            'end' => $periodRows[$lastPeriod]['end'],
        ];
    }

    private function normalizedDayConfigs(TimetableSetting $setting): array
    {
        $configs = $setting->day_configs ?: TimetableSetting::defaultDayConfigs();
        $normalized = [];

        foreach (self::DAYS as $dayNumber => $dayName) {
            $config = $configs[(string) $dayNumber] ?? $configs[$dayNumber] ?? [];
            $normalized[$dayNumber] = [
                'start_time' => $config['start_time'] ?? '07:30',
                'active_periods' => min((int) ($config['active_periods'] ?? $setting->max_periods_per_day), $setting->max_periods_per_day),
            ];
        }

        return $normalized;
    }

    private function minutesFromTime(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));

        return $hours * 60 + $minutes;
    }

    private function timeFromMinutes(int $minutes): string
    {
        return Carbon::createFromTime(0, 0)->addMinutes($minutes)->format('H:i');
    }
}
