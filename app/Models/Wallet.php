<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'total_deposited',
        'total_spent',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_deposited' => 'decimal:2',
        'total_spent' => 'decimal:2',
    ];

    // العلاقات
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class);
    }

    // دوال مساعدة
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    public function deposit(float $amount, ?string $description = null): WalletTransaction
    {
        $this->balance += $amount;
        $this->total_deposited += $amount;
        $this->save();

        return $this->transactions()->create([
            'user_id' => $this->user_id,
            'type' => 'deposit',
            'amount' => $amount,
            'balance_after' => $this->balance,
            'description' => $description ?? 'Deposit to wallet',
        ]);
    }

    public function withdraw(float $amount, string $referenceType, int $referenceId, ?string $description = null): WalletTransaction
    {
        if (!$this->hasSufficientBalance($amount)) {
            throw new \Exception('Insufficient balance');
        }

        $this->balance -= $amount;
        $this->total_spent += $amount;
        $this->save();

        return $this->transactions()->create([
            'user_id' => $this->user_id,
            'type' => 'payment',
            'amount' => -$amount,
            'balance_after' => $this->balance,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description ?? "Payment for {$referenceType} #{$referenceId}",
        ]);
    }

    public function refund(float $amount, string $referenceType, int $referenceId, ?string $description = null): WalletTransaction
    {
        $this->balance += $amount;
        $this->save();

        return $this->transactions()->create([
            'user_id' => $this->user_id,
            'type' => 'refund',
            'amount' => $amount,
            'balance_after' => $this->balance,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description ?? "Refund for {$referenceType} #{$referenceId}",
        ]);
    }

    public function addCancellationFee(float $amount, string $referenceType, int $referenceId, ?string $description = null): WalletTransaction
    {
        $this->balance += $amount;
        $this->save();

        return $this->transactions()->create([
            'user_id' => $this->user_id,
            'type' => 'cancellation_fee',
            'amount' => $amount,
            'balance_after' => $this->balance,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'description' => $description ?? "Cancellation fee for {$referenceType} #{$referenceId}",
        ]);
    }
}
