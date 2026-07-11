<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Http\Requests\StoreMealRequest;
use App\Http\Requests\UpdateMealRequest;
use App\Http\Resources\MealResource;
use Illuminate\Http\JsonResponse;
use Gate;

class MealController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    /**
     * عرض جميع الوجبات (مع تصفية الوجبات النشطة فقط للعملاء)
     */
    public function index(): JsonResponse
    {
        $user = auth('sanctum')->user();

        $query = Meal::query();

        // العملاء يشوفون الوجبات النشطة فقط
        if ($user && $user->role === 'customer') {
            $query->where('is_active', true);
        }
        // الـ Admin و Chef يشوفون الكل

        $meals = $query->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => MealResource::collection($meals),
            'meta' => [
                'total' => $meals->total(),
                'per_page' => $meals->perPage(),
                'current_page' => $meals->currentPage(),
            ]
        ]);
    }

    /**
     * عرض وجبة معينة
     */
    public function show(Meal $meal): JsonResponse
    {
        $user = auth('sanctum')->user();

        // العملاء ما يشوفون الوجبات المعطلة
        if ($user?->role === 'customer' && !$meal->is_active) {
            return response()->json([
                'status' => 'error',
                'message' => 'الوجبة غير متاحة'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => new MealResource($meal)
        ]);
    }

    /**
     * إضافة وجبة جديدة (Admin و Chef فقط)
     */
    public function store(StoreMealRequest $request): JsonResponse
    {
        Gate::authorize('create', Meal::class);

        $meal = Meal::create($request->validated());

        // إضافة المكونات الافتراضية
        if ($request->has('ingredients')) {
            foreach ($request->ingredients as $ingredient) {
                $meal->ingredients()->attach(
                    $ingredient['ingredient_id'],
                    ['is_default' => $ingredient['is_default'] ?? false]
                );
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم إضافة الوجبة بنجاح',
            'data' => new MealResource($meal)
        ], 201);
    }

    /**
     * تحديث وجبة (Admin و Chef فقط)
     */
    public function update(UpdateMealRequest $request, Meal $meal): JsonResponse
    {
        Gate::authorize('update', $meal);

        $meal->update($request->validated());

        // تحديث المكونات
        if ($request->has('ingredients')) {
            $meal->ingredients()->sync(
                collect($request->ingredients)->mapWithKeys(function ($ingredient) {
                    return [$ingredient['ingredient_id'] => ['is_default' => $ingredient['is_default'] ?? false]];
                })->all()
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث الوجبة بنجاح',
            'data' => new MealResource($meal)
        ]);
    }

    /**
     * حذف وجبة (تعطيل بدل الحذف الفعلي)
     */
    public function destroy(Meal $meal): JsonResponse
    {
        Gate::authorize('delete', $meal);

        $meal->update(['is_active' => false]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم تعطيل الوجبة'
        ]);
    }
}
