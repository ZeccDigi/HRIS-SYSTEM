import test from 'node:test';
import assert from 'node:assert/strict';
import register from '../resources/js/money-input.js';

test('money fields display commas while the calculation model stays numeric', () => {
    let directive;
    register({ directive(name, callback) { directive = callback; } });
    const row = { amount: '31705' }, listeners = {};
    const el = { value: '', selectionStart: 0, addEventListener(name, fn) { listeners[name] = fn; }, removeEventListener() {}, setSelectionRange() {} };
    globalThis.document = { activeElement: null };
    let refresh;
    directive(el, { expression: 'row.amount' }, {
        evaluate(expression, options = {}) { return Function('row', 'moneyValue', `return (${expression})`)(row, options.scope?.moneyValue); },
        effect(fn) { refresh = fn; fn(); }, cleanup() {},
    });
    assert.equal(el.value, '31,705.00');
    document.activeElement = el;
    el.value = '1,234,567.89'; el.selectionStart = el.value.length; listeners.input();
    assert.equal(row.amount, '1234567.89');
    assert.equal(el.value, '1,234,567.89');
    assert.equal(Number(row.amount) + 1, 1234568.89);
    row.amount = '42000'; document.activeElement = null; refresh();
    assert.equal(el.value, '42,000.00');
    el.value = ''; listeners.input();
    assert.equal(row.amount, '');
});
