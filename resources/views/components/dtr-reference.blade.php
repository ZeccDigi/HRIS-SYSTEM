@props(['value'])

@php
    $reference = trim((string) $value);
    $parts = explode('-', $reference);

    if (count($parts) >= 3) {
        $firstLine = implode('-', array_slice($parts, 0, 2));
        $secondLine = implode('-', array_slice($parts, 2));
    } else {
        $firstLine = $reference;
        $secondLine = '';
    }
@endphp

<span class="dtr-reference-value" style="display:inline-block; max-width:100%; line-height:1.05; white-space:nowrap; text-align:center;" title="{{ $reference }}" aria-label="{{ $reference }}">
    <span style="display:block;">{{ $firstLine }}</span>
    @if($secondLine !== '')
        <span style="display:block;">{{ $secondLine }}</span>
    @endif
</span>
