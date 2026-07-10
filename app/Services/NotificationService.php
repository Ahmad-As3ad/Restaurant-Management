<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

class NotificationService
{
    /**
     * إرسال إشعار لمستخدم واحد
     */
    public function sendToUser(int $userId, string $type, string $title, string $message, ?array $data = null): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * إرسال إشعار لمجموعة من المستخدمين (حسب الدور)
     */
    public function sendToRole(string $role, string $type, string $title, string $message, ?array $data = null): void
    {
        $users = User::where('role', $role)->get();

        foreach ($users as $user) {
            $this->sendToUser($user->id, $type, $title, $message, $data);
        }
    }

    /**
     * إرسال إشعار للمستخدمين المحددين
     */
    public function sendToUsers(array $userIds, string $type, string $title, string $message, ?array $data = null): void
    {
        foreach ($userIds as $userId) {
            $this->sendToUser($userId, $type, $title, $message, $data);
        }
    }

    /**
     * إرسال إشعارات متعددة (للإشعارات الجماعية)
     */
    public function sendBulk(array $notifications): void
    {
        $data = [];

        foreach ($notifications as $notification) {
            $data[] = [
                'user_id' => $notification['user_id'],
                'type' => $notification['type'],
                'title' => $notification['title'],
                'message' => $notification['message'],
                'data' => $notification['data'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Notification::insert($data);
    }

    /**
     * إشعارات مخصصة
     */
    public function orderCreated(int $userId, string $orderNumber): void
    {
        $this->sendToUser(
            $userId,
            'order',
            '📋 New Order Created',
            "Your order #{$orderNumber} has been received and is being processed.",
            ['order_number' => $orderNumber]
        );
    }

    public function orderReady(int $userId, string $orderNumber): void
    {
        $this->sendToUser(
            $userId,
            'order',
            '✅ Order Ready for Pickup',
            "Your order #{$orderNumber} is ready for pickup!",
            ['order_number' => $orderNumber]
        );
    }

    public function orderCancelled(int $userId, string $orderNumber): void
    {
        $this->sendToUser(
            $userId,
            'order',
            '❌ Order Cancelled',
            "Your order #{$orderNumber} has been cancelled.",
            ['order_number' => $orderNumber]
        );
    }

    public function bookingCreated(int $userId, string $bookingNumber): void
    {
        $this->sendToUser(
            $userId,
            'booking',
            '📅 Booking Created',
            "Your booking #{$bookingNumber} has been created and is pending confirmation.",
            ['booking_number' => $bookingNumber]
        );
    }

    public function bookingConfirmed(int $userId, string $bookingNumber): void
    {
        $this->sendToUser(
            $userId,
            'booking',
            '✅ Booking Confirmed',
            "Your booking #{$bookingNumber} has been confirmed!",
            ['booking_number' => $bookingNumber]
        );
    }

    public function bookingCancelled(int $userId, string $bookingNumber): void
    {
        $this->sendToUser(
            $userId,
            'booking',
            '❌ Booking Cancelled',
            "Your booking #{$bookingNumber} has been cancelled.",
            ['booking_number' => $bookingNumber]
        );
    }

    public function couponExpiringSoon(int $userId, string $couponCode): void
    {
        $this->sendToUser(
            $userId,
            'coupon',
            '⚠️ Coupon Expiring Soon',
            "Your coupon {$couponCode} is expiring soon! Use it before it expires.",
            ['coupon_code' => $couponCode]
        );
    }

    public function newOrderForAdmin(int $adminId, string $orderNumber): void
    {
        $this->sendToUser(
            $adminId,
            'order',
            '🆕 New Order Received',
            "A new order #{$orderNumber} has been placed.",
            ['order_number' => $orderNumber]
        );
    }

    public function newOrderForChef(int $chefId, string $orderNumber): void
    {
        $this->sendToUser(
            $chefId,
            'order',
            '👨‍🍳 New Order for Kitchen',
            "Order #{$orderNumber} is ready to be prepared.",
            ['order_number' => $orderNumber]
        );
    }

    public function lowInventoryAlert(int $userId, string $ingredientName): void
    {
        $this->sendToUser(
            $userId,
            'system',
            '⚠️ Low Inventory Alert',
            "Ingredient '{$ingredientName}' is running low. Please restock.",
            ['ingredient' => $ingredientName]
        );
    }
}
