<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->select('id', 'name', 'email', 'phone_no', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('users/Index', [
            'users' => $users,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('users/Create');
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->route('users.index');
    }

    public function show(User $user): Response
    {
        // Reference car models available for selection
        $availableCars = \App\Models\Car::query()->select('id', 'brand', 'model')->orderBy('brand')->get();

        $cars = $user->cars()->get()->map(function ($car) {
            return array_merge($car->only(['id', 'brand', 'model', 'variant', 'year']), [
                'plate' => $car->pivot->plate ?? null,
                'owned_at' => $car->pivot->created_at ?? null,
            ]);
        });

        return Inertia::render('users/Show', [
            'user' => array_merge($user->only(['id', 'name', 'email', 'phone_no', 'created_at']), [
                'cars' => $cars,
                'available_cars' => $availableCars,
                'is_current_user' => auth()->check() && auth()->id() === $user->id,
            ]),
        ]);
    }

    public function edit(User $user): Response
    {
        $availableCars = \App\Models\Car::query()->select('id', 'brand', 'model')->orderBy('brand')->get();

        $cars = $user->cars()->get()->map(function ($car) {
            return array_merge($car->only(['id', 'brand', 'model', 'variant', 'year']), [
                'plate' => $car->pivot->plate ?? null,
                'owned_at' => $car->pivot->created_at ?? null,
            ]);
        });

        return Inertia::render('users/Edit', [
            'user' => array_merge($user->only(['id', 'name', 'email', 'phone_no']), [
                'cars' => $cars,
                'available_cars' => $availableCars,
            ]),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        if (!empty($data['password'] ?? '')) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('users.index');
    }
}
