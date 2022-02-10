<x-livewire-tables::bs4.table.cell>
    {{ $row->name }}
</x-livewire-tables::bs4.table.cell>

<x-livewire-tables::bs4.table.cell>
    @switch($row->active)
    @case(0)
    <span class="badge badge-danger text-uppercase">Inactive</span>
    @break
    @case(1)
    <span class="badge badge-success text-uppercase">Active</span>
    @break
    @default
    <span class="badge badge-secondary text-uppercase">{{ $row->active }}</span>
    @endswitch()
</x-livewire-tables::bs4.table.cell>