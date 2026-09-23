
export const initChartFour = () => {
    const chartElement = document.querySelector('#chartFour');

    if (chartElement) {
        let series = [];
        let labels = [];

        try {
            if (chartElement.dataset.series) {
                const parsed = JSON.parse(chartElement.dataset.series);
                if (Array.isArray(parsed)) {
                    series = parsed;
                }
            }
        } catch {}

        try {
            if (chartElement.dataset.labels) {
                const parsed = JSON.parse(chartElement.dataset.labels);
                if (Array.isArray(parsed)) {
                    labels = parsed;
                }
            }
        } catch {}

        const formatRupiah = (value) => {
            const num = Number(value) || 0;
            return 'Rp' + num.toLocaleString('id-ID');
        };

        if (chartElement.__apexChart) {
            chartElement.__apexChart.updateOptions({ labels }, false, true);
            chartElement.__apexChart.updateSeries(series, true);
            return chartElement.__apexChart;
        }

        const chartFourOptions = {
            series,
            labels,
            colors: ["#2d4299", "#22c55e", "#f59e0b", "#ef4444", "#06b6d4", "#a855f7", "#ec4899", "#64748b", "#94a3b8"],
            chart: {
                fontFamily: "Outfit, sans-serif",
                height: 360,
                type: "donut",
            },
            legend: {
                show: true,
                position: "bottom",
            },
            dataLabels: {
                enabled: true,
                formatter: function (val) {
                    return val.toFixed(1) + "%";
                },
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return formatRupiah(val);
                    },
                },
            },
        };

        const chart = new ApexCharts(chartElement, chartFourOptions);
        chart.render();
        chartElement.__apexChart = chart;
        return chart;
    }
}

export const updateChartFour = (series, labels) => {
    const chartElement = document.querySelector('#chartFour');
    if (!chartElement) return;

    if (!chartElement.__apexChart) {
        return initChartFour();
    }

    const safeSeries = Array.isArray(series) ? series : chartElement.__apexChart.w.config.series;
    const safeLabels = Array.isArray(labels) ? labels : chartElement.__apexChart.w.config.labels;

    chartElement.__apexChart.updateOptions({ labels: safeLabels }, false, true);
    chartElement.__apexChart.updateSeries(safeSeries, true);
    return chartElement.__apexChart;
};

export default initChartFour;
