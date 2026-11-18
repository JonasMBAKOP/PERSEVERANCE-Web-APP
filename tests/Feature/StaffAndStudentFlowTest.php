<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\ClassSubject;
use App\Models\Level;
use App\Models\Section;
use App\Models\Sequence;
use App\Models\Staff;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentPayment;
use App\Models\Subject;
use App\Models\SubjectCategory;
use App\Models\Trimester;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffAndStudentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * Test: Create staff with user and positions
     */
    public function test_create_staff_with_user_and_positions()
    {
        // Admin user
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $response = $this->actingAs($admin)->post(route('staff.store'), [
            'first_name'      => 'Jean',
            'last_name'       => 'KAMGA',
            'gender'          => 'M',
            'date_of_birth'   => '1990-01-15',
            'phone'           => '237999888777',
            'email'           => 'jean.kamga@coptan.cm',
            'diploma'         => 'Baccalauréat',
            'start_date'      => '2020-01-01',
            'contract_type'   => 'permanent',
            'is_active'       => true,
            'positions'       => ['enseignant', 'censeur'],
            'primary_position' => 'enseignant',
            'user_option'     => 'create',
            'new_user_name'   => 'Jean Kamga Enseignant',
            'new_user_email'  => 'jean.kamga.user@coptan.cm',
            'new_user_password' => 'SecurePassword123!',
            'new_user_role'   => 'enseignant',
        ]);

        // Should redirect to show page
        $this->assertTrue($response->status() === 302 || $response->status() === 200);

        // Verify staff was created
        $staff = Staff::whereEmail('jean.kamga@coptan.cm')->firstOrFail();
        $this->assertEquals('Jean', $staff->first_name);
        $this->assertEquals('KAMGA', $staff->last_name);

        // Verify positions were assigned
        $this->assertTrue($staff->positions()->count() >= 2);
        $this->assertTrue($staff->positions->pluck('position')->contains('enseignant'));
        $this->assertTrue($staff->positions->pluck('position')->contains('censeur'));

        // Verify primary position
        $primaryPosition = $staff->positions()->where('is_primary', true)->first();
        $this->assertNotNull($primaryPosition);
        $this->assertEquals('enseignant', $primaryPosition->position);

        // Verify user was created and linked
        $this->assertNotNull($staff->user_id);
        $this->assertTrue($staff->user()->exists());
        $this->assertEquals('Jean Kamga Enseignant', $staff->user->name);
    }

    /**
     * Test: Create student with enrollment
     */
    public function test_students_index_can_show_students_for_inactive_year()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $year = AcademicYear::factory()->create(['is_active' => false]);
        $section = Section::factory()->create();
        $level = Level::factory()->create(['section_id' => $section->id]);
        $class = ClassGroup::factory()->create([
            'academic_year_id' => $year->id,
            'level_id' => $level->id,
            'max_students' => 50,
        ]);

        $student = Student::factory()->create([
            'first_name' => 'Alice',
            'last_name' => 'Tchoua',
            'matricule' => 'STU-1001',
        ]);

        StudentEnrollment::factory()->create([
            'student_id' => $student->id,
            'class_group_id' => $class->id,
            'academic_year_id' => $year->id,
            'status' => 'inactive',
        ]);

        $response = $this->actingAs($admin)
            ->withoutMiddleware()
            ->get(route('students.index', ['year_id' => $year->id]));

        $response->assertOk();
        $response->assertSee('Alice');
        $response->assertSee('Tchoua');
    }

    public function test_create_student_with_enrollment()
    {
        // Setup
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $year = AcademicYear::factory()->create(['is_active' => true]);
        $section = Section::factory()->create();
        $level = Level::factory()->create(['section_id' => $section->id]);
        $class = ClassGroup::factory()->create([
            'academic_year_id' => $year->id,
            'level_id'        => $level->id,
            'max_students'    => 50,
        ]);

        // Create student via API
        $response = $this->actingAs($admin)->post(route('students.store'), [
            'first_name'           => 'Marie',
            'last_name'            => 'NKOMO',
            'gender'               => 'F',
            'date_of_birth'        => '2010-05-10',
            'place_of_birth'       => 'Yaoundé',
            'nationality'          => 'Camerounaise',
            'address'              => '123 Rue de la Paix',
            'father_name'          => 'Jean Nkomo',
            'father_phone'         => '237666555444',
            'mother_name'          => 'Marie Nkomo',
            'mother_phone'         => '237777888999',
            'academic_year_id'     => $year->id,
            'class_group_id'       => $class->id,
            'enrollment_date'      => now()->toDateString(),
            'is_repeating'         => false,
        ]);

        // Should redirect to show page
        $this->assertTrue($response->status() === 302 || $response->status() === 200);

        // Verify student was created
        $student = Student::whereEmail('marie.nkomo@coptan.cm')
            ->orWhere('first_name', 'Marie')
            ->orWhere('last_name', 'NKOMO')
            ->firstOrFail();
        $this->assertEquals('Marie', $student->first_name);
        $this->assertEquals('NKOMO', $student->last_name);

        // Verify enrollment was created
        $enrollment = StudentEnrollment::where('student_id', $student->id)
            ->where('academic_year_id', $year->id)
            ->where('class_group_id', $class->id)
            ->firstOrFail();
        $this->assertEquals('active', $enrollment->status);
    }

    /**
     * Test: Cannot create duplicate enrollment
     */
    public function test_cannot_create_duplicate_enrollment()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $year = AcademicYear::factory()->create(['is_active' => true]);
        $section = Section::factory()->create();
        $level = Level::factory()->create(['section_id' => $section->id]);
        $class1 = ClassGroup::factory()->create(['academic_year_id' => $year->id, 'level_id' => $level->id]);
        $class2 = ClassGroup::factory()->create(['academic_year_id' => $year->id, 'level_id' => $level->id]);

        $student = Student::factory()->create();

        // First enrollment
        StudentEnrollment::create([
            'student_id'      => $student->id,
            'academic_year_id' => $year->id,
            'class_group_id'  => $class1->id,
            'enrollment_date' => now()->toDateString(),
            'status'          => 'active',
        ]);

        // Try to create second enrollment (should fail)
        $response = $this->actingAs($admin)->post(route('students.store'), [
            'first_name'           => $student->first_name,
            'last_name'            => $student->last_name,
            'gender'               => $student->gender,
            'date_of_birth'        => $student->date_of_birth->toDateString(),
            'academic_year_id'     => $year->id,
            'class_group_id'       => $class2->id,
            'enrollment_date'      => now()->toDateString(),
            'is_repeating'         => false,
        ]);

        // Should redirect back with error
        $response->assertRedirect();
        $response->assertSessionHasErrors();
        $response->assertSessionHas('error');
    }

    /**
     * Test: Cannot exceed class capacity
     */
    public function test_cannot_exceed_class_capacity()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $year = AcademicYear::factory()->create(['is_active' => true]);
        $section = Section::factory()->create();
        $level = Level::factory()->create(['section_id' => $section->id]);
        $class = ClassGroup::factory()->create([
            'academic_year_id' => $year->id,
            'level_id'        => $level->id,
            'max_students'    => 2, // Small capacity
        ]);

        // Fill the class
        for ($i = 0; $i < 2; $i++) {
            Student::factory()->has(
                StudentEnrollment::factory()->state([
                    'academic_year_id' => $year->id,
                    'class_group_id'   => $class->id,
                    'status'           => 'active',
                ])
            )->create();
        }

        // Try to enroll one more (should fail)
        $response = $this->actingAs($admin)->post(route('students.store'), [
            'first_name'       => 'Extra',
            'last_name'        => 'Student',
            'gender'           => 'M',
            'date_of_birth'    => '2010-05-10',
            'academic_year_id' => $year->id,
            'class_group_id'   => $class->id,
            'enrollment_date'  => now()->toDateString(),
            'is_repeating'     => false,
        ]);

        // Should redirect back with error about capacity
        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_can_update_enrollment_date_for_existing_enrollment()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $year = AcademicYear::factory()->create(['is_active' => true]);
        $section = Section::factory()->create();
        $level = Level::factory()->create(['section_id' => $section->id]);
        $class = ClassGroup::factory()->create([
            'academic_year_id' => $year->id,
            'level_id'        => $level->id,
            'max_students'    => 50,
        ]);

        $student = Student::factory()->create();
        $enrollment = StudentEnrollment::factory()->create([
            'student_id'       => $student->id,
            'academic_year_id' => $year->id,
            'class_group_id'   => $class->id,
            'status'           => 'active',
            'enrollment_date'  => '2024-09-01',
        ]);

        $response = $this->actingAs($admin)->patch(route('students.enrollments.update-date', $enrollment), [
            'enrollment_date' => '2024-09-15',
        ]);

        $response->assertRedirect();
        $this->assertSame('2024-09-15', $enrollment->fresh()->enrollment_date->toDateString());
    }

    public function test_can_delete_enrolled_student_with_related_records()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $year = AcademicYear::factory()->create(['is_active' => true]);
        $section = Section::factory()->create();
        $level = Level::factory()->create(['section_id' => $section->id]);
        $class = ClassGroup::factory()->create([
            'academic_year_id' => $year->id,
            'level_id'        => $level->id,
            'max_students'    => 50,
        ]);

        $student = Student::factory()->create();
        $enrollment = StudentEnrollment::factory()->create([
            'student_id'       => $student->id,
            'academic_year_id' => $year->id,
            'class_group_id'   => $class->id,
            'status'           => 'active',
        ]);

        $enrollment->grades()->create([
            'class_subject_id' => 1,
            'sequence_id'      => 1,
            'grade'            => 15.5,
        ]);

        StudentPayment::create([
            'student_enrollment_id' => $enrollment->id,
            'fee_installment_id'    => null,
            'amount_paid'           => 10000,
            'payment_date'          => now()->toDateString(),
            'payment_method'        => 'cash',
            'reference'             => null,
            'receipt_number'        => 'RCP-TEST-1',
            'recorded_by'           => $admin->id,
            'notes'                 => 'Test payment',
        ]);

        $response = $this->actingAs($admin)->delete(route('students.destroy', $student));

        $response->assertRedirect(route('students.index'));
        $this->assertNull(Student::withTrashed()->find($student->id));
        $this->assertNull(StudentEnrollment::withTrashed()->find($enrollment->id));
        $this->assertSame(0, StudentPayment::where('student_enrollment_id', $enrollment->id)->count());
    }

    public function test_student_detail_page_shows_real_notes_summary()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $year = AcademicYear::factory()->create(['is_active' => true]);
        $section = Section::factory()->create();
        $level = Level::factory()->create(['section_id' => $section->id]);
        $class = ClassGroup::factory()->create([
            'academic_year_id' => $year->id,
            'level_id'        => $level->id,
            'max_students'    => 50,
        ]);
        $subjectCategory = SubjectCategory::factory()->create();
        $subject = Subject::create([
            'subject_category_id' => $subjectCategory->id,
            'code' => 'MATH',
            'name_fr' => 'Mathématiques',
            'name_en' => 'Mathematics',
        ]);
        $classSubject = ClassSubject::create([
            'class_group_id' => $class->id,
            'subject_id' => $subject->id,
            'coefficient' => 1,
            'hours_per_week' => 4,
            'is_active' => true,
        ]);
        $trimester = Trimester::create([
            'academic_year_id' => $year->id,
            'number' => 1,
            'label' => 'Trimestre 1',
            'start_date' => now()->subMonths(2)->toDateString(),
            'end_date' => now()->addMonths(2)->toDateString(),
        ]);
        $sequence = Sequence::create([
            'academic_year_id' => $year->id,
            'trimester_id' => $trimester->id,
            'number' => 1,
            'label' => 'Séquence 1',
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'is_grades_locked' => false,
        ]);

        $student = Student::factory()->create();
        $enrollment = StudentEnrollment::factory()->create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'class_group_id' => $class->id,
            'status' => 'active',
        ]);
        $enrollment->grades()->create([
            'class_subject_id' => $classSubject->id,
            'sequence_id' => $sequence->id,
            'grade' => 14.5,
        ]);

        $response = $this->actingAs($admin)->get(route('students.show', $student));

        $response->assertOk();
        $response->assertSee('Notes & Moyennes');
        $response->assertSee('Moyenne générale');
        $response->assertSee('Mathématiques');
    }

    public function test_transfer_deletes_related_payments_for_the_old_enrollment()
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $year = AcademicYear::factory()->create(['is_active' => true]);
        $section = Section::factory()->create();
        $level = Level::factory()->create(['section_id' => $section->id]);
        $sourceClass = ClassGroup::factory()->create([
            'academic_year_id' => $year->id,
            'level_id'        => $level->id,
            'max_students'    => 50,
        ]);
        $destinationClass = ClassGroup::factory()->create([
            'academic_year_id' => $year->id,
            'level_id'        => $level->id,
            'max_students'    => 50,
        ]);

        $student = Student::factory()->create();
        $enrollment = StudentEnrollment::factory()->create([
            'student_id'       => $student->id,
            'academic_year_id' => $year->id,
            'class_group_id'   => $sourceClass->id,
            'status'           => 'active',
        ]);

        StudentPayment::create([
            'student_enrollment_id' => $enrollment->id,
            'fee_installment_id'    => null,
            'amount_paid'           => 5000,
            'payment_date'          => now()->toDateString(),
            'payment_method'        => 'cash',
            'reference'             => null,
            'receipt_number'        => 'RCP-TEST-2',
            'recorded_by'           => $admin->id,
            'notes'                 => 'Transfer payment',
        ]);

        $response = $this->actingAs($admin)->patch(route('students.enrollments.transfer', $enrollment), [
            'new_class_id' => $destinationClass->id,
            'transfer_reason' => 'Changement de classe',
        ]);

        $response->assertRedirect(route('students.index'));
        $this->assertSame(0, StudentPayment::where('student_enrollment_id', $enrollment->id)->count());
        $this->assertNull(StudentEnrollment::withTrashed()->find($enrollment->id));
    }

    public function test_econome_cannot_access_staff_creation_or_edit_routes(): void
    {
        $econome = User::factory()->create();
        $econome->assignRole('econome');
        $staff = Staff::create([
            'first_name' => 'Jean',
            'last_name' => 'Test',
            'gender' => 'M',
            'contract_type' => 'vacataire',
            'is_active' => true,
        ]);

        $this->assertTrue($econome->can('view-staff'));
        $this->assertFalse($econome->can('manage-staff'));

        $this->actingAs($econome)
            ->get(route('staff.create'))
            ->assertForbidden();

        $this->get(route('staff.edit', $staff))->assertForbidden();
    }
}
