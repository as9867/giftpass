<?php

namespace App\Http\Livewire\Backend;

use Illuminate\Database\Eloquent\Builder;
use App\Domains\Marketplace\Models\Marketplace;
use Rappasoft\LaravelLivewireTables\DataTableComponent;

class MarketplaceCardsTable extends DataTableComponent
{
    /** @var Marketplace */
    public $marketplace;

    public function mount(Marketplace $marketplace)
    {
        $this->marketplace = $marketplace;
    }

    public function query() : Builder
    {
        return $this->marketplace->cards()->getQuery();
    }

    public function columns() : array
    {
        return [

        ];
    }
}
