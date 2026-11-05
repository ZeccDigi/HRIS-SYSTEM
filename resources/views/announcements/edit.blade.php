<x-app-layout>
    <x-slot name="title">{{ __('Edit Announcement') }}</x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm rounded-2xl border border-gray-100">
                <div class="p-8">
                    <form action="{{ route('announcements.update', $announcement) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        @method('PUT')
                        
                        <div>
                            <label for="title" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('Title') }}</label>
                            <input type="text" name="title" id="title" value="{{ old('title', $announcement->title) }}" placeholder="{{ __('What\'s the news?') }}" class="w-full px-4 py-3 rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition-all text-gray-900 font-medium placeholder:text-gray-400 @error('title') border-red-500 @enderror" required>
                            @error('title')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="tags" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('Tags (Comma separated)') }}</label>
                            @if(strtolower(auth()->user()->role ?? '') === 'chief')
                                <input type="hidden" name="tags" value="Office Orders">
                                <div class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-700">{{ __('Office Orders — Your division only') }}</div>
                            @else
                                <input type="text" name="tags" id="tags" value="{{ old('tags', $announcement->tags) }}" placeholder="HR, Event, Important" class="w-full px-4 py-3 rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition-all text-sm @error('tags') border-red-500 @enderror">
                            @endif
                            @error('tags')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="content" class="block text-sm font-semibold text-gray-700 mb-1">{{ __('Content') }}</label>
                            <textarea name="content" id="content" rows="12" placeholder="{{ __('Write your announcement detail here...') }}" class="w-full px-4 py-3 rounded-xl border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition-all text-gray-900 @error('content') border-red-500 @enderror" required>{{ old('content', $announcement->content) }}</textarea>
                            @error('content')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <div class="mt-2 text-xs text-gray-400 flex items-center justify-between">
                                <span>{{ __('Formatting with plain text or HTML is supported.') }}</span>
                                <span id="charCount">{{ strlen($announcement->content) }} {{ __('characters') }}</span>
                            </div>
                        </div>

                        <div x-data="{ fileName: '' }">
                            <label for="attachment" class="block text-sm font-semibold text-gray-700 mb-1">
                                {{ __('Attachment') }}
                                <span class="text-gray-400 font-normal text-xs">&mdash; {{ __('Optional') }}</span>
                            </label>
                            <div class="relative group">
                                <input type="file"
                                    name="attachment"
                                    id="attachment"
                                    x-ref="attachment"
                                    class="hidden"
                                    @change="fileName = $event.target.files[0]?.name || ''"
                                    accept=".pdf">

                                <label for="attachment"
                                    class="flex items-center justify-between w-full border-2 border-dashed border-gray-200 group-hover:border-indigo-400 rounded-xl p-4 bg-gray-50/50 cursor-pointer transition-all duration-300 @error('attachment') border-red-300 @enderror">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="p-2 bg-indigo-100 rounded-lg text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                            </svg>
                                        </div>
                                        <div class="min-w-0">
                                            <span x-show="!fileName" class="text-sm font-bold text-gray-500">
                                                {{ $announcement->attachment_path ? __('Replace Current PDF') : __('Attach PDF File') }}
                                            </span>
                                            <span x-show="fileName" x-text="fileName" class="block text-sm font-bold text-indigo-700 truncate max-w-xs"></span>
                                            <p class="text-xs text-gray-400">{{ __('PDF file only (Max 5MB)') }}</p>
                                        </div>
                                    </div>

                                    <span x-show="!fileName" class="text-[10px] font-black text-indigo-600 bg-indigo-50 px-3 py-1 rounded-full group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                                        {{ __('Browse') }}
                                    </span>
                                    <button type="button" x-show="fileName" @click.prevent="fileName = ''; $refs.attachment.value = ''" class="text-red-500 hover:text-red-700">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </label>
                            </div>
                            @if($announcement->attachment_path)
                                <a href="{{ route('announcements.attachment', $announcement) }}" target="_blank" class="mt-3 inline-flex items-center gap-2 rounded-xl bg-indigo-50 px-3 py-2 text-sm font-bold text-indigo-600 hover:bg-indigo-100 hover:text-indigo-800">
                                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path>
                                    </svg>
                                    <span class="truncate">{{ basename($announcement->attachment_path) }}</span>
                                </a>
                            @endif
                            @error('attachment')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{--
                        <div class="bg-gray-50/50 p-6 rounded-2xl border border-gray-100/50 flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="flex items-center h-5">
                                    <input type="checkbox" name="is_published" id="is_published" value="1" class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 transition-all cursor-pointer" {{ old('is_published', $announcement->is_published) ? 'checked' : '' }}>
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="is_published" class="font-bold text-gray-900 cursor-pointer">{{ __('Published Status') }}</label>
                                    <p class="text-gray-500">{{ __('Announcement will be visible to everyone if checked.') }}</p>
                                </div>
                            </div>
                            
                            <div class="hidden sm:block">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium bg-amber-100 text-amber-800" id="draftBadge" style="{{ $announcement->is_published ? 'display: none' : '' }}">
                                    {{ __('Status: Draft') }}
                                </span>
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-medium bg-green-100 text-green-800" id="publishedBadge" style="{{ !$announcement->is_published ? 'display: none' : '' }}">
                                    {{ __('Status: Published') }}
                                </span>
                            </div>
                        </div>
                        --}}

                        <div class="flex items-center justify-end gap-4 pt-4">
                            <a href="{{ route('announcements.index') }}" class="px-6 py-2.5 text-sm font-bold text-gray-700 hover:text-gray-900 transition-colors uppercase tracking-wider">
                                {{ __('Cancel') }}
                            </a>
                            <button type="submit" class="px-8 py-2.5 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 shadow-sm hover:shadow-indigo-200 transition-all uppercase tracking-wider text-sm">
                                {{ __('Update Announcement') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const contentArea = document.getElementById('content');
        const charCount = document.getElementById('charCount');
        const publishCheck = document.getElementById('is_published');
        const draftBadge = document.getElementById('draftBadge');
        const publishedBadge = document.getElementById('publishedBadge');

        if(contentArea) {
            contentArea.addEventListener('input', () => {
                const count = contentArea.value.length;
                charCount.textContent = `${count} characters`;
            });
        }

        if(publishCheck) {
            publishCheck.addEventListener('change', () => {
                if(publishCheck.checked) {
                    draftBadge.style.display = 'none';
                    publishedBadge.style.display = 'inline-flex';
                } else {
                    draftBadge.style.display = 'inline-flex';
                    publishedBadge.style.display = 'none';
                }
            });
        }
    </script>
</x-app-layout>
