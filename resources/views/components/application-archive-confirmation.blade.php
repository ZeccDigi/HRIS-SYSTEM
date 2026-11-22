<dialog id="application-archive-confirmation" aria-labelledby="application-archive-question"
    class="m-auto w-[calc(100%-2rem)] max-w-sm rounded-2xl border border-slate-200 bg-white p-5 text-left text-slate-900 shadow-xl backdrop:bg-slate-900/50">
    <p id="application-archive-question" class="text-base font-semibold"></p>
    <div class="mt-5 flex justify-end gap-2">
        <button type="button" data-archive-cancel autofocus class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('Cancel') }}</button>
        <button type="button" data-archive-confirm class="rounded-lg bg-[#0038a8] px-4 py-2 text-sm font-semibold text-white hover:bg-blue-900 disabled:opacity-50">{{ __('Archive') }}</button>
    </div>
</dialog>
<script>
(() => {
    const dialog = document.getElementById('application-archive-confirmation');
    const confirmButton = dialog.querySelector('[data-archive-confirm]');
    let pendingForm = null;
    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-archive-question')) return;
        event.preventDefault();
        if (dialog.open) return;
        pendingForm = form;
        confirmButton.disabled = false;
        confirmButton.textContent = form.dataset.confirmLabel || @js(__('Archive'));
        dialog.querySelector('#application-archive-question').textContent = form.dataset.archiveQuestion;
        dialog.showModal();
    });
    dialog.querySelector('[data-archive-cancel]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => { pendingForm = null; });
    confirmButton.addEventListener('click', () => {
        if (!pendingForm || !pendingForm.isConnected || confirmButton.disabled) return;
        confirmButton.disabled = true;
        HTMLFormElement.prototype.submit.call(pendingForm);
    });
})();
</script>
