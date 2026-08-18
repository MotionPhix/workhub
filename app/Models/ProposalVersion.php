<?php

namespace App\Models;

use App\Enums\ProposalStatus;
use Database\Factories\ProposalVersionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One frozen revision of a proposal, including its priced line items.
 *
 * @property string $id
 * @property string $proposal_id
 * @property int $version
 * @property ProposalStatus $status
 * @property array<int, array{key: string, title: string, body: string, enabled: bool}> $sections
 * @property array<int, array{description: string, quantity: float, unit_price: float, total: float}>|null $line_items
 * @property float $subtotal
 * @property float $discount
 * @property float $tax_rate
 * @property float $total_amount
 * @property string $currency
 * @property string|null $timeline
 * @property string|null $terms
 * @property string|null $change_summary
 * @property Carbon|null $valid_until
 * @property Carbon|null $sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Proposal $proposal
 */
#[Fillable([
    'proposal_id', 'version', 'status', 'sections', 'line_items', 'subtotal', 'discount',
    'tax_rate', 'total_amount', 'currency', 'timeline', 'terms', 'change_summary',
    'valid_until', 'sent_at',
])]
class ProposalVersion extends Model
{
    /** @use HasFactory<ProposalVersionFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProposalStatus::class,
            'sections' => 'array',
            'line_items' => 'array',
            'version' => 'integer',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'valid_until' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Proposal, $this>
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * The sections to render, in order, skipping the ones switched off.
     *
     * @return array<int, array{key: string, title: string, body: string, enabled: bool}>
     */
    public function enabledSections(): array
    {
        return array_values(array_filter(
            $this->sections,
            fn (array $section): bool => $section['enabled'] ?? true,
        ));
    }

    /**
     * Recalculate the money columns from the line items.
     *
     * Totals are derived here rather than trusted from the client so a tampered
     * payload cannot change what a proposal says it costs.
     */
    public function recalculateTotals(): void
    {
        $subtotal = 0.0;

        foreach ($this->line_items ?? [] as $item) {
            $subtotal += (float) $item['quantity'] * (float) $item['unit_price'];
        }

        $taxable = max($subtotal - (float) $this->discount, 0);

        $this->subtotal = round($subtotal, 2);
        $this->total_amount = round($taxable * (1 + (float) $this->tax_rate / 100), 2);
    }
}
