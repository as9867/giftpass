<x-livewire-tables::bs4.table.cell>
    {{ $row->category->name }}
</x-livewire-tables::bs4.table.cell>

<x-livewire-tables::bs4.table.cell>
    {{ $row->name }}
</x-livewire-tables::bs4.table.cell>

<x-livewire-tables::bs4.table.cell>
    <img src="{{$row->logo}}" height="auto" width="50px">
</x-livewire-tables::bs4.table.cell>

<!-- <x-livewire-tables::bs4.table.cell>
    {{ $row->bg_color }}
</x-livewire-tables::bs4.table.cell> -->

<!-- <x-livewire-tables::bs4.table.cell>
    {!! $row->terms_and_conditions !!}
</x-livewire-tables::bs4.table.cell>

<x-livewire-tables::bs4.table.cell>
    {!! $row->description !!}
</x-livewire-tables::bs4.table.cell>

<x-livewire-tables::bs4.table.cell>
    {!! $row->how_to_redeem !!}
</x-livewire-tables::bs4.table.cell> -->

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

<x-livewire-tables::bs4.table.cell>
    <x-utils.form-button :action="route('admin.brand.editbrand', $row)" method="post" button-class="btn btn-success btn-sm" icon="fas fa-edit" name="confirm-item">Edit </x-utils.form-button>
    <x-utils.delete-button :href="route('admin.brand.delete', $row)" :text="__('Delete')" />
</x-livewire-tables::bs4.table.cell>