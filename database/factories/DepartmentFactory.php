<?php

namespace Database\Factories;

use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'code' => strtoupper(fake()->unique()->lexify('????')),
            'tag' => strtoupper($name),
            'name' => ucfirst($name),
            'description' => fake()->sentence(),
            'icon' => 'building',
            'color' => null,
            'position' => 0,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
