<x-app-layout>
    <x-slot name="title">Utilities</x-slot>

    @php
        $currentMeta = $groups[$tab] ?? null;
        $groupOptions = $currentMeta ? $optionsByGroup->get($tab, collect()) : collect();
        $parentGroup = $currentMeta['parent_group'] ?? null;
        $parentOptions = $parentGroup ? $optionsByGroup->get($parentGroup, collect()) : collect();
    @endphp

    <div class="p-4 sm:p-6 lg:p-8" x-data="{ createLeaveTypeModalOpen: false }">
        <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white/80 shadow-lg backdrop-blur-xl">
            <div class="border-b border-gray-100 px-6 py-5">
                <h2 class="text-2xl font-bold text-gray-800">Utilities</h2>
                <p class="mt-1 text-sm text-gray-500">Manage fixed dropdown values used across leave, PDS, and SALN.</p>
            </div>

            @if(session('success'))
                <div class="border-b border-emerald-100 bg-emerald-50 px-6 py-3 text-sm font-semibold text-emerald-700">
                    {{ session('success') }}
                </div>
            @endif

            <div class="border-b border-gray-100 px-6 py-4">
                <div class="overflow-x-auto">
                    <div class="flex w-max min-w-full items-center gap-2">
                        @foreach($tabs as $tabKey => $tabLabel)
                            <a
                                href="{{ route('utilities.index', ['tab' => $tabKey]) }}"
                                class="shrink-0 whitespace-nowrap rounded-[6px] px-4 py-2 text-xs font-black uppercase tracking-[0.14em] transition
                                    {{ $tab === $tabKey
                                        ? 'bg-blue-50 text-[#0038a8]'
                                        : 'bg-transparent text-slate-500 hover:bg-slate-50 hover:text-slate-700' }}"
                            >
                                {{ $tabLabel }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="space-y-8 p-6">
                @if($tab === 'leave-types')
                    <div
                        x-data="{ leaveTypeSearch: '' }"
                        class="space-y-5"
                    >
                    <div class="flex flex-col gap-4 mb-6 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Leave Types</h3>
                        </div>
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <div class="relative w-full sm:w-80">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M10.5 18a7.5 7.5 0 1 1 0-15 7.5 7.5 0 0 1 0 15Z" />
                                </svg>
                                <input
                                    type="search"
                                    x-model.debounce.150ms="leaveTypeSearch"
                                    placeholder="Search leave types..."
                                    class="h-11 w-full rounded-xl border-slate-200 bg-white pl-10 pr-3 text-sm font-semibold text-slate-700 shadow-sm transition placeholder:text-slate-400 focus:border-[#0038a8] focus:ring-[#0038a8]"
                                >
                            </div>
                            <button type="button" @click="createLeaveTypeModalOpen = true" class="inline-flex h-11 items-center justify-center rounded-lg bg-[#0038a8] px-4 text-xs font-black uppercase tracking-widest text-white hover:bg-[#002f8f]">
                                Add Leave Type
                            </button>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs font-black uppercase tracking-widest text-slate-500">
                                <tr>
                                    <th class="px-4 py-3">Leave Type</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Created</th>
                                    <th class="px-4 py-3">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @forelse($leaveTypes as $leaveType)
                                    @php
                                        $leaveTypeSearchText = strtolower(implode(' ', array_filter([
                                            $leaveType->name,
                                            ($leaveType->is_active ?? true) ? 'active' : 'inactive',
                                            optional($leaveType->created_at)->format('M d, Y'),
                                        ])));
                                    @endphp
                                    <tr
                                        data-leave-type-row
                                        data-search="{{ $leaveTypeSearchText }}"
                                        x-show="$el.dataset.search.includes(leaveTypeSearch.toLowerCase())"
                                    >
                                        <td class="px-4 py-3 font-semibold text-slate-700">{{ $leaveType->name }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ ($leaveType->is_active ?? true) ? 'Active' : 'Inactive' }}</td>
                                        <td class="px-4 py-3 text-slate-600">{{ optional($leaveType->created_at)->format('M d, Y') }}</td>
                                        <td class="px-4 py-3"><a href="{{ route('leave-types.edit', $leaveType) }}" class="text-xs font-black uppercase tracking-widest text-[#0038a8] hover:text-[#002f8f]">Edit</a></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">No leave types found.</td></tr>
                                @endforelse
                                @if($leaveTypes->isNotEmpty())
                                    <tr
                                        x-show="leaveTypeSearch && !Array.from($root.querySelectorAll('[data-leave-type-row]')).some(row => row.dataset.search.includes(leaveTypeSearch.toLowerCase()))"
                                        style="display: none;"
                                    >
                                        <td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">No leave types match your search.</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                    </div>
                @else
                    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 bg-slate-50 px-5 py-4">
                            <h3 class="text-sm font-black uppercase tracking-[0.14em] text-slate-700">{{ $currentMeta['label'] ?? $tabs[$tab] }}</h3>
                        </div>

                        <div class="space-y-5 p-5">
                            <form method="POST" action="{{ route('utilities.options.store') }}" class="grid gap-3 md:grid-cols-3">
                                @csrf
                                <input type="hidden" name="group_key" value="{{ $tab }}">
                                <div>
                                    <label class="mb-1 block text-[11px] font-black uppercase tracking-widest text-slate-400">Option</label>
                                    <input type="text" name="label" class="w-full rounded-lg border-slate-200 text-sm font-semibold" required>
                                </div>
                                <input type="hidden" name="value" value="">
                                @if($parentGroup)
                                    <div x-data="{
                                        open: false,
                                        search: '',
                                        selectedVal: '',
                                        selectedLabel: 'No parent',
                                        options: @js($parentOptions->map(fn($o) => ['value' => $o->value, 'label' => $o->label])->values()->all())
                                    }" class="relative">
                                        <label class="mb-1 block text-[11px] font-black uppercase tracking-widest text-slate-400">Parent</label>
                                        <input type="hidden" name="parent_value" :value="selectedVal">
                                        <button type="button" @click="open = !open" class="flex h-[38px] w-full items-center justify-between rounded-lg border border-slate-200 bg-white px-3 text-sm font-semibold text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none">
                                            <span x-text="selectedLabel" class="truncate"></span>
                                            <svg class="h-4 w-4 text-slate-400 shrink-0 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7"/></svg>
                                        </button>
                                        <div x-show="open" @click.outside="open = false" class="absolute z-30 mt-1 max-h-60 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white p-2 shadow-lg" x-cloak>
                                            <input type="text" x-model="search" placeholder="Search parent..." class="mb-2 w-full rounded-md border-slate-200 px-2.5 py-1.5 text-xs font-semibold focus:border-blue-500 focus:outline-none">
                                            <div class="space-y-0.5">
                                                <button type="button" @click="selectedVal = ''; selectedLabel = 'No parent'; open = false" class="w-full rounded px-2 py-1.5 text-left text-xs font-semibold hover:bg-slate-50">No parent</button>
                                                <template x-for="opt in options.filter(o => o.label.toLowerCase().includes(search.toLowerCase())).slice(0, 50)" :key="opt.value">
                                                    <button type="button" @click="selectedVal = opt.value; selectedLabel = opt.label; open = false" class="w-full rounded px-2 py-1.5 text-left text-xs font-semibold hover:bg-slate-50" x-text="opt.label"></button>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                <div class="flex items-end justify-end">
                                    <button type="submit" class="inline-flex items-center rounded-lg bg-[#0038a8] px-4 py-2 text-xs font-black uppercase tracking-widest text-white hover:bg-[#002f8f]">Add Option</button>
                                </div>
                            </form>

                            <div class="space-y-3">
                                @forelse($groupOptions->take(200) as $option)
                                    <div class="flex flex-col gap-3 rounded-xl border border-slate-200 p-4 lg:flex-row lg:items-center lg:justify-between">
                                        <form method="POST" action="{{ route('utilities.options.update', $option) }}" class="flex flex-1 flex-col gap-3 md:flex-row md:items-center">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="label" value="{{ $option->label }}" class="w-full rounded-lg border-slate-200 text-sm font-semibold md:w-[30rem]">
                                            <input type="hidden" name="value" value="{{ $option->label }}">
                                            @if($parentGroup)
                                                @if($parentOptions->count() > 100)
                                                    <div class="flex items-center rounded-lg border border-slate-200 bg-slate-50/50 px-3 py-2 text-sm font-semibold text-slate-500 md:w-64">
                                                        @php
                                                            $parentOpt = $parentOptions->firstWhere('value', $option->parent_value);
                                                        @endphp
                                                        <span class="truncate max-w-[12rem]">{{ $parentOpt ? $parentOpt->label : ($option->parent_value ?: 'No Parent') }}</span>
                                                        <input type="hidden" name="parent_value" value="{{ $option->parent_value }}">
                                                    </div>
                                                @else
                                                    <select name="parent_value" class="rounded-lg border-slate-200 text-sm font-semibold md:w-64">
                                                        <option value="">No parent</option>
                                                        @foreach($parentOptions as $parentOption)
                                                            <option value="{{ $parentOption->value }}" @selected($option->parent_value === $parentOption->value)>{{ $parentOption->label }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            @endif
                                            <div class="flex items-center gap-2 lg:ml-auto">
                                                <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-600">
                                                    <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-[#0038a8]" {{ $option->is_active ? 'checked' : '' }}>
                                                    Active
                                                </label>
                                                <button type="submit" class="rounded-lg bg-slate-100 px-4 py-2 text-xs font-black uppercase tracking-widest text-[#0038a8] hover:bg-slate-200">Save</button>
                                            </div>
                                        </form>

                                        <form method="POST" action="{{ route('utilities.options.destroy', $option) }}" onsubmit="return confirm('Remove this option?')" class="lg:ml-1">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg bg-red-50 px-4 py-2 text-xs font-black uppercase tracking-widest text-red-600 hover:bg-red-100">Delete</button>
                                        </form>
                                    </div>
                                @empty
                                    <div class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">No options yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </section>
                @endif
            </div>
        </div>

        <!-- Add Leave Type Modal Overlay -->
        <div x-show="createLeaveTypeModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="createLeaveTypeModalOpen = false"></div>

            <!-- Modal Dialog -->
            <div class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-slate-100 bg-white p-6 shadow-2xl flex flex-col max-h-[90vh]">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-5">
                    <h2 class="text-lg font-bold text-slate-900">Add New Leave Type</h2>
                    <button type="button" @click="createLeaveTypeModalOpen = false" class="rounded-lg p-1 text-slate-400 hover:text-slate-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('leave-types.store') }}" class="space-y-5 overflow-y-auto pr-1">
                    @csrf

                    <!-- LEAVE TYPE NAME -->
                    <div>
                        <label for="modal_name" class="mb-1.5 block text-[11px] font-black uppercase tracking-wider text-slate-500">Leave Type Name</label>
                        <input
                            id="modal_name"
                            type="text"
                            name="name"
                            required
                            class="w-full rounded-xl border-slate-200 text-sm font-semibold text-slate-800 shadow-sm focus:border-[#0038a8] focus:ring-[#0038a8]"
                        >
                    </div>

                    <!-- DAYS PER YEAR (LEAVE CREDIT) -->
                    <div>
                        <label for="modal_days_per_year" class="mb-1.5 block text-[11px] font-black uppercase tracking-wider text-slate-500">Days Per Year (Leave Credit)</label>
                        <input
                            id="modal_days_per_year"
                            type="number"
                            min="0"
                            name="days_per_year"
                            placeholder="Leave empty for unlimited"
                            class="w-full rounded-xl border-slate-200 text-sm font-semibold text-slate-800 shadow-sm focus:border-[#0038a8] focus:ring-[#0038a8]"
                        >
                        <p class="mt-1 text-[11px] font-medium text-slate-400 italic">Leave blank or enter 0 if this type of leave has no specific annual limit.</p>
                    </div>

                    <!-- LEGAL BASIS -->
                    <div>
                        <label for="modal_legal_basis" class="mb-1.5 block text-[11px] font-black uppercase tracking-wider text-slate-500">Legal Basis</label>
                        <input
                            id="modal_legal_basis"
                            type="text"
                            name="legal_basis"
                            placeholder="e.g., Sec. 51, Rule XVI of the Omnibus Rules Implementing E.O. No. 292"
                            class="w-full rounded-xl border-slate-200 text-sm font-semibold text-slate-800 shadow-sm focus:border-[#0038a8] focus:ring-[#0038a8]"
                        >
                        <p class="mt-1 text-[11px] font-medium text-slate-400 italic">Enter the applicable law, rule, or CSC resolution.</p>
                    </div>

                    <!-- BUTTONS -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="createLeaveTypeModalOpen = false"
                            class="rounded-lg px-4 py-2 text-xs font-bold uppercase tracking-wider text-slate-500 hover:text-slate-700 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-lg bg-[#0038a8] px-5 py-2.5 text-xs font-black uppercase tracking-widest text-white shadow hover:bg-[#002f8f] transition"
                        >
                            Save Leave Type
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
