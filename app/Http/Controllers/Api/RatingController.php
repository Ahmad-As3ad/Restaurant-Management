<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rating;
use App\Models\Meal;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RatingController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'meal_id' => 'required|exists:meals,id',
            'order_id' => 'required|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        if (!Rating::canRate($user->id, $request->meal_id, $request->order_id)) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot rate this meal. Either you did not order it, order is not completed, or you already rated it.',
            ], 400);
        }

        $rating = Rating::create([
            'user_id' => $user->id,
            'meal_id' => $request->meal_id,
            'order_id' => $request->order_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        $meal = Meal::find($request->meal_id);
        $meal->updateRating();

        return response()->json([
            'success' => true,
            'message' => 'Rating submitted successfully',
            'data' => [
                'rating' => $rating,
                'meal' => [
                    'id' => $meal->id,
                    'name' => $meal->name,
                    'rating_avg' => $meal->rating_avg,
                    'rating_count' => $meal->rating_count,
                ],
            ],
        ], 201);
    }

    public function myRatings(Request $request)
    {
        $user = $request->user();

        $ratings = Rating::where('user_id', $user->id)
            ->with(['meal', 'order'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $ratings,
        ]);
    }

    public function mealRatings($mealId)
    {
        $meal = Meal::find($mealId);

        if (!$meal) {
            return response()->json([
                'success' => false,
                'message' => 'Meal not found',
            ], 404);
        }

        $ratings = Rating::where('meal_id', $mealId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => [
                'meal' => $meal->only(['id', 'name', 'rating_avg', 'rating_count']),
                'ratings' => $ratings,
            ],
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        $rating = Rating::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$rating) {
            return response()->json([
                'success' => false,
                'message' => 'Rating not found',
            ], 404);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $rating->update([
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        $meal = Meal::find($rating->meal_id);
        $meal->updateRating();

        return response()->json([
            'success' => true,
            'message' => 'Rating updated successfully',
            'data' => $rating,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $rating = Rating::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$rating) {
            return response()->json([
                'success' => false,
                'message' => 'Rating not found',
            ], 404);
        }

        $mealId = $rating->meal_id;
        $rating->delete();

        $meal = Meal::find($mealId);
        $meal->updateRating();

        return response()->json([
            'success' => true,
            'message' => 'Rating deleted successfully',
        ]);
    }
}
