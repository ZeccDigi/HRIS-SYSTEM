import '../css/profile-date-picker.css';

// Keep the original date inputs so Alpine bindings, validation and submitted dates stay intact.
const selector = '.pds-form-body input[type="date"], input[type="date"][name="service_start_date"], .reports-main-filters input[type="date"]';
let input = null;
let month = new Date();
const popup = document.createElement('div');
popup.className = 'profile-date-calendar';
popup.hidden = true;
popup.setAttribute('role', 'dialog');
popup.setAttribute('aria-label', 'Choose date');
document.body.append(popup);

const dateValue = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
const parse = (value) => /^\d{4}-\d{2}-\d{2}$/.test(value) ? new Date(`${value}T00:00:00`) : new Date();
function close(restoreFocus = false) {
    popup.hidden = true;
    if (restoreFocus) input?.focus();
    input = null;
}
function position() {
    if (!input || popup.hidden) return;
    if (input.matches(':disabled') || !input.getClientRects().length) return close();
    const rect = input.getBoundingClientRect();
    const height = popup.offsetHeight;
    popup.style.left = `${Math.max(12, Math.min(rect.left, window.innerWidth - popup.offsetWidth - 12))}px`;
    popup.style.top = `${Math.max(12, rect.bottom + height + 8 < window.innerHeight ? rect.bottom + 8 : rect.top - height - 8)}px`;
}
function button(label, action, className = '') {
    const element = document.createElement('button');
    element.type = 'button';
    element.textContent = label;
    element.className = className;
    element.addEventListener('click', action);
    return element;
}
function choose(value) {
    if (!input || input.matches(':disabled')) return close();
    if (value && ((input.min && value < input.min) || (input.max && value > input.max))) return;
    input.value = value;
    input.dispatchEvent(new Event('input', { bubbles: true }));
    input.dispatchEvent(new Event('change', { bubbles: true }));
    close(true);
}
function render() {
    popup.replaceChildren();
    const header = document.createElement('div');
    header.className = 'profile-date-header';
    const previous = button('‹', () => { month = new Date(month.getFullYear(), month.getMonth() - 1, 1); render(); });
    previous.setAttribute('aria-label', 'Previous month');
    header.append(previous);
    const months = document.createElement('select');
    months.setAttribute('aria-label', 'Month');
    for (let i = 0; i < 12; i++) months.add(new Option(new Date(2026, i, 1).toLocaleString('en', { month: 'long' }), i));
    months.value = month.getMonth();
    months.addEventListener('change', () => { month = new Date(month.getFullYear(), Number(months.value), 1); render(); });
    const years = document.createElement('select');
    years.setAttribute('aria-label', 'Year');
    const maxYear = input.max ? parse(input.max).getFullYear() : Number(new Intl.DateTimeFormat('en', { timeZone: 'Asia/Manila', year: 'numeric' }).format(new Date()));
    const minYear = input.min ? parse(input.min).getFullYear() : Math.min(1900, month.getFullYear());
    for (let y = maxYear; y >= minYear; y--) years.add(new Option(y, y));
    years.value = month.getFullYear();
    years.addEventListener('change', () => { month = new Date(Number(years.value), month.getMonth(), 1); render(); });
    const next = button('›', () => { month = new Date(month.getFullYear(), month.getMonth() + 1, 1); render(); });
    next.setAttribute('aria-label', 'Next month');
    previous.disabled = Boolean(input.min && dateValue(new Date(month.getFullYear(), month.getMonth(), 0)) < input.min);
    next.disabled = Boolean(input.max && dateValue(new Date(month.getFullYear(), month.getMonth() + 1, 1)) > input.max);
    header.append(months, years, next);
    popup.append(header);
    const grid = document.createElement('div');
    grid.className = 'profile-date-grid';
    for (const name of ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']) {
        const label = document.createElement('span');
        label.textContent = name;
        grid.append(label);
    }
    for (let i = 0; i < new Date(month.getFullYear(), month.getMonth(), 1).getDay(); i++) grid.append(document.createElement('span'));
    for (let day = 1; day <= new Date(month.getFullYear(), month.getMonth() + 1, 0).getDate(); day++) {
        const value = dateValue(new Date(month.getFullYear(), month.getMonth(), day));
        const cell = button(day, () => choose(value));
        cell.setAttribute('aria-label', value);
        cell.disabled = Boolean((input.min && value < input.min) || (input.max && value > input.max));
        if (value === input.value) { cell.classList.add('selected'); cell.setAttribute('aria-current', 'date'); }
        grid.append(cell);
    }
    popup.append(grid);
    const footer = document.createElement('div');
    footer.className = 'profile-date-footer';
    footer.append(button('Clear', () => choose('')), button('Today', () => {
        const today = dateValue(new Date());
        if ((!input.min || today >= input.min) && (!input.max || today <= input.max)) choose(today);
    }));
    popup.append(footer);
    position();
}
function open(target) {
    if (target.matches(':disabled') || target.readOnly) return;
    const currentYear = Number(new Intl.DateTimeFormat('en', { timeZone: 'Asia/Manila', year: 'numeric' }).format(new Date()));
    const yearEnd = `${currentYear}-12-31`;
    target.max = target.max && target.max < yearEnd ? target.max : yearEnd;
    input = target;
    const value = input.value;
    const date = parse(input.max && value > input.max ? input.max : input.min && value && value < input.min ? input.min : value);
    month = new Date(date.getFullYear(), date.getMonth(), 1);
    popup.hidden = false;
    render();
    popup.querySelector('.selected:not(:disabled), .profile-date-grid button:not(:disabled)')?.focus();
}
document.addEventListener('pointerdown', (event) => {
    if (event.target.closest(selector)) event.preventDefault();
}, true);
document.addEventListener('click', (event) => {
    const target = event.target.closest(selector);
    if (target) { event.preventDefault(); open(target); }
    else if (!popup.contains(event.target)) close();
}, true);
document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !popup.hidden) { event.preventDefault(); close(true); }
    else if (event.target.matches(selector) && [' ', 'Enter', 'ArrowDown'].includes(event.key)) {
        event.preventDefault(); open(event.target);
    } else if (event.key === 'Tab' && !popup.hidden) close();
});
window.addEventListener('resize', position);
document.addEventListener('scroll', position, true);
