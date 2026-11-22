import './bootstrap';
import './profile-date-picker';
import Chart from 'chart.js/auto';
import Alpine from 'alpinejs';
import registerMoneyInput from './money-input';

window.Chart = Chart;
window.Alpine = Alpine;

registerMoneyInput(Alpine);
Alpine.start();
