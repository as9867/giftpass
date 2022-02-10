<?php

namespace App\Http\Livewire\Backend;

use App\Domains\Card\Models\Brand;
use Illuminate\Database\Eloquent\Builder;
use App\Domains\Marketplace\Models\Marketplace;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use Rappasoft\LaravelLivewireTables\DataTableComponent;

/**
 * Class UsersTable.
 */
class BrandTable extends DataTableComponent
{
    public function mount(): void
    {
    }

    public function query(): Builder
    {
        $query = Brand::query();

        return $query
            ->when($this->getFilter('active'), function ($query, $term) {
                $query->where('active', $term);
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Category'),
            Column::make('Name'),
            Column::make('Logo'),
            // Column::make('bg_color'),
            // Column::make('terms_and_conditions'),
            // Column::make('description'),
            // Column::make('how_to_redeem'),
            Column::make('Active'),
            Column::make('Action')
        ];
    }

    public function filters(): array
    {
        return [
            'active' => Filter::make('active')
                ->select([ 
                    '' => 'Any',
                    '1' => 'Active',
                    '0' => 'Inactive',
                ]),
        ];
    }

    public function rowView(): string
    {
        return 'backend.brand.includes.row';
    }
}
