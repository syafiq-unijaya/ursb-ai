<?php
namespace App\AiTools;

use DeveloperUnijaya\AiChatbox\Orchestration\Contracts\ToolInterface;
use Illuminate\Http\Request;

class GetUsersTool implements ToolInterface
{
    public function name(): string
    {
        return 'get_users';
    }

    public function description(): string
    {
        return 'List users records, optionally filtered. Returns the selected columns.';
    }

    public function parameters(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'name' => ['type' => 'string', 'description' => 'Filter by name.'],
                'email' => ['type' => 'string', 'description' => 'Filter by email.'],
                'phone_no' => ['type' => 'string', 'description' => 'Filter by phone_no.'],
                'email_verified_at' => ['type' => 'string', 'description' => 'Filter by email_verified_at.'],
                'is_admin' => ['type' => 'integer', 'description' => 'Filter by is_admin.'],
            ],
            'required' => [],
        ];
    }

    public function authorize(?Request $request = null): bool
    {
        // TODO: tighten this to your needs. Defaults to authenticated users only.
        return $request?->user() !== null;
    }

    public function handle(array $arguments): mixed
    {
        $query = \App\Models\User::query();

        // TODO: scope this query to the current user, e.g. ->where('user_id', auth()->id())
        if (array_key_exists('name', $arguments) && $arguments['name'] !== null) {
            $query->where('name', $arguments['name']);
        }
        if (array_key_exists('email', $arguments) && $arguments['email'] !== null) {
            $query->where('email', $arguments['email']);
        }
        if (array_key_exists('phone_no', $arguments) && $arguments['phone_no'] !== null) {
            $query->where('phone_no', $arguments['phone_no']);
        }
        if (array_key_exists('email_verified_at', $arguments) && $arguments['email_verified_at'] !== null) {
            $query->where('email_verified_at', $arguments['email_verified_at']);
        }
        if (array_key_exists('is_admin', $arguments) && $arguments['is_admin'] !== null) {
            $query->where('is_admin', $arguments['is_admin']);
        }

        return [
            'users' => $query->get(['id', 'name', 'email', 'phone_no', 'email_verified_at', 'password', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'remember_token', 'is_admin'])->toArray(),
        ];
    }
}
