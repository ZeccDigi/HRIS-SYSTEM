@props(['announcement'])

<form action="{{ route('announcements.destroy', $announcement) }}" method="POST">
    @csrf
    @method('DELETE')
    <button type="button" title="{{ __('Archive') }}" aria-label="{{ __('Archive announcement') }}"
            onclick="this.nextElementSibling.showModal()"
            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-700 transition hover:bg-red-50 hover:text-red-900">
        <i class="fa-solid fa-box-archive text-sm" aria-hidden="true"></i>
    </button>
    <dialog aria-label="{{ __('Archive announcement') }}"
            class="m-auto w-[calc(100%-2rem)] max-w-sm rounded-2xl border border-slate-200 bg-white p-5 text-left text-slate-900 shadow-xl backdrop:bg-slate-900/50">
        <p class="text-base font-semibold">{{ __('Archive this announcement?') }}</p>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" autofocus onclick="this.closest('dialog').close()"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                {{ __('Cancel') }}
            </button>
            <button type="submit" class="rounded-lg bg-[#0038a8] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-900">
                {{ __('Archive') }}
            </button>
        </div>
    </dialog>
</form>
