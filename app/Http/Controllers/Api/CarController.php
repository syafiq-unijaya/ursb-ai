<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Car;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CarController extends Controller
{
    public function index(Request $request)
    {
        $cars = Car::query()
            ->select('id', 'brand', 'model', 'variant', 'year', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($cars);
    }

    public function show(Car $car)
    {
        return response()->json($car->only(['id', 'brand', 'model', 'variant', 'year', 'created_at']));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'variant' => 'nullable|string|max:255',
            'year' => 'nullable|integer',
        ]);

        $car = Car::create($request->only(['brand', 'model', 'variant', 'year']));

        return response()->json($car, 201);
    }

    public function update(Request $request, Car $car)
    {
        $this->validate($request, [
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'variant' => 'nullable|string|max:255',
            'year' => 'nullable|integer',
        ]);

        $car->update($request->only(['brand', 'model', 'variant', 'year']));

        return response()->json($car);
    }

    public function destroy(Car $car)
    {
        $car->delete();

        return response()->json(null, 204);
    }
}
