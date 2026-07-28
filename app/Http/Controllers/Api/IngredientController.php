<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Inventory;
use Illuminate\Http\Request;

class IngredientController extends Controller
{
    public function index(Request $request)
    {
        $ingredients = Ingredient::with('inventory')
            ->when($request->search, function ($query, $search) {
                return $query->where('name', 'LIKE', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate($request->per_page ?? 50);

        return response()->json([
            'success' => true,
            'data' => $ingredients,
        ]);
    }

    public function show($id)
    {
        $ingredient = Ingredient::with(['inventory', 'meals'])->find($id);

        if (!$ingredient) {
            return response()->json([
                'success' => false,
                'message' => 'Ingredient not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $ingredient,
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:ingredients,name|max:255',
            'unit' => 'required|string|in:piece,gram,kg,ml,liter',
            'initial_quantity' => 'nullable|numeric|min:0',
            'min_quantity' => 'nullable|numeric|min:0',
        ]);

        $ingredient = Ingredient::create($request->only(['name', 'unit']));

        Inventory::create([
            'ingredient_id' => $ingredient->id,
            'quantity' => $request->initial_quantity ?? 0,
            'min_quantity' => $request->min_quantity ?? 0,
            'unit' => $request->unit,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ingredient created successfully',
            'data' => $ingredient->load('inventory'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $ingredient = Ingredient::find($id);

        if (!$ingredient) {
            return response()->json([
                'success' => false,
                'message' => 'Ingredient not found',
            ], 404);
        }

        $request->validate([
            'name' => "sometimes|string|unique:ingredients,name,{$id}|max:255",
            'unit' => 'sometimes|string|in:piece,gram,kg,ml,liter',
        ]);

        $ingredient->update($request->only(['name', 'unit']));

        if ($request->has('initial_quantity') || $request->has('min_quantity')) {
            $inventory = $ingredient->inventory;
            if ($inventory) {
                if ($request->has('initial_quantity')) {
                    $inventory->quantity = $request->initial_quantity;
                }
                if ($request->has('min_quantity')) {
                    $inventory->min_quantity = $request->min_quantity;
                }
                $inventory->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Ingredient updated successfully',
            'data' => $ingredient->load('inventory'),
        ]);
    }

    public function destroy($id)
    {
        $ingredient = Ingredient::find($id);

        if (!$ingredient) {
            return response()->json([
                'success' => false,
                'message' => 'Ingredient not found',
            ], 404);
        }

        if ($ingredient->mealIngredients()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete ingredient that is used in meals',
            ], 400);
        }

        if ($ingredient->inventory) {
            $ingredient->inventory->delete();
        }

        $ingredient->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ingredient deleted successfully',
        ]);
    }
}
