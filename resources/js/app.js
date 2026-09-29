import './bootstrap';
import Chart from 'chart.js/auto';
import Alpine from 'alpinejs';

window.Chart = Chart;
window.Alpine = Alpine;

Alpine.start();

function isPdfDownloadLink(link) {
    if (link.hasAttribute('download') || link.hasAttribute('data-no-transition')) {
        return true;
    }

    const href = link.getAttribute('href') ?? '';

    return href.includes('/download') || href.endsWith('.pdf');
}

// Page Transition Logic
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', e => {
            if (
                link.hostname === window.location.hostname &&
                link.pathname !== window.location.pathname &&
                link.target !== '_blank' &&
                !isPdfDownloadLink(link) &&
                !e.ctrlKey && !e.metaKey && !e.shiftKey
            ) {
                e.preventDefault();

                document.body.classList.add('page-transition-leave');

                setTimeout(() => {
                    window.location.href = link.href;
                }, 280);
            }
        });
    });
});
