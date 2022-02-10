@switch($status)
    @case('active')
        <span class="badge badge-success text-uppercase">Active</span>
        @break
    @case('hold')
        <span class="badge badge-danger text-uppercase">Hold</span>
        @break
    @case('dispute')
        <span class="badge badge-warning text-uppercase">Dispute</span>
        @break
    @case('dispute_completed')
        <span class="badge badge-success text-uppercase">Dispute Completed</span>
        @break
    @case('inactive')
        <span class="badge badge-secondary text-uppercase">Inactive</span>
        @break
    @case('completed')
        <span class="badge badge-success text-uppercase">Completed</span>
        @break
    @case('pending_live')
        <span class="badge badge-warning text-uppercase">Pending Live</span>
        @break
    @default
        <span class="badge badge-secondary text-uppercase">{{ strtoupper($status) }}</span>
@endswitch()
