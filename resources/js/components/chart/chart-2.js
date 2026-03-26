
export const initChartTwo = () => {
    const chartElement = document.querySelector('#chartTwo');

    if (chartElement) {
        let series = [75.55];

        try {
            if (chartElement.dataset.series) {
                const parsed = JSON.parse(chartElement.dataset.series);
                if (Array.isArray(parsed)) {
                    series = parsed;
                }
            }
        } catch {}

        if (chartElement.__apexChart) {
            chartElement.__apexChart.updateSeries(series, true);
            return chartElement.__apexChart;
        }

        const chartTwoOptions = {
            series,
            colors: ["#e5394a"],
            chart: {
                fontFamily: "Outfit, sans-serif",
                type: "radialBar",
                height: 330,
                sparkline: {
                    enabled: true,
                },
            },
            plotOptions: {
                radialBar: {
                    startAngle: -90,
                    endAngle: 90,
                    hollow: {
                        size: "80%",
                    },
                    track: {
                        background: "#f2f4f7",
                        strokeWidth: "100%",
                        margin: 5, // margin is in pixels
                    },
                    dataLabels: {
                        name: {
                            show: false,
                        },
                        value: {
                            fontSize: "36px",
                            fontWeight: "600",
                            offsetY: 60,
                            color: "#e5394a",
                            formatter: function (val) {
                                return val + "%";
                            },
                        },
                    },
                },
            },
            fill: {
                type: "solid",
                colors: ["#e5394a"],
            },
            stroke: {
                lineCap: "round",
            },
            labels: ["Progress"],
        };

        const chart = new ApexCharts(chartElement, chartTwoOptions);
        chart.render();
        chartElement.__apexChart = chart;
        return chart;
    }
}

export const updateChartTwo = (progressPercent) => {
    const chartElement = document.querySelector('#chartTwo');
    if (!chartElement) return;

    const value = Number(progressPercent);
    const series = [Number.isFinite(value) ? value : 0];

    if (!chartElement.__apexChart) {
        return initChartTwo();
    }

    chartElement.__apexChart.updateSeries(series, true);
    return chartElement.__apexChart;
};

export default initChartTwo;
