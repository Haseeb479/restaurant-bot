<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'restaurant_id', 'customer_phone', 'customer_name',
        'customer_address', 'state', 'cart', 'payment_method', 'last_message_at',
    ];
    protected $casts = ['cart' => 'array', 'last_message_at' => 'datetime'];

    public function restaurant(): BelongsTo { return $this->belongsTo(Restaurant::class); }
    public function orders(): HasMany       { return $this->hasMany(Order::class); }

    public function cartTotal(): float
    {
        return collect($this->cart ?? [])->sum(fn($i) => $i['price'] * $i['qty']);
    }

    public function cartSummary(): string
    {
        return collect($this->cart ?? [])
            ->map(fn($i) => "• {$i['name']} x{$i['qty']} = Rs." . number_format($i['price'] * $i['qty'], 0))
            ->join("\n");
    }

    public function addToCart(MenuItem $item): void
    {
        $cart  = $this->cart ?? [];
        $found = false;
        foreach ($cart as &$entry) {
            if ($entry['item_id'] === $item->id) { $entry['qty']++; $found = true; break; }
        }
        if (!$found) {
            $cart[] = ['item_id' => $item->id, 'name' => $item->name, 'price' => (float)$item->price, 'qty' => 1];
        }
        $this->update(['cart' => $cart]);
    }

    public function clearCart(): void
    {
        $this->update(['cart' => [], 'state' => 'greeting', 'customer_name' => null, 'customer_address' => null]);
    }
}