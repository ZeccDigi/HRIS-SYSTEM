@props(['employeeId' => null])
<div class="contents text-sm"
    x-data="{
        result: null, message: '', timer: null, sequence: 0,
        queue() { clearTimeout(this.timer); this.timer = setTimeout(() => this.calculate(), 250); },
        async calculate() {
            const token = ++this.sequence;
            const form = this.$el.closest('form');
            const date = form.querySelector('[name=overtime_date], [name=date_start]')?.value;
            const hours = Number(form.querySelector('[name=hours]')?.value);
            this.result = null;
            if (!date || hours < 2) { this.message = 'Choose an overtime date and enter at least 2 rendered hours.'; return; }
            this.message = 'Calculating…';
            const url = new URL(@js(route('coc.quote')));
            url.searchParams.set('date', date); url.searchParams.set('hours', hours);
            if (@js($employeeId)) url.searchParams.set('employee_id', @js($employeeId));
            try {
                const response = await fetch(url, {headers: {'Accept': 'application/json'}});
                const data = await response.json();
                if (token !== this.sequence) return;
                if (!response.ok) { this.message = Object.values(data.errors || {}).flat()[0] || 'Unable to calculate. Check the date and hours.'; return; }
                this.result = data; this.message = '';
            } catch (error) { if (token === this.sequence) this.message = 'Calculation unavailable. Please try again.'; }
        }
    }"
    x-init="$el.closest('form').addEventListener('input', () => queue()); $el.closest('form').addEventListener('change', () => queue()); if (typeof dateStart !== 'undefined') $watch('dateStart', () => queue()); queue()">
    <label class="block text-sm font-bold text-gray-700">
        {{ __('Converted COC Hours') }}
        <input type="text" readonly :value="result ? Number(result.hours).toFixed(2) : ''" placeholder="Automatic" class="mt-1 block h-11 w-full rounded-xl border-gray-300 bg-gray-50 text-sm font-semibold text-gray-900 shadow-sm" aria-live="polite">
    </label>
    <p class="col-span-full text-xs text-slate-600" x-show="!result" x-text="message"></p>
    <template x-if="result"><div class="col-span-full">
        <p><span x-text="Number(result.rendered_hours).toFixed(2)"></span> rendered hours × <span x-text="Number(result.coc_rate).toFixed(1)"></span> = <strong x-text="Number(result.hours).toFixed(2) + ' COC hours'"></strong></p>
        <p class="mt-1 text-xs text-slate-600" x-text="result.overtime_classification"></p>
    </div></template>
</div>
