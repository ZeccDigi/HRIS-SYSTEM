export default function registerMoneyInput(Alpine) {
    Alpine.directive('money', (el, { expression }, { evaluate, effect, cleanup }) => {
        el.type = 'text';
        el.inputMode = 'decimal';
        const format = (value, fixed = false) => {
            if (value === null || value === undefined || value === '') return '';
            const raw = String(value).replaceAll(',', '');
            if (!/^\d*(\.\d{0,2})?$/.test(raw)) return raw;
            const [whole, fraction] = (fixed && Number.isFinite(Number(raw)) ? Number(raw).toFixed(2) : raw).split('.');
            return (whole || '0').replace(/\B(?=(\d{3})+(?!\d))/g, ',') + (fraction !== undefined ? '.' + fraction : '');
        };
        const refresh = () => {
            const text = format(evaluate(expression), document.activeElement !== el);
            if (el.value !== text) el.value = text;
        };
        const input = () => {
            const caret = el.selectionStart ?? el.value.length;
            const digitsBefore = el.value.slice(0, caret).replaceAll(',', '').length;
            const raw = el.value.replaceAll(',', '');
            // Keep invalid input visible for server validation; never silently change its amount.
            evaluate(`${expression} = moneyValue`, { scope: { moneyValue: raw } });
            const text = format(raw);
            el.value = text;
            let position = 0, count = 0;
            while (position < text.length && count < digitsBefore) { if (text[position] !== ',') count++; position++; }
            el.setSelectionRange(position, position);
        };
        el.addEventListener('input', input);
        el.addEventListener('blur', refresh);
        effect(refresh);
        cleanup(() => { el.removeEventListener('input', input); el.removeEventListener('blur', refresh); });
    });
}
