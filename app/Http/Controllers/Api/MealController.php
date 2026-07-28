<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Models\MealIngredient;
use App\Models\Ingredient;
use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MealController extends Controller
{
    public function index(Request $request)
    {
        $query = Meal::with(['mealIngredients.ingredient', 'ingredients'])
            ->active();

        if ($request->category) {
            $query->category($request->category);
        }

        if ($request->sort === 'price_asc') {
            $query->orderBy('price', 'asc');
        } elseif ($request->sort === 'price_desc') {
            $query->orderBy('price', 'desc');
        } elseif ($request->sort === 'rating') {
            $query->orderBy('rating_avg', 'desc');
        } elseif ($request->sort === 'popular') {
            $query->orderBy('rating_count', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $meals = $query->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $meals,
        ]);
    }

    public function show($id)
    {
        $meal = Meal::with([
            'mealIngredients.ingredient',
            'ingredients',
            'ratings' => function ($query) {
                $query->latest()->limit(5);
            }
        ])->find($id);

        if (!$meal) {
            return response()->json([
                'success' => false,
                'message' => 'Meal not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $meal,
        ]);
    }

    public function ingredients($id)
    {
        $meal = Meal::with(['mealIngredients.ingredient', 'ingredients'])->find($id);

        if (!$meal) {
            return response()->json([
                'success' => false,
                'message' => 'Meal not found',
            ], 404);
        }

        $defaultIngredients = $meal->mealIngredients()
            ->where('is_default', true)
            ->with('ingredient')
            ->get();

        $optionalIngredients = $meal->mealIngredients()
            ->where('is_default', false)
            ->with('ingredient')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'meal' => $meal->only(['id', 'name', 'price']),
                'default_ingredients' => $defaultIngredients,
                'optional_ingredients' => $optionalIngredients,
            ],
        ]);
    }

    public function byCategory($category)
    {
        $meals = Meal::active()
            ->category($category)
            ->with('ingredients')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $meals,
        ]);
    }

    public function search($query)
    {
        $meals = Meal::active()
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('description', 'LIKE', "%{$query}%")
            ->with('ingredients')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $meals,
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category' => 'required|in:appetizer,main,drink,dessert',
            'image' => 'nullable|image|max:2048',
            'ingredients' => 'nullable|array',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.01',
            'ingredients.*.is_default' => 'boolean',
        ]);

        $data = $request->except(['image', 'ingredients']);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('meals', 'public');
            $data['image'] = $path;
        }

        $meal = Meal::create($data);

        if ($request->ingredients) {
            foreach ($request->ingredients as $ingredient) {
                MealIngredient::create([
                    'meal_id' => $meal->id,
                    'ingredient_id' => $ingredient['ingredient_id'],
                    'quantity' => $ingredient['quantity'],
                    'is_default' => $ingredient['is_default'] ?? true,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Meal created successfully',
            'data' => $meal->load('ingredients'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $meal = Meal::find($id);

        if (!$meal) {
            return response()->json([
                'success' => false,
                'message' => 'Meal not found',
            ], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'category' => 'sometimes|in:appetizer,main,drink,dessert',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'sometimes|boolean',
            'ingredients' => 'nullable|array',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity' => 'required|numeric|min:0.01',
            'ingredients.*.is_default' => 'boolean',
        ]);

        $data = $request->except(['image', 'ingredients']);

        if ($request->hasFile('image')) {
            if ($meal->image && Storage::disk('public')->exists($meal->image)) {
                Storage::disk('public')->delete($meal->image);
            }

            $path = $request->file('image')->store('meals', 'public');
            $data['image'] = $path;
        }

        $meal->update($data);

        if ($request->ingredients) {
            MealIngredient::where('meal_id', $meal->id)->delete();

            foreach ($request->ingredients as $ingredient) {
                MealIngredient::create([
                    'meal_id' => $meal->id,
                    'ingredient_id' => $ingredient['ingredient_id'],
                    'quantity' => $ingredient['quantity'],
                    'is_default' => $ingredient['is_default'] ?? true,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Meal updated successfully',
            'data' => $meal->load('ingredients'),
        ]);
    }

    public function destroy($id)
    {
        $meal = Meal::find($id);

        if (!$meal) {
            return response()->json([
                'success' => false,
                'message' => 'Meal not found',
            ], 404);
        }

        if ($meal->image && Storage::disk('public')->exists($meal->image)) {
            Storage::disk('public')->delete($meal->image);
        }

        MealIngredient::where('meal_id', $meal->id)->delete();

        $meal->delete();

        return response()->json([
            'success' => true,
            'message' => 'Meal deleted successfully',
        ]);
    }

    public function toggle($id)
    {
        $meal = Meal::find($id);

        if (!$meal) {
            return response()->json([
                'success' => false,
                'message' => 'Meal not found',
            ], 404);
        }

        $meal->is_active = !$meal->is_active;
        $meal->save();

        return response()->json([
            'success' => true,
            'message' => $meal->is_active ? 'Meal activated' : 'Meal deactivated',
            'data' => [
                'id' => $meal->id,
                'is_active' => $meal->is_active,
            ],
        ]);
    }

    public function addIngredient(Request $request, $id)
    {
        $meal = Meal::find($id);

        if (!$meal) {
            return response()->json([
                'success' => false,
                'message' => 'Meal not found',
            ], 404);
        }

        $request->validate([
            'ingredient_id' => 'required|exists:ingredients,id',
            'quantity' => 'required|numeric|min:0.01',
            'is_default' => 'boolean',
        ]);

        $mealIngredient = MealIngredient::create([
            'meal_id' => $meal->id,
            'ingredient_id' => $request->ingredient_id,
            'quantity' => $request->quantity,
            'is_default' => $request->is_default ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ingredient added to meal',
            'data' => $mealIngredient->load('ingredient'),
        ]);
    }

    public function removeIngredient($mealId, $ingredientId)
    {
        $mealIngredient = MealIngredient::where('meal_id', $mealId)
            ->where('ingredient_id', $ingredientId)
            ->first();

        if (!$mealIngredient) {
            return response()->json([
                'success' => false,
                'message' => 'Ingredient not found in this meal',
            ], 404);
        }

        $mealIngredient->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ingredient removed from meal',
        ]);
    }
}
