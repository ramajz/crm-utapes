<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;

class LoyaltyService
{
    /**
     * Detect if a customer is new or repeat.
     *
     * Business Logic:
     * - Search customer by phone number in customers table
     * - If customer has previous orders (total_orders > 0) → "repeat"
     * - If first time → "new"
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
     * Find or create a customer by phone number.
     */
    public function findOrCreate(string $phone, string $name, float $totalValue = 0): Customer
    {
        $customer = Customer::where('phone', $phone)->first();

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

        return $customer;
    }
}
