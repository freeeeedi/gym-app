import './bootstrap';
import Chart from 'chart.js/auto';
import { registerTimers } from './timers';

// Alpine поставляется вместе с Livewire 3 — регистрируем компоненты до его старта.
window.Chart = Chart;
document.addEventListener('alpine:init', () => registerTimers(window.Alpine));
