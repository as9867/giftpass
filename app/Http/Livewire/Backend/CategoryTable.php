<?php

namespace App\Http\Livewire\Backend;

use App\Domains\Card\Models\Brand;
use App\Domains\Card\Models\Categories;
use Illuminate\Database\Eloquent\Builder;
use App\Domains\Marketplace\Models\Marketplace;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use Rappasoft\LaravelLivewireTables\DataTableComponent;

/**
 * Class UsersTable.
 */
class CategoryTable extends DataTableComponent
{
    public function mount(): void
    {
    }

    public function query(): Builder
    {
        $query = Categories::query();

        return $query
            ->when($this->getFilter('active'), function ($query, $term) {
                $query->where('active', $term);
            });
    }

    public function columns(): array
    {
        return [
            Column::make('Name'),
            Column::make('Active'),
        ];
    }

    public function filters(): array
    {
        return [
            'active' => Filter::make('Active')
                ->select([ 
                    '1' => 'Active',
                    '0' => 'Inactive',
                ]),
        ];
    }

    public function rowView(): string
    {
        return 'backend.brand.includes.row_category';
    }
}
