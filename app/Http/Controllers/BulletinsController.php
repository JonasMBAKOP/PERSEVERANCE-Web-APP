<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\BulletinReport;
use App\Models\ClassGroup;
use App\Models\SchoolPhone;
use App\Models\SchoolSetting;
use App\Models\Section;
use App\Models\Sequence;
use App\Services\BulletinService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BulletinsController extends Controller
{
    public function __construct(
        private readonly BulletinService $bulletins
    ) {
        $user = auth()->user();
        if ($user?->isTeacherOnly()) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $activeYear = AcademicYear::active();
        $sequences  = collect();
        $sections   = collect();

        if ($activeYear) {
            $sequences = Sequence::where('academic_year_id', $activeYear->id)
                ->with('trimester')
                ->orderBy('number')
                ->get();

            $sections = Section::with([
                'levels.classGroups' => fn ($q) =>
                    $q->where('academic_year_id', $activeYear->id)
                      ->withCount([
                          'studentEnrollments as enrolled' => fn ($q2) =>
                              $q2->where('status', 'active'),
                      ])
                      ->orderBy('name'),
            ])->orderBy('id')->get();
        }

        $bulletinCounts = BulletinReport::selectRaw(
            'bulletin_reports.sequence_id,
             student_enrollments.class_group_id,
             COUNT(*) as cnt'
        )
        ->join('student_enrollments',
            'student_enrollments.id', '=', 'bulletin_reports.student_enrollment_id')
        ->where('bulletin_reports.type', BulletinService::TYPE_SEQUENTIAL)
        ->groupBy('bulletin_reports.sequence_id', 'student_enrollments.class_group_id')
        ->get()
        ->keyBy(fn ($row) => $row->class_group_id . '_' . $row->sequence_id);

        return view('bulletins.index', compact(
            'activeYear', 'sequences', 'sections', 'bulletinCounts'
        ));
    }

    public function generate(Request $request)
    {
        $request->validate([
            'class_group_id' => 'required|exists:class_groups,id',
            'sequence_id'    => 'required|exists:sequences,id',
        ]);

        $classGroup = ClassGroup::findOrFail($request->class_group_id);
        $sequence   = Sequence::with('trimester')->findOrFail($request->sequence_id);

        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        if (! $this->bulletins->isSequenceLocked($classGroup, $sequence)
            && ! $authUser->hasAnyRole(['super-admin', 'directeur', 'censeur'])) {
            return back()->with('error',
                'Les notes doivent être verrouillées avant de générer les bulletins.');
        }

        $result = $this->bulletins->generateSequential(
            $classGroup,
            $sequence,
            (int) Auth::id()
        );

        AuditLog::log('bulletins_generated', $classGroup);

        return redirect()
            ->route('bulletins.class', [
                'classGroup' => $classGroup->id,
                'sequence'   => $sequence->id,
            ])
            ->with('success',
                "Bulletins générés pour {$classGroup->full_name} — {$sequence->label}. "
                . "{$result['count']} bulletin(s) créé(s).");
    }

    public function classIndex(ClassGroup $classGroup, Sequence $sequence)
    {
        $classGroup->load(['level.section', 'academicYear']);

        $bulletins = BulletinReport::where([
            'sequence_id' => $sequence->id,
            'type'        => BulletinService::TYPE_SEQUENTIAL,
        ])->whereHas('studentEnrollment', fn ($q) =>
            $q->where('class_group_id', $classGroup->id)
        )
        ->with([
            'studentEnrollment.student',
            'distinction',
            'councilDecision',
        ])
        ->orderBy('rank')
        ->get();

        return view('bulletins.class-index', compact(
            'classGroup', 'sequence', 'bulletins'
        ));
    }

    public function show(BulletinReport $bulletin)
    {
        $data = $this->bulletinViewData($bulletin);

        return view('bulletins.show', $data);
    }

    public function pdf(BulletinReport $bulletin)
    {
        $data = $this->bulletinViewData($bulletin);
        $data['forPdf'] = true;

        $student = $bulletin->studentEnrollment->student;
        $filename = 'bulletin-' . Str::slug($student->last_name . '-' . $student->first_name)
            . '-' . ($bulletin->sequence?->label ?? 'seq') . '.pdf';

        return Pdf::loadView('bulletins.show', $data)
            ->setPaper('a4', 'portrait')
            ->download($filename);
    }

    public function printAll(ClassGroup $classGroup, Sequence $sequence)
    {
        $bulletins = BulletinReport::where([
            'sequence_id' => $sequence->id,
            'type'        => BulletinService::TYPE_SEQUENTIAL,
        ])->whereHas('studentEnrollment', fn ($q) =>
            $q->where('class_group_id', $classGroup->id)
        )
        ->with([
            'studentEnrollment.student',
            'studentEnrollment.classGroup.level.section',
            'studentEnrollment.classGroup.academicYear',
            'sequence.trimester',
            'subjectDetails.classSubject.subject.category',
            'distinction',
            'councilDecision',
        ])
        ->orderBy('rank')
        ->get();

        $school = SchoolSetting::instance();
        $phones = SchoolPhone::orderByDesc('is_primary')->orderBy('id')->get();
        $classGroup->load(['level.section', 'academicYear']);

        return view('bulletins.print-all', compact(
            'bulletins', 'school', 'phones', 'classGroup', 'sequence'
        ));
    }

    public function publishAll(ClassGroup $classGroup, Sequence $sequence)
    {
        $updated = BulletinReport::where([
            'sequence_id' => $sequence->id,
            'type'        => BulletinService::TYPE_SEQUENTIAL,
        ])->whereHas('studentEnrollment', fn ($q) =>
            $q->where('class_group_id', $classGroup->id)
        )->update(['is_published' => true]);

        return back()->with('success', "{$updated} bulletin(s) publié(s).");
    }

    public function togglePublish(BulletinReport $bulletin)
    {
        $bulletin->update(['is_published' => ! $bulletin->is_published]);

        return back()->with('success',
            $bulletin->is_published ? 'Bulletin publié.' : 'Bulletin repassé en brouillon.');
    }

    private function bulletinViewData(BulletinReport $bulletin): array
    {
        $bulletin->load([
            'studentEnrollment.student',
            'studentEnrollment.classGroup.level.section',
            'studentEnrollment.classGroup.academicYear',
            'studentEnrollment.classGroup.classSubjects.subject.category',
            'sequence.trimester',
            'subjectDetails.classSubject.subject.category',
            'distinction',
            'councilDecision',
            'generatedBy',
        ]);

        $school = SchoolSetting::instance();
        $phones = SchoolPhone::orderByDesc('is_primary')->orderBy('id')->get();

        $subjectsByCategory = $bulletin->subjectDetails
            ->groupBy(fn ($d) =>
                $d->classSubject?->subject?->category?->name_fr ?? 'Général'
            );

        return compact('bulletin', 'school', 'phones', 'subjectsByCategory');
    }
}
