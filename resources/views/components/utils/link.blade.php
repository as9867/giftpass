@props([
    'active' => '',
    'text' => '',
    'hide' => false,
    'icon' => false,
    'permission' => false,
    'badge' => false,
])

@if ($permission)
    @if ($logged_in_user->can($permission))
        @if (!$hide)
            <a
                {{ $attributes->merge(['href' => '#', 'class' => $active]) }}
            >
                @if ($icon)
                    <i class="{{ $icon }}"></i>
                @endif
                {{ strlen($text) ? $text : $slot }}
                @if ($badge)
                    <span class="badge badge-info">{{ $badge }}</span>
                @endif
            </a>
        @endif
    @endif
@else
    @if (!$hide)
        <a
            {{ $attributes->merge(['href' => '#', 'class' => $active]) }}
        >
            @if ($icon)
                <i class="{{ $icon }}"></i>
            @endif
            {{ strlen($text) ? $text : $slot }}
            @if ($badge)
                <span class="badge badge-info">{{ $badge }}</span>
            @endif
        </a>
    @endif
@endif
