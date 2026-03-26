import './bootstrap';
import './realtime';
import ApexCharts from 'apexcharts';
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import { Calendar } from '@fullcalendar/core';

window.ApexCharts = ApexCharts;
window.flatpickr = flatpickr;
window.FullCalendar = Calendar;

const applyThemeFromStorage = () => {
    const savedTheme = localStorage.getItem('theme');
    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    const theme = savedTheme || systemTheme;
    const html = document.documentElement;
    const body = document.body;

    if (theme === 'dark') {
        html.classList.add('dark');
        body.classList.add('dark', 'bg-gray-900');
    } else {
        html.classList.remove('dark');
        body.classList.remove('dark', 'bg-gray-900');
    }
};

const initWidgets = () => {
    
    if (document.querySelector('#memberMap')) {
        import('./components/member-map').then(module => module.initMemberMap());
    }
    if (document.querySelector('#chartTwo')) {
        import('./components/chart/chart-2').then(module => module.initChartTwo());
    }

    if (document.querySelector('#chartThree')) {
        import('./components/chart/chart-3').then(module => module.initChartThree());
    }
    if (document.querySelector('#reader')) {
        import('./components/qr-scanner').then(module => module.initQrScanner());
    }
};

document.addEventListener('DOMContentLoaded', initWidgets);
document.addEventListener('livewire:navigated', initWidgets);
document.addEventListener('DOMContentLoaded', applyThemeFromStorage);
document.addEventListener('livewire:navigated', applyThemeFromStorage);
document.addEventListener('livewire:init', () => {
    if (window.Livewire?.on) {
        let chartTwoModulePromise;
        let chartThreeModulePromise;
        let memberMapModulePromise;
        const getChartTwoModule = () => chartTwoModulePromise ??= import('./components/chart/chart-2');
        const getChartThreeModule = () => chartThreeModulePromise ??= import('./components/chart/chart-3');
        const getMemberMapModule = () => memberMapModulePromise ??= import('./components/member-map');

        window.Livewire.on('monthly-target-updated', (data) => {
            const payload = data?.detail ?? data ?? {};
            getChartTwoModule().then((module) => {
                module.updateChartTwo?.(payload.progressPercent);
            });
        });

        window.Livewire.on('statistics-updated', (data) => {
            const payload = data?.detail ?? data ?? {};
            getChartThreeModule().then((module) => {
                module.updateChartThree?.(payload.series, payload.categories);
            });
        });

        window.Livewire.on('member-map-updated', (data) => {
            const payload = data?.detail ?? data ?? {};
            getMemberMapModule().then((module) => {
                module.updateMemberMap?.(payload.markers);
            });
        });

        window.Livewire.on('printer-sources-updated', (data) => {
            const payload = data?.detail ?? (Array.isArray(data) ? data[0] : data) ?? {};
            const sources = payload?.sources ?? payload;

            window.PRINTER_SOURCES = Array.isArray(sources) ? sources : [];

            if (window.PrinterManager?.configureSources) {
                window.PrinterManager.configureSources(window.PRINTER_SOURCES);
            }

            window.dispatchEvent(new CustomEvent('printer-sources-updated', { detail: { sources: window.PRINTER_SOURCES } }));
        });
    }
});

document.addEventListener('alpine:init', () => {
    Alpine.data('currencyInput', (entangle) => ({
        value: entangle,
        displayValue: '',

        init() {
            this.formatDisplay();

            this.$watch('value', (val) => {
                const currentNumeric = this.unformat(this.displayValue);
                if (val !== currentNumeric) {
                    this.formatDisplay();
                }
            });
        },

        formatDisplay() {
            if (this.value === null || this.value === undefined || this.value === '') {
                this.displayValue = '';
                return;
            }
            this.displayValue = new Intl.NumberFormat('id-ID').format(this.value);
        },

        unformat(val) {
            if (!val) return null;
            const raw = val.replace(/[^\d]/g, '');
            return raw === '' ? null : parseInt(raw, 10);
        },

        handleInput(e) {
            const input = e.target;
            const originalVal = input.value;
            const raw = this.unformat(originalVal);
            this.value = raw;
            
            if (raw !== null) {
                this.displayValue = new Intl.NumberFormat('id-ID').format(raw);
            } else {
                this.displayValue = '';
            }
        }
    }));
});
