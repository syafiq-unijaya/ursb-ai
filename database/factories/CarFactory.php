<?php

namespace Database\Factories;

use App\Models\Car;
use Illuminate\Database\Eloquent\Factories\Factory;

class CarFactory extends Factory
{
    protected $model = Car::class;

    public function definition()
    {
        return [
            'brand' => $this->faker->randomElement(['Toyota', 'Honda', 'Ford', 'BMW', 'Audi']),
            'model' => $this->faker->word,
            'variant' => $this->faker->word,
            'year' => $this->faker->numberBetween(1990, (int) date('Y')),
        ];
    }
}
