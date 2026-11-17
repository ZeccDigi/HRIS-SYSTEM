@props([
    'payload' => null,
    'event' => 'approval-selected',
    'empty' => 'No approval to track yet.',
    'compact' => false,
])

<div class="{{ $compact ? 'mb-4' : 'mb-6' }}" x-data="{ selectedApproval: @js($payload) }" x-on:{{ $event }}.window="selectedApproval = $event.detail">
    <div class="mx-auto {{ $compact ? 'max-w-4xl rounded-xl px-4 py-3 sm:px-5 sm:py-3.5' : 'max-w-5xl rounded-2xl px-6 py-5 sm:px-8 sm:py-6' }} border border-gray-100 bg-white text-center shadow-sm">
        <template x-if="selectedApproval">
            <div>
                <div class="flex flex-wrap items-center justify-center {{ $compact ? 'gap-3 sm:gap-4' : 'gap-4 sm:gap-6' }}">
                    <template x-for="(stage, index) in selectedApproval.stages" :key="stage.label">
                        <div class="inline-flex items-center {{ $compact ? 'gap-2.5 sm:gap-3' : 'gap-3 sm:gap-5' }}">
                            <div class="inline-flex items-center {{ $compact ? 'gap-2' : 'gap-3' }}" :title="stage.label + ': ' + stage.status">
                                <span class="{{ $compact ? 'h-3 w-3' : 'h-4 w-4' }} rounded-full" :class="stage.status === 'approved' ? 'bg-green-500' : (stage.status === 'rejected' ? 'bg-red-500' : 'bg-gray-300')"></span>
                                <span class="{{ $compact ? 'text-xs sm:text-sm' : 'text-sm sm:text-base' }} font-bold text-gray-600" x-text="stage.label"></span>
                            </div>
                            <span class="hidden {{ $compact ? 'text-base' : 'text-xl' }} font-bold text-gray-300 sm:inline" x-show="index < selectedApproval.stages.length - 1">&rarr;</span>
                        </div>
                    </template>
                </div>
            </div>
        </template>
        <template x-if="! selectedApproval">
            <div class="text-base font-bold text-gray-500">{{ __($empty) }}</div>
        </template>
    </div>
</div>
