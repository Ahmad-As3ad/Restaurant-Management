<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderMeal;
use App\Models\OrderMealIngredient;
use App\Models\Inventory;
use App\Models\Meal;
use App\Models\MealIngredient;
use App\Models\Wallet;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Requests\Order\CancelOrderRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function create(CreateOrderRequest $request)
    {
        $user = $request->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found. Please contact support.',
            ], 404);
        }

        DB::beginTransaction();

        try {
            $subtotal = 0;
            $orderMealsData = [];

            foreach ($request->items as $item) {
                $meal = Meal::with(['mealIngredients.ingredient'])->find($item['meal_id']);

                if (!$meal) {
                    throw new \Exception("Meal not found: {$item['meal_id']}");
                }

                foreach ($meal->mealIngredients as $mealIngredient) {
                    $inventory = $mealIngredient->ingredient->inventory;

                    if (!$inventory) {
                        throw new \Exception("Inventory not found for ingredient: {$mealIngredient->ingredient->name}");
                    }

                    $neededQuantity = $mealIngredient->quantity * $item['quantity'];

                    if (!$inventory->hasSufficientQuantity($neededQuantity)) {
                        throw new \Exception("Insufficient quantity for ingredient: {$mealIngredient->ingredient->name}");
                    }
                }

                $itemTotal = $meal->price * $item['quantity'];
                $subtotal += $itemTotal;

                $orderMealsData[] = [
                    'meal' => $meal,
                    'quantity' => $item['quantity'],
                    'unit_price' => $meal->price,
                    'total_price' => $itemTotal,
                    'customizations' => $item['customizations'] ?? null,
                ];
            }

            $discount = 0;
            $couponId = null;

            $tax = $subtotal * 0.10;

            $total = $subtotal - $discount + $tax;

            if (!$wallet->hasSufficientBalance($total)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient balance',
                    'data' => [
                        'balance' => $wallet->balance,
                        'required' => $total,
                    ],
                ], 400);
            }

            $orderNumber = 'ORD-' . strtoupper(uniqid());

            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $orderNumber,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,
                'pickup_time' => $request->pickup_time,
                'notes' => $request->notes,
            ]);

            foreach ($orderMealsData as $data) {
                $orderMeal = OrderMeal::create([
                    'order_id' => $order->id,
                    'meal_id' => $data['meal']->id,
                    'quantity' => $data['quantity'],
                    'unit_price' => $data['unit_price'],
                    'total_price' => $data['total_price'],
                    'customizations' => $data['customizations'],
                ]);

                if ($data['customizations']) {
                    $this->handleCustomizations($orderMeal, $data['customizations'], $data['meal']);
                }

                $this->deductInventory($data['meal'], $data['quantity'], $data['customizations'] ?? null);
            }

            $wallet->withdraw($total, 'order', $order->id, "Payment for order #{$orderNumber}");

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => [
                    'order' => $order->load('orderMeals.meal'),
                    'new_balance' => $wallet->balance,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Order creation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(CancelOrderRequest $request)
    {
        $user = $request->user();
        $order = Order::where('id', $request->order_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        if (!$order->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Order cannot be cancelled at this stage',
                'current_status' => $order->status,
            ], 400);
        }

        DB::beginTransaction();

        try {
            $orderTotal = $order->total;

            $refundAmount = $orderTotal * 0.5;
            $cancellationFee = $orderTotal * 0.5;

            $wallet = $user->wallet;

            if ($refundAmount > 0) {
                $wallet->refund($refundAmount, 'order', $order->id, "Refund for cancelled order #{$order->order_number}");
            }

            if ($cancellationFee > 0) {
                $wallet->addCancellationFee($cancellationFee, 'order', $order->id, "Cancellation fee for order #{$order->order_number}");
            }

            $this->restoreInventory($order);

            $order->markAsCancelled();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'data' => [
                    'refund_amount' => $refundAmount,
                    'cancellation_fee' => $cancellationFee,
                    'new_balance' => $wallet->balance,
                    'order_status' => $order->status,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Order cancellation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, int $id)
    {
        $user = $request->user();

        $order = Order::with(['orderMeals.meal', 'orderMeals.orderMealIngredients.ingredient'])
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order,
        ]);
    }

    public function userOrders(Request $request)
    {
        $user = $request->user();

        $orders = Order::where('user_id', $user->id)
            ->with('orderMeals.meal')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $orders,
        ]);
    }


    private function handleCustomizations(OrderMeal $orderMeal, array $customizations, Meal $meal): void
    {
        if (isset($customizations['added'])) {
            foreach ($customizations['added'] as $ingredientId) {
                $this->validateIngredientBelongsToMeal($ingredientId, $meal);

                OrderMealIngredient::create([
                    'order_meal_id' => $orderMeal->id,
                    'ingredient_id' => $ingredientId,
                    'action' => 'added',
                ]);

                $this->deductIngredientInventory($ingredientId, 1);
            }
        }

        if (isset($customizations['removed'])) {
            foreach ($customizations['removed'] as $ingredientId) {
                $this->validateIngredientBelongsToMeal($ingredientId, $meal);

                OrderMealIngredient::create([
                    'order_meal_id' => $orderMeal->id,
                    'ingredient_id' => $ingredientId,
                    'action' => 'removed',
                ]);

                $this->restoreIngredientInventory($ingredientId, 1);
            }
        }
    }

    private function validateIngredientBelongsToMeal(int $ingredientId, Meal $meal): void
    {
        $exists = MealIngredient::where('meal_id', $meal->id)
            ->where('ingredient_id', $ingredientId)
            ->exists();

        if (!$exists) {
            throw new \Exception("Ingredient does not belong to this meal");
        }
    }

    private function deductInventory(Meal $meal, int $quantity, ?array $customizations = null): void
    {
        foreach ($meal->mealIngredients as $mealIngredient) {
            if ($customizations && isset($customizations['removed'])) {
                if (in_array($mealIngredient->ingredient_id, $customizations['removed'])) {
                    continue;
                }
            }

            $inventory = $mealIngredient->ingredient->inventory;
            $neededQuantity = $mealIngredient->quantity * $quantity;

            $inventory->deduct($neededQuantity);
        }

        if ($customizations && isset($customizations['added'])) {
            foreach ($customizations['added'] as $ingredientId) {
                $this->deductIngredientInventory($ingredientId, $quantity);
            }
        }
    }

    private function deductIngredientInventory(int $ingredientId, int $quantity): void
    {
        $ingredient = \App\Models\Ingredient::find($ingredientId);
        if (!$ingredient) {
            throw new \Exception("Ingredient not found: {$ingredientId}");
        }

        $inventory = $ingredient->inventory;
        if (!$inventory) {
            throw new \Exception("Inventory not found for ingredient: {$ingredient->name}");
        }

        $inventory->deduct($quantity);
    }

    private function restoreInventory(Order $order): void
    {
        foreach ($order->orderMeals as $orderMeal) {
            $meal = $orderMeal->meal;
            $quantity = $orderMeal->quantity;

            foreach ($meal->mealIngredients as $mealIngredient) {
                $inventory = $mealIngredient->ingredient->inventory;
                $quantityToRestore = $mealIngredient->quantity * $quantity;

                $inventory->add($quantityToRestore);
            }

            $addedIngredients = $orderMeal->orderMealIngredients()
                ->where('action', 'added')
                ->get();

            foreach ($addedIngredients as $added) {
                $this->restoreIngredientInventory($added->ingredient_id, $quantity);
            }
        }
    }

    private function restoreIngredientInventory(int $ingredientId, int $quantity): void
    {
        $ingredient = \App\Models\Ingredient::find($ingredientId);
        if (!$ingredient) {
            return; }

        $inventory = $ingredient->inventory;
        if ($inventory) {
            $inventory->add($quantity);
        }
    }

public function adminOrders(Request $request)
{
    $user = $request->user();

    $query = Order::with(['user', 'orderMeals.meal']);

    if ($user->role === 'chef') {
        $query->whereIn('status', ['pending', 'preparing']);
    }

    if ($request->status) {
        $query->where('status', $request->status);
    }

    if ($request->date) {
        $query->whereDate('created_at', $request->date);
    }

    $orders = $query->orderBy('created_at', 'desc')
        ->paginate($request->per_page ?? 20);

    return response()->json([
        'success' => true,
        'data' => $orders,
    ]);
}

public function updateStatus(Request $request, $id)
{
    $user = $request->user();
    $order = Order::find($id);

    if (!$order) {
        return response()->json([
            'success' => false,
            'message' => 'Order not found',
        ], 404);
    }

    $request->validate([
        'status' => 'required|in:pending,preparing,ready,collected,cancelled',
    ]);

    if ($user->role === 'chef' && $request->status !== 'ready') {
        return response()->json([
            'success' => false,
            'message' => 'Chef can only change status to ready',
        ], 403);
    }

    $oldStatus = $order->status;
    $order->status = $request->status;

    if ($request->status === 'ready') {
        $order->ready_at = now();
    }

    if ($request->status === 'collected') {
        $order->collected_at = now();
    }

    $order->save();

    $notificationService = new \App\Services\NotificationService();

    if ($request->status === 'ready') {
        $notificationService->orderReady($order->user_id, $order->order_number);
    }

    if ($request->status === 'cancelled') {
        $notificationService->orderCancelled($order->user_id, $order->order_number);
    }

    return response()->json([
        'success' => true,
        'message' => 'Order status updated',
        'data' => [
            'order' => $order,
            'old_status' => $oldStatus,
            'new_status' => $order->status,
        ],
    ]);
}

public function adminShow($id)
{
    $order = Order::with(['user', 'orderMeals.meal', 'orderMeals.orderMealIngredients.ingredient'])
        ->find($id);

    if (!$order) {
        return response()->json([
            'success' => false,
            'message' => 'Order not found',
        ], 404);
    }

    return response()->json([
        'success' => true,
        'data' => $order,
    ]);
}
}
