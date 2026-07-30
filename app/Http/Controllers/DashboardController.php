<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassGroupRequest;
use App\Http\Requests\UpdateClassGroupRequest;
use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentPayment;
use App\Models\Level;
use App\Models\Section;
use App\Models\Staff;
use App\Models\TimetableSetting;
use App\Services\TimetableGridService;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

use App\Models\ClassSubject;
use App\Models\TeacherAssignment;
use App\Models\Sequence;
use App\Models\Grade;
use App\Models\Absence;
use App\Models\DisciplineIncident;
use App\Models\TimetableSlot;
use App\Models\Announcement;
use Carbon\Carbon;
use Illuminate\Support\Str;


class DashboardController extends Controller
{
    private TimetableGridService $timetableGridService;

    public function __construct(TimetableGridService $timetableGridService)
    {
        $this->timetableGridService = $timetableGridService;
    }

    // ── REDIRECTION SELON LE RÔLE ─────────────────────────────────────────
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole('super-admin'))              return redirect()->route('dashboard.admin');
        if ($user->hasRole('directeur'))                return redirect()->route('dashboard.directeur');
        if ($user->hasRole('fondateur'))                return redirect()->route('dashboard.directeur');
        if ($user->hasRole('censeur'))                  return redirect()->route('dashboard.censeur');
        if ($user->hasRole('econome'))                  return redirect()->route('dashboard.econome');
        if ($user->hasRole('enseignant'))               return redirect()->route('dashboard.enseignant');
        if ($user->hasRole('surveillant-general'))      return redirect()->route('dashboard.surveillant');
        if ($user->hasRole('surveillant-de-secteur'))   return redirect()->route('dashboard.surveillant-secteur');
        if ($user->hasRole('secretaire'))               return redirect()->route('dashboard.secretaire');

        return redirect()->route('dashboard.econome');
    }

    // ── SUPER ADMIN / DIRECTEUR ───────────────────────────────────────────
    public function admin()
    {
        return view('dashboard.admin');
    }

    public function directeur()
    {
        $activeYear = AcademicYear::active();

        if (!$activeYear) {
            return view('dashboards.directeur', ['activeYear' => null]);
        }

        // ── EFFECTIFS ──────────────────────────────────────────────────────
        $totalStudents = StudentEnrollment::where('academic_year_id', $activeYear->id)
            ->where('status', 'active')->count();
        $totalStaff     = Staff::where('is_active', true)->count();
        $totalTeachers  = Staff::where('is_active', true)
            ->whereHas('positions', fn($q) => $q->where('position', 'enseignant'))->count();
        $totalClasses   = ClassGroup::where('academic_year_id', $activeYear->id)->count();

        $bySection = Section::with(['levels.classGroups' => fn($q) =>
            $q->where('academic_year_id', $activeYear->id)
            ->withCount(['studentEnrollments as enrolled' => fn($q2) =>
                $q2->where('status', 'active')
            ])
        ])->orderBy('id')->get()->map(fn($s) => [
            'section' => $s,
            'count'   => $s->levels->flatMap->classGroups->sum('enrolled'),
        ]);

        // ── FINANCES ────────────────────────────────────────────────────────
        $classes = ClassGroup::where('academic_year_id', $activeYear->id)
            ->with('feeStructures.installments')
            ->withCount(['studentEnrollments as enrolled' => fn($q) => $q->where('status', 'active')])
            ->get();

        $totalExpected = 0;
        foreach ($classes as $c) {
            $fee = $c->feeStructures->first();
            $totalExpected += ($fee?->installments->sum('amount') ?? 0) * $c->enrolled;
        }
        $totalCollected = (int)StudentPayment::visible()->whereHas('studentEnrollment',
            fn($q) => $q->where('academic_year_id', $activeYear->id)
        )->sum('amount_paid');
        $collectionRate = $totalExpected > 0 ? round(($totalCollected / $totalExpected) * 100) : 0;

        $monthPeriods = $activeYear->monthPeriods();
        $monthlyRevenue = StudentPayment::visible()->whereHas('studentEnrollment',
            fn($q) => $q->where('academic_year_id', $activeYear->id)
        )->whereBetween('payment_date', [
            $activeYear->start_date->copy()->startOfMonth(),
            $activeYear->end_date->copy()->endOfMonth(),
        ])->selectRaw('YEAR(payment_date) y, MONTH(payment_date) m, SUM(amount_paid) total')
        ->groupBy('y','m')->orderBy('y')->orderBy('m')->get();

        $revenueChart = collect($monthPeriods)->map(function ($period) use ($monthlyRevenue) {
            $found = $monthlyRevenue->first(fn($r) => $r->y === $period['year'] && $r->m === $period['month']);
            return [
                'label' => $period['label'],
                'total' => $found ? (float)$found->total : 0,
                'year'  => $period['year'],
                'month' => $period['month'],
            ];
        });

        // ── ACADÉMIQUE ─────────────────────────────────────────────────────
        $currentSeq = $this->resolveCurrentSequence($activeYear, $classes);
        $gradeProgress = 0;
        if ($currentSeq) {
            $classesWithSubj = ClassGroup::where('academic_year_id', $activeYear->id)
                ->withCount([
                    'studentEnrollments as enrolled' => fn($q)=>$q->where('status','active'),
                    'classSubjects as subjects_count' => fn($q)=>$q->where('is_active',true),
                ])->get();
            $totalExp = $classesWithSubj->sum(fn($c)=>$c->enrolled*$c->subjects_count);
            $filled = Grade::where('sequence_id', $currentSeq->id)
                ->where(fn($q)=>$q->whereNotNull('grade')->orWhere('is_absent',true))->count();
            $gradeProgress = $totalExp>0 ? round(($filled/$totalExp)*100) : 0;
        }

        $sequences = Sequence::where('academic_year_id', $activeYear->id)->orderBy('number')->get();
        $trimesters = $activeYear->trimesters()->with('sequences')->get();
        $classGroups = ClassGroup::where('academic_year_id', $activeYear->id)
            ->with('level.section')->get();

        $resultsByClass = $classGroups->map(function ($class) use ($sequences, $trimesters, $activeYear) {
            $baseCriteria = fn($query) => $query
                ->whereHas('classSubject', fn($q) => $q
                    ->where('class_group_id', $class->id)
                    ->where('is_active', true))
                ->whereHas('studentEnrollment', fn($q) => $q
                    ->where('status', 'active')
                    ->where('academic_year_id', $activeYear->id))
                ->whereHas('sequence', fn($q) => $q
                    ->where('academic_year_id', $activeYear->id));

            $annualTotal = Grade::query()
                ->where($baseCriteria)
                ->whereNotNull('grade')
                ->count();

            $annualSuccess = Grade::query()
                ->where($baseCriteria)
                ->whereNotNull('grade')
                ->where('grade', '>=', 10)
                ->count();

            $annualAvg = Grade::query()
                ->where($baseCriteria)
                ->whereNotNull('grade')
                ->avg('grade') ?: 0;

            $sequenceResults = $sequences->map(function ($seq) use ($class, $baseCriteria) {
                $query = Grade::query()
                    ->where($baseCriteria)
                    ->where('sequence_id', $seq->id)
                    ->whereNotNull('grade');

                $count = $query->count();
                $successCount = Grade::query()
                    ->where($baseCriteria)
                    ->where('sequence_id', $seq->id)
                    ->whereNotNull('grade')
                    ->where('grade', '>=', 10)
                    ->count();

                return [
                    'id' => $seq->id,
                    'label' => $seq->label,
                    'success_pct' => $count > 0 ? round(($successCount / $count) * 100) : 0,
                    'avg_grade' => Grade::query()
                        ->where($baseCriteria)
                        ->where('sequence_id', $seq->id)
                        ->whereNotNull('grade')
                        ->avg('grade') ?: 0,
                ];
            });

            $trimesterResults = $trimesters->map(function ($trimester) use ($class, $baseCriteria) {
                $sequenceIds = $trimester->sequences->pluck('id');

                $count = Grade::query()
                    ->where($baseCriteria)
                    ->whereIn('sequence_id', $sequenceIds)
                    ->whereNotNull('grade')
                    ->count();

                $successCount = Grade::query()
                    ->where($baseCriteria)
                    ->whereIn('sequence_id', $sequenceIds)
                    ->whereNotNull('grade')
                    ->where('grade', '>=', 10)
                    ->count();

                return [
                    'id' => $trimester->id,
                    'label' => $trimester->label,
                    'success_pct' => $count > 0 ? round(($successCount / $count) * 100) : 0,
                    'avg_grade' => Grade::query()
                        ->where($baseCriteria)
                        ->whereIn('sequence_id', $sequenceIds)
                        ->whereNotNull('grade')
                        ->avg('grade') ?: 0,
                ];
            });

            return [
                'class' => $class,
                'annual' => [
                    'success_pct' => $annualTotal > 0 ? round(($annualSuccess / $annualTotal) * 100) : 0,
                    'avg_grade' => round($annualAvg, 2),
                ],
                'sequences' => $sequenceResults,
                'trimesters' => $trimesterResults,
            ];
        });

        $performanceScope = request('performance_scope', 'annuel');
        $performanceTargetId = request('performance_target_id');
        $sequenceTargetId = request('sequence_target_id');
        $trimesterTargetId = request('trimester_target_id');

        if ($performanceScope === 'sequence') {
            $performanceTargetId = $sequenceTargetId ?? $performanceTargetId;
        } elseif ($performanceScope === 'trimestre') {
            $performanceTargetId = $trimesterTargetId ?? $performanceTargetId;
        } else {
            $performanceTargetId = null;
        }

        if ($performanceScope === 'sequence' && !$performanceTargetId && $sequences->isNotEmpty()) {
            $performanceTargetId = $sequences->first()->id;
        }
        if ($performanceScope === 'trimestre' && !$performanceTargetId && $trimesters->isNotEmpty()) {
            $performanceTargetId = $trimesters->first()->id;
        }

        $chartResultsByClass = $resultsByClass->map(function ($row) use ($performanceScope, $performanceTargetId) {
            $meta = ['label' => 'Annuel', 'success_pct' => $row['annual']['success_pct'], 'avg_grade' => $row['annual']['avg_grade']];
            if ($performanceScope === 'sequence') {
                $found = $row['sequences']->firstWhere('id', $performanceTargetId);
                if ($found) {
                    $meta = ['label' => $found['label'], 'success_pct' => $found['success_pct'], 'avg_grade' => $found['avg_grade']];
                }
            } elseif ($performanceScope === 'trimestre') {
                $found = $row['trimesters']->firstWhere('id', $performanceTargetId);
                if ($found) {
                    $meta = ['label' => $found['label'], 'success_pct' => $found['success_pct'], 'avg_grade' => $found['avg_grade']];
                }
            }
            return [
                'class' => $row['class'],
                'success_pct' => $meta['success_pct'],
                'avg_grade' => $meta['avg_grade'],
                'label' => $meta['label'],
            ];
        })->sortByDesc('success_pct')->values();

        $performanceScopes = [
            'annuel' => 'Annuel',
            'trimestre' => 'Trimestre',
            'sequence' => 'Séquence',
        ];

        $recentEnrollments = StudentEnrollment::where('academic_year_id', $activeYear->id)
            ->with(['student', 'classGroup.level.section', 'enrollmentAudit.user'])
            ->orderByDesc('created_at')->take(8)->get();

        $recentActivities = AuditLog::with('user')
            ->orderByDesc('created_at')->take(6)->get()
            ->map(function ($a) {
                $title = null;
                $target = null;
                try {
                    // Grades-related actions logged with ClassGroup as model
                    if (str_starts_with($a->action, 'grades')) {
                        $title = 'Notes mises à jour';
                        if ($a->model_type === 'ClassGroup' && $a->model_id) {
                            $cg = ClassGroup::find($a->model_id);
                            $target = $cg?->full_name;
                        }
                    } elseif ($a->action === 'enrolled') {
                        $title = 'Inscription';
                        if ($a->model_type === 'StudentEnrollment' && $a->model_id) {
                            $enr = StudentEnrollment::with('classGroup')->find($a->model_id);
                            $target = $enr?->classGroup?->full_name ?? null;
                        }
                    } elseif ($a->action === 'payment_recorded') {
                        $title = 'Paiement enregistré';
                        if ($a->model_type === 'StudentPayment' && $a->model_id) {
                            $p = StudentPayment::with('studentEnrollment.student')->find($a->model_id);
                            $target = $p?->studentEnrollment?->student?->full_name ?? null;
                        }
                    } elseif (in_array($a->action, ['created','updated','deleted'])) {
                        $title = match ($a->action) {
                            'created' => 'Création',
                            'updated' => 'Modifications',
                            'deleted' => 'Suppression',
                            default => Str::ucfirst(str_replace(['_','-'], ' ', $a->action)),
                        };
                        if ($a->model_type) $target = $a->model_type;
                    } else {
                        $title = Str::ucfirst(str_replace(['_','-'], ' ', $a->action));
                        if ($a->model_type) $target = $a->model_type;
                    }
                } catch (\Throwable $e) {
                    $title = Str::ucfirst(str_replace(['_','-'], ' ', $a->action));
                }

                $message = $title . ($target ? ' · ' . $target : '');

                return (object) [
                    'id' => $a->id,
                    'message' => $message,
                    'user' => $a->user,
                    'created_at' => $a->created_at,
                    'raw' => $a,
                ];
            });

        // ── ABSENTÉISME & DISCIPLINE ─────────────────────────────────────────
        $monthAbsenceHours = (float)Absence::whereMonth('absence_date', now()->month)->sum('hours');
        $disciplinePending = DisciplineIncident::where('status', 'ouvert')->count();
        $disciplineThisMonth = DisciplineIncident::whereMonth('incident_date', now()->month)->count();

        // ── ALERTES (à traiter en priorité) ──────────────────────────────────
        $unconfiguredFees = $classes->filter(fn($c) => $c->feeStructures->isEmpty())->count();
        $debtorsCount = StudentEnrollment::where('academic_year_id', $activeYear->id)
            ->where('status', 'active')->get()
            ->filter(function($e) {
                $due = $e->classGroup->feeStructures->first()?->installments->sum('amount') ?? 0;
                $paid = StudentPayment::visible()
                    ->where('student_enrollment_id', $e->id)
                    ->sum(DB::raw('COALESCE(amount_paid, 0) + COALESCE(scholarship_amount, 0)'));
                return $paid < $due;
            })->count();

        return view('dashboards.directeur', compact(
            'activeYear', 'totalStudents', 'totalStaff', 'totalTeachers', 'totalClasses',
            'bySection', 'totalExpected', 'totalCollected', 'collectionRate', 'revenueChart',
            'currentSeq', 'gradeProgress', 'monthAbsenceHours',
            'disciplinePending', 'disciplineThisMonth',
            'unconfiguredFees', 'debtorsCount',
            'resultsByClass', 'chartResultsByClass', 'performanceScope', 'performanceTargetId', 'performanceScopes',
            'recentEnrollments', 'recentActivities',
            'trimesters', 'sequences'
        ));
    }

    // ── CENSEUR ───────────────────────────────────────────────────────────
    public function censeur()
    {
        $activeYear = AcademicYear::active();

        if (!$activeYear) {
            return view('dashboards.censeur', [
                'activeYear' => null, 'classes' => collect(),
                'classProgress' => collect(), 'currentSeq' => null,
                'criticalAbsences' => collect(), 'mySlots' => collect(),
                'sections' => collect(), 'selectedSectionId' => 0,
                'days' => [1=>'Lundi',2=>'Mardi',3=>'Mercredi',4=>'Jeudi',5=>'Vendredi'],
                'kpiNotesPending' => 0, 'kpiBulletinsToGenerate' => 0,
                'kpiAbsencesToday' => 0, 'kpiIncidentsThisMonth' => 0,
            ]);
        }

        $sections = Section::whereHas('levels.classGroups', fn($q) =>
            $q->where('academic_year_id', $activeYear->id)
        )->orderBy('name')->get();

        $selectedSectionId = (int) request('section_id');

        $classes = ClassGroup::where('academic_year_id', $activeYear->id)
            ->with('level.section')
            ->withCount([
                'studentEnrollments as enrolled' => fn($q) => $q->where('status', 'active'),
                'classSubjects as subjects_count' => fn($q) => $q->where('is_active', true),
            ]);

        if ($selectedSectionId > 0) {
            $classes = $classes->whereHas('level', fn($q) => $q->where('section_id', $selectedSectionId));
        }

        $classes = $classes->orderBy('name')->get();

        $currentSeq = $this->resolveCurrentSequence($activeYear, $classes);

        // ── Progression par classe (style "Bulletins — Avancement") ─────────
        $classProgress = $classes->map(function ($c) use ($currentSeq) {
            if (!$currentSeq) return null;
            $totalExpected = $c->enrolled * $c->subjects_count;
            $filled = Grade::whereHas('classSubject', fn($q) => $q->where('class_group_id', $c->id))
                ->where('sequence_id', $currentSeq->id)
                ->where(fn($q) => $q->whereNotNull('grade')->orWhere('is_absent', true))
                ->count();

            return [
                'class'    => $c,
                'pct'      => $totalExpected > 0 ? round(($filled / $totalExpected) * 100) : 0,
                'filled'   => $filled,
                'total'    => $totalExpected,
            ];
        })->filter()->sortByDesc('pct')->values();

        // ── KPI ────────────────────────────────────────────────────────────
        $kpiNotesPending = $classProgress->filter(fn($r) => $r['pct'] < 100)->count();

        $kpiBulletinsToGenerate = $currentSeq
            ? $classes->filter(function ($c) use ($currentSeq) {
                $expected = $c->enrolled;
                $generated = \App\Models\BulletinReport::where('sequence_id', $currentSeq->id)
                    ->whereHas('studentEnrollment', fn($q) => $q->where('class_group_id', $c->id))
                    ->count();
                return $generated < $expected;
            })->count()
            : 0;

        $kpiAbsencesToday = (float)Absence::whereDate('absence_date', today())->sum('hours');
        $kpiIncidentsThisMonth = DisciplineIncident::whereMonth('incident_date', now()->month)->count();

        // ── Absences critiques (>= 6h cumulées sur 30 jours, non justifiées) ──
        $criticalAbsences = Absence::where('absence_date', '>=', now()->subDays(30))
            ->where('is_justified', false)
            ->selectRaw('student_enrollment_id, SUM(hours) as total_hours')
            ->groupBy('student_enrollment_id')
            ->havingRaw('SUM(hours) >= 6')
            ->orderByDesc('total_hours')
            ->take(5)
            ->get()
            ->map(function ($row) {
                $enr = StudentEnrollment::with('student', 'classGroup')->find($row->student_enrollment_id);
                return $enr ? ['enrollment' => $enr, 'hours' => (float)$row->total_hours] : null;
            })->filter()->values();

        // ── Emploi du temps personnel (si le censeur enseigne aussi) ──────────
        $staff = Auth::user()->staff;
        $mySlots = collect();
        if ($staff) {
            $classSubjectIds = TeacherAssignment::where('staff_id', $staff->id)
                ->where('academic_year_id', $activeYear->id)
                ->pluck('class_subject_id');
            $mySlots = TimetableSlot::whereIn('class_subject_id', $classSubjectIds)
                ->where('academic_year_id', $activeYear->id)
                ->with('classGroup', 'classSubject.subject')
                ->orderBy('day_of_week')->orderBy('start_time')->get();
        }

        $setting = TimetableSetting::current();
        $grid = $this->timetableGridService->buildGrid($setting, [1=>'Lundi',2=>'Mardi',3=>'Mercredi',4=>'Jeudi',5=>'Vendredi']);

        return view('dashboards.censeur', compact(
            'activeYear', 'classes', 'classProgress', 'currentSeq',
            'criticalAbsences', 'mySlots', 'sections', 'selectedSectionId',
            'kpiNotesPending', 'kpiBulletinsToGenerate',
            'kpiAbsencesToday', 'kpiIncidentsThisMonth'
        ) + [
            'days' => [1=>'Lundi',2=>'Mardi',3=>'Mercredi',4=>'Jeudi',5=>'Vendredi'],
            'gridRows' => $grid['rows'],
        ]);
    }

    // ── ENSEIGNANT ────────────────────────────────────────────────────────
    public function enseignant()
    {
        $user       = Auth::user();
        $staff      = $user->staff;
        $activeYear = AcademicYear::active();
        $days       = [1=>'Lundi',2=>'Mardi',3=>'Mercredi',4=>'Jeudi',5=>'Vendredi'];

        if (!$staff || !$activeYear) {
            return view('dashboards.enseignant', [
                'noStaff' => !$staff, 'activeYear' => $activeYear,
                'myClasses' => collect(), 'mySlots' => collect(), 'days' => $days,
                'totalStudents' => 0, 'totalClasses' => 0, 'totalSubjects' => 0,
                'currentSeq' => null,
            ]);
        }

        $assignments = TeacherAssignment::where('staff_id', $staff->id)
            ->where('academic_year_id', $activeYear->id)
            ->with(['classSubject.subject', 'classSubject.classGroup.level.section'])
            ->get();

        $totalClasses  = $assignments->pluck('classSubject.class_group_id')->unique()->count();
        $totalSubjects = $assignments->pluck('classSubject.subject_id')->unique()->count();

        $classGroups = $assignments->pluck('classSubject.classGroup')->filter()->unique('id');
        $totalStudents = StudentEnrollment::whereIn('class_group_id', $classGroups->pluck('id'))
            ->where('status', 'active')->count();

        $currentSeq = $this->resolveCurrentSequence($activeYear);

        // ── Mes classes (cartes, avec statut séquence courante) ──────────────
        $myClasses = $assignments->map(function ($a) use ($currentSeq) {
            $cs = $a->classSubject;
            if (!$cs || !$cs->classGroup) return null;

            $enrolled = StudentEnrollment::where('class_group_id', $cs->class_group_id)
                ->where('status', 'active')->count();

            $seqStatus = null;
            if ($currentSeq) {
                $filled = Grade::where('class_subject_id', $cs->id)
                    ->where('sequence_id', $currentSeq->id)
                    ->where(fn($q) => $q->whereNotNull('grade')->orWhere('is_absent', true))
                    ->count();
                $isLocked = \App\Models\GradeLock::where([
                    'class_group_id' => $cs->class_group_id,
                    'sequence_id'    => $currentSeq->id,
                    'is_locked'      => true,
                ])->exists();

                $seqStatus = [
                    'label'   => $currentSeq->label,
                    'locked'  => $isLocked,
                    'complete'=> $enrolled > 0 && $filled >= $enrolled,
                    'filled'  => $filled,
                    'total'   => $enrolled,
                ];
            }

            return [
                'class_subject_id' => $cs->id,
                'class'            => $cs->classGroup,
                'subject'          => $cs->subject,
                'coefficient'      => $cs->coefficient,
                'enrolled'         => $enrolled,
                'seqStatus'        => $seqStatus,
            ];
        })->filter()->sortBy('class.name')->values();

        // ── Mon emploi du temps de la semaine ──────────────────────────────
        $classSubjectIds = $assignments->pluck('class_subject_id');
        $mySlots = TimetableSlot::whereIn('class_subject_id', $classSubjectIds)
            ->where('academic_year_id', $activeYear->id)
            ->with('classGroup', 'classSubject.subject')
            ->orderBy('day_of_week')->orderBy('start_time')->get();

        $setting = TimetableSetting::current();
        $grid = $this->timetableGridService->buildGrid($setting, $days);

        return view('dashboards.enseignant', compact(
            'activeYear', 'myClasses', 'mySlots', 'days',
            'totalClasses', 'totalSubjects', 'totalStudents', 'currentSeq'
        ) + ['noStaff' => false, 'gridRows' => $grid['rows']]);
    }

    // ── SURVEILLANT GÉNÉRAL ───────────────────────────────────────────────
    public function surveillant()
    {
        $activeYear = AcademicYear::active();

        // ── Absences du jour ─────────────────────────────────────────────
        $todayAbsences = Absence::whereDate('absence_date', today())
            ->with('studentEnrollment.student', 'studentEnrollment.classGroup')
            ->get();

        $todayHours    = (float)$todayAbsences->sum('hours');
        $todayStudents = $todayAbsences->pluck('student_enrollment_id')->unique()->count();

        // ── Liste détaillée des élèves absents aujourd'hui ───────────────
        $absentToday = $todayAbsences->groupBy('student_enrollment_id')->map(function ($g) {
            $enr = $g->first()->studentEnrollment;
            return [
                'enrollment'   => $enr,
                'hours'        => (float)$g->sum('hours'),
                'is_justified' => $g->every(fn($a) => $a->is_justified),
            ];
        })->filter(fn($r) => $r['enrollment'])->sortByDesc('hours')->values();

        // ── Semaine ────────────────────────────────────────────────────────
        $weekAbsences = Absence::whereBetween('absence_date', [
            now()->startOfWeek(), now()->endOfWeek(),
        ])->get();
        $weekHours       = (float)$weekAbsences->sum('hours');
        $weekUnjustified = (float)$weekAbsences->where('is_justified', false)->sum('hours');

        // ── Top absentéistes 30j (déplacé à côté de discipline) ─────────────
        $topAbsentees = Absence::where('absence_date', '>=', now()->subDays(30))
            ->selectRaw('student_enrollment_id, SUM(hours) as total_hours, SUM(CASE WHEN is_justified=0 THEN hours ELSE 0 END) as unjustified')
            ->groupBy('student_enrollment_id')
            ->orderByDesc('total_hours')->take(5)->get()
            ->map(function ($row) {
                $enr = StudentEnrollment::with('student', 'classGroup')->find($row->student_enrollment_id);
                return $enr ? [
                    'enrollment'  => $enr,
                    'total_hours' => (float)$row->total_hours,
                    'unjustified' => (float)$row->unjustified,
                ] : null;
            })->filter()->values();

        // ── Discipline ────────────────────────────────────────────────────
        $disciplineStats = [
            'pending'     => DisciplineIncident::where('status', 'ouvert')->count(),
            'thisWeek'    => DisciplineIncident::whereBetween('incident_date', [
                now()->startOfWeek(), now()->endOfWeek(),
            ])->count(),
            'suspensions' => DisciplineIncident::where('sanction_type', 'temporary_suspension')
                ->whereDate('incident_date', '>=', now()->subDays(30))->count(),
        ];

        $recentIncidents = DisciplineIncident::with('studentEnrollment.student', 'studentEnrollment.classGroup')
            ->orderByDesc('created_at')->take(5)->get();

        return view('dashboards.surveillant', compact(
            'activeYear', 'todayHours', 'todayStudents', 'absentToday',
            'weekHours', 'weekUnjustified', 'topAbsentees',
            'disciplineStats', 'recentIncidents'
        ));
    }

    // ── ÉCONOME ───────────────────────────────────────────────────────────
    // public function econome()
    // {
    //     $activeYear = AcademicYear::active();

    //     // ── Classes de l'année active ──────────────────────────────────
    //     $classes = $activeYear
    //         ? ClassGroup::where('academic_year_id', $activeYear->id)
    //             ->with(['level.section', 'feeStructures.installments'])
    //             ->withCount(['studentEnrollments as enrolled' => fn($q) =>
    //                 $q->where('status', 'active')
    //             ])
    //             ->orderBy('name')
    //             ->get()
    //         : collect();

    //     // ── KPI financiers ─────────────────────────────────────────────
    //     $totalExpected  = 0;
    //     $totalCollected = 0;
    //     $totalStudents  = 0;

    //     foreach ($classes as $class) {
    //         $fee             = $class->feeStructures->first();
    //         $feeTotal        = $fee?->installments->sum('amount') ?? 0;
    //         $totalExpected  += $feeTotal * $class->enrolled;
    //         $totalStudents  += $class->enrolled;
    //         $totalCollected += StudentPayment::whereHas(
    //             'studentEnrollment', fn($q) =>
    //                 $q->where('class_group_id', $class->id)
    //                   ->where('status', 'active')
    //         )->sum('amount_paid');
    //     }

    //     $collectionRate = $totalExpected > 0
    //         ? round(($totalCollected / $totalExpected) * 100)
    //         : 0;

    //     // ── Encaissements du jour et de la semaine ─────────────────────
    //     $todayTotal = StudentPayment::whereDate('payment_date', today())
    //                     ->sum('amount_paid');

    //     $weekTotal = StudentPayment::whereBetween('payment_date', [
    //                     now()->startOfWeek(), now()->endOfWeek(),
    //                  ])->sum('amount_paid');

    //     // ── Paiements récents ──────────────────────────────────────────
    //     $recentPayments = StudentPayment::with([
    //         'studentEnrollment.student',
    //         'studentEnrollment.classGroup',
    //         'feeInstallment',
    //         'recordedBy',
    //     ])->orderByDesc('payment_date')
    //       ->orderByDesc('created_at')
    //       ->take(8)
    //       ->get();

    //     // ── Débiteurs ──────────────────────────────────────────────────
    //     $debtors             = 0;
    //     $outstandingStudents = collect();

    //     if ($activeYear) {
    //         StudentEnrollment::where('academic_year_id', $activeYear->id)
    //             ->where('status', 'active')
    //             ->with(['student', 'classGroup.feeStructures.installments'])
    //             ->chunk(200, function($enrollments) use (
    //                 &$debtors, &$outstandingStudents
    //             ) {
    //                 foreach ($enrollments as $enr) {
    //                     $due  = $enr->classGroup
    //                                 ->feeStructures->first()
    //                                 ?->installments->sum('amount') ?? 0;
    //                     $paid = StudentPayment::where(
    //                                 'student_enrollment_id', $enr->id
    //                             )->sum('amount_paid');

    //                     if ($paid < $due) {
    //                         $debtors++;
    //                         $outstandingStudents->push([
    //                             'enrollment' => $enr,
    //                             'due'        => $due,
    //                             'paid'       => $paid,
    //                             'remaining'  => $due - $paid,
    //                         ]);
    //                     }
    //                 }
    //             });

    //         $outstandingStudents = $outstandingStudents
    //             ->sortByDesc('remaining')
    //             ->take(5)
    //             ->values();
    //     }

    //     // ── Classes sans frais configurés ──────────────────────────────
    //     $classesWithoutFees = $activeYear
    //         ? ClassGroup::where('academic_year_id', $activeYear->id)
    //             ->whereDoesntHave('feeStructures')
    //             ->with('level.section')
    //             ->get()
    //         : collect();

    //     // ── Stats par mode de paiement ─────────────────────────────────
    //     $paymentByMethod = StudentPayment::when($activeYear, fn($q) =>
    //         $q->whereHas('studentEnrollment', fn($q2) =>
    //             $q2->where('academic_year_id', $activeYear->id)
    //         )
    //     )->selectRaw('payment_method, SUM(amount_paid) as total, COUNT(*) as count')
    //      ->groupBy('payment_method')
    //      ->get()
    //      ->keyBy('payment_method');

    //     return view('dashboards.econome', compact(
    //         'activeYear',
    //         'classes',
    //         'totalExpected',
    //         'totalCollected',
    //         'totalStudents',
    //         'collectionRate',
    //         'todayTotal',
    //         'weekTotal',
    //         'recentPayments',
    //         'debtors',
    //         'outstandingStudents',
    //         'classesWithoutFees',
    //         'paymentByMethod'
    //     ));
    // }
    // public function econome()
    // {
    //     $activeYear = AcademicYear::active();
    //     $userId     = Auth::id();

    //     // ── FRAIS ATTENDUS (tous élèves, toutes classes) ───────────────────
    //     $classes = $activeYear
    //         ? ClassGroup::where('academic_year_id', $activeYear->id)
    //             ->with(['feeStructures.installments'])
    //             ->withCount(['studentEnrollments as enrolled' => fn($q) =>
    //                 $q->where('status', 'active')
    //             ])
    //             ->get()
    //         : collect();

    //     $totalExpected = 0;
    //     foreach ($classes as $class) {
    //         $fee           = $class->feeStructures->first();
    //         $feeTotal      = $fee?->installments->sum('amount') ?? 0;
    //         $totalExpected += $feeTotal * $class->enrolled;
    //     }

    //     // ── PAIEMENTS PAR CETTE ÉCONOME UNIQUEMENT ─────────────────────────
    //     $todayPayments = StudentPayment::where('recorded_by', $userId)
    //         ->whereDate('payment_date', today())->get();
    //     $todayAmount   = $todayPayments->sum('amount_paid');
    //     $todayCount    = $todayPayments->count();

    //     $weekAmount = StudentPayment::where('recorded_by', $userId)
    //         ->whereBetween('payment_date', [
    //             now()->startOfWeek(), now()->endOfWeek()
    //         ])->sum('amount_paid');

    //     // Derniers paiements enregistrés par elle
    //     $recentPayments = StudentPayment::where('recorded_by', $userId)
    //         ->with([
    //             'studentEnrollment.student',
    //             'studentEnrollment.classGroup',
    //             'feeInstallment',
    //         ])
    //         ->orderByDesc('payment_date')
    //         ->orderByDesc('created_at')
    //         ->take(5)->get();

    //     // ── COLLECTE MENSUELLE (par elle, 6 derniers mois) ─────────────────
    //     $monthlyRaw = StudentPayment::where('recorded_by', $userId)
    //         ->selectRaw(
    //             'YEAR(payment_date) as year,
    //             MONTH(payment_date) as month,
    //             SUM(amount_paid) as total,
    //             COUNT(*) as count'
    //         )
    //         ->where('payment_date', '>=', now()->subMonths(5)->startOfMonth())
    //         ->groupBy('year', 'month')
    //         ->orderBy('year')->orderBy('month')
    //         ->get();

    //     $monthLabels = ['Jan','Fév','Mar','Avr','Mai','Juin',
    //                     'Juil','Aoû','Sep','Oct','Nov','Déc'];
    //     $chartData   = [];

    //     for ($i = 5; $i >= 0; $i--) {
    //         $date  = now()->subMonths($i);
    //         $y     = (int) $date->format('Y');
    //         $m     = (int) $date->format('m');
    //         $found = $monthlyRaw->first(
    //             fn($r) => $r->year == $y && $r->month == $m
    //         );
    //         $chartData[] = [
    //             'label' => $monthLabels[$m - 1],
    //             'total' => $found ? (float) $found->total : 0,
    //             'count' => $found ? (int)   $found->count : 0,
    //         ];
    //     }

    //     // ── INSCRIPTIONS ───────────────────────────────────────────────────
    //     $totalEnrolled = $activeYear
    //         ? StudentEnrollment::where('academic_year_id', $activeYear->id)
    //             ->where('status', 'active')->count()
    //         : 0;

    //     $newEnrollmentsWeek = $activeYear
    //         ? StudentEnrollment::where('academic_year_id', $activeYear->id)
    //             ->whereBetween('created_at', [
    //                 now()->startOfWeek(), now()->endOfWeek()
    //             ])->count()
    //         : 0;

    //     // Réinscriptions en attente = anciens élèves pas encore inscrits cette année
    //     $pendingReEnrollments = 0;
    //     if ($activeYear) {
    //         $prevYear = AcademicYear::where('is_active', false)
    //             ->where('id', '<', $activeYear->id)
    //             ->orderByDesc('id')->first();

    //         if ($prevYear) {
    //             $prevIds    = StudentEnrollment::where('academic_year_id', $prevYear->id)
    //                 ->where('status', 'active')->pluck('student_id');
    //             $currentIds = StudentEnrollment::where('academic_year_id', $activeYear->id)
    //                 ->pluck('student_id');
    //             $pendingReEnrollments = $prevIds->diff($currentIds)->count();
    //         }
    //     }

    //     // Inscriptions récentes
    //     $recentEnrollments = $activeYear
    //         ? StudentEnrollment::where('academic_year_id', $activeYear->id)
    //             ->with(['student', 'classGroup.level.section'])
    //             ->orderByDesc('created_at')
    //             ->take(6)->get()
    //         : collect();

    //     return view('dashboards.econome', compact(
    //         'activeYear',
    //         'totalExpected',
    //         'todayAmount', 'todayCount', 'weekAmount',
    //         'recentPayments',
    //         'chartData',
    //         'totalEnrolled', 'newEnrollmentsWeek', 'pendingReEnrollments',
    //         'recentEnrollments'
    //     ));
    // }
    public function econome()
    {
        $activeYear = AcademicYear::active();
        $userId     = Auth::id();

        // ── FRAIS ATTENDUS ─────────────────────────────────────────────────
        $classes = $activeYear
            ? ClassGroup::where('academic_year_id', $activeYear->id)
                ->with(['feeStructures.installments'])
                ->withCount([
                    'studentEnrollments as enrolled' => fn($q) =>
                        $q->where('status', 'active'),
                ])
                ->get()
            : collect();

        $totalExpected = 0;
        $totalStudents = 0;

        foreach ($classes as $class) {
            $fee           = $class->feeStructures->first();
            $feeTotal      = $fee?->installments->sum('amount') ?? 0;
            $totalExpected += $feeTotal * ($class->enrolled ?? 0);
            $totalStudents += $class->enrolled ?? 0;
        }

        $totalCollected = $activeYear
            ? (int) StudentPayment::visible()->whereHas('studentEnrollment', fn($q) =>
                $q->where('academic_year_id', $activeYear->id)
            )->sum('amount_paid')
            : 0;

        $collectionRate = $totalExpected > 0
            ? round(($totalCollected / $totalExpected) * 100)
            : 0;

        // ── PAIEMENTS PAR CET UTILISATEUR ─────────────────────────────────
        $todayPayments = StudentPayment::visible()
            ->where('recorded_by', $userId)
            ->whereDate('payment_date', today())
            ->get();
        $todayAmount   = (int)$todayPayments->sum('amount_paid');
        $todayCount    = $todayPayments->count();

        $weekAmount = (int)StudentPayment::visible()
            ->where('recorded_by', $userId)
            ->whereBetween('payment_date', [
                now()->startOfWeek(), now()->endOfWeek()
            ])->sum('amount_paid');

        $recentPayments = StudentPayment::visible()
            ->where('recorded_by', $userId)
            ->with([
                'studentEnrollment.student',
                'studentEnrollment.classGroup',
                'feeInstallment',
            ])
            ->orderByDesc('payment_date')
            ->orderByDesc('created_at')
            ->take(5)->get();

        // ── COLLECTE MENSUELLE (6 mois, par cet utilisateur) ──────────────
        $monthLabels = ['Jan','Fév','Mar','Avr','Mai','Juin',
                        'Juil','Aoû','Sep','Oct','Nov','Déc'];

        $monthlyRaw = StudentPayment::visible()
            ->where('recorded_by', $userId)
            ->selectRaw(
                'YEAR(payment_date)  AS yr,
                MONTH(payment_date) AS mo,
                SUM(amount_paid)    AS total,
                COUNT(*)            AS cnt'
            )
            ->where('payment_date', '>=', now()->subMonths(5)->startOfMonth())
            ->groupBy('yr', 'mo')
            ->orderBy('yr')->orderBy('mo')
            ->get();

        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date  = now()->subMonths($i);
            $y     = (int)$date->format('Y');
            $m     = (int)$date->format('m');
            $found = $monthlyRaw->first(fn($r) => $r->yr == $y && $r->mo == $m);
            $chartData[] = [
                'label' => $monthLabels[$m - 1],
                'year'  => $y,
                'month' => $m,
                'total' => $found ? (float)$found->total : 0,
                'count' => $found ? (int)  $found->cnt   : 0,
            ];
        }

        // ── INSCRIPTIONS ───────────────────────────────────────────────────
        $totalEnrolled = $activeYear
            ? StudentEnrollment::where('academic_year_id', $activeYear->id)
                ->where('status', 'active')->count()
            : 0;

        $newEnrollmentsWeek = $activeYear
            ? StudentEnrollment::where('academic_year_id', $activeYear->id)
                ->whereBetween('created_at', [
                    now()->startOfWeek(), now()->endOfWeek()
                ])->count()
            : 0;

        // ── RÉINSCRIPTIONS EN ATTENTE ──────────────────────────────────────
        $pendingReEnrollments = 0;
        if ($activeYear) {
            $prevYear = AcademicYear::where('is_active', false)
                ->where('id', '<', $activeYear->id)
                ->orderByDesc('id')->first();

            if ($prevYear) {
                $prevIds    = StudentEnrollment::where('academic_year_id', $prevYear->id)
                    ->where('status', 'active')->pluck('student_id');
                $currentIds = StudentEnrollment::where('academic_year_id', $activeYear->id)
                    ->pluck('student_id');
                $pendingReEnrollments = $prevIds->diff($currentIds)->count();
            }
        }

        // ── INSCRIPTIONS RÉCENTES ──────────────────────────────────────────
        $recentEnrollments = $activeYear
            ? StudentEnrollment::where('academic_year_id', $activeYear->id)
                ->with(['student', 'classGroup.level.section'])
                ->orderByDesc('created_at')->take(6)->get()
            : collect();

        return view('dashboards.econome', compact(
            'activeYear',
            'totalExpected', 'totalCollected', 'collectionRate',
            'todayAmount', 'todayCount', 'weekAmount',
            'recentPayments',
            'chartData',
            'totalEnrolled', 'newEnrollmentsWeek', 'pendingReEnrollments',
            'recentEnrollments',
            'totalStudents'
        ));
    }

    /**
     * Détermine la séquence "en cours" : la première séquence non verrouillée
     * et non complète (toutes notes saisies). Si toutes sont verrouillées/complètes,
     * retourne la dernière.
     */
    private function resolveCurrentSequence(AcademicYear $activeYear, ?\Illuminate\Support\Collection $classes = null): ?Sequence
    {
        $sequences = Sequence::where('academic_year_id', $activeYear->id)
            ->orderBy('number')->get();

        if ($sequences->isEmpty()) return null;

        $classes = $classes ?? ClassGroup::where('academic_year_id', $activeYear->id)
            ->withCount([
                'studentEnrollments as enrolled' => fn($q) => $q->where('status', 'active'),
                'classSubjects as subjects_count' => fn($q) => $q->where('is_active', true),
            ])->get();

        foreach ($sequences as $seq) {
            $isLocked = \App\Models\GradeLock::where('sequence_id', $seq->id)
                ->where('is_locked', true)->exists();

            $totalExpected = $classes->sum(fn($c) => $c->enrolled * $c->subjects_count);
            $totalFilled = Grade::where('sequence_id', $seq->id)
                ->where(fn($q) => $q->whereNotNull('grade')->orWhere('is_absent', true))
                ->count();

            $isComplete = $totalExpected > 0 && $totalFilled >= $totalExpected;

            if (!$isLocked && !$isComplete) {
                return $seq; // première séquence encore "ouverte" → c'est la courante
            }
        }

        return $sequences->last(); // tout est bouclé → dernière séquence
    }

    // ── SURVEILLANT DE SECTEUR ────────────────────────────────────────────
    public function surveillantSecteur()
    {
        $activeYear = AcademicYear::active();

        // Absences du jour
        $todayAbsences = Absence::whereDate('absence_date', today())
            ->with('studentEnrollment.student', 'studentEnrollment.classGroup')
            ->get();

        $todayHours    = (float)$todayAbsences->sum('hours');
        $todayStudents = $todayAbsences->pluck('student_enrollment_id')->unique()->count();

        $absentToday = $todayAbsences->groupBy('student_enrollment_id')->map(function ($g) {
            $enr = $g->first()->studentEnrollment;
            return [
                'enrollment'   => $enr,
                'hours'        => (float)$g->sum('hours'),
                'is_justified' => $g->every(fn($a) => $a->is_justified),
            ];
        })->filter(fn($r) => $r['enrollment'])->sortByDesc('hours')->values();

        // Semaine
        $weekAbsences    = Absence::whereBetween('absence_date', [now()->startOfWeek(), now()->endOfWeek()])->get();
        $weekHours       = (float)$weekAbsences->sum('hours');
        $weekUnjustified = (float)$weekAbsences->where('is_justified', false)->sum('hours');

        // Top absentéistes 30j
        $topAbsentees = Absence::where('absence_date', '>=', now()->subDays(30))
            ->selectRaw('student_enrollment_id, SUM(hours) as total_hours, SUM(CASE WHEN is_justified=0 THEN hours ELSE 0 END) as unjustified')
            ->groupBy('student_enrollment_id')
            ->orderByDesc('total_hours')->take(5)->get()
            ->map(function ($row) {
                $enr = StudentEnrollment::with('student', 'classGroup')->find($row->student_enrollment_id);
                return $enr ? [
                    'enrollment'  => $enr,
                    'total_hours' => (float)$row->total_hours,
                    'unjustified' => (float)$row->unjustified,
                ] : null;
            })->filter()->values();

        // Discipline
        $disciplinePending = DisciplineIncident::where('status', 'ouvert')->count();

        $recentIncidents = DisciplineIncident::with('studentEnrollment.student', 'studentEnrollment.classGroup')
            ->orderByDesc('created_at')->take(5)->get();

        // Élèves totaux
        $totalStudents = $activeYear
            ? StudentEnrollment::where('academic_year_id', $activeYear->id)->where('status', 'active')->count()
            : 0;

        return view('dashboards.surveillant_secteur', compact(
            'activeYear', 'todayHours', 'todayStudents', 'absentToday',
            'weekHours', 'weekUnjustified', 'topAbsentees',
            'disciplinePending', 'recentIncidents', 'totalStudents'
        ));
    }

    // ── SECRÉTAIRE ───────────────────────────────────────────────────────
    public function secretaire()
    {
        $activeYear = AcademicYear::active();

        $totalStudents = $activeYear
            ? StudentEnrollment::where('academic_year_id', $activeYear->id)->where('status', 'active')->count()
            : 0;

        $totalStaff = Staff::where('is_active', true)->count();

        $totalClasses = $activeYear
            ? ClassGroup::where('academic_year_id', $activeYear->id)->count()
            : 0;

        $pinnedAnnouncements = Announcement::published()->pinned()->count();

        $recentAnnouncements = Announcement::published()
            ->with('author')
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->take(6)->get();

        $recentEnrollments = $activeYear
            ? StudentEnrollment::where('academic_year_id', $activeYear->id)
                ->with(['student', 'classGroup'])
                ->orderByDesc('created_at')->take(8)->get()
            : collect();

        $staffList = Staff::where('is_active', true)
            ->with('positions')
            ->orderBy('last_name')->take(10)->get();

        return view('dashboards.secretaire', compact(
            'activeYear', 'totalStudents', 'totalStaff', 'totalClasses',
            'pinnedAnnouncements', 'recentAnnouncements',
            'recentEnrollments', 'staffList'
        ));
    }
}
