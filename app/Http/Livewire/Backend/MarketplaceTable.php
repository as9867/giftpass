<?php

namespace App\Http\Livewire\Backend;

use Illuminate\Database\Eloquent\Builder;
use App\Domains\Marketplace\Models\Marketplace;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use Rappasoft\LaravelLivewireTables\DataTableComponent;

/**
 * Class UsersTable.
 */
class MarketplaceTable extends DataTableComponent
{
    public function mount(): void
    {
    }

    public function query(): Builder
    {
        $query = Marketplace::query();

        return $query
            ->when($this->getFilter('status'), function ($query, $term) {
                $query->where('status', $term);
            })
            ->when($this->getFilter('type'), function ($query, $term) {
                $query->where('listing_type', $term);
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Seller'),
            Column::make('Brand'),
            Column::make('Type', 'listing_type'),
            Column::make('Card Value'),
            Column::make('Selling Amount'),
            Column::make('Status'),
            Column::make(__('Actions')),
        ];
    }

    public function filters(): array
    {
        return [
            'status' => Filter::make('Status')
                ->select([ 
                    '' => 'Any',
                    'active' => 'Active',
                    'hold' => 'Hold',
                    'dispute' => 'Dispute',
                    'dispute_completed' => 'Dispute Completed',
                    'inactive' => 'Inactive',
                    'completed' => 'Completed',
                    'pending_live' => 'Pending Live',
                    'auction_timeup' => 'Auction Timeup',
                    'cancel_requested' => 'Cancel Requested'
                ]),
            'type' => Filter::make('Type')
                ->select([
                    '' => 'Any',
                    'sell' => 'Sell',
                    'trade' => 'Trade',
                    // 'auction' => 'Auction'
                ])
        ];
    }

    public function rowView(): string
    {
        return 'backend.marketplace.includes.row';
    }
}
