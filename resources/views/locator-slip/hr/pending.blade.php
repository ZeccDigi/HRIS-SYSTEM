<x-app-layout>
    <x-slot name="title">{{ __('Pending Locator Slips') }}</x-slot>

    <div class="p-4 sm:p-6 lg:p-8">
        <div class="bg-white/80 backdrop-blur-xl rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-2xl font-bold text-gray-800">Pending Locator Slips</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Employee</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Date Covered</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Purpose</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Time</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($locatorSlips as $slip)
                            <tr class="hover:bg-gray-50 transition-colors duration-150 group cursor-pointer" onclick="window.location='{{ route('hr.locator-slips.show', $slip->id) }}'">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $slip->employee->firstname }} {{ $slip->employee->lastname }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ $slip->date_covered }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 uppercase">{{ Str::limit($slip->purpose, 30) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ \Carbon\Carbon::parse($slip->time_from)->format('h:i A') }} - {{ \Carbon\Carbon::parse($slip->time_to)->format('h:i A') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-4 py-1 inline-flex text-xs leading-5 font-semibold rounded-full shadow-sm 
                                        @if($slip->status == 'approved') bg-[#00c950] text-white @endif
                                        @if($slip->status == 'rejected') bg-red-500 text-white @endif
                                        @if(Str::contains($slip->status, 'pending')) border border-orange-100 bg-orange-50 text-orange-700 @endif
                                        @if($slip->status == 'approved by chief') bg-blue-500 text-white @endif
                                    ">
                                        {{ ucfirst($slip->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium" onclick="event.stopPropagation()">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('hr.locator-slips.show', $slip->id) }}" class="text-indigo-600 hover:text-indigo-900 font-bold uppercase tracking-wider text-xs"> <i class="fa-solid fa-eye"></i></a>
                                        @if(strtolower(Auth::user()->role) === 'chief')
                                            <form action="{{ route('locator-slips.approve', $slip->id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-green-600 hover:text-green-900 font-bold uppercase tracking-wider text-xs"><i class="fa-solid fa-check"></i></button>
                                            </form>
                                        @endif
                                        <!-- @if(in_array(strtolower(Auth::user()->role), ['chief', 'admin', 'hrstaff']))
                                            <form action="{{ route('locator-slips.reject', $slip->id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-red-600 hover:text-red-900 font-bold uppercase tracking-wider text-xs">button>
                                            </form>
                                        @endif -->
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 whitespace-nowrap text-sm text-gray-500 text-center">No pending locator slips found.</td>
                            </tr><i class="fa-solid fa-trash"></i></
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
