<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DraftOrder extends Model
{
    protected $table = 'shopify_draft_orders';

    protected $fillable = [
        'store_id',
        'shopify_id',
        'name',
        'status',
        'customer_id',
        'email',
        'phone',
        'shipping_address',
        'billing_address',
        'line_items',
        'applied_discount',
        'shipping_line',
        'subtotal_price',
        'total_tax',
        'total_price',
        'currency',
        'note',
        'note_attributes',
        'tags',
        'tax_exempt',
        'taxes_included',
        'invoice_url',
        'invoice_sent_at',
        'order_id',
        'completed_at',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'customer_id' => 'integer',
        'shipping_address' => 'array',
        'billing_address' => 'array',
        'line_items' => 'array',
        'applied_discount' => 'array',
        'note_attributes' => 'array',
        'tags' => 'array',
        'subtotal_price' => 'decimal:2',
        'total_tax' => 'decimal:2',
        'total_price' => 'decimal:2',
        'tax_exempt' => 'boolean',
        'taxes_included' => 'boolean',
        'invoice_sent_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isInvoiceSent(): bool
    {
        return $this->status === 'invoice_sent';
    }

    public function getLineItemsCountAttribute(): int
    {
        return count($this->line_items ?? []);
    }
}
