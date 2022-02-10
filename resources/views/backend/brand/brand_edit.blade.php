@extends('backend.layouts.app')

@section('title', 'Marketplace Dispute')

@section('content')

<x-forms.post :action="route('admin.brand.updatebrand')" enctype="multipart/form-data">
    <x-backend.card>
        <x-slot name="header">
            @lang('Brand')
        </x-slot>

        <x-slot name="headerActions">
            <x-utils.link class="card-header-action" :href="route('admin.brand.updatebrand')" :text="__('Cancel')" />
        </x-slot>

        <x-slot name="body">
            <div x-data="{userType : 'user'}">
                <div class="form-group row">
                    <label for="name" class="col-md-2 col-form-label">@lang('Category')</label>
                    <div class="col-md-10">
                        <input type="hidden" name="brand_id" value="{{ $brand->id}}">
                        <select class="form-control" name="category_id">
                            <option>Select</option>
                            @foreach($category as $key=>$value)
                            <option value="{{ $value->id }}" @if($brand->category_id == $value->id) selected @endif >{{ $value->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <label for="name" class="col-md-2 col-form-label">@lang('Brand Name')</label>

                    <div class="col-md-10">
                        <input type="text" name="name" class="form-control" placeholder="{{ __('Brand Name') }}" value="{{ $brand->name }}" maxlength="100" required />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="name" class="col-md-2 col-form-label">@lang('Image')</label>
                    <div class="col-md-1">
                        <img id="preview" src="{{$brand->logo}}" height="auto" width="50px">
                    </div>
                    <div class="col-md-9">
                        <input type="file" id="logo" name="logo" class="form-control-file" placeholder="{{ __('Brand Name') }}" value="{{ old('logo') }}" maxlength="100" />
                    </div>
                </div>

                <div class="form-group row">
                    <label for="name" class="col-md-2 col-form-label">@lang('BG Color')</label>

                    <div class="col-md-10">
                        <input type="text" name="bg_color" class="form-control" placeholder="{{ __('BG Color') }}" value="{{ $brand->bg_color }}" maxlength="100" />
                    </div>
                </div>

                <div class="form-group row">
                    <label for="name" class="col-md-2 col-form-label">@lang('Terms and Conditions')</label>

                    <div class="col-md-10">
                        <textarea id="mytextareaTermsandcon" name="terms_and_conditions">{{ $brand->terms_and_conditions }}</textarea>
                        <!-- <input type="text" name="terms_and_conditions" class="form-control" placeholder="{{ __('Terms and Conditions') }}" value="{{ old('terms_and_conditions') }}" maxlength="100" /> -->
                    </div>
                </div>

                <div class="form-group row">
                    <label for="description" class="col-md-2 col-form-label">@lang('Description')</label>

                    <div class="col-md-10">
                        <textarea id="mytextareaDescription" name="description">{{ $brand->description }}</textarea>
                        <!-- <input type="text" name="description" class="form-control" placeholder="{{ __('Description') }}" value="{{ old('description') }}" maxlength="100" /> -->
                    </div>
                </div>
                <div class="form-group row">
                    <label for="how_to_redeem" class="col-md-2 col-form-label">@lang('How to redeem')</label>

                    <div class="col-md-10">
                        <!-- <input type="text" name="how_to_redeem" class="form-control" placeholder="{{ __('How to redeem') }}" value="{{ old('how_to_redeem') }}" maxlength="100" required /> -->
                        <textarea id="mytextareaHowtoredeem" name="how_to_redeem">{{ $brand->how_to_redeem }}</textarea>
                    </div>
                </div>

                <!--form-group-->
            </div>
        </x-slot>

        <x-slot name="footer">
            <button class="btn btn-sm btn-primary float-right" type="submit">@lang('Save')</button>
        </x-slot>
    </x-backend.card>
</x-forms.post>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>

<script type="text/javascript">
    $(document).ready(function(e) {
        $('#logo').change(function() {
            let reader = new FileReader();
            reader.onload = (e) => {
                $('#preview').attr('src', e.target.result);
            }
            reader.readAsDataURL(this.files[0]);
        });
    });
</script>

@endsection