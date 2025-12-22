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

        // Provide a conventional API pagination envelope (data, links, meta)
        return response()->json([
            'data' => $cars->items(),
            'links' => [
                'first' => $cars->url(1),
                'last' => $cars->url($cars->lastPage()),
                'prev' => $cars->previousPageUrl(),
                'next' => $cars->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $cars->currentPage(),
                'from' => $cars->firstItem(),
                'last_page' => $cars->lastPage(),
                'path' => $cars->path(),
                'per_page' => $cars->perPage(),
                'to' => $cars->lastItem(),
                'total' => $cars->total(),
            ],
        ]);
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
