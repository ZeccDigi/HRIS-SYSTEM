@if($leaveRequest->attachment_path)
    @php
        $attachmentUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($leaveRequest->attachment_path);
        $attachmentName = basename($leaveRequest->attachment_path);
    @endphp

    <section class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 px-4 py-2.5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 18h10M9 14h6m-7 8h8a2 2 0 0 0 2-2V6l-4-4H8a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2Z" />
                    </svg>
                </span>
                <div class="min-w-0">
                    <p class="text-[9px] font-black uppercase tracking-[0.14em] text-slate-400">{{ __('Supporting Document') }}</p>
                    <p class="truncate text-xs font-semibold text-slate-700" title="{{ $attachmentName }}">{{ $attachmentName }}</p>
                </div>
            </div>

            <a
                href="{{ $attachmentUrl }}"
                target="_blank"
                rel="noopener"
                class="inline-flex h-8 shrink-0 items-center justify-center rounded-lg border border-blue-200 bg-white px-3 text-[10px] font-black uppercase tracking-wider text-blue-700 transition hover:bg-blue-50"
            >
                {{ __('Open Full File') }}
            </a>
        </div>
    </section>
@endif
