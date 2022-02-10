<x-livewire-tables::bs4.table.cell>
    {{ $row->seller->name }}
</x-livewire-tables::bs4.table.cell>

<x-livewire-tables::bs4.table.cell>
    {{ $row->card_brands }}
</x-livewire-tables::bs4.table.cell>

<x-livewire-tables::bs4.table.cell>
    {{ ucfirst($row->listing_type) }}
</x-livewire-tables::bs4.table.cell>

<x-livewire-tables::bs4.table.cell>
{{ config('app.currency') }}{{ ucfirst($row->cards[0]->card()->withoutGlobalScope('active')->first()->value) }}
</x-livewire-tables::bs4.table.cell>

<x-livewire-tables::bs4.table.cell>
@if(isset($row->selling_amount)) {{ config('app.currency') }}{{ $row->selling_amount }} @endif
</x-livewire-tables::bs4.table.cell>

<x-livewire-tables::bs4.table.cell>
    @include('backend.marketplace.includes.status', ['status' => $row->status])
</x-livewire-tables::bs4.table.cell>
 
<x-livewire-tables::bs4.table.cell>
    @include('backend.marketplace.includes.actions', ['marketplace' => $row])
</x-livewire-tables::bs4.table.cell>
