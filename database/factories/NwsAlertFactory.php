<?php

namespace Database\Factories;

use App\Models\NwsAlert;
use Illuminate\Database\Eloquent\Factories\Factory;

class NwsAlertFactory extends Factory
{
    protected $model = NwsAlert::class;

    public function definition(): array
    {
        $sent = now()->subHours(rand(1, 12));

        return [
            'id' => 'https://api.weather.gov/alerts/urn:oid:2.49.0.1.840.0.' . $this->faker->unique()->uuid(),
            'event' => $this->faker->randomElement(['Special Marine Warning', 'Small Craft Advisory', 'Gale Warning']),
            'headline' => $this->faker->sentence(),
            'severity' => $this->faker->randomElement(['Minor', 'Moderate', 'Severe', 'Extreme']),
            'certainty' => $this->faker->randomElement(['Observed', 'Likely', 'Possible']),
            'urgency' => $this->faker->randomElement(['Immediate', 'Expected', 'Future']),
            'status' => 'Actual',
            'message_type' => 'Alert',
            'category' => 'Met',
            'sent' => $sent,
            'effective' => $sent,
            'onset' => $sent,
            'expires' => now()->addHours(rand(1, 6)), // always still active; override for expired cases
            'ends' => null,
            'raw' => [],
            'nws_updated_at' => $sent,
        ];
    }
}
