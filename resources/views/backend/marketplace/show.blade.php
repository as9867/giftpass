@extends('backend.layouts.app')

@section('title', 'Marketplace Listing')

@section('content')
<x-backend.card>
    <x-slot name="header">
        Marketplace Listing
    </x-slot>

    <x-slot name="headerActions">
        <x-utils.link class="card-header-action" :href="route('admin.marketplace.index')" :text="__('Back')" />
    </x-slot>

    <x-slot name="body">
        <table class="table">
            <tr>
                <th>User</th>
                <td>{{ $marketplace->seller->name }}</td>
            </tr>

            <tr>
                <th>Brand</th>
                <td>
                    {{ $marketplace->card_brands }}
                </td>
            </tr>

            <tr>
                <th>Type</th>
                <td>{{ ucfirst($marketplace->listing_type) }}</td>
            </tr>

            <tr>
                <th>Card Value</th>
                <td>@if(isset($marketplace->cards[0]->card->value)) {{ config('app.currency') }}{{ $marketplace->cards[0]->card->value }} @endif</td>
            </tr>

            <tr>
                @if($marketplace->listing_type == 'auction') <th>Reserved Price</th> @else @if($marketplace->listing_type == 'trade') <th>Trading Amount</th> @else <th>Selling Amount</th> @endif @endif
                <td>@if(isset($marketplace->selling_amount)) {{ config('app.currency') }}{{ $marketplace->selling_amount }} @endif</td>
            </tr>

            @if($marketplace->listing_type == 'auction')
            <tr>
                <th>Min bid Amount</th>
                <td>@if(isset($marketplace->minbid)) {{config('app.currency')}}{{$marketplace->minbid}} @endif</td>
            </tr>
            <tr>
                <th>Bid Expiry</th>
                <th>{{ date('d-m-Y H:i', strtotime($marketplace->bidding_expiry)) }}</th>
            </tr>
            @endif

            @if($marketplace->listing_type == 'trade')
            <tr>
                <?php $offer_trades_data = $marketplace->load(['cards.trading_brand']);
                if (isset($offer_trades_data->cards[0]->trading_brand)) {
                    $offer = $offer_trades_data->cards[0]->trading_brand->name;
                } else {
                    $offer = '--';
                }

                ?>
                <th>Brand you want to receive</th>
                <td>{{ $offer }}</td>
            </tr>
            @endif

            @if($marketplace->listing_type == 'trade')
            <tr>
                <?php $offer_trades_data = $marketplace->load(['cards']); ?>
                <th>Open to other brand</th>
                <td>@if($offer_trades_data->cards[0]->recive_other_brands == 0) No @else Yes @endif </td>
            </tr>
            @endif
            <tr>
                <th>Status</th>
                <td>@include('backend.marketplace.includes.status', ['status' => $marketplace->status])</td>
            </tr>
            <tr>
                <th>Admin Reason</th>
                <td>{{ $marketplace->admin_reason }}</td>
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

@if ($marketplace->listing_type == 'trade')
<x-backend.card>
    <x-slot name="header">
        Offers
    </x-slot>

    <x-slot name="body">
        <table class="table table-sm">
            <tr>
                <th>Trader</th>
                <th>Cards</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            @foreach ($marketplace->offer_trades as $trade)
            <tr>
                <td>{{ $trade->user->name }}
                <td>{{ $trade->trade_cards }}</td>
                <td>{{ date('d-m-Y H:i', strtotime($trade->created_at)) }}</td>
                <td>@switch($trade->status)
                    @case('pending')
                    <span class="badge badge-warning text-uppercase">Pending</span>
                    @break
                    @case('rejected')
                    <span class="badge badge-danger text-uppercase">Rejected</span>
                    @break
                    @case('accepted_by_buyer')
                    <span class="badge badge-warning text-uppercase">Accepted by buyer</span>
                    @break
                    @case('accepted')
                    <span class="badge badge-success text-uppercase">Accepted</span>
                    @break
                    @case('accepted_by_both')
                    <span class="badge badge-success text-uppercase">Accepted by both</span>
                    @break
                    @case('accepted_by_seller')
                    <span class="badge badge-warning text-uppercase">Accepted by seller</span>
                    @break
                    @default
                    <span class="badge badge-secondary text-uppercase">{{ strtoupper($status) }}</span>
                    @endswitch()
                </td>
                <td>
                    <!-- @if(isset($trade->withdraw_message) && !isset($trade->admin_reason))
                    <x-utils.form-button :action="route('admin.marketplace.offer', $trade)" method="get" button-class="btn btn-secondary btn-sm" icon="fas fa-edit" name="confirm-item">Withdraw </x-utils.form-button>
                    @endif -->
                    @if($trade->active == 0)
                    <button class="btn btn-success btn-sm btn-offer-status" data-id="{{ $trade->id }}">Activate </button>
                    <!-- <x-utils.form-button :action="route('admin.marketplace.offerstatus', $trade)" method="post" button-class="btn btn-success btn-sm" name="confirm-item">Activate </x-utils.form-button> -->
                    @endif
                    @if($trade->active == 1)
                    <button class="btn btn-danger btn-sm btn-offer-status" data-id="{{ $trade->id }}">Deactivate </button>
                    @endif
                </td>
            </tr>
            @endforeach
        </table>
    </x-slot>
</x-backend.card>
@endif

<!-- withdraw offers -->

@if ($marketplace->listing_type == 'trade')
<x-backend.card>
    <x-slot name="header">
        Withdraw request Offers
    </x-slot>

    <x-slot name="body">
        <table class="table table-sm">
            <tr>
                <th>Trader</th>
                <th>Cards</th>
                <th>Date</th>
                <th>Withdraw Message</th>
                <th>Withdraw Request Time</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            @foreach ($marketplace->offer_trades as $trade)
            @if(isset($trade->withdraw_message) && !isset($trade->admin_reason))
            <tr>
                <td>{{ $trade->user->name }}
                <td>{{ $trade->trade_cards }}</td>
                <td>{{ date('d-m-Y H:i', strtotime($trade->created_at)) }}</td>
                <td>{{ $trade->withdraw_message }}</td>
                <td>{{ date('d-m-Y H:i', strtotime($trade->withdraw_datetime)) }}</td>
                <td>@switch($trade->status)
                    @case('pending')
                    <span class="badge badge-warning text-uppercase">Pending</span>
                    @break
                    @case('rejected')
                    <span class="badge badge-danger text-uppercase">Rejected</span>
                    @break
                    @case('accepted_by_buyer')
                    <span class="badge badge-warning text-uppercase">Accepted by buyer</span>
                    @break
                    @case('accepted')
                    <span class="badge badge-success text-uppercase">Accepted</span>
                    @break
                    @case('accepted_by_both')
                    <span class="badge badge-success text-uppercase">Accepted by both</span>
                    @break
                    @case('accepted_by_seller')
                    <span class="badge badge-warning text-uppercase">Accepted by seller</span>
                    @break
                    @default
                    <span class="badge badge-secondary text-uppercase">{{ strtoupper($status) }}</span>
                    @endswitch()
                </td>
                <td>
                    @if(isset($trade->withdraw_message) && !isset($trade->admin_reason))
                    <x-utils.form-button :action="route('admin.marketplace.offer', $trade)" method="get" button-class="btn btn-secondary btn-sm" icon="fas fa-edit" name="confirm-item">Withdraw </x-utils.form-button>
                    @endif
                    <!-- @if($trade->active == 0)
                    <x-utils.form-button :action="route('admin.marketplace.offerstatus', $trade)" method="post" button-class="btn btn-success btn-sm" name="confirm-item">Activate </x-utils.form-button>
                    @endif
                    @if($trade->active == 1)
                    <x-utils.form-button :action="route('admin.marketplace.offerstatus', $trade)" method="post" button-class="btn btn-danger btn-sm" name="confirm-item">Deactivate </x-utils.form-button>
                    @endif -->
                </td>
            </tr>
            @endif
            @endforeach
        </table>
    </x-slot>
</x-backend.card>
@endif

@if ($marketplace->listing_type == 'auction')
<x-backend.card>
    <x-slot name="header">
        Bids
    </x-slot>

    <x-slot name="body">
        <table class="table table-sm">
            <tr>
                <th>Sr.No
                <th>Bidder</th>
                <th>Bidding Amount</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            @foreach ($marketplace->biddings()->withoutGlobalScope('active')->get() as $key=>$bid)
            <tr>
                <td>{{$key+1}}
                <td>{{ $bid->user->name }} </td>
                <td>{{ config('app.currency') }}{{ $bid->bidding_amount }}</td>
                <td>{{ date('d-m-Y H:i', strtotime($bid->created_at)) }}</td>
                <td>@switch($bid->payment_status)
                    @case('pending-payment')
                    <span class="badge badge-warning text-uppercase">Payment Pending</span>
                    @break
                    @case('past_due')
                    <span class="badge badge-danger text-uppercase">Past Due</span>
                    @break
                    @case('peyment-completed')
                    <span class="badge badge-success text-uppercase">Payment Completed</span>
                    @break
                    @default
                    @if(isset($bid->payment_status))
                    <span class="badge badge-secondary text-uppercase">{{ strtoupper($bid->payment_status) }}</span>
                    @else
                    <span class="badge badge-secondary text-uppercase"> Initiated </span>
                    @endif

                    @endswitch()
                </td>
                <td>
                    @if($bid->active == 0)
                    <button class="btn btn-success btn-sm btn-bid-status" data-id="{{ $bid->id }}">Activate </button>
                    <!-- <x-utils.form-button :action="route('admin.marketplace.bidstatus', $bid)" class="btn btn-success btn-sm btn-bid-status" name="confirm-item">Activate </x-utils.form-button> -->
                    @endif
                    @if($bid->active == 1)
                    <button class="btn btn-danger btn-sm btn-bid-status" data-id="{{ $bid->id }}">Deactivate </button>
                    <!-- <x-utils.form-button :action="route('admin.marketplace.bidstatus', $bid)" class="btn btn-danger btn-sm btn-bid-status" name="confirm-item">Deactivate </x-utils.form-button> -->
                    @endif
                </td>
            </tr>
            @endforeach
        </table>
    </x-slot>
</x-backend.card>
@endif


<x-backend.card>
    <x-slot name="header">
        Activity
    </x-slot>

    <x-slot name="body">
        <table class="table table-sm">
            <tr>
                <th>Peformer</th>
                <th>Type</th>
                <th>Amount</th>
                <th>Time</th>
            </tr>
            @foreach ($marketplace->activities as $activity)
            @if($activity->reciver_user_id == $marketplace->user_id)
            <tr>
                <td>{{ $activity->performer->name }}
                <td>{{ strtoupper(str_replace('_', ' ', $activity->activity_type)) }}</td>
                <td>@if(isset($activity->amount)){{ config('app.currency') }}{{ $activity->amount }} @else -- @endif</td>
                <td>{{ $activity->created_at->toDateTimeString() }}</td>
            </tr>
            @endif
            @endforeach
        </table>
    </x-slot>
</x-backend.card>

<!-- Deactivate activate offer modal -->

<!-- The Modal -->
<div class="modal" id="offerModal">
    <div class="modal-dialog">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title">Offer Status Change</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <form method="POST" action="{{ route('admin.marketplace.offerstatus') }}" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <div class="form-group">
                        <label for="admin_reason">Reason</label>
                        <input type="text" name="offer_id" id="offer_id">
                        <input type="text" class="form-control" id="admin_reason" name="admin_reason" aria-describedby="emailHelp" placeholder="Enter Reason" required>
                        <small id="emailHelp" class="form-text text-muted">Enter reason why need to change status</small>
                    </div>

                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>

            <!-- Modal footer
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
            </div> -->

        </div>
    </div>
</div>

<!-- Deactivate activate bids modal -->

<!-- The Modal -->
<div class="modal" id="bidModal">
    <div class="modal-dialog">
        <div class="modal-content">

            <!-- Modal Header -->
            <div class="modal-header">
                <h4 class="modal-title">Bid Status Change</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <!-- Modal body -->
            <div class="modal-body">
                <form method="POST" action="{{ route('admin.marketplace.bidstatus') }}" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <div class="form-group">
                        <label for="admin_reason">Reason</label>
                        <input type="text" name="bid_id" id="bid_id">
                        <input type="text" class="form-control" id="admin_reason" name="admin_reason" aria-describedby="emailHelp" placeholder="Enter Reason" required>
                        <small id="emailHelp" class="form-text text-muted">Enter reason why need to change status</small>
                    </div>

                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>

            <!-- Modal footer
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
            </div> -->

        </div>
    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script>
    $(document).ready(function() {
        $(".btn-offer-status").click(function() {
            $('#offer_id').val($('.btn-offer-status').data('id'));
            $('#offerModal').show();
        });

        $(".btn-bid-status").click(function() {
            $('#bid_id').val($('.btn-bid-status').data('id'));
            $('#bidModal').show();
        });

    });
</script>
@endsection