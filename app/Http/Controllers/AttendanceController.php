<?php

namespace App\Http\Controllers;

use App\Models\Absence;
use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\StudentEnrollment;
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
        $isTeacher = $this->isTeachingAttendanceUser($user) && ! $isUnrestricted;
        $date = $isTeacher
            ? today()
            : Carbon::parse($request->input('date', now()->toDateString()));

        $classes = $activeYear
            ? ClassGroup::where('academic_year_id', $activeYear->id)
                ->when($isTeacher, fn ($query) => $query->whereHas(
                    'classSubjects.teacherAssignments', fn ($assignment) =>
                        $assignment->where('staff_id', $user?->staff?->id)
                            ->where('academic_year_id', $activeYear->id)
                ))
                ->with('level.section')
                ->orderBy('name')
                ->get()
            : collect();

        $selectedClass = $classes->firstWhere('id', (int) $request->input('class_id'));
        $enrollments = collect();
        $records = collect();

        if ($selectedClass) {
            $enrollments = StudentEnrollment::where([
                'class_group_id' => $selectedClass->id,
                'academic_year_id' => $activeYear->id,
                'status' => StudentEnrollment::STATUS_ACTIVE,
            ])->with('student')->get()->sortBy('student.last_name')->values();

            $records = Absence::whereDate('absence_date', $date)
                ->whereNull('timetable_slot_id')
                ->where('period', 'journée')
                ->whereIn('student_enrollment_id', $enrollments->pluck('id'))
                ->get()
                ->keyBy('student_enrollment_id');
        }

        return view('attendance.index', compact(
            'activeYear', 'date', 'classes', 'selectedClass',
            'enrollments', 'records', 'isUnrestricted'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $isUnrestricted = $this->canManageAllAttendance($user);
        $rules = [
            'class_group_id' => ['required', 'integer', 'exists:class_groups,id'],
            'absence_date' => ['required', 'date'],
            'attendance' => ['required', 'array'],
            'attendance.*.status' => ['required', 'in:present,absent'],
            'attendance.*.arrival_time' => ['nullable', 'date_format:H:i'],
            'attendance.*.observation' => ['nullable', 'string', 'max:1000'],
        ];
        if (! $isUnrestricted) {
            $rules['absence_date'][] = 'before_or_equal:today';
        }

        $data = $request->validate($rules);
        $activeYear = AcademicYear::active();
        $isTeacher = $this->isTeachingAttendanceUser($user) && ! $isUnrestricted;
        abort_unless($isUnrestricted || $isTeacher, 403, 'Vous n\'etes pas autorise a enregistrer cet appel.');
        $class = ClassGroup::where('academic_year_id', $activeYear?->id)
            ->findOrFail($data['class_group_id']);
        $date = Carbon::parse($data['absence_date']);

        abort_if($isTeacher && $date->toDateString() !== today()->toDateString(), 403,
            'Un enseignant ne peut enregistrer que l appel du jour.');
        abort_if($isTeacher && ! $class->classSubjects()->whereHas(
            'teacherAssignments', fn ($assignment) =>
                $assignment->where('staff_id', $user?->staff?->id)
                    ->where('academic_year_id', $activeYear?->id)
        )->exists(), 403, 'Cette classe ne vous est pas attribuee.');

        $enrollments = StudentEnrollment::where([
            'class_group_id' => $class->id,
            'academic_year_id' => $activeYear?->id,
            'status' => StudentEnrollment::STATUS_ACTIVE,
        ])->pluck('id');

        foreach ($enrollments as $enrollmentId) {
            $studentData = $data['attendance'][$enrollmentId] ?? ['status' => 'absent'];
            $status = $studentData['status'] === 'present' ? 'present' : 'absent';
            $arrival = $status === 'present' ? ($studentData['arrival_time'] ?? null) : null;
            $delayMinutes = 0;

            if ($arrival) {
                $arrivalAt = Carbon::createFromFormat('H:i', $arrival);
                $openingAt = Carbon::createFromFormat('H:i', '07:30');
                $delayMinutes = max(0, $openingAt->diffInMinutes($arrivalAt, false));
            }

            Absence::updateOrCreate(
                [
                    'student_enrollment_id' => $enrollmentId,
                    'absence_date' => $date->toDateString(),
                    'period' => 'journée',
                    'timetable_slot_id' => null,
                ],
                [
                    'status' => $status,
                    'arrival_time' => $arrival,
                    'observation' => $studentData['observation'] ?? null,
                    'delay_minutes' => $delayMinutes,
                    'class_subject_id' => null,
                    'timetable_period_index' => null,
                    'hours' => 0,
                    'is_justified' => false,
                    'recorded_by' => Auth::id(),
                ]
            );
        }

        return redirect()->route('attendance.index', [
            'class_id' => $class->id,
            'date' => $date->toDateString(),
        ])->with('success', "Appel enregistre pour {$enrollments->count()} eleve(s).");
    }

    private function canManageAllAttendance($user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->can('manage-absences')) {
            return true;
        }

        if ($user->hasAnyRole([
            'super-admin', 'directeur', 'censeur',
            'surveillant-general', 'assistant-direction',
        ])) {
            return true;
        }

        return $user->staff?->positions?->contains(
            fn ($position) => in_array($position->position, [
                'directeur', 'censeur', 'prefet_des_etudes', 'surveillant_general',
            ], true)
        ) ?? false;
    }

    private function isTeachingAttendanceUser($user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasRole('enseignant') || $user->can('enter-grades')) {
            return true;
        }

        return $user->staff?->positions?->contains(
            fn ($position) => $position->position === 'enseignant'
        ) ?? false;
    }
}
