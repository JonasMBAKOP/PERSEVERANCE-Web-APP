<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\InfirmaryVisit;
use App\Models\SchoolAgreement;
use App\Models\SchoolPhone;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentEnrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class InfirmaryController extends Controller
{
    public function index(Request $request): View
    {
        $activeYear = $this->currentAcademicYear();
        $selectedDate = $request->input('date');
        $selectedClass = $request->integer('class_group_id') ?: null;
        $visits = $this->visitsQuery($activeYear, $selectedDate, $selectedClass)
            ->orderByDesc('visit_date')
            ->orderByDesc('visit_time')
            ->paginate(20)
            ->withQueryString();

        $classes = $activeYear
            ? StudentEnrollment::active()
                ->where('academic_year_id', $activeYear->id)
                ->with('classGroup.level.section')
                ->get()
                ->pluck('classGroup')
                ->filter()
                ->unique('id')
                ->sortBy('full_name')
                ->values()
            : collect();

        return view('infirmary.index', [
            'activeYear' => $activeYear,
            'academicYearLabel' => $this->academicYearLabel($activeYear),
            'visits' => $visits,
            'classes' => $classes,
            'selectedDate' => $selectedDate,
            'selectedClass' => $selectedClass,
        ]);
    }

    public function dashboard(): View
    {
        $activeYear = $this->currentAcademicYear();
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();

        $baseQuery = InfirmaryVisit::query()
            ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id));

        $todayVisits = (clone $baseQuery)->whereDate('visit_date', $today)->count();
        $monthVisits = (clone $baseQuery)->whereBetween('visit_date', [$monthStart, $today])->count();
        $yearVisits = (clone $baseQuery)->count();
        $studentsSeen = (clone $baseQuery)->distinct('student_id')->count('student_id');

        $recentVisits = (clone $baseQuery)
            ->with(['student', 'classGroup'])
            ->orderByDesc('visit_date')
            ->orderByDesc('visit_time')
            ->limit(8)
            ->get();

        return view('dashboards.infirmary', [
            'activeYear' => $activeYear,
            'academicYearLabel' => $this->academicYearLabel($activeYear),
            'todayVisits' => $todayVisits,
            'monthVisits' => $monthVisits,
            'yearVisits' => $yearVisits,
            'studentsSeen' => $studentsSeen,
            'recentVisits' => $recentVisits,
        ]);
    }

    public function create(): View
    {
        $activeYear = $this->currentAcademicYear();
        $enrollments = $activeYear
            ? StudentEnrollment::active()
                ->where('academic_year_id', $activeYear->id)
                ->with(['student', 'classGroup.level.section'])
                ->get()
                ->sortBy(fn (StudentEnrollment $enrollment) => $enrollment->student?->full_name)
                ->values()
            : collect();

        return view('infirmary.create', [
            'activeYear' => $activeYear,
            'academicYearLabel' => $this->academicYearLabel($activeYear),
            'enrollments' => $enrollments,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateVisit($request);
        $activeYear = $this->currentAcademicYear();

        if (! $activeYear) {
            return back()->withInput()->with('error', 'Aucune annee scolaire active.');
        }

        $student = Student::findOrFail($data['student_id']);
        $enrollment = StudentEnrollment::active()
            ->where('academic_year_id', $activeYear->id)
            ->where('student_id', $student->id)
            ->with('classGroup.level.section')
            ->first();

        if (! $enrollment) {
            return back()->withInput()->with('error', 'Cet eleve n est pas inscrit dans l annee scolaire active.');
        }

        $this->createVisit($data, $student, $enrollment, $request);

        return redirect()->route('infirmary.index')->with('success', 'La consultation a ete enregistree.');
    }

    public function edit(InfirmaryVisit $visit): View
    {
        return view('infirmary.edit', compact('visit'));
    }

    public function update(Request $request, InfirmaryVisit $visit): RedirectResponse
    {
        $data = $request->validate([
            'visit_date' => ['required', 'date'],
            'visit_time' => ['required', 'date_format:H:i'],
            'temperature' => ['nullable', 'numeric', 'between:30,45'],
            'visit_reason' => ['required', 'string', 'max:3000'],
            'treatment' => ['nullable', 'string', 'max:3000'],
        ]);

        $visit->update([
            'visit_date' => $data['visit_date'],
            'visit_time' => $data['visit_time'],
            'temperature' => $data['temperature'] ?? null,
            'visit_reason' => trim($data['visit_reason']),
            'treatment' => filled($data['treatment'] ?? null) ? trim($data['treatment']) : null,
        ]);

        return redirect()->route('infirmary.index')->with('success', 'La consultation a ete modifiee.');
    }

    public function destroy(InfirmaryVisit $visit): RedirectResponse
    {
        $visit->delete();

        return redirect()->route('infirmary.index')->with('success', 'La consultation a ete supprimee.');
    }

    public function patients(Request $request): View
    {
        $search = trim((string) $request->input('q'));
        $patients = Student::query()
            ->whereHas('infirmaryVisits')
            ->withCount('infirmaryVisits')
            ->withMax('infirmaryVisits', 'visit_date')
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('matricule', 'like', "%{$search}%");
            }))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20)
            ->withQueryString();

        return view('infirmary.patients', compact('patients', 'search'));
    }

    public function patient(Student $student): View
    {
        $visits = $student->infirmaryVisits()
            ->with(['recordedBy', 'academicYear'])
            ->orderByDesc('visit_date')
            ->orderByDesc('visit_time')
            ->paginate(20);

        return view('infirmary.patient', compact('student', 'visits'));
    }

    public function print(Request $request): View
    {
        $activeYear = $this->currentAcademicYear();
        $selectedDate = $request->input('date');
        $selectedClass = $request->integer('class_group_id') ?: null;
        $visits = $this->visitsQuery($activeYear, $selectedDate, $selectedClass)
            ->orderByDesc('visit_date')
            ->orderByDesc('visit_time')
            ->get();

        $school = SchoolSetting::instance();
        $phones = SchoolPhone::orderByDesc('is_primary')->orderBy('id')->get();
        $agreements = SchoolAgreement::orderBy('cycle')->get();
        $classGroup = (object) ['academicYear' => $activeYear];
        $selectedClassLabel = $selectedClass ? ClassGroup::find($selectedClass)?->full_name : null;
        $filterLabel = collect([
            $selectedDate ? 'Date : ' . Carbon::parse($selectedDate)->format('d/m/Y') : null,
            $selectedClassLabel ? 'Classe : ' . $selectedClassLabel : null,
        ])->filter()->join(' - ') ?: 'Toutes les consultations';

        return view('infirmary.print', [
            'activeYear' => $activeYear,
            'academicYearLabel' => $this->academicYearLabel($activeYear),
            'visits' => $visits,
            'school' => $school,
            'phones' => $phones,
            'agreements' => $agreements,
            'classGroup' => $classGroup,
            'filterLabel' => $filterLabel,
        ]);
    }

    private function validateVisit(Request $request): array
    {
        return $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'visit_date' => ['required', 'date'],
            'visit_time' => ['required', 'date_format:H:i'],
            'temperature' => ['nullable', 'numeric', 'between:30,45'],
            'visit_reason' => ['required', 'string', 'max:3000'],
            'treatment' => ['nullable', 'string', 'max:3000'],
        ]);
    }

    private function createVisit(array $data, Student $student, StudentEnrollment $enrollment, Request $request): InfirmaryVisit
    {
        $classGroup = $enrollment->classGroup;
        $recorder = $request->user();

        return InfirmaryVisit::create([
            'academic_year_id' => $enrollment->academic_year_id,
            'student_id' => $student->id,
            'class_group_id' => $classGroup?->id,
            'recorded_by_staff_id' => $recorder?->staff?->id,
            'recorded_by_name' => $recorder?->staff?->full_name ?? $recorder?->name,
            'visit_date' => $data['visit_date'],
            'visit_time' => $data['visit_time'],
            'student_name' => trim($student->full_name),
            'student_gender' => $student->gender,
            'class_name' => $classGroup?->full_name,
            'student_age' => $student->date_of_birth ? Carbon::parse($student->date_of_birth)->age : null,
            'parent_phone' => $this->parentPhones($student),
            'temperature' => $data['temperature'] ?? null,
            'visit_reason' => trim($data['visit_reason']),
            'treatment' => filled($data['treatment'] ?? null) ? trim($data['treatment']) : null,
        ]);
    }

    private function visitsQuery(?AcademicYear $activeYear, ?string $date, ?int $classGroupId)
    {
        return InfirmaryVisit::with(['recordedBy', 'student', 'classGroup.level.section'])
            ->when($activeYear, fn ($query) => $query->where('academic_year_id', $activeYear->id))
            ->when($date, fn ($query) => $query->whereDate('visit_date', $date))
            ->when($classGroupId, fn ($query) => $query->where('class_group_id', $classGroupId));
    }

    private function parentPhones(Student $student): ?string
    {
        $phones = collect([
            $student->father_phone,
            $student->mother_phone,
            $student->guardian_phone,
        ])->filter(fn ($phone) => filled($phone))
            ->map(fn ($phone) => trim($phone))
            ->unique()
            ->values();

        return $phones->isNotEmpty() ? $phones->implode("\n") : null;
    }

    private function currentAcademicYear(): ?AcademicYear
    {
        return AcademicYear::active() ?? AcademicYear::query()->orderByDesc('start_date')->first();
    }

    private function academicYearLabel(?AcademicYear $academicYear): string
    {
        return $academicYear?->label ?: 'Annee scolaire non configuree';
    }
}
