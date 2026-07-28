<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'table_id',
        'booking_number',
        'booking_date',
        'booking_time',
        'number_of_people',
        'total_price',
        'status',
        'notes',
    ];

    protected $casts = [
        'total_price' => 'decimal:2',
        'booking_date' => 'date',
        'booking_time' => 'datetime:H:i:s',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function table()
    {
        return $this->belongsTo(Table::class);
    }

    public function walletTransactions()
    {
        return $this->morphMany(WalletTransaction::class, 'reference');
    }

    public function canBeCancelled(): bool
    {
        if ($this->status === 'cancelled' || $this->status === 'completed') {
            return false;
        }

        $bookingDateTime = $this->booking_date->setTimeFromTimeString($this->booking_time);
        $now = now();

        return $bookingDateTime->diffInHours($now) >= 1;
    }

    public function isWithinOneHour(): bool
    {
        $bookingDateTime = $this->booking_date->setTimeFromTimeString($this->booking_time);
        $now = now();

        return $bookingDateTime->diffInMinutes($now) <= 60 && $bookingDateTime->isFuture();
    }

    public function markAsConfirmed(): void
    {
        if ($this->status === 'pending') {
            $this->status = 'confirmed';
            $this->save();
        }
    }

    public function markAsCancelled(): void
    {
        if ($this->canBeCancelled()) {
            $this->status = 'cancelled';
            $this->save();
        }
    }

    public function markAsCompleted(): void
    {
        if ($this->status === 'confirmed') {
            $this->status = 'completed';
            $this->save();
        }
    }
}
