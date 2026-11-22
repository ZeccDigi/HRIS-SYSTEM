@props(['field' => 'overtime_date', 'value' => ''])
<div x-data="{
    selectedDate: @js($value), activePicker: null, currentMonth: new Date(),
    today: @js(now('Asia/Manila')->toDateString()),
    toDate(value) { return new Date(value + 'T00:00:00'); },
    formatDate(value) { return value ? this.toDate(value).toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'}) : ''; },
    openPicker(field) { this.activePicker = field; this.currentMonth = this.toDate(this.selectedDate || this.today); },
    moveMonth(direction) { this.currentMonth = new Date(this.currentMonth.getFullYear(), this.currentMonth.getMonth() + direction, 1); },
    get monthLabel() { return this.currentMonth.toLocaleDateString('en-US', {month:'long', year:'numeric'}); },
    calendarDays(field) {
        const year = this.currentMonth.getFullYear(), month = this.currentMonth.getMonth();
        const days = [];
        for (let i = 0; i < new Date(year, month, 1).getDay(); i++) days.push({key:'blank-'+i, blank:true});
        for (let day = 1; day <= new Date(year, month+1, 0).getDate(); day++) {
            const value = year + '-' + String(month+1).padStart(2,'0') + '-' + String(day).padStart(2,'0');
            days.push({key:value, blank:false, day, value, selected:value === this.selectedDate, disabled:value > this.today});
        }
        return days;
    },
    selectDate(field, value) {
        if (value > this.today) return;
        this.selectedDate = value; this.activePicker = null;
        this.$nextTick(() => this.$el.querySelector('input[type=hidden]').dispatchEvent(new Event('input', {bubbles:true})));
    }
}">
    <x-weekday-date-picker :field="$field" :label="__('Authorized Overtime Date')" model="selectedDate" :plain-label="true" />
</div>
