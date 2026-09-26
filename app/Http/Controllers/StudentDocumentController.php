<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\StudentDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html as WordHtml;

class StudentDocumentController extends Controller
{
    public function __construct(
        private readonly StudentDocumentService $documents
    ) {}

    public function index(Request $request): View
    {
        $year    = $this->documents->yearFromRequest($request->integer('year_id') ?: null);
        $years   = \App\Models\AcademicYear::orderByDesc('start_date')->get();
        $options     = $this->documents->filterOptions($year);
        $classesJson = $this->documents->classesJsonForHub();

        return view('students.documents.index', array_merge(
            $this->documents->schoolContext(),
            $options,
            compact('year', 'years', 'classesJson')
        ));
    }

    public function single(Request $request, Student $student, string $type)
    {
        $year       = $this->documents->yearFromRequest($request->integer('year_id') ?: null);
        $enrollment = $this->documents->enrollmentForStudent($student, $year);

        abort_if(! $enrollment && in_array($type, ['certificat', 'carte', 'livret'], true), 404,
            'Aucune inscription active trouvée pour cet élève.');

        if ($type === 'livret') {
            return redirect()->route('livrets.show', $enrollment);
        }

        return $this->renderDocument($type, collect([$student]), $year, $enrollment, $student);
    }

    public function bulkCards(Request $request): View
    {
        return $this->bulk($request, 'cartes');
    }

    public function bulkCertificates(Request $request): View
    {
        return $this->bulk($request, 'certificats');
    }

    public function bulkInformationSheets(Request $request): View
    {
        return $this->bulk($request, 'fiches');
    }

    public function bulkBooklets(Request $request)
    {
        $year    = $this->documents->yearFromRequest($request->integer('year_id') ?: null);
        $filters = $this->filtersFromRequest($request);

        abort_if(! $year, 422, 'Aucune année scolaire sélectionnée.');

        $classId = $filters['class_id'];
        abort_if(!$classId, 422, 'Aucune classe sélectionnée.');

        $students = $this->documents->getStudentsForPrint($year, $filters);
        $enrollmentIds = \App\Models\StudentEnrollment::whereIn('student_id', $students->pluck('id'))
            ->where('class_group_id', $classId)
            ->where('academic_year_id', $year->id)
            ->pluck('id')
            ->toArray();

        return redirect()->route('livrets.bulk', [
            'class_group_id' => $classId,
            'student_ids' => $enrollmentIds,
        ]);
    }

    public function bulkGradeEntrySheets(Request $request): View
    {
        $year    = $this->documents->yearFromRequest($request->integer('year_id') ?: null);
        $filters = $this->filtersFromRequest($request);

        abort_if(! $year, 422, 'Aucune année scolaire sélectionnée.');

        $classId = $filters['class_id'];
        abort_if(!$classId, 422, 'Aucune classe sélectionnée.');

        $classGroup = \App\Models\ClassGroup::with(['level'])->findOrFail($classId);

        $subjects = \App\Models\ClassSubject::where('class_group_id', $classId)
            ->where('is_active', true)
            ->with('subject')
            ->orderBy('id')
            ->get();

        abort_if($subjects->isEmpty(), 404, 'Aucune matière assignée à cette classe.');

        $students = $this->documents->getStudentsForPrint($year, $filters);

        abort_if($students->isEmpty(), 404, 'Aucun élève trouvé pour cette classe.');

        return view('students.documents.bulk.grade-entry-sheets', array_merge(
            $this->documents->schoolContext(),
            compact('year', 'classGroup', 'subjects', 'students', 'filters')
        ));
    }

    public function bulkLists(Request $request): View
    {
        $year    = $this->documents->yearFromRequest($request->integer('year_id') ?: null);
        $filters = $this->filtersFromRequest($request);

        abort_if(! $year, 422, 'Aucune année scolaire sélectionnée.');

        $groups = $this->documents->getListGroups($year, $filters);

        abort_if($groups === [], 404, 'Aucun élève trouvé pour cette sélection.');

        return view('students.documents.bulk.listes', array_merge(
            $this->documents->schoolContext(),
            compact('year', 'groups', 'filters')
        ));
    }

    public function enrollmentTotalsReport(Request $request): View
    {
        $year    = $this->documents->yearFromRequest($request->integer('year_id') ?: null);
        $filters = $this->filtersFromRequest($request);

        abort_if(! $year, 422, 'Aucune année scolaire sélectionnée.');
        abort_if(($filters['scope'] ?? null) === 'class', 422,
            'Le rapport des effectifs s\'imprime uniquement par section ou pour tout l\'établissement.');

        $report = $this->documents->getEnrollmentTotalsReport($year, $filters);

        abort_if($report['sections'] === [], 404, 'Aucune classe trouvée pour cette sélection.');

        return view('students.documents.reports.effectifs-totaux', array_merge(
            $this->documents->schoolContext(),
            compact('year', 'filters', 'report')
        ));
    }

    public function bulkListsWord(Request $request)
    {
        $year    = $this->documents->yearFromRequest($request->integer('year_id') ?: null);
        $filters = $this->filtersFromRequest($request);

        abort_if(! $year, 422, 'Aucune année scolaire sélectionnée.');

        $groups = $this->documents->getListGroups($year, $filters);

        abort_if($groups === [], 404, 'Aucun élève trouvé pour cette sélection.');

        return $this->downloadListWordDocument(
            $year,
            $groups,
            $filters,
            $this->documents->schoolContext(),
            $this->listWordFilename($year, $groups, $filters)
        );
    }

    public function enrollmentTotalsReportWord(Request $request)
    {
        $year    = $this->documents->yearFromRequest($request->integer('year_id') ?: null);
        $filters = $this->filtersFromRequest($request);

        abort_if(! $year, 422, 'Aucune année scolaire sélectionnée.');
        abort_if(($filters['scope'] ?? null) === 'class', 422,
            'Le rapport des effectifs s\'exporte uniquement par section ou pour tout l\'établissement.');

        $report = $this->documents->getEnrollmentTotalsReport($year, $filters);

        abort_if($report['sections'] === [], 404, 'Aucune classe trouvée pour cette sélection.');

        return $this->downloadEnrollmentReportWordDocument(
            $year,
            $report,
            $filters,
            $this->documents->schoolContext(),
            $this->enrollmentReportWordFilename($year, $report, $filters)
        );
    }

    private function bulk(Request $request, string $type): View
    {
        $year    = $this->documents->yearFromRequest($request->integer('year_id') ?: null);
        $filters = $this->filtersFromRequest($request);

        abort_if(! $year, 422, 'Aucune année scolaire sélectionnée.');

        $students = $this->documents->getStudentsForPrint($year, $filters);

        abort_if($students->isEmpty(), 404, 'Aucun élève trouvé pour cette sélection.');

        return $this->renderDocument($type, $students, $year, null, null, $filters);
    }

    private function filtersFromRequest(Request $request): array
    {
        return [
            'scope'      => $request->input('scope', 'class'),
            'class_id'   => $request->integer('class_id') ?: null,
            'section_id' => $request->integer('section_id') ?: null,
        ];
    }

    private function describeScope(array $filters, $year): string
    {
        $scope = $filters['scope'] ?? 'class';

        if ($scope === 'section' && ! empty($filters['section_id'])) {
            $section = \App\Models\Section::find($filters['section_id']);

            return 'Section ' . ($section?->name ?? $filters['section_id']);
        }

        if ($scope === 'class' && ! empty($filters['class_id'])) {
            $class = \App\Models\ClassGroup::find($filters['class_id']);

            return 'Classe ' . ($class?->full_name ?? $filters['class_id']);
        }

        return 'Établissement';
    }

    private function schoolBrand(array $schoolContext): string
    {
        $name = mb_strtolower((string) ($schoolContext['school']->full_name ?? ''));

        return str_contains($name, 'perseverance') ? 'PERSEVERANCE' : 'COPTAN';
    }

    private function schoolYearRange($year): string
    {
        $start = $year->start_date?->format('Y') ?? '';
        $end = $year->end_date?->format('Y') ?? '';

        return $start && $end ? $start . '-' . $end : (string) ($year->label ?? $year->id);
    }

    private function filenamePart(string $value): string
    {
        return trim((string) preg_replace('/[\\\\\/:*?"<>|]+/', '-', $value));
    }

    private function listWordFilename($year, array $groups, array $filters): string
    {
        $context = $this->documents->schoolContext();
        $scope = 'Complet';

        if (($filters['scope'] ?? null) === 'class' && ! empty($filters['class_id'])) {
            $class = \App\Models\ClassGroup::find($filters['class_id']);
            $scope = $class?->full_name ?: 'Complet';
        } elseif (($filters['scope'] ?? null) === 'section' && ! empty($filters['section_id'])) {
            $section = \App\Models\Section::find($filters['section_id']);
            $scope = $section?->code ?: ($section?->name ?: 'Complet');
        }

        return $this->filenamePart('Liste des élèves ' . $this->schoolBrand($context) . ' ' . $scope . ' ' . $this->schoolYearRange($year)) . '.docx';
    }

    private function enrollmentReportWordFilename($year, array $report, array $filters): string
    {
        $context = $this->documents->schoolContext();
        $scope = 'Complet';

        if (($filters['scope'] ?? null) === 'section' && ! empty($filters['section_id'])) {
            $section = \App\Models\Section::find($filters['section_id']);
            $scope = $section?->code ?: ($section?->name ?: 'Complet');
        }

        return $this->filenamePart('Rapport des effectifs ' . $scope . ' ' . $this->schoolBrand($context) . ' ' . $this->schoolYearRange($year)) . '.docx';
    }

    private function wordImagePath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $relative = ltrim(str_replace('storage/', '', $path), '/');
        $candidates = [
            public_path('storage/' . $relative),
            storage_path('app/public/' . $relative),
            public_path($relative),
        ];

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function appendWordOfficialHeader($section, array $schoolContext): void
    {
        $school = $schoolContext['school'];
        $phones = $schoolContext['phones'];
        $phoneLine = $phones->isNotEmpty() ? $phones->pluck('number')->join(' / ') : '';
        $fr = ['REPUBLIQUE DU CAMEROUN', 'Paix-Travail-Patrie', 'MINISTERE DES ENSEIGNEMENTS SECONDAIRES', strtoupper((string) $school->full_name)];
        $en = ['REPUBLIC OF CAMEROON', 'Peace-Work-Fatherland', 'MINISTRY OF SECONDARY EDUCATION', strtoupper((string) ($school->full_name_en ?: 'NTANKEU POLYVALENT COLLEGE'))];
        $small = ['name' => 'Arial', 'size' => 7, 'color' => '000000'];
        $bold = ['name' => 'Arial', 'size' => 8, 'bold' => true, 'color' => '000000'];

        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 0, 'tblLayout' => 'fixed']);
        $table->addRow(1700, ['cantSplit' => true]);
        $left = $table->addCell(4400);
        foreach ($fr as $line) {
            $left->addText($line, $bold, ['alignment' => 'center', 'spaceAfter' => 0]);
        }
        $left->addText('********', $small, ['alignment' => 'center', 'spaceAfter' => 0]);
        if ($phoneLine) {
            $left->addText('Tél. ' . $phoneLine, $small, ['alignment' => 'center', 'spaceAfter' => 0]);
        }
        $left->addText(($school->postal_box ? 'B.P. ' . $school->postal_box : ''), $small, ['alignment' => 'center', 'spaceAfter' => 0]);

        $center = $table->addCell(2500);
        $logo = $this->wordImagePath($school->logo) ?: $this->wordImagePath('images/logo.jpg');
        if ($logo) {
            $center->addImage($logo, ['width' => 82, 'height' => 82, 'alignment' => 'center']);
        } else {
            $center->addText(strtoupper(substr((string) ($school->short_name ?? 'C'), 0, 1)), ['name' => 'Arial', 'size' => 28, 'bold' => true, 'color' => '000000'], ['alignment' => 'center']);
        }

        $right = $table->addCell(4400);
        foreach ($en as $line) {
            $right->addText($line, $bold, ['alignment' => 'center', 'spaceAfter' => 0]);
        }
        $right->addText('********', $small, ['alignment' => 'center', 'spaceAfter' => 0]);
        if ($phoneLine) {
            $right->addText('Phone. ' . $phoneLine, $small, ['alignment' => 'center', 'spaceAfter' => 0]);
        }
        $right->addText(($school->postal_box ? 'P.O. BOX ' . $school->postal_box : ''), $small, ['alignment' => 'center', 'spaceAfter' => 0]);

        foreach ($schoolContext['agreements'] as $agreement) {
            $section->addText('N° ' . $agreement->number, $small, ['alignment' => 'center', 'spaceAfter' => 0]);
        }
    }

    private function appendWordSignature($section, array $schoolContext): void
    {
        $table = $section->addTable(['borderSize' => 0, 'cellMargin' => 0, 'tblLayout' => 'fixed']);
        $table->addRow(1200, ['cantSplit' => true]);
        $table->addCell(6500)->addText('Signature du parent', ['name' => 'Arial', 'size' => 9, 'color' => '000000'], ['alignment' => 'center']);
        $right = $table->addCell(4800);
        $right->addText($this->schoolBrand($schoolContext) === 'PERSEVERANCE' ? 'La Direction' : 'Le Principal',
            ['name' => 'Arial', 'size' => 9, 'bold' => true, 'color' => '000000'], ['alignment' => 'center']);
        if ($seal = $this->wordImagePath($schoolContext['school']->signature_seal)) {
            $right->addImage($seal, ['width' => 92, 'height' => 92, 'alignment' => 'center']);
        }
    }

    private function downloadListWordDocument($year, array $groups, array $filters, array $schoolContext, string $filename)
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginTop' => 113,
            'marginRight' => 227,
            'marginBottom' => 510,
            'marginLeft' => 227,
        ]);

        $section->addFooter()->addPreserveText('{PAGE} / {NUMPAGES}',
            ['name' => 'Arial', 'size' => 9, 'color' => '000000'],
            ['alignment' => 'right']);

        $this->appendListWordContent($section, $year, $groups, $filters, $schoolContext);

        return $this->saveWordDocument($phpWord, $filename);
    }

    private function downloadEnrollmentReportWordDocument($year, array $report, array $filters, array $schoolContext, string $filename)
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginTop' => 113,
            'marginRight' => 227,
            'marginBottom' => 510,
            'marginLeft' => 227,
        ]);

        $section->addFooter()->addPreserveText('{PAGE} / {NUMPAGES}',
            ['name' => 'Arial', 'size' => 9, 'color' => '000000'],
            ['alignment' => 'right']);

        $this->appendEnrollmentReportWordContent($section, $year, $report, $filters, $schoolContext);

        return $this->saveWordDocument($phpWord, $filename);
    }

    private function saveWordDocument(PhpWord $phpWord, string $filename)
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'docx');

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);

        return Response::file($tempFile, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ])->deleteFileAfterSend(true);
    }

    private function appendListWordContent($section, $year, array $groups, array $filters, array $schoolContext): void
    {
        $this->appendWordOfficialHeader($section, $schoolContext);
        $section->addText('Liste des élèves', ['bold' => true, 'size' => 16, 'color' => '000000'], ['alignment' => 'center']);
        $section->addText('Année scolaire ' . ($year->label ?? ''), ['size' => 10, 'color' => '000000'], ['alignment' => 'center']);
        $section->addTextBreak(1);

        $totalStudents = 0;
        $totalGirls = 0;
        $totalBoys = 0;
        $classCount = 0;
        $isSingleClass = ($filters['scope'] ?? '') === 'class';

        foreach ($groups as $group) {
            if (! $isSingleClass) {
                $section->addText('Section : ' . ($group['section']->name ?? '') . ' (' . ($group['section']->code ?? '') . ')', ['bold' => true, 'size' => 13, 'color' => '000000']);
            }

            foreach ($group['classes'] as $block) {
                $classStudents = $block['students'];
                $classTotal = $classStudents->count();
                $classGirls = $classStudents->filter(fn ($student) => strtoupper((string) $student->gender) === 'F')->count();
                $classBoys = $classStudents->filter(fn ($student) => strtoupper((string) $student->gender) === 'M')->count();
                $totalStudents += $classTotal;
                $totalGirls += $classGirls;
                $totalBoys += $classBoys;
                $classCount++;
                $section->addText((string) ($block['class']->full_name ?? ''), ['bold' => true, 'size' => 12, 'color' => '000000'], ['alignment' => 'center']);
                $summaryTable = $section->addTable(['borderSize' => 0, 'cellMargin' => 0, 'tblLayout' => 'fixed']);
                $summaryTable->addRow(260);
                $summaryTable->addCell(2900)->addText('Effectif Total : ' . $classTotal . ' élève(s)', ['bold' => true, 'size' => 10, 'color' => '000000'], ['alignment' => 'left']);
                $summaryTable->addCell(2900)->addText('Filles : ' . $classGirls, ['bold' => true, 'size' => 10, 'color' => '000000'], ['alignment' => 'center']);
                $summaryTable->addCell(2900)->addText('Garçons : ' . $classBoys, ['bold' => true, 'size' => 10, 'color' => '000000'], ['alignment' => 'right']);
                $section->addTextBreak(0.5);

                $table = $section->addTable([
                    'borderSize' => 6,
                    'borderColor' => 'B7C0CC',
                    'cellMargin' => 80,
                    'tblLayout' => 'fixed',
                ]);

                $table->addRow(260, ['cantSplit' => true]);
                $table->addCell(500)->addText('N°', ['bold' => true, 'color' => '000000']);
                $table->addCell(4400)->addText('Nom(s) et Prénom(s)', ['bold' => true, 'color' => '000000']);
                $table->addCell(1800)->addText('Matricule', ['bold' => true, 'color' => '000000']);
                $table->addCell(600)->addText('Sexe', ['bold' => true, 'color' => '000000']);
                $table->addCell(1400)->addText('Date naiss.', ['bold' => true, 'color' => '000000']);
                $table->addCell(1800)->addText('Lieu de naissance', ['bold' => true, 'color' => '000000']);
                $table->addCell(1400)->addText('Inscrit(e) le', ['bold' => true, 'color' => '000000']);

                foreach ($block['students'] as $index => $student) {
                    $table->addRow(240, ['cantSplit' => true]);
                    $table->addCell(500)->addText((string) ($index + 1), ['size' => 9, 'color' => '000000']);
                    $table->addCell(4400)->addText((string) ($student->full_name ?? ''), ['size' => 9, 'color' => '000000']);
                    $table->addCell(1800)->addText((string) ($student->matricule ?? ''), ['size' => 9, 'color' => '000000']);
                    $table->addCell(600)->addText($student->gender === 'M' ? 'M' : 'F', ['size' => 9, 'color' => '000000']);
                    $table->addCell(1400)->addText($student->date_of_birth?->format('d/m/Y') ?? '—', ['size' => 9, 'color' => '000000']);
                    $table->addCell(1800)->addText(strtoupper((string) ($student->place_of_birth ?? '—')), ['size' => 9, 'color' => '000000']);
                    $enrollment = $student->printEnrollment ?? null;
                    $table->addCell(1400)->addText($enrollment?->enrollment_date?->format('d/m/Y') ?? '—', ['size' => 9, 'color' => '000000']);
                }

                $section->addTextBreak(1);
            }
        }

        if ($classCount > 1) {
            $summaryTable = $section->addTable(['borderSize' => 0, 'cellMargin' => 0, 'tblLayout' => 'fixed']);
            $summaryTable->addRow(260);
            $summaryTable->addCell(2200)->addText('Bilan des effectifs :', ['bold' => true, 'size' => 10, 'color' => '000000'], ['alignment' => 'left']);
            $summaryTable->addCell(2200)->addText('Effectif total des élèves : ' . $totalStudents, ['bold' => true, 'size' => 10, 'color' => '000000'], ['alignment' => 'center']);
            $summaryTable->addCell(2200)->addText('Nombre de Filles : ' . $totalGirls, ['bold' => true, 'size' => 10, 'color' => '000000'], ['alignment' => 'center']);
            $summaryTable->addCell(2200)->addText('Nombre de Garçons : ' . $totalBoys, ['bold' => true, 'size' => 10, 'color' => '000000'], ['alignment' => 'right']);
        }
        $this->appendWordSignature($section, $schoolContext);
        $section->addText('Document généré le ' . now()->format('d/m/Y à H:i'), ['size' => 9, 'color' => '000000'], ['alignment' => 'center']);
    }

    private function appendEnrollmentReportWordContent($section, $year, array $report, array $filters, array $schoolContext): void
    {
        $this->appendWordOfficialHeader($section, $schoolContext);
        $section->addText('RAPPORT DES EFFECTIFS TOTAUX', ['bold' => true, 'size' => 16, 'color' => '000000'], ['alignment' => 'center']);
        $section->addText('Année scolaire ' . ($year->label ?? ''), ['size' => 10, 'color' => '000000'], ['alignment' => 'center']);
        $section->addTextBreak(1);

        $isSectionScope = ($filters['scope'] ?? 'school') === 'section';

        if (! $isSectionScope) {
            $section->addText('Synthèse générale par section', ['bold' => true, 'size' => 13, 'color' => '1A3A6B']);
            $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'B7C0CC', 'cellMargin' => 80]);
            $table->addRow(260);
            $table->addCell(3000)->addText('Sections', ['bold' => true]);
            $table->addCell(1200)->addText('Filles', ['bold' => true]);
            $table->addCell(1200)->addText('Garçons', ['bold' => true]);
            $table->addCell(1200)->addText('Total', ['bold' => true]);
            foreach ($report['sections'] as $sectionReport) {
                $table->addRow(240);
                $table->addCell(3000)->addText($sectionReport['section']->name ?? '', ['size' => 9]);
                $table->addCell(1200)->addText((string) ($sectionReport['totals']['girls'] ?? 0), ['size' => 9]);
                $table->addCell(1200)->addText((string) ($sectionReport['totals']['boys'] ?? 0), ['size' => 9]);
                $table->addCell(1200)->addText((string) ($sectionReport['totals']['total'] ?? 0), ['size' => 9]);
            }
            $table->addRow(260);
            $table->addCell(3000)->addText('TOTAL', ['bold' => true]);
            $table->addCell(1200)->addText((string) ($report['totals']['girls'] ?? 0), ['bold' => true]);
            $table->addCell(1200)->addText((string) ($report['totals']['boys'] ?? 0), ['bold' => true]);
            $table->addCell(1200)->addText((string) ($report['totals']['total'] ?? 0), ['bold' => true]);
            $section->addTextBreak(1);
        }

        foreach ($report['sections'] as $sectionReport) {
            $section->addText('Détail par classe — ' . ($sectionReport['section']->name ?? ''), ['bold' => true, 'size' => 13, 'color' => '1A3A6B']);
            $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'B7C0CC', 'cellMargin' => 80]);
            $table->addRow(260);
            $table->addCell(3000)->addText('Classes', ['bold' => true]);
            $table->addCell(1200)->addText('Filles', ['bold' => true]);
            $table->addCell(1200)->addText('Garçons', ['bold' => true]);
            $table->addCell(1200)->addText('Total', ['bold' => true]);
            foreach ($sectionReport['rows'] as $row) {
                $table->addRow(240);
                $table->addCell(3000)->addText($row['class']->full_name ?? '', ['size' => 9]);
                $table->addCell(1200)->addText((string) ($row['girls'] ?? 0), ['size' => 9]);
                $table->addCell(1200)->addText((string) ($row['boys'] ?? 0), ['size' => 9]);
                $table->addCell(1200)->addText((string) ($row['total'] ?? 0), ['size' => 9]);
            }
            $table->addRow(260);
            $table->addCell(3000)->addText('TOTAL', ['bold' => true]);
            $table->addCell(1200)->addText((string) ($sectionReport['totals']['girls'] ?? 0), ['bold' => true]);
            $table->addCell(1200)->addText((string) ($sectionReport['totals']['boys'] ?? 0), ['bold' => true]);
            $table->addCell(1200)->addText((string) ($sectionReport['totals']['total'] ?? 0), ['bold' => true]);
            $section->addTextBreak(1);
        }

        $this->appendWordSignature($section, $schoolContext);
        $section->addText('Document généré le ' . now()->format('d/m/Y à H:i'), ['size' => 9, 'color' => '000000'], ['alignment' => 'center']);
    }

    private function renderDocument(
        string $type,
        $students,
        $year,
        $enrollment = null,
        ?Student $student = null,
        array $filters = []
    ): View {
        $viewMap = [
            'fiche'       => 'students.documents.fiche-renseignement',
            'certificat'  => 'students.documents.certificat-scolarite',
            'carte'       => 'students.documents.carte-scolaire',
            'cartes'      => 'students.documents.bulk.cartes',
            'certificats' => 'students.documents.bulk.certificats',
            'fiches'      => 'students.documents.bulk.fiches',
            'livret'      => 'students.documents.livret-scolaire',
            'livrets'     => 'students.documents.bulk.livrets',
        ];

        abort_unless(isset($viewMap[$type]), 404);

        $data = array_merge($this->documents->schoolContext(), [
            'year'       => $year,
            'students'   => $students,
            'student'    => $student ?? $students->first(),
            'enrollment' => $enrollment ?? ($student
                ? $this->documents->enrollmentForStudent($student, $year)
                : $students->first()?->printEnrollment),
            'filters'    => $filters,
            'sequences'  => $this->documents->sequencesForYear($year),
        ]);

        if (in_array($type, ['livret', 'livrets'], true)) {
            $data['subjectsByEnrollment'] = $students->mapWithKeys(function ($s) use ($year) {
                $enr = $s->printEnrollment
                    ?? $this->documents->enrollmentForStudent($s, $year);

                return [$s->id => $this->documents->subjectsForEnrollment($enr)];
            });
        }

        if ($type === 'fiche') {
            $data['enrollment'] = $enrollment
                ?? $this->documents->enrollmentForStudent($data['student'], $year);
        }

        return view($viewMap[$type], $data);
    }
}
