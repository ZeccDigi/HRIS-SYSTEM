export default function registerSalaryRateFilters(Alpine) {
 Alpine.data('salaryRateFilters', () => ({
  editing: null, loading: false, failed: false, descending: false, controller: null,
  init() { this.descending = this.$refs.filters.elements.salary_sort.value === 'salary_desc'; },
  sort() { this.descending = !this.descending; this.$refs.filters.elements.salary_sort.value = this.descending ? 'salary_desc' : 'salary_asc'; this.load(); },
  reset() { const form = this.$refs.filters; ['salary_search','salary_year','salary_grade','salary_step','salary_status'].forEach(name => form.elements[name].value = ''); this.descending = false; form.elements.salary_sort.value = 'salary_asc'; this.load(); },
  async load(pageUrl = null) {
   this.controller?.abort(); const controller = new AbortController(); this.controller = controller;
   const url = pageUrl ? new URL(pageUrl, location.href) : new URL(this.$refs.filters.action, location.href);
   if (!pageUrl) url.search = new URLSearchParams(new FormData(this.$refs.filters)).toString();
   this.loading = true; this.failed = false;
   try { const response = await fetch(url, {signal: controller.signal, headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
    if (!response.ok) throw new Error('Request failed'); const result = await response.json();
    if (controller.signal.aborted) return;
    this.$refs.results.innerHTML = result.html; this.editing = null; history.replaceState(null, '', url);
   } catch (error) { if (error.name !== 'AbortError') this.failed = true; }
   finally { if (this.controller === controller) this.loading = false; }
  }
 }));
}
