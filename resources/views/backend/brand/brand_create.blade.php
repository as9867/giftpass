@extends('backend.layouts.app')

@section('title', 'Marketplace Dispute')

@section('content')

<x-forms.post :action="route('admin.brand.createbrand')"  enctype="multipart/form-data">
    <x-backend.card>
        <x-slot name="header">
            @lang('Brand')
        </x-slot>

        <x-slot name="headerActions">
            <x-utils.link class="card-header-action" :href="route('admin.brand.createbrand')" :text="__('Cancel')" />
        </x-slot>

        <x-slot name="body">
            <div x-data="{userType : 'user'}">
                <div class="form-group row">
                    <label for="name" class="col-md-2 col-form-label">@lang('Category')</label>
                    <div class="col-md-10">
                        <select class="form-control" name="category_id">
                            <option>Select</option>
                            @foreach($cartegory as $key=>$value)
                            <option value="{{ $value->id }}">{{ $value->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <label for="name" class="col-md-2 col-form-label">@lang('Brand Name')</label>

                    <div class="col-md-10">
                        <input type="text" name="name" class="form-control" placeholder="{{ __('Brand Name') }}" value="{{ old('name') }}" maxlength="100" required />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="name" class="col-md-2 col-form-label">@lang('Image')</label>

                    <div class="col-md-10">
                        <input type="file" name="logo" class="form-control-file" placeholder="{{ __('Brand Name') }}" value="{{ old('logo') }}" maxlength="100" />
                    </div>
                </div>

                <div class="form-group row">
                    <label for="name" class="col-md-2 col-form-label">@lang('BG Color')</label>

                    <div class="col-md-10">
                        <input type="text" name="bg_color" class="form-control" placeholder="{{ __('BG Color') }}" value="{{ old('bg_color') }}" maxlength="100" />
                    </div>
                </div>

                <div class="form-group row">
                    <label for="name" class="col-md-2 col-form-label">@lang('Terms and Conditions')</label>

                    <div class="col-md-10">
                        <textarea id="mytextareaTermsandcon" name="terms_and_conditions"></textarea>
                        <!-- <input type="text" name="terms_and_conditions" class="form-control" placeholder="{{ __('Terms and Conditions') }}" value="{{ old('terms_and_conditions') }}" maxlength="100" /> -->
                    </div>
                </div>

                <div class="form-group row">
                    <label for="description" class="col-md-2 col-form-label">@lang('Description')</label>

                    <div class="col-md-10">
                        <textarea id="mytextareaDescription" name="description"></textarea>
                        <!-- <input type="text" name="description" class="form-control" placeholder="{{ __('Description') }}" value="{{ old('description') }}" maxlength="100" /> -->
                    </div>
                </div>
                <div class="form-group row">
                    <label for="how_to_redeem" class="col-md-2 col-form-label">@lang('How to redeem')</label>

                    <div class="col-md-10">
                        <!-- <input type="text" name="how_to_redeem" class="form-control" placeholder="{{ __('How to redeem') }}" value="{{ old('how_to_redeem') }}" maxlength="100" required /> -->
                        <textarea id="mytextareaHowtoredeem" name="how_to_redeem"></textarea>
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