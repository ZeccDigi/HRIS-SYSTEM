<fieldset x-show="monetization" :disabled="!monetization" x-cloak class="mt-4 space-y-4 rounded-xl border border-blue-100 bg-blue-50/60 p-4">
    <legend class="px-1 text-sm font-bold text-blue-900">Monetization of Leave Credits</legend>
    <p class="text-sm text-slate-700">Current credits: <strong x-text="Number(info?.balance || 0).toFixed(3) + ' days'"></strong></p>
    <div>
        <label class="mb-1 block text-sm font-semibold" for="monetization_basis">Monetization basis</label>
        <select id="monetization_basis" name="monetization_basis" x-model="monetizationBasis" class="w-full rounded-lg border-slate-300 text-sm">
            <option value="regular" :disabled="isSickLeave">Regular Vacation Leave monetization</option>
            <option value="exceptional">Exceptional VL / SL monetization</option>
        </select>
        <p class="mt-1 text-xs text-slate-600" x-show="monetizationBasis === 'regular'">At least 15 available VL days, a minimum of 10 days requested, 5 days retained, and up to 30 days per year.</p>
        <p class="mt-1 text-xs text-slate-600" x-show="monetizationBasis === 'exceptional'">Requires agency approval and available funds. VL must be exhausted before SL.</p>
        <x-input-error :messages="$errors->get('monetization_basis')" />
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1 block text-sm font-semibold" for="monetization_days">Days to monetize</label>
            <input id="monetization_days" name="monetization_days" type="number" min="0.001" step="0.001" x-model="monetizationDays" :required="monetization" class="w-full rounded-lg border-slate-300 text-sm">
            <x-input-error :messages="$errors->get('monetization_days')" />
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold" for="monetization_salary">Highest monthly salary received (₱)</label>
            <input id="monetization_salary" type="text" :value="monetizationSalary === '' || monetizationSalary == null ? '' : Number(monetizationSalary).toLocaleString('en-PH', { maximumFractionDigits: 2 })" readonly class="w-full rounded-lg border-slate-300 bg-slate-50 text-sm">
            <input name="monetization_salary" type="hidden" :value="monetizationSalary">
            @if($monetizationSalarySource)
                <p class="mt-1 text-xs text-slate-600">{{ collect([$monetizationSalarySource['grade'], $monetizationSalarySource['step'], $monetizationSalarySource['year'] ? $monetizationSalarySource['year'] : null])->filter()->implode(' · ') }}</p>
            @else
                <p role="status" class="mt-1 text-xs text-amber-700">No salary recorded in the PDS</p>
            @endif
            <x-input-error :messages="$errors->get('monetization_salary')" />
        </div>
    </div>
    <div class="grid gap-3 rounded-lg border border-blue-100 bg-white p-3 sm:grid-cols-2">
        <div><p class="text-xs text-slate-500">Estimated gross amount</p><p class="font-bold text-blue-900" x-text="(Number(monetizationSalary || 0) * Number(monetizationDays || 0) * 0.0481927).toLocaleString('en-PH', { style: 'currency', currency: 'PHP' })"></p></div>
        <div><p class="text-xs text-slate-500">Estimated remaining credits</p><p class="font-bold" x-text="Math.max(0, Number(info?.balance || 0) - Number(monetizationDays || 0)).toFixed(3) + ' days'"></p></div>
    </div>
    <p class="text-xs text-slate-600">Salary × days × 0.0481927. Amount is subject to HR verification and approval. Credits are deducted after final approval.</p>
</fieldset>
