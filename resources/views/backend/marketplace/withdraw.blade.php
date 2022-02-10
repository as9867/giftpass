@extends('backend.layouts.app')

@section('title', 'Bid Withdraw')

@section('content')
<x-backend.card>
    <x-slot name="header">
        Marketplace Card Offer Withdraw
    </x-slot>

    <x-slot name="headerActions">
        <x-utils.link class="card-header-action" :href="route('admin.marketplace.index')" :text="__('Back')" />
    </x-slot>

    <x-slot name="body">
        <table class="table">
            <tr>
                <th>Seller</th>
                <td>{{$marketplace->seller->name}}</td>
            </tr>

            <tr>
                <th>Card</th>
                <td>{{ $marketplace->card_brands }}</td>
            </tr>

            <tr>
                <th>Offered Card</th>
                <td>{{ $bidding->offer_details[0]->card->brand->name}}</td>
            </tr>            
           
            <tr>
                <th>Trading Amount</th>
                <td>{{ config('app.currency') }} {{ $marketplace->selling_amount }}</td>
            </tr>
            <tr>
                <th>Offered Name</th>
                <td>{{ $bidding->user->name }}</td>
            </tr>
            <tr>
                <th>Status</th>
                <td>@include('backend.marketplace.includes.status', ['status' => $bidding->status])</td>
            </tr>
        </table>
    </x-slot>

    <x-slot name="footer">
        <small class="float-right text-muted">
            <strong>@lang('Listing Created'):</strong> @displayDate($bidding->created_at) ({{ $bidding->created_at->diffForHumans() }}),
            <strong>@lang('Listing Updated'):</strong> @displayDate($bidding->updated_at) ({{ $bidding->updated_at->diffForHumans() }})
        </small>
    </x-slot>
</x-backend.card>

<x-forms.post :action="route('admin.marketplace.withdraw')">
    <x-backend.card>
        <x-slot name="header">
            @lang('Withdraw')
        </x-slot>

        <x-slot name="headerActions">
            <x-utils.link class="card-header-action" :href="route('admin.marketplace.withdraw')" :text="__('Cancel')" />
        </x-slot>

        <x-slot name="body">
            <div x-data="{userType : 'user'}">
                <div class="form-group row">
                    <label for="name" class="col-md-2 col-form-label">@lang('Withdraw Reason')</label>

                    <div class="col-md-10">
                        <input type="hidden" name="bid" value="{{ $bidding->id }}">
                        <input type="text" name="admin_reason" class="form-control" placeholder="{{ __('Withdraw Reason') }}" value="{{ old('admin_reason') }}" maxlength="100" required />
                    </div>
                </div>
                <!--form-group-->
            </div>
        </x-slot>

        <x-slot name="footer">
            <button class="btn btn-sm btn-primary float-right" type="submit">@lang('Withdraw')</button>
        </x-slot>
    </x-backend.card>
</x-forms.post>
@endsection