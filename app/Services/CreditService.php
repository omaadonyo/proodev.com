<?php

namespace App\Services;

use App\Enums\CreditTransactionType;
use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreditService
{
    public function balance(User $user): int
    {
        return (int) $user->credit_balance;
    }

    public function has(User $user, int $amount = 1): bool
    {
        return $this->balance($user) >= $amount;
    }

    public function grant(User $user, int $credits, CreditTransactionType $type = CreditTransactionType::Grant, ?string $description = null, ?Model $reference = null): CreditTransaction
    {
        return DB::transaction(function () use ($user, $credits, $type, $description, $reference) {
            $user = User::lockForUpdate()->findOrFail($user->id);

            $balance = $user->credit_balance + $credits;
            $user->update(['credit_balance' => $balance]);

            return $user->creditTransactions()->create([
                'change' => $credits,
                'balance_after' => $balance,
                'type' => $type,
                'description' => $description,
                'reference_type' => $reference ? $reference::class : null,
                'reference_id' => $reference?->getKey(),
            ]);
        });
    }

    public function spend(User $user, int $credits, CreditTransactionType $type = CreditTransactionType::Submission, ?string $description = null, ?Model $reference = null): CreditTransaction
    {
        return DB::transaction(function () use ($user, $credits, $type, $description, $reference) {
            $user = User::lockForUpdate()->findOrFail($user->id);

            if ($user->credit_balance < $credits) {
                throw new InsufficientCreditsException($credits);
            }

            $balance = $user->credit_balance - $credits;
            $user->update(['credit_balance' => max(0, $balance)]);

            return $user->creditTransactions()->create([
                'change' => -$credits,
                'balance_after' => $balance,
                'type' => $type,
                'description' => $description,
                'reference_type' => $reference ? $reference::class : null,
                'reference_id' => $reference?->getKey(),
            ]);
        });
    }
}
