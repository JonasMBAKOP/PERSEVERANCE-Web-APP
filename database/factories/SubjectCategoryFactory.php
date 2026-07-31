<?php

namespace Database\Factories;

use App\Models\SubjectCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubjectCategory>
 */
class SubjectCategoryFactory extends Factory
{
    protected $model = SubjectCategory::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'section_id' => \App\Models\Section::factory(),
            'code' => strtoupper($this->faker->unique()->bothify('CAT-###??')),
            'name' => $name,
            'name_fr' => $name,
            'name_en' => null,
            'order_index' => $this->faker->numberBetween(1, 10),
        ];
    }
}
