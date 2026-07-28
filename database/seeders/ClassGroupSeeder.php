<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\ClassGroup;
use App\Models\Level;
use App\Models\Staff;
use Illuminate\Database\Seeder;

class ClassGroupSeeder extends Seeder
{
    public function run(): void
    {
        $activeYear = AcademicYear::where('is_active', true)->first();
        $staff = Staff::where('is_active', true)->get();

        if (! $activeYear || $staff->isEmpty()) {
            return;
        }

        foreach (Level::query()->orderBy('section_id')->orderBy('order_index')->get() as $level) {
            ClassGroup::updateOrCreate(
                [
                    'academic_year_id' => $activeYear->id,
                    'level_id' => $level->id,
                ],
                [
                    'name' => ClassGroup::composeName($level->name),
                    'series' => '',
                    'sub_group' => '',
                    'max_students' => 60,
                    'titular_staff_id' => $staff->first()->id,
                    'room' => null,
                ]
            );
        }
    }
}