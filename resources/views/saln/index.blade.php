<x-app-layout>
    <x-slot name="title">{{ __('My SALN') }}</x-slot>

    <div class="p-4 sm:p-6 lg:p-8">
        <div class="bg-white/80 backdrop-blur-xl rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="p-6 flex justify-between items-center border-b border-gray-100">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">My SALN</h2>
                    <p class="mt-1 text-sm text-gray-500">Open a saved record for printing, or continue with a new SALN entry.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    @if($salns->isNotEmpty())
                        <a href="{{ route('salns.show', ['saln' => $salns->first(), 'print' => 1]) }}" class="inline-flex w-fit items-center gap-2 rounded-xl bg-[#0038a8] px-5 py-2.5 text-xs font-black uppercase tracking-widest text-white shadow-md shadow-blue-100 hover:bg-[#002f8f] transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9V2h12v7M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2m-12 0h12v-6H6v6z"></path>
                            </svg>
                            Print SALN Copy
                        </a>
                    @endif
                    <a href="{{ route('salns.create') }}" class="inline-flex w-fit items-center gap-2 rounded-xl bg-[#0038a8] px-5 py-2.5 text-xs font-black uppercase tracking-widest text-white shadow-md shadow-blue-100 transition hover:bg-[#002f8f]">
                        Save SALN Entry
                    </a>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">As of Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Filing Type</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Total Assets</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Total Liabilities</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Net Worth</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($salns as $saln)
                            <tr class="hover:bg-gray-50 transition-colors duration-150 group cursor-pointer" onclick="window.location='{{ route('salns.show', $saln) }}'">
                                <td class="py-4 px-6 text-sm text-gray-700 whitespace-nowrap">
                                    {{ $saln->as_of_date->format('F d, Y') }}
                                </td>
                                <td class="py-4 px-6 text-sm text-gray-700 uppercase whitespace-nowrap">
                                    {{ str_replace('_', ' ', $saln->type_of_filing) }}
                                </td>
                                <td class="py-4 px-6 text-sm text-gray-700 whitespace-nowrap">
                                    ₱{{ number_format($saln->total_assets, 2) }}
                                </td>
                                <td class="py-4 px-6 text-sm text-gray-700 whitespace-nowrap">
                                    ₱{{ number_format($saln->total_liabilities, 2) }}
                                </td>
                                <td class="py-4 px-6 text-sm font-semibold text-gray-900 whitespace-nowrap">
                                    ₱{{ number_format($saln->net_worth, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-gray-500">
                                    No SALN records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
