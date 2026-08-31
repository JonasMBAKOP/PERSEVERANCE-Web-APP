<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\StudentEnrollment;
use App\Models\TimetableSlot;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $activeYear = AcademicYear::active();
        $user = Auth::user();
        $isUnrestricted = $this->canManageAllAttendance($user);
        $isTeacher = $user?->hasRole('enseignant') && ! $isUnrestricted;
        $staffId = $user?->staff?->id;
        $date = $isTeacher
            ? today()
            : Carbon::parse($request->input('date', now()->toDateString()));

        $classes = $activeYear
            ? ClassGroup::where('academic_year_id', $activeYear->id)
                ->when($isTeacher, fn ($query) => $query->whereHas(
                    'classSubjects.teacherAssignments', fn ($assignment) =>
                        $assignment->where('staff_id', $staffId)
                            ->where('academic_year_id', $activeYear->id)
                ))
                ->with('level.section')
                ->orderBy('name')
                ->get()
            : collect();

        $selectedClass = $classes->firstWhere('id', (int) $request->input('class_id'));
        $periods = collect();
        $enrollments = collect();
        $existing = [];

        if ($selectedClass) {
            $slots = TimetableSlot::where('academic_year_id', $activeYear->id)
                ->where('class_group_id', $selectedClass->id)
                ->where('day_of_week', $date->dayOfWeekIso)
                ->when($isTeacher, fn ($query) => $query->whereHas(
                    'classSubject.teacherAssignments', fn ($assignment) =>
                        $assignment->where('staff_id', $staffId)
                            ->where('academic_year_id', $activeYear->id)
                ))
                ->with('classSubject.subject')
                ->orderBy('period_index')
                ->get();

            foreach ($slots as $slot) {
                $count = max(1, (int) ($slot->periods_count ?: 1));
                for ($offset = 0; $offset < $count; $offset++) {
                    $index = (int) $slot->period_index + $offset;
                    $periods->push([
                        'key' => "slot-{$slot->id}-{$index}",
                        'slot_id' => $slot->id,
                        'index' => $index,
                        'label' => 'Période ' . $index,
                        'start' => $slot->start_time?->copy()->addHours($offset)->format('H:i'),
                        'end' => $slot->start_time?->copy()->addHours($offset + 1)->format('H:i'),
                        'subject' => $slot->classSubject?->subject?->name_fr ?? 'Cours non renseigné',
                        'class_subject_id' => $slot->class_subject_id,
                    ]);
                }
            }

            $enrollments = StudentEnrollment::where([
                'class_group_id' => $selectedClass->id,
                'academic_year_id' => $activeYear->id,
                'status' => StudentEnrollment::STATUS_ACTIVE,
            ])->with('student')->get()->sortBy('student.last_name')->values();

            if ($periods->isNotEmpty() && $enrollments->isNotEmpty()) {
                $absences = Absence::whereDate('absence_date', $date)
                    ->whereIn('student_enrollment_id', $enrollments->pluck('id'))
                    ->whereIn('timetable_slot_id', $periods->pluck('slot_id')->unique())
                    ->get();

                foreach ($absences as $absence) {
                    $existing[$absence->student_enrollment_id . ':' . $absence->timetable_slot_id . ':' . $absence->timetable_period_index] = true;
                }
            }
        }

        return view('attendance.index', compact(
            'activeYear', 'date', 'classes', 'selectedClass', 'periods', 'enrollments', 'existing', 'isUnrestricted'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $isUnrestricted = $this->canManageAllAttendance($user);
        $rules = [
            'class_group_id' => ['required', 'integer', 'exists:class_groups,id'],
            'absence_date' => ['required', 'date'],
            'periods' => ['required', 'array', 'min:1'],
            'periods.*' => ['required', 'regex:/^slot-[0-9]+-[0-9]+$/'],
            'attendance' => ['nullable', 'array'],
        ];
        if (! $isUnrestricted) $rules['absence_date'][] = 'before_or_equal:today';
        $validated = $request->validate($rules);

        $activeYear = AcademicYear::active();
        $isTeacher = $user?->hasRole('enseignant') && ! $isUnrestricted;
        $staffId = $user?->staff?->id;
        $class = ClassGroup::where('academic_year_id', $activeYear?->id)
            ->findOrFail($validated['class_group_id']);
        $date = Carbon::parse($validated['absence_date']);
        abort_if($isTeacher && $date->toDateString() !== today()->toDateString(), 403,
            'Un enseignant ne peut enregistrer que l’appel du jour.');
        abort_if($isTeacher && ! $class->classSubjects()->whereHas(
            'teacherAssignments', fn ($assignment) =>
                $assignment->where('staff_id', $staffId)
                    ->where('academic_year_id', $activeYear?->id)
        )->exists(), 403, 'Cette classe ne vous est pas attribuée.');
        $periods = $this->periodDefinitions($class, $date, $validated['periods'], $staffId, $isTeacher);
        $enrollments = StudentEnrollment::where([
            'class_group_id' => $class->id,
            'academic_year_id' => $activeYear?->id,
            'status' => StudentEnrollment::STATUS_ACTIVE,
        ])->pluck('id');
        $attendance = $request->input('attendance', []);
        $saved = 0;

        foreach ($enrollments as $enrollmentId) {
            foreach ($periods as $period) {
                $key = $enrollmentId . ':' . $period['slot_id'] . ':' . $period['index'];
                $query = Absence::where('student_enrollment_id', $enrollmentId)
                    ->whereDate('absence_date', $date)
                    ->where('timetable_slot_id', $period['slot_id'])
                    ->where('timetable_period_index', $period['index']);

                if (!empty($attendance[$enrollmentId][$period['key']])) {
                    Absence::updateOrCreate(
                        [
                            'student_enrollment_id' => $enrollmentId,
                            'absence_date' => $date->toDateString(),
                            'timetable_slot_id' => $period['slot_id'],
                            'timetable_period_index' => $period['index'],
                        ],
                        [
                            'period' => $period['label'],
                            'class_subject_id' => $period['class_subject_id'],
                            'hours' => 1,
                            'is_justified' => false,
                            'recorded_by' => Auth::id(),
                        ]
                    );
                    $saved++;
                } else {
                    $query->where('is_justified', false)->delete();
                }
            }
        }

        return redirect()->route('attendance.index', [
            'class_id' => $class->id,
            'date' => $date->toDateString(),
        ])->with('success', "Appel enregistré : {$saved} absence(s) sur les périodes sélectionnées.");
    }

    private function periodDefinitions(ClassGroup $class, Carbon $date, array $keys, ?int $staffId = null, bool $isTeacher = false)
    {
        $wanted = collect($keys)->values();
        $definitions = collect();
        $slots = TimetableSlot::where('academic_year_id', $class->academic_year_id)
            ->where('class_group_id', $class->id)
            ->where('day_of_week', $date->dayOfWeekIso)
            ->when($isTeacher, fn ($query) => $query->whereHas(
                'classSubject.teacherAssignments', fn ($assignment) =>
                    $assignment->where('staff_id', $staffId)
                        ->where('academic_year_id', $class->academic_year_id)
            ))
            ->with('classSubject.subject')
            ->get();

        foreach ($slots as $slot) {
            $count = max(1, (int) ($slot->periods_count ?: 1));
            for ($offset = 0; $offset < $count; $offset++) {
                $index = (int) $slot->period_index + $offset;
                $key = "slot-{$slot->id}-{$index}";
                if (!$wanted->contains($key)) continue;
                $definitions->push([
                    'key' => $key,
                    'slot_id' => $slot->id,
                    'index' => $index,
                    'label' => 'Période ' . $index,
                    'class_subject_id' => $slot->class_subject_id,
                ]);
            }
        }

        abort_if($definitions->isEmpty(), 422, 'Aucune période valide n’a été trouvée pour cette classe et cette date.');
        return $definitions;
    }
    private function canManageAllAttendance($user): bool
    {
        if (! $user) return false;
        if ($user->hasAnyRole(['super-admin', 'directeur', 'censeur', 'surveillant-general'])) return true;
        return $user->staff?->positions?->contains(fn ($position) => in_array($position->position, ['directeur', 'censeur', 'prefet_des_etudes', 'surveillant_general'], true)) ?? false;
    }
}
