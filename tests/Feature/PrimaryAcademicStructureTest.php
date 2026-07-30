<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\ClassGroup;
use Database\Seeders\SectionsAndLevelsSeeder;
use Tests\TestCase;

class PrimaryAcademicStructureTest extends TestCase
{
    public function test_primary_structure_has_only_fr_and_en_sections_with_ordered_primary_levels(): void
    {
        $sections = SectionsAndLevelsSeeder::primaryStructure();

        $this->assertCount(2, $sections);
        $this->assertSame(['FR', 'EN'], array_column($sections, 'code'));
        $this->assertSame(['fr', 'en'], array_column($sections, 'language'));
        $this->assertSame(['Garderie', 'Pré-Maternelle', 'Petite Section', 'Moyenne Section', 'Grande Section', 'SIL', 'CP', 'CE1', 'CE2', 'CM1', 'CM2'], $sections[0]['levels']);
        $this->assertSame(['Pre-Nursery', 'Nursery 1', 'Nursery 2', 'Class 1', 'Class 2', 'Class 3', 'Class 4', 'Class 5', 'Class 6'], $sections[1]['levels']);
    }

    public function test_primary_class_name_includes_its_optional_sub_group_but_not_series(): void
    {
        $this->assertSame('CP Spécial', ClassGroup::composeName('CP', 'A', 'Spécial'));
    }

    public function test_academic_calendar_has_exactly_six_sequences_with_two_per_trimester(): void
    {
        $calendar = AcademicYear::sequenceCalendar();
        $numbers = array_merge(...array_map('array_keys', $calendar));

        $this->assertSame(3, count($calendar));
        $this->assertSame([1, 2, 3, 4, 5, 6], $numbers);
        $this->assertSame('SEQ 6', $calendar[3][6]);
    }
}