<?php

namespace App\Models;

use App\Enums\ProductCategory;
use App\Models\Concerns\BelongsToOrganization;
use App\Support\Money;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * Something a facility sells or rents (plan.md §17).
 *
 * @property ProductCategory $category
 */
#[Fillable([
    'organization_id',
    'branch_id',
    'name',
    'slug',
    'description',
    'category',
    'sku',
    'barcode',
    'price',
    'cost',
    'supplier_id',
    'currency',
    'stock',
    'reorder_level',
    'image_path',
    'is_active',
    'track_stock',
    'tax_rate',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use BelongsToOrganization, HasFactory, SoftDeletes;

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Search by name, SKU or barcode — the three things a cashier tries
     * (plan.md §17 product search and barcode support).
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.$term.'%';

        return $query->where(function (Builder $builder) use ($like): void {
            $builder->where('name', 'like', $like)
                ->orWhere('sku', 'like', $like)
                ->orWhere('barcode', 'like', $like);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => ProductCategory::class,
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'stock' => 'integer',
            'reorder_level' => 'integer',
            'track_stock' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $product): void {
            $product->slug ??= self::uniqueSlug($product->name);
            $product->currency ??= 'PHP';
        });

        static::updating(function (self $product): void {
            if ($product->isDirty('name') && ! $product->isDirty('slug')) {
                $product->slug = self::uniqueSlug($product->name, $product->id);
            }
        });
    }

    /**
     * A URL-safe slug that does not collide with another product.
     */
    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $suffix = 2;

        while (self::where('slug', $slug)->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    public function formattedPrice(): string
    {
        return Money::format($this->price);
    }

    /**
     * Whether this item can be sold right now.
     */
    public function isAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return ! $this->track_stock || $this->stock > 0;
    }

    /**
     * Whether stock has fallen to or below the reorder level.
     */
    public function needsReorder(): bool
    {
        return $this->track_stock && $this->stock <= $this->reorder_level;
    }
}
