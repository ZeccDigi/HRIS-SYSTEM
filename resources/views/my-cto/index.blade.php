<x-app-layout>
    <x-slot name="title">{{ __('My Compensatory Time-Off') }}</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif
            <div x-data="myCtoRequestsTable()" x-init="init()">
                <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-end">
                    <div class="flex items-center gap-2">
                        <label for="my_cto_year" class="text-sm font-semibold text-gray-700">
                            {{ __('Year') }}
                        </label>

                        <select
                            id="my_cto_year"
                            x-model="year"
                            @change="applyFilters()"
                            class="h-10 w-28 appearance-none rounded-lg border-gray-300 bg-white pl-4 pr-10 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            @php
                                $currentYear = now()->year;
                            @endphp
                            @for ($yearOption = $currentYear; $yearOption >= $currentYear - 4; $yearOption--)
                                <option value="{{ $yearOption }}">{{ $yearOption }}</option>
                            @endfor
                        </select>

                        <button
                            type="button"
                            @click="$dispatch('open-cto-request-modal', {
                                url: @js(route('my-cto.create', ['modal' => 1])),
                                title: @js(__('File CTO Request'))
                            })"
                            class="inline-flex items-center justify-center rounded-xl bg-blue-900 px-4 py-2.5 text-xs font-black uppercase tracking-widest text-white transition hover:bg-blue-800"
                        >
                            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            {{ __('File CTO Request') }}
                        </button>
                    </div>
                </div>

                <div class="mb-6 border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8 overflow-x-auto" aria-label="{{ __('CTO tabs') }}">
                        <button
                            type="button"
                            @click="tab = 'applications'"
                            :class="tab === 'applications' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                            class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-bold uppercase tracking-widest transition-colors duration-200"
                        >
                            {{ __('My CTO Applications') }}
                        </button>

                        <button
                            type="button"
                            @click="tab = 'credits'"
                            :class="tab === 'credits' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                            class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-bold uppercase tracking-widest transition-colors duration-200"
                        >
                            {{ __('My CTO Credits') }}
                        </button>
                    </nav>
                </div>

                <div
                    x-show="tab === 'credits'"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100"
                    style="display: none;"
                >
                    <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div class="rounded-2xl bg-blue-900 p-6 text-white shadow-xl shadow-blue-200 md:col-span-1">
                            <span class="text-[10px] font-black uppercase tracking-widest text-blue-100">{{ __('Available Balance') }}</span>
                            <div class="mt-2 flex items-baseline gap-2">
                                <span class="text-4xl font-black">{{ number_format($employee->cto_balance, 1) }}</span>
                                <span class="text-sm font-bold uppercase text-blue-100">{{ __('Hours') }}</span>
                            </div>
                            <p class="mt-2 text-xs text-blue-100">{{ __('Compensatory time-off credits available to use') }}</p>
                        </div>

                        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm md:col-span-2">
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">{{ __('Quick Guide') }}</span>
                            <div class="mt-3 grid grid-cols-1 gap-4 text-sm sm:grid-cols-2">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-xs font-black text-emerald-600">+</span>
                                    <div>
                                        <p class="font-bold text-gray-900">{{ __('Earn CTO') }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500">{{ __('File overtime or extra work rendered to earn credits.') }}</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3">
                                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-xs font-black text-blue-600">-</span>
                                    <div>
                                        <p class="font-bold text-gray-900">{{ __('Use CTO') }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500">{{ __('Apply to use earned credits as time off.') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    x-show="tab === 'applications'"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 transform scale-95"
                    x-transition:enter-end="opacity-100 transform scale-100"
                >
                <div class="bg-white/80 backdrop-blur-xl rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="border-b border-gray-100 bg-gray-50/70 p-2">
                    <div class="flex min-w-max items-center gap-2 overflow-x-auto">
                        <div class="relative min-w-[260px] flex-1">
                            <label for="my_cto_search" class="sr-only">{{ __('Search CTO request') }}</label>
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M21 21l-4.35-4.35M10.75 18.5a7.75 7.75 0 100-15.5 7.75 7.75 0 000 15.5z" />
                                </svg>
                            </span>
                            <input id="my_cto_search" type="search" x-model="search" @input.debounce.200ms="applyFilters()" placeholder="{{ __('Search type, status, or approver...') }}" class="block h-9 w-full rounded-lg border-gray-300 pl-10 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>

                        <select x-model="type" @change="applyFilters()" class="block h-9 w-44 shrink-0 rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('All Types') }}</option>
                            @foreach($ctoRequests->pluck('type_label')->filter()->unique()->sort() as $typeOption)
                                <option value="{{ Str::lower($typeOption) }}">{{ $typeOption }}</option>
                            @endforeach
                        </select>

                        <select x-model="status" @change="applyFilters()" class="block h-9 w-40 shrink-0 rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">{{ __('All Statuses') }}</option>
                            <option value="pending">{{ __('Pending') }}</option>
                            <option value="approved">{{ __('Approved') }}</option>
                            <option value="rejected">{{ __('Rejected') }}</option>
                            <option value="cancelled">{{ __('Cancelled') }}</option>
                        </select>

                        <button type="button" @click="reset()" class="inline-flex h-9 shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-xs font-bold uppercase tracking-wider text-gray-700 transition hover:bg-gray-50">
                            {{ __('Reset') }}
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="w-[15%] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">{{ __('Date Filed') }}</th>
                                <th scope="col" class="w-[25%] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">{{ __('Type') }}</th>
                                <th scope="col" class="w-[17%] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">{{ __('Human Resource') }}</th>
                                <th scope="col" class="w-[18%] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">{{ __('Chief') }}</th>
                                <th scope="col" class="w-[20%] px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">{{ __('Regional Director') }}</th>
                                <th scope="col" class="w-[5%] px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody id="myCtoRequestsTableBody" class="divide-y divide-gray-100 bg-white">
                            @forelse($ctoRequests as $request)
                                @php
                                    $searchText = Str::lower($request->type_label . ' ' . $request->purpose . ' ' . $request->status . ' ' . $request->date_start->format('M d, Y'));
                                    $statusClass = match($request->status) {
                                        'approved' => 'bg-[#00c950] text-white',
                                        'rejected' => 'bg-red-50 text-white',
                                        'pending' => 'border border-orange-100 bg-orange-50 text-orange-700',
                                        default => 'bg-gray-400 text-white',
                                    };
                                    $approvalDotClass = fn (string $status) => match (strtolower($status)) {
                                        'approved' => 'bg-green-500',
                                        'rejected' => 'bg-red-500',
                                        default => 'bg-gray-300',
                                    };
                                @endphp
                                <tr
                                    class="transition-colors duration-150 hover:bg-gray-50"
                                    data-approval-row="{{ $request->id }}"
                                    data-cto-row
                                    data-search="{{ $searchText }}"
                                    data-type="{{ Str::lower($request->type_label) }}"
                                    data-status="{{ Str::lower($request->status) }}"
                                    data-year="{{ $request->created_at?->year ?? now()->year }}"
                                >
                                    <td class="px-6 py-3 text-sm text-gray-700 whitespace-nowrap">
                                        {{ $request->created_at?->format('M d, Y') }}
                                    </td>
                                    <td class="px-6 py-3 text-sm font-bold text-gray-900 whitespace-nowrap">
                                        <span class="font-bold text-gray-900">
                                            {{ $request->type_label }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 align-middle">
                                        <div class="flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $approvalDotClass($request->hrstaff_status ?: 'pending') }}" title="{{ ucfirst($request->hrstaff_status ?: 'pending') }}"></span>
                                            <span class="truncate text-xs font-semibold text-gray-700">HR Admin</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 align-middle">
                                        <div class="flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $approvalDotClass($request->chief_status ?: 'pending') }}" title="{{ ucfirst($request->chief_status ?: 'pending') }}"></span>
                                            <span class="truncate text-xs font-semibold text-gray-700" title="{{ $request->chief ? trim($request->chief->firstname . ' ' . $request->chief->lastname) : 'Chief' }}">
                                                {{ $request->chief ? trim($request->chief->firstname . ' ' . $request->chief->lastname) : 'Chief' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 align-middle">
                                        <div class="flex items-center gap-2">
                                            <span class="h-2.5 w-2.5 shrink-0 rounded-full {{ $approvalDotClass($request->rd_status ?: 'pending') }}" title="{{ ucfirst($request->rd_status ?: 'pending') }}"></span>
                                            <span class="truncate text-xs font-semibold text-gray-700" title="{{ $request->regionalDirector ? trim($request->regionalDirector->firstname . ' ' . $request->regionalDirector->lastname) : 'Regional Director' }}">
                                                {{ $request->regionalDirector ? trim($request->regionalDirector->firstname . ' ' . $request->regionalDirector->lastname) : 'Regional Director' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-3 text-right whitespace-nowrap">
                                        <a href="{{ route('my-cto.show', $request) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-indigo-700 transition-colors hover:bg-indigo-50 hover:text-indigo-900" title="{{ __('View details') }}" aria-label="{{ __('View details') }}">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-8 text-center text-gray-500">
                                        {{ __('No compensatory time-off records found.') }}
                                    </td>
                                </tr>
                            @endforelse
                            @if($ctoRequests->isNotEmpty())
                                <tr x-show="noResults" style="display: none;">
                                    <td colspan="6" class="py-8 text-center text-gray-500">
                                        {{ __('No CTO requests match your search or filter.') }}
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            </div>
            </div>
        </div>
    </div>

    {{-- Create CTO Request modal --}}
    <template x-teleport="body">
        <div
            x-data="{
                createModalOpen: false,
                createModalUrl: '',
                createModalTitle: '',

                open(event) {
                    this.createModalUrl = event.detail.url;
                    this.createModalTitle = event.detail.title;
                    this.createModalOpen = true;

                    document.documentElement.classList.add('overflow-hidden');
                    document.body.classList.add('overflow-hidden');
                },

                closeCreateModal() {
                    this.createModalOpen = false;
                    this.createModalUrl = '';

                    document.documentElement.classList.remove('overflow-hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            }"
            @open-cto-request-modal.window="open($event)"
            @message.window="
                if ($event.data === 'close-cto-request-modal') {
                    closeCreateModal();
                }
            "
            @keydown.escape.window="createModalOpen && closeCreateModal()"
            x-show="createModalOpen"
            x-cloak
            class="fixed inset-0 flex items-center justify-center p-4 sm:p-6"
            style="
                display: none;
                z-index: 99999;
                isolation: isolate;
            "
            role="dialog"
            aria-modal="true"
            aria-labelledby="cto-request-modal-title"
        >
            {{-- Same overlay as the Travel Authority modal --}}
            <div
                class="absolute inset-0"
                style="
                    z-index: 0;
                    background-color: rgba(15, 23, 42, 0.62);
                "
                @click="closeCreateModal()"
                aria-hidden="true"
            ></div>

            {{-- Same modal design and dimensions as Travel Authority --}}
            <div
                class="relative flex w-full max-w-[820px] flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
                style="
                    z-index: 10;
                    width: min(92vw, 820px);
                    height: min(84dvh, 820px);
                    max-height: calc(100dvh - 40px);
                "
                @click.stop
            >
                <div class="flex shrink-0 items-center justify-between bg-blue-900 px-5 py-4 text-white">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-blue-100">
                            {{ __('Request Form') }}
                        </p>

                        <h2
                            id="cto-request-modal-title"
                            class="mt-1 text-xl font-black"
                            x-text="createModalTitle"
                        ></h2>
                    </div>

                    <button
                        type="button"
                        @click="closeCreateModal()"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-full text-white transition hover:bg-white/15 focus:outline-none focus:ring-2 focus:ring-white/70"
                        aria-label="{{ __('Close') }}"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2.5"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-hidden bg-white">
                    <iframe
                        :src="createModalUrl"
                        @load="
                            try {
                                const href = $event.target.contentWindow.location.href;

                                if (
                                    createModalUrl &&
                                    !href.includes('modal=1') &&
                                    !href.includes('/create')
                                ) {
                                    window.location.href = href;
                                }
                            } catch (error) {}
                        "
                        class="h-full w-full border-0 bg-white"
                        :title="createModalTitle"
                    ></iframe>
                </div>
            </div>
        </div>
    </template>

    <script>
        function myCtoRequestsTable() {
            return {
                tab: 'applications',
                search: '',
                type: '',
                status: '',
                year: '{{ now()->year }}',
                rows: [],
                noResults: false,
                init() {
                    this.rows = Array.from(document.querySelectorAll('[data-cto-row]'));
                    this.applyFilters();
                },
                applyFilters() {
                    const search = this.search.trim().toLowerCase();
                    let visibleCount = 0;

                    this.rows.forEach((row) => {
                        const matchesSearch = !search || row.dataset.search.includes(search);
                        const matchesType = !this.type || row.dataset.type === this.type;
                        const matchesStatus = !this.status || row.dataset.status === this.status;
                        const matchesYear = !this.year || row.dataset.year === this.year;
                        const visible = matchesSearch && matchesType && matchesStatus && matchesYear;

                        row.classList.toggle('hidden', !visible);
                        if (visible) visibleCount++;
                    });

                    this.noResults = this.rows.length > 0 && visibleCount === 0;
                },
                reset() {
                    this.search = '';
                    this.type = '';
                    this.status = '';
                    this.year = '{{ now()->year }}';
                    this.applyFilters();
                },
            };
        }
    </script>
</x-app-layout>
