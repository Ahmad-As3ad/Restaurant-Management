<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Table;
use App\Models\Wallet;
use App\Http\Requests\Booking\CreateBookingRequest;
use App\Http\Requests\Booking\CancelBookingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    // إنشاء حجز جديد
    public function create(CreateBookingRequest $request)
    {
        $user = $request->user();
        $wallet = $user->wallet;

        if (!$wallet) {
            return response()->json([
                'success' => false,
                'message' => 'Wallet not found. Please contact support.',
            ], 404);
        }

        $table = Table::find($request->table_id);

        // التحقق من سعة الطاولة
        if ($table->capacity < $request->number_of_people) {
            return response()->json([
                'success' => false,
                'message' => 'Table capacity is insufficient',
                'data' => [
                    'table_capacity' => $table->capacity,
                    'requested' => $request->number_of_people,
                ],
            ], 400);
        }

        // التحقق من توفر الطاولة في الوقت المطلوب
        $isAvailable = $this->checkTableAvailability(
            $request->table_id,
            $request->booking_date,
            $request->booking_time
        );

        if (!$isAvailable) {
            return response()->json([
                'success' => false,
                'message' => 'Table is not available at this time',
            ], 400);
        }

        // حساب السعر الإجمالي
        $totalPrice = $table->price_per_person * $request->number_of_people;

        // التحقق من الرصيد
        if (!$wallet->hasSufficientBalance($totalPrice)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance',
                'data' => [
                    'balance' => $wallet->balance,
                    'required' => $totalPrice,
                ],
            ], 400);
        }

        DB::beginTransaction();

        try {
            // إنشاء الحجز
            $bookingNumber = 'BK-' . strtoupper(uniqid());

            $booking = Booking::create([
                'user_id' => $user->id,
                'table_id' => $request->table_id,
                'booking_number' => $bookingNumber,
                'booking_date' => $request->booking_date,
                'booking_time' => $request->booking_time,
                'number_of_people' => $request->number_of_people,
                'total_price' => $totalPrice,
                'status' => 'pending',
                'notes' => $request->notes,
            ]);

            // خصم الرصيد
            $wallet->withdraw($totalPrice, 'booking', $booking->id, "Payment for booking #{$bookingNumber}");

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully',
                'data' => [
                    'booking' => $booking->load('table'),
                    'new_balance' => $wallet->balance,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Booking creation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // إلغاء حجز
    public function cancel(CancelBookingRequest $request)
    {
        $user = $request->user();

        $booking = Booking::where('id', $request->booking_id)
            ->where('user_id', $user->id)
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        if (!$booking->canBeCancelled()) {
            $message = 'Booking cannot be cancelled at this stage. ';
            if ($booking->status === 'cancelled') {
                $message = 'Booking is already cancelled.';
            } elseif ($booking->status === 'completed') {
                $message = 'Completed booking cannot be cancelled.';
            } else {
                $message .= 'Please cancel at least 1 hour before the booking time.';
            }

            return response()->json([
                'success' => false,
                'message' => $message,
                'current_status' => $booking->status,
                'booking_time' => $booking->booking_date . ' ' . $booking->booking_time,
            ], 400);
        }

        DB::beginTransaction();

        try {
            $bookingTotal = $booking->total_price;

            // 50% refund
            $refundAmount = $bookingTotal * 0.5;
            $cancellationFee = $bookingTotal * 0.5;

            $wallet = $user->wallet;

            // استرداد 50% للمحفظة
            if ($refundAmount > 0) {
                $wallet->refund($refundAmount, 'booking', $booking->id, "Refund for cancelled booking #{$booking->booking_number}");
            }

            // تسجيل رسوم الإلغاء
            if ($cancellationFee > 0) {
                $wallet->addCancellationFee($cancellationFee, 'booking', $booking->id, "Cancellation fee for booking #{$booking->booking_number}");
            }

            // تحديث حالة الحجز
            $booking->markAsCancelled();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully',
                'data' => [
                    'refund_amount' => $refundAmount,
                    'cancellation_fee' => $cancellationFee,
                    'new_balance' => $wallet->balance,
                    'booking_status' => $booking->status,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Booking cancellation failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // عرض تفاصيل الحجز
    public function show(Request $request, int $id)
    {
        $user = $request->user();

        $booking = Booking::with('table')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $booking,
        ]);
    }

    // قائمة حجوزات المستخدم
    public function userBookings(Request $request)
    {
        $user = $request->user();

        $bookings = Booking::where('user_id', $user->id)
            ->with('table')
            ->orderBy('booking_date', 'asc')
            ->orderBy('booking_time', 'asc')
            ->paginate($request->per_page ?? 20);

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    // الحجوزات القادمة
    public function upcomingBookings(Request $request)
    {
        $user = $request->user();

        $bookings = Booking::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where('booking_date', '>=', now()->toDateString())
            ->with('table')
            ->orderBy('booking_date', 'asc')
            ->orderBy('booking_time', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    // دوال مساعدة

    private function checkTableAvailability(int $tableId, string $date, string $time): bool
    {
        // التحقق من الحجوزات النشطة في نفس الوقت
        $existingBooking = Booking::where('table_id', $tableId)
            ->where('booking_date', $date)
            ->where('booking_time', $time)
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        if ($existingBooking) {
            return false;
        }

        return true;
    }
}
