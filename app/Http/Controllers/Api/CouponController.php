<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\UserCoupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function validateCoupon(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'code' => 'required|string|exists:coupons,code',
            'order_amount' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon->isValid($user->id, $request->order_amount)) {
            $reason = $this->getInvalidReason($coupon, $user->id, $request->order_amount);

            return response()->json([
                'success' => false,
                'message' => 'Coupon is not valid',
                'reason' => $reason,
            ], 400);
        }

        $discount = $coupon->calculateDiscount($request->order_amount);

        return response()->json([
            'success' => true,
            'message' => 'Coupon is valid',
            'data' => [
                'coupon' => [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'name' => $coupon->name,
                    'type' => $coupon->type,
                    'value' => $coupon->value,
                    'max_discount' => $coupon->max_discount,
                ],
                'discount' => $discount,
            ],
        ]);
    }

    public function myCoupons(Request $request)
    {
        $user = $request->user();

        $userCoupons = UserCoupon::where('user_id', $user->id)
            ->with('coupon')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $userCoupons,
        ]);
    }

    public function availableCoupons(Request $request)
    {
        $user = $request->user();
        $orderAmount = $request->order_amount ?? 0;

        $coupons = Coupon::valid()
            ->get()
            ->filter(function ($coupon) use ($user, $orderAmount) {
                return $coupon->isValid($user->id, $orderAmount);
            })
            ->values()
            ->map(function ($coupon) use ($orderAmount) {
                return [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'name' => $coupon->name,
                    'type' => $coupon->type,
                    'value' => $coupon->value,
                    'discount' => $coupon->calculateDiscount($orderAmount),
                    'max_discount' => $coupon->max_discount,
                    'min_order_amount' => $coupon->min_order_amount,
                    'expires_at' => $coupon->end_date,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $coupons,
        ]);
    }


    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:coupons,code|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'start_date' => 'required|date|before_or_equal:end_date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_per_user' => 'nullable|integer|min:1|max:10',
        ]);

        $coupon = Coupon::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Coupon created successfully',
            'data' => $coupon,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $coupon = Coupon::find($id);

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found',
            ], 404);
        }

        $request->validate([
            'code' => "sometimes|string|unique:coupons,code,{$id}|max:50",
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:percentage,fixed',
            'value' => 'sometimes|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'start_date' => 'sometimes|date|before_or_equal:end_date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_per_user' => 'nullable|integer|min:1|max:10',
            'is_active' => 'sometimes|boolean',
        ]);

        $coupon->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Coupon updated successfully',
            'data' => $coupon,
        ]);
    }

    public function destroy($id)
    {
        $coupon = Coupon::find($id);

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found',
            ], 404);
        }

        $coupon->delete();

        return response()->json([
            'success' => true,
            'message' => 'Coupon deleted successfully',
        ]);
    }

    public function toggle($id)
    {
        $coupon = Coupon::find($id);

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Coupon not found',
            ], 404);
        }

        $coupon->is_active = !$coupon->is_active;
        $coupon->save();

        return response()->json([
            'success' => true,
            'message' => $coupon->is_active ? 'Coupon activated' : 'Coupon deactivated',
            'data' => [
                'id' => $coupon->id,
                'is_active' => $coupon->is_active,
            ],
        ]);
    }

    private function getInvalidReason($coupon, $userId, $orderAmount)
    {
        if (!$coupon->is_active) {
            return 'Coupon is not active';
        }

        $now = now()->toDateString();
        if ($now < $coupon->start_date || $now > $coupon->end_date) {
            return 'Coupon has expired or not started yet';
        }

        if ($orderAmount < $coupon->min_order_amount) {
            return "Minimum order amount is {$coupon->min_order_amount}";
        }

        if ($coupon->usage_limit) {
            $totalUsed = UserCoupon::where('coupon_id', $coupon->id)
                ->whereNotNull('used_at')
                ->count();
            if ($totalUsed >= $coupon->usage_limit) {
                return 'Coupon usage limit has been reached';
            }
        }

        $userUsage = UserCoupon::where('user_id', $userId)
            ->where('coupon_id', $coupon->id)
            ->whereNotNull('used_at')
            ->count();

        if ($userUsage >= $coupon->usage_per_user) {
            return 'You have already used this coupon';
        }

        return 'Coupon is not valid';
    }
}
