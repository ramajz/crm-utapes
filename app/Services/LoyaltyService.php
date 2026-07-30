<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class LoyaltyService
{
    /**
     * Detect if a customer is new or repeat.
     */
    public function detect(string $phone): array
    {
        $customer = Customer::where('phone', $phone)->first();

        if ($customer && $customer->total_orders > 0) {
            return [
                'type' => 'repeat',
                'customer' => $customer,
            ];
        }

        return [
            'type' => 'new',
            'customer' => $customer,
        ];
    }

    /**
     * Find or create a customer by phone number — uses lock to prevent race condition.
     */
    public function findOrCreate(string $phone, string $name, float $totalValue = 0): Customer
    {
        $customer = DB::transaction(function () use ($phone, $name, $totalValue) {
            $customer = Customer::where('phone', $phone)->lockForUpdate()->first();

            if ($customer) {
                $customer->increment('total_orders');
                $customer->increment('total_spend', $totalValue);
                $customer->last_purchase_at = now();
                $customer->save();
            } else {
                $customer = Customer::create([
                    'phone' => $phone,
                    'name' => $name,
                    'total_orders' => 1,
                    'total_spend' => $totalValue,
                    'first_purchase_at' => now(),
                    'last_purchase_at' => now(),
                ]);
            }

            return $customer->fresh();
        });

        return $customer;
    }
}
