
export const initChartThree = () => {
    const chartElement = document.querySelector('#chartThree');

    if (chartElement) {
        let series = [{
            name: "Revenue",
            data: [1800000, 1900000, 1700000, 1600000, 1750000, 1650000, 1700000, 2050000, 2300000, 2100000, 2400000, 2350000],
        },
        ];

        let categories = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

        try {
            if (chartElement.dataset.series) {
                const parsed = JSON.parse(chartElement.dataset.series);
                if (Array.isArray(parsed)) {
                    series = parsed;
                }
            }
        } catch {}

        try {
            if (chartElement.dataset.categories) {
                const parsed = JSON.parse(chartElement.dataset.categories);
                if (Array.isArray(parsed)) {
                    categories = parsed;
                }
            }
        } catch {}

        const formatShort = (value) => {
            const num = Number(value) || 0;
            const abs = Math.abs(num);

            if (abs >= 1_000_000_000) {
                return `${(num / 1_000_000_000).toFixed(1)}M`;
            }

            if (abs >= 1_000_000) {
                return `${(num / 1_000_000).toFixed(1)}JT`;
            }

            if (abs >= 1_000) {
                return `${(num / 1_000).toFixed(1)}K`;
            }

            return `${num}`;
        };

        if (chartElement.__apexChart) {
            chartElement.__apexChart.updateOptions({
                xaxis: {
                    categories,
                },
            }, false, true);
            chartElement.__apexChart.updateSeries(series, true);
            return chartElement.__apexChart;
        }

        const chartThreeOptions = {
            series,
            legend: {
                show: series.length > 1,
                position: "top",
                horizontalAlign: "left",
            },
            colors: ["#e5394a", "#64748b", "#22c55e", "#ff9ca5", "#ffc9b9"],
            chart: {
                fontFamily: "Outfit, sans-serif",
                height: 310,
                type: "area",
                toolbar: {
                    show: false,
                },
            },
            fill: {
                gradient: {
                    enabled: true,
                    opacityFrom: 0.55,
                    opacityTo: 0,
                },
            },
            stroke: {
                curve: "straight",
                width: 2,
            },
            markers: {
                size: 0,
            },
            labels: {
                show: false,
                position: "top",
            },
            grid: {
                xaxis: {
                    lines: {
                        show: false,
                    },
                },
                yaxis: {
                    lines: {
                        show: true,
                    },
                },
            },
            dataLabels: {
                enabled: false,
            },
            tooltip: {
                x: {
                    format: "dd MMM yyyy",
                },
                y: {
                    formatter: function (val) {
                        return formatShort(val);
                    },
                },
            },
            xaxis: {
                type: "category",
                categories,
                axisBorder: {
                    show: false,
                },
                axisTicks: {
                    show: false,
                },
                tooltip: false,
            },
            yaxis: {
                title: {
                    style: {
                        fontSize: "0px",
                    },
                },
                labels: {
                    formatter: function (val) {
                        return formatShort(val);
                    },
                },
            },
        };

        const chart = new ApexCharts(chartElement, chartThreeOptions);
        chart.render();
        chartElement.__apexChart = chart;
        return chart;
    }
}

export const updateChartThree = (series, categories) => {
    const chartElement = document.querySelector('#chartThree');
    if (!chartElement) return;

    if (!chartElement.__apexChart) {
        return initChartThree();
    }

    const safeSeries = Array.isArray(series) ? series : chartElement.__apexChart.w.config.series;
    const safeCategories = Array.isArray(categories) ? categories : chartElement.__apexChart.w.config.xaxis.categories;

    chartElement.__apexChart.updateOptions({
        xaxis: {
            categories: safeCategories,
        },
    }, false, true);
    chartElement.__apexChart.updateSeries(safeSeries, true);
    return chartElement.__apexChart;
};

export default initChartThree;
