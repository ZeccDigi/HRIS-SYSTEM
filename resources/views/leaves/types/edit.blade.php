<x-app-layout>
    <x-slot name="title">{{ __('Edit Leave Type') }}</x-slot>

    <div class="py-4 px-4 sm:px-6 lg:px-8">

{{-- Back button – upper left --}}
<div class="mb-3">
    <a href="{{ route('utilities.index', ['tab' => 'leave-types']) }}"
       style="border-radius: 12px !important;"
       class="inline-flex items-center gap-1 border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-blue-700 shadow-sm hover:bg-slate-50 transition">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        {{ __('Back') }}
    </a>
</div>

        <div class="max-w-2xl mx-auto">
            {{-- Page heading --}}
            <div class="mb-3">
                <h2 class="text-xl font-bold text-gray-800">{{ __('Edit Leave Type') }}</h2>
                <p class="mt-0.5 text-xs text-gray-500">{{ __('Update leave type details, credit limit, and legal basis.') }}</p>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100">
                <div class="p-6">
                    <form method="POST" action="{{ route('leave-types.update', $leaveType) }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        {{-- Name --}}
                        <div>
                            <x-input-label for="name" :value="__('Leave Type Name')" class="text-xs font-black uppercase text-gray-500 tracking-widest" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full text-sm font-bold border-gray-100" :value="old('name', $leaveType->name)" required autofocus />
                            <x-input-error class="mt-1" :messages="$errors->get('name')" />
                        </div>

                        {{-- Days Per Year --}}
                        <div>
                            <x-input-label for="days_per_year" :value="__('Days Per Year (Leave Credit)')" class="text-xs font-black uppercase text-gray-500 tracking-widest" />
                            <x-text-input id="days_per_year" name="days_per_year" type="number" class="mt-1 block w-full text-sm font-bold border-gray-100" :value="old('days_per_year', $leaveType->days_per_year)" placeholder="Leave empty for unlimited" />
                            <x-input-error class="mt-1" :messages="$errors->get('days_per_year')" />
                            <p class="mt-0.5 text-[10px] text-gray-400 font-medium italic">{{ __('Leave blank or enter 0 if this type of leave has no specific annual limit.') }}</p>
                        </div>

                        {{-- Description --}}
                        <div>
                            <x-input-label for="description" :value="__('Description / Remarks')" class="text-xs font-black uppercase text-gray-500 tracking-widest" />
                            <textarea id="description" name="description" class="mt-1 block w-full border-gray-100 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl shadow-sm text-sm font-medium" rows="3">{{ old('description', $leaveType->description) }}</textarea>
                            <x-input-error class="mt-1" :messages="$errors->get('description')" />
                        </div>

                        {{-- Legal Basis --}}
                        <div>
                            <x-input-label for="legal_basis" :value="__('Legal Basis')" class="text-xs font-black uppercase text-gray-500 tracking-widest" />
                            <x-text-input id="legal_basis" name="legal_basis" type="text" class="mt-1 block w-full text-sm font-medium border-gray-100" :value="old('legal_basis', $leaveType->legal_basis)" placeholder="e.g., Sec. 51, Rule XVI of the Omnibus Rules Implementing E.O. No. 292" />
                            <x-input-error class="mt-1" :messages="$errors->get('legal_basis')" />
                            <p class="mt-0.5 text-[10px] text-gray-400 font-medium italic">{{ __('Enter the applicable law, rule, or CSC resolution.') }}</p>
                        </div>

                        {{-- Is Active --}}
                        <div class="flex items-center gap-2 py-2">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $leaveType->is_active) ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <label for="is_active" class="text-sm font-bold text-gray-700">{{ __('Mark as Active') }}</label>
                        </div>

                        <div class="flex items-center justify-end gap-4 pt-2">
                            <a href="{{ route('utilities.index', ['tab' => 'leave-types']) }}" class="text-sm font-bold text-gray-400 hover:text-gray-600 transition-colors duration-200">
                                {{ __('Cancel') }}
                            </a>
                            <x-primary-button class="bg-[#0038a8] hover:bg-[#002f8f] active:bg-blue-900 border-none shadow-lg shadow-blue-200 translate-y-0 hover:-translate-y-0.5 transition-all duration-200">
                                {{ __('Update Leave Type') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
