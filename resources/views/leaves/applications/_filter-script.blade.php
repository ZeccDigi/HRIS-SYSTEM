@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('leaveApplicationFilterForm');
            const tableBody = document.getElementById('leaveApplicationTableBody');
            const pagination = document.getElementById('leaveApplicationPagination');

            if (!form || !tableBody) {
                return;
            }

            let searchTimer = null;

            async function filterLeaveApplications(params = new URLSearchParams(new FormData(form))) {
                tableBody.style.opacity = '0.55';

                try {
                    const response = await fetch(`${form.dataset.filterUrl}?${params.toString()}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    if (!response.ok) {
                        throw new Error('Unable to filter leave applications.');
                    }

                    const data = await response.json();
                    tableBody.innerHTML = data.html;
                    if (pagination && typeof data.pagination === 'string') {
                        pagination.innerHTML = data.pagination;
                    }
                    if (window.Alpine) {
                        window.Alpine.initTree(tableBody);
                    }

                    const nextUrl = `${form.action}?${params.toString()}`;
                    window.history.replaceState({}, '', nextUrl);
                } catch (error) {
                    form.submit();
                } finally {
                    tableBody.style.opacity = '1';
                }
            }

            form.addEventListener('submit', (event) => {
                event.preventDefault();
                filterLeaveApplications();
            });

            form.querySelectorAll('input[type="search"], input[type="text"]').forEach((input) => {
                input.addEventListener('input', () => {
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(() => filterLeaveApplications(), 250);
                });
            });

            form.querySelectorAll('select').forEach((select) => {
                select.addEventListener('change', () => filterLeaveApplications());
            });

            form.querySelectorAll('a[href]').forEach((resetLink) => {
                resetLink.addEventListener('click', (event) => {
                    event.preventDefault();
                    form.querySelectorAll('input').forEach((input) => {
                        input.value = '';
                    });
                    form.querySelectorAll('select').forEach((select) => {
                        select.selectedIndex = 0;
                    });
                    filterLeaveApplications(new URLSearchParams());
                });
            });
        });
    </script>
@endonce
