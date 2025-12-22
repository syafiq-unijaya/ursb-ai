<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class OwnershipController extends Controller
{
    public function store(Request $request, Car $car): RedirectResponse
    {
        $data = $request->validate([
            'plate' => ['required', 'string', 'max:20', Rule::unique('car_user', 'plate')],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $targetUserId = $data['user_id'] ?? $request->user()->id;

        // Allow adding ownership for self or for admins
        if ($request->user()->id !== $targetUserId && !$request->user()->is_admin) {
            abort(403);
        }

        // Prevent duplicate ownership for same car/user
        if ($car->users()->where('user_id', $targetUserId)->exists()) {
            return redirect()->back();
        }

        $car->users()->attach($targetUserId, ['plate' => $data['plate']]);

        return redirect()->route('users.show', $targetUserId);
    }

    public function storeMany(Request $request, \App\Models\User $user): RedirectResponse
    {
        $data = $request->validate([
            'owners' => ['required', 'array'],
            'owners.*.car_id' => ['required', 'integer', 'exists:cars,id'],
            'owners.*.plate' => ['required', 'string', 'max:20', 'distinct'],
        ]);

        // only allow adding ownerships for yourself
        if ($request->user()->id !== $user->id) {
            abort(403);
        }

        // Ensure plates are globally unique
        foreach ($data['owners'] as $owner) {
            $exists = \DB::table('car_user')->where('plate', $owner['plate'])->exists();
            if ($exists) {
                return redirect()->back();
            }
        }

        foreach ($data['owners'] as $owner) {
            $carId = $owner['car_id'];
            $plate = $owner['plate'];

            // skip if ownership already exists
            if (!\DB::table('car_user')->where('car_id', $carId)->where('user_id', $user->id)->exists()) {
                \DB::table('car_user')->insert([
                    'car_id' => $carId,
                    'user_id' => $user->id,
                    'plate' => $plate,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        return redirect()->route('users.show', $user->id);
    }

    public function destroy(Request $request, Car $car)
    {
        $targetUserId = $request->input('user_id') ?? $request->user()->id;

        // Only allow removing ownership for yourself via the UI.
        if ($request->user()->id !== (int) $targetUserId) {
            abort(403);
        }

        // Ensure the target user owns this car
        if (!$car->users()->where('user_id', $targetUserId)->exists()) {
            abort(403);
        }

        $car->users()->detach($targetUserId);

        return redirect()->back();
    }
}
