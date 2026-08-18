<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Meal;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function add(Request $request)
    {
        $request->validate([
            'meal_id' => 'required|exists:meals,id',
        ]);

        $user = $request->user();

        $exists = Favorite::where('user_id', $user->id)
            ->where('meal_id', $request->meal_id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Meal already in favorites',
            ], 400);
        }

        $favorite = Favorite::create([
            'user_id' => $user->id,
            'meal_id' => $request->meal_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Meal added to favorites',
            'data' => $favorite,
        ]);
    }


    public function remove(Request $request)
    {
        $request->validate([
            'meal_id' => 'required|exists:meals,id',
        ]);

        $user = $request->user();

        $favorite = Favorite::where('user_id', $user->id)
            ->where('meal_id', $request->meal_id)
            ->first();

        if (!$favorite) {
            return response()->json([
                'success' => false,
                'message' => 'Meal not in favorites',
            ], 404);
        }

        $favorite->delete();

        return response()->json([
            'success' => true,
            'message' => 'Meal removed from favorites',
        ]);
    }


    public function index(Request $request)
    {
        $user = $request->user();

        $favorites = Favorite::where('user_id', $user->id)
            ->with('meal')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $favorites,
        ]);
    }

    public function check(Request $request, $mealId)
    {
        $user = $request->user();

        $isFavorite = Favorite::where('user_id', $user->id)
            ->where('meal_id', $mealId)
            ->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'meal_id' => $mealId,
                'is_favorite' => $isFavorite,
            ],
        ]);
    }


    public function clear(Request $request)
    {
        $user = $request->user();

        $count = Favorite::where('user_id', $user->id)->count();

        Favorite::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => "All favorites cleared ({$count} items)",
        ]);
    }
}
