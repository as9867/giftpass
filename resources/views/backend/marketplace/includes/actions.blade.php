<x-utils.view-button :href="route('admin.marketplace.show', $marketplace)" />

@if ($marketplace->status == 'dispute')
    <x-utils.form-button
        :action="route('admin.marketplace.reverse', $marketplace)"
        method="post"
        button-class="btn btn-danger btn-sm"
        icon="fas fa-sync-alt"
        name="confirm-item">Dispute</x-utils.form-button>
@endif
