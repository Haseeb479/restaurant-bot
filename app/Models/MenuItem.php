<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $fillable = [
        'restaurant_id',
        'category_id',
        'name',
        'description',
        'price',
        'sizes',
        'is_available',
        'sort_order',
    ];

    protected $casts = [
        'price'        => 'decimal:2',
        'sizes'        => 'array',
        'is_available' => 'boolean',
    ];

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(MenuItemVariant::class)->orderBy('sort_order');
    }

    public function activeVariants(): HasMany
    {
        return $this->hasMany(MenuItemVariant::class)->where('is_active', true)->orderBy('sort_order');
    }

    public function hasSizes(): bool
    {
        if (!empty($this->sizes) && is_array($this->sizes)) {
            foreach ($this->sizes as $s) {
                if (!isset($s['is_active']) || $s['is_active']) {
                    return true;
                }
            }
        }
        return false;
    }

    public function getActiveSizesList(): array
    {
        if (empty($this->sizes) || !is_array($this->sizes)) {
            return [];
        }
        return array_values(array_filter($this->sizes, function ($s) {
            return !isset($s['is_active']) || $s['is_active'];
        }));
    }

    public function getPriceDisplayAttribute(): string
    {
        $activeSizes = $this->getActiveSizesList();
        if (!empty($activeSizes)) {
            return collect($activeSizes)
                ->map(fn($s) => ($s['name'] ?? $s['size']) . ": Rs." . number_format($s['price'], 0))
                ->implode(' / ');
        }
        return 'Rs.' . number_format($this->price, 0);
    }

    public static function normalizeSizeName(string $raw): string
    {
        $s = strtolower(trim(preg_replace('/[\s\-_]+/', ' ', $raw)));
        if (str_contains($s, 'family')) return 'Family';
        if (str_contains($s, 'jumbo') || str_contains($s, 'party')) return 'Jumbo';
        if (str_contains($s, 'personal')) return 'Personal';
        if (preg_match('/\b(extra\s*large|xlarge|xl|x-large)\b/i', $s) || preg_match('/(?:16["”]|16\s*inch\b)/i', $s)) return 'XL';
        if (preg_match('/\b(large|lg)\b/i', $s) || preg_match('/(?:13["”]|13\s*inch\b)/i', $s) || $s === 'l') return 'Large';
        if (preg_match('/\b(medium|med)\b/i', $s) || preg_match('/(?:10["”]|10\s*inch\b)/i', $s) || $s === 'm') return 'Medium';
        if (preg_match('/\b(small|sm)\b/i', $s) || preg_match('/(?:7["”]|7\s*inch\b)/i', $s) || $s === 's') return 'Small';
        if (str_contains($s, 'regular') || $s === 'reg') return 'Regular';
        if (str_contains($s, 'half') || $s === 'single') return 'Half';
        if (str_contains($s, 'full') || $s === 'double') return 'Full';

        return ucwords(strtolower(trim($raw)));
    }

    public static function getSizeSortOrder(string $sizeName): float
    {
        $order = [
            'Personal' => 0.5,
            'Small'    => 1.0,
            'Regular'  => 1.5,
            'Half'     => 1.8,
            'Medium'   => 2.0,
            'Full'     => 2.8,
            'Large'    => 3.0,
            'XL'       => 4.0,
            'Family'   => 5.0,
            'Jumbo'    => 6.0,
        ];
        return $order[$sizeName] ?? 99.0;
    }

    public function syncVariants(?array $sizesList = null): void
    {
        if (empty($sizesList)) {
            $this->variants()->delete();
            $this->update(['sizes' => null]);
            return;
        }

        $formatted = [];
        $existingIds = [];

        foreach ($sizesList as $s) {
            $rawName = $s['name'] ?? $s['size'] ?? '';
            if (empty($rawName)) continue;
            $price = (float) ($s['price'] ?? 0);
            if ($price <= 0) continue;

            $canonicalName = self::normalizeSizeName($rawName);
            $isActive = !isset($s['is_active']) || filter_var($s['is_active'], FILTER_VALIDATE_BOOLEAN);
            $sortOrder = (float) ($s['sort_order'] ?? self::getSizeSortOrder($canonicalName));

            $variant = $this->variants()->updateOrCreate(
                ['name' => $canonicalName],
                [
                    'price'      => $price,
                    'sort_order' => (int) round($sortOrder * 10),
                    'is_active'  => $isActive,
                ]
            );
            $existingIds[] = $variant->id;

            $formatted[] = [
                'name'       => $canonicalName,
                'size'       => $canonicalName,
                'price'      => $price,
                'sort_order' => $sortOrder,
                'is_active'  => $isActive,
            ];
        }

        // Delete removed variants
        if (!empty($existingIds)) {
            $this->variants()->whereNotIn('id', $existingIds)->delete();
        } else {
            $this->variants()->delete();
        }

        // Sort formatted list
        usort($formatted, fn($a, $b) => ($a['sort_order'] ?? 99) <=> ($b['sort_order'] ?? 99));

        // Lowest active size price or base price
        $activePrices = array_column(array_filter($formatted, fn($f) => $f['is_active']), 'price');
        $lowestPrice = !empty($activePrices) ? min($activePrices) : ($this->price ?: 0);

        $this->update([
            'sizes' => !empty($formatted) ? $formatted : null,
            'price' => $lowestPrice,
        ]);
    }
}