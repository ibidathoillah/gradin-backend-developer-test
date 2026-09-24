<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourierRequest;
use App\Http\Requests\UpdateCourierRequest;
use App\Models\Courier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CourierController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Courier::query();
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            foreach (preg_split('/\s+/', $search, -1, PREG_SPLIT_NO_EMPTY) as $term) {
                $query->where('name', 'like', '%'.$term.'%');
            }
        }

        $levels = collect(explode(',', (string) $request->query('level', '')))
            ->filter(fn ($value) => ctype_digit(trim($value)))
            ->map(fn ($value) => (int) trim($value))
            ->filter(fn ($value) => $value >= 1 && $value <= 5)
            ->unique()->values();
        if ($levels->isNotEmpty()) {
            $query->whereIn('level', $levels);
        }

        $sort = in_array($request->query('sort'), ['created_at', 'registered_at'], true) ? 'created_at' : 'name';
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = min(max((int) $request->query('per_page', 15), 1), 100);

        return response()->json($query->orderBy($sort, $direction)->paginate($perPage)->withQueryString());
    }

    public function store(StoreCourierRequest $request): JsonResponse
    {
        return response()->json(Courier::create($request->validated()), 201);
    }

    public function show(Courier $courier): JsonResponse
    {
        return response()->json($courier);
    }

    public function update(UpdateCourierRequest $request, Courier $courier): JsonResponse
    {
        $courier->update($request->validated());
        return response()->json($courier->refresh());
    }

    public function destroy(Courier $courier): Response
    {
        $courier->delete();
        return response()->noContent();
    }
}
