<?php

namespace App\AiTools;

use App\Models\Car;
use DeveloperUnijaya\AiChatbox\Orchestration\Contracts\ToolInterface;
use Illuminate\Http\Request;

/**
 * Demo orchestrator tool: lets the chatbot look up real rows from the `cars` table.
 * Registered in AppServiceProvider::boot().
 */
class GetCarsTool implements ToolInterface
{
    public function name(): string
    {
        return 'get_cars';
    }

    public function description(): string
    {
        return 'List cars stored in the system, optionally filtered by brand. '
            . 'Returns each car\'s brand, model, variant and year.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'brand' => [
                    'type' => 'string',
                    'description' => 'Optional brand to filter by, e.g. "Toyota".',
                ],
            ],
            'required' => [],
        ];
    }

    public function authorize(?Request $request = null): bool
    {
        return true;
    }

    public function handle(array $arguments): mixed
    {
        $query = Car::query();

        if (!empty($arguments['brand'])) {
            $query->where('brand', 'like', '%' . $arguments['brand'] . '%');
        }

        return [
            'cars' => $query->get(['brand', 'model', 'variant', 'year'])->toArray(),
        ];
    }
}
