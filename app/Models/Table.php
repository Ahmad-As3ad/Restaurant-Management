<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table extends Model
{
    use HasFactory;

    protected $fillable = [
        'table_number',
        'capacity',
        'location',
        'price_per_person',
        'status',
    ];

    protected $casts = [
        'capacity' => 'integer',
        'price_per_person' => 'decimal:2',
    ];

    // العلاقات
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // التحقق من توفر الطاولة
    public function isAvailable(string $date, string $time): bool
    {
        if ($this->status === 'occupied') {
            return false;
        }

        $existingBooking = Booking::where('table_id', $this->id)
            ->where('booking_date', $date)
            ->where('booking_time', $time)
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();

        return !$existingBooking;
    }

    public function markAsAvailable(): void
    {
        $this->status = 'available';
        $this->save();
    }

    public function markAsOccupied(): void
    {
        $this->status = 'occupied';
        $this->save();
    }

    public function markAsReserved(): void
    {
        $this->status = 'reserved';
        $this->save();
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    public function scopeByCapacity($query, int $capacity)
    {
        return $query->where('capacity', '>=', $capacity);
    }
}
