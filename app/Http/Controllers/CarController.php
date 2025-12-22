<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCarRequest;
use App\Http\Requests\UpdateCarRequest;
use App\Models\Car;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Inertia\Inertia;
use Inertia\Response;

class CarController extends Controller
{
    public function index(Request $request): Response
    {
        $cars = Car::query()
            ->select('id', 'brand', 'model', 'variant', 'year', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('cars/Index', [
            'cars' => $cars,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('cars/Create');
    }

    public function store(StoreCarRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Create a reference car model (not ownership)
        Car::create($data);

        return redirect()->route('cars.index');
    }

    public function show(Car $car): Response
    {
        return Inertia::render('cars/Show', [
            'car' => $car->only(['id', 'brand', 'model', 'variant', 'year', 'created_at']),
        ]);
    }

    public function edit(Car $car): Response
    {
        return Inertia::render('cars/Edit', [
            'car' => $car->only(['id', 'brand', 'model', 'variant', 'year']),
        ]);
    }

    public function update(UpdateCarRequest $request, Car $car): RedirectResponse
    {
        $data = $request->validated();

        $car->update($data);

        return redirect()->route('cars.index');
    }

    public function destroy(Car $car)
    {
        $car->delete();

        return redirect()->route('cars.index');
    }
}
