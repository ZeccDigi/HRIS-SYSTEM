<div x-show="printPreviewOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6">
    <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" @click="printPreviewOpen = false"></div>
    <div class="relative w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white shadow-2xl border border-gray-100">
        <div class="px-6 py-5 border-b border-gray-100 flex items-start justify-between gap-4">
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-[#0038a8]">{{ __('UI Preview Only') }}</p>
                <h2 class="text-xl font-black text-gray-900">{{ __('Printable SALN Copy') }}</h2>
                <p class="text-xs text-gray-500 mt-1">{{ __('Sample printable layout preview') }}</p>
            </div>
            <button type="button" @click="printPreviewOpen = false" class="h-9 w-9 rounded-lg text-gray-400 hover:bg-gray-50 hover:text-gray-700">
                <svg class="mx-auto w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="p-6 space-y-6">
            @isset($saln)
                <div class="saln-official saln-preview rounded-xl border border-gray-200 bg-white p-6">
                    @include('saln.partials.content')
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('salns.download', $saln) }}" data-no-transition class="rounded-lg bg-gray-800 px-4 py-2 text-[10px] font-black uppercase tracking-widest text-white hover:bg-gray-700">{{ __('Download') }}</a>
                    <a href="{{ route('salns.download', ['saln' => $saln, 'inline' => 1]) }}" target="_blank" rel="noopener noreferrer" data-no-transition class="rounded-lg bg-[#0038a8] px-4 py-2 text-[10px] font-black uppercase tracking-widest text-white hover:bg-[#002f8f]">{{ __('Print') }}</a>
                </div>
            @else
                <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-6 text-center text-sm font-semibold text-gray-500">
                    {{ __('Open a saved SALN record to preview the printable official copy.') }}
                </div>
            @endisset
        </div>
    </div>
</div>
