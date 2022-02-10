@extends('backend.layouts.app')

@section('title', 'Marketplace Dispute')

@section('content')
<x-backend.card>
    <x-slot name="header">
        Marketplace Dispute
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
                <th>Listing Type</th>
                <td>{{ $marketplace->listing_type }}</td>
            </tr>

            <tr>
                <th>Dispute Message</th>
                <td>{{ $marketplace->dispute_message }}</td>
            </tr>

            <tr>
                <th>Selling Amount</th>
                <td> @if(isset($marketplace->selling_amount)) {{ config('app.currency') }} {{ $marketplace->selling_amount }} @endif</td>
            </tr>

            <tr>
                <th>Status</th>
                <td>@include('backend.marketplace.includes.status', ['status' => $marketplace->status])</td>
            </tr>
        </table>
    </x-slot>

    <x-slot name="footer">
        <small class="float-right text-muted">
            <strong>@lang('Listing Created'):</strong> @displayDate($marketplace->created_at) ({{ $marketplace->created_at->diffForHumans() }}),
            <strong>@lang('Listing Updated'):</strong> @displayDate($marketplace->updated_at) ({{ $marketplace->updated_at->diffForHumans() }})
        </small>
    </x-slot>
</x-backend.card>

<x-forms.post :action="route('admin.marketplace.dispute')">
    <x-backend.card>
        <x-slot name="header">
            @lang('Dispute')
        </x-slot>

        <x-slot name="headerActions">
            <x-utils.link class="card-header-action" :href="route('admin.marketplace.dispute')" :text="__('Cancel')" />
        </x-slot>

        <x-slot name="body">
            <div x-data="{userType : 'user'}">
                <div class="form-group row">
                    <label for="name" class="col-md-2 col-form-label">@lang('')</label>

                    <div class="col-md-10">
                        <div class="form-check-inline">
                            <label class="form-check-label">
                                <input type="radio" class="form-check-input" name="status" value="accept" checked>Accept
                            </label>
                        </div>
                        <div class="form-check-inline">
                            <label class="form-check-label">
                                <input type="radio" class="form-check-input" name="status" value="reject">Reject
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-group row">
                    <label for="name" class="col-md-2 col-form-label">@lang('Dispute Reason')</label>

                    <div class="col-md-10">
                        <input type="hidden" name="marketplace_id" value="{{ $marketplace->id }}">
                        <input type="text" name="admin_reason" class="form-control" placeholder="{{ __('Dispute Reason') }}" value="{{ old('admin_reason') }}" maxlength="100" required />
                    </div>
                </div>
                <!--form-group-->
            </div>
        </x-slot>

        <x-slot name="footer">
            <button class="btn btn-sm btn-primary float-right" type="submit">@lang('Dispute')</button>
        </x-slot>
    </x-backend.card>
</x-forms.post>
@endsection