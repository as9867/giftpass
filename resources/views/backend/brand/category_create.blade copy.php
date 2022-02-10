@extends('backend.layouts.app')

@section('title', 'Marketplace Dispute')

@section('content')

<x-forms.post :action="route('admin.category.create')">
    <x-backend.card>
        <x-slot name="header">
            @lang('Category')
        </x-slot>

        <x-slot name="headerActions">
            <x-utils.link class="card-header-action" :href="route('admin.category.create')" :text="__('Cancel')" />
        </x-slot>

        <x-slot name="body">
            <div x-data="{userType : 'user'}">
                <div class="form-group row">
                    <label for="name" class="col-md-2 col-form-label">@lang('Category Name')</label>

                    <div class="col-md-10">
                        <input type="text" name="name" class="form-control" placeholder="{{ __('Category Name') }}" value="{{ old('name') }}" maxlength="100" required />
                    </div>
                </div>
                <!--form-group-->
            </div>
        </x-slot>

        <x-slot name="footer">
            <button class="btn btn-sm btn-primary float-right" type="submit">@lang('Create')</button>
        </x-slot>
    </x-backend.card>
</x-forms.post>
@endsection