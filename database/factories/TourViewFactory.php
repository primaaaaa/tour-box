<?php

namespace Database\Factories;

use App\Models\TourView;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TourView>
 */
class TourViewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->words(2, true),
            'custom_id' => 'view-'.fake()->unique()->numerify('###'),
            'path_px' => null,
            'path_nx' => null,
            'path_py' => null,
            'path_ny' => null,
            'path_pz' => null,
            'path_nz' => null,
            'has_nadir_censor' => false,
            'nadir_censor_type' => 'blur',
            'default_yaw' => 0.0,
            'default_pitch' => 0.0,
        ];
    }
}
