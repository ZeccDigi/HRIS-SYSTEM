@props(['reviews' => []])
@php
    $comments = collect($reviews)->map(fn ($review) => [
        'label' => $review['label'],
        'comment' => \App\Support\ApproverRemark::visible($review['comment'] ?? null),
    ])->filter(fn ($review) => $review['comment'] !== null);
@endphp
@if($comments->isNotEmpty())
    <section class="mt-6 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm md:p-8">
        <h3 class="mb-6 inline-block border-b-2 border-indigo-100 pb-1 text-xs font-black uppercase tracking-widest text-indigo-600">{{ __('Approver Remarks') }}</h3>
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            @foreach($comments as $review)
                <div class="rounded-2xl border border-gray-100 bg-gray-50 p-5">
                    <span class="block text-[10px] font-black uppercase tracking-widest text-gray-400">{{ $review['label'] }}</span>
                    <p class="mt-2 whitespace-pre-line break-words text-sm font-semibold leading-relaxed text-gray-700">{{ $review['comment'] }}</p>
                </div>
            @endforeach
        </div>
    </section>
@endif
