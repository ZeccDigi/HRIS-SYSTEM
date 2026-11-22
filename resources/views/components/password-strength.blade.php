@props(['inputId' => 'password'])
<div class="mt-2" x-data="{
    value: '',
    get strength() {
        if (Array.from(this.value).length < 8) return 1;
        const categories = [/\p{Lu}/u.test(this.value), /\p{Ll}/u.test(this.value), /\p{N}/u.test(this.value), /[\p{Z}\p{S}\p{P}]/u.test(this.value)].filter(Boolean).length;
        return categories === 4 ? 3 : categories >= 2 ? 2 : 1;
    }
}" x-init="value = document.getElementById(@js($inputId))?.value || ''"
    @input.window="if ($event.target.id === @js($inputId)) value = $event.target.value">
    <div x-show="value.length > 0" x-cloak>
        <div class="flex gap-1" aria-hidden="true">
            <template x-for="segment in [1, 2, 3]" :key="segment">
                <span class="h-1 flex-1 rounded-full" :class="segment > strength ? 'bg-slate-200' : (strength === 3 ? 'bg-emerald-500' : strength === 2 ? 'bg-amber-500' : 'bg-red-500')"></span>
            </template>
        </div>
        <p class="mt-1 text-xs font-medium" role="status" aria-live="polite"
            :class="strength === 3 ? 'text-emerald-700' : strength === 2 ? 'text-amber-700' : 'text-red-600'"
            x-text="strength === 3 ? 'Strong' : strength === 2 ? 'Medium' : 'Low'"></p>
    </div>
</div>
