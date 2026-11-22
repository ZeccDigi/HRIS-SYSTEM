@props(['href', 'kind' => 'back'])

@php
    $isEdit = $kind === 'edit';
    $appearance = $isEdit
        ? 'rounded-lg border-blue-700 hover:bg-blue-50'
        : 'rounded-xl border-slate-200 shadow-sm hover:bg-slate-50';
@endphp

<a href="{{ $href }}" {{ $attributes->class(["inline-flex h-9 shrink-0 items-center justify-center gap-2 border bg-white px-4 text-xs font-semibold text-blue-800 transition focus-visible:ring-2 focus-visible:ring-blue-600", $appearance]) }}>
    @if($isEdit)
        <i class="fa-solid fa-pen text-xs" aria-hidden="true"></i>
        {{ __('Edit') }}
    @else
        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m15 19-7-7 7-7" />
        </svg>
        {{ __('Back') }}
    @endif
</a>
