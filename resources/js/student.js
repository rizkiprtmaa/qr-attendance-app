// resources/js/student-charts.js
let weeklyChart = null;
let statusChart = null;
let monthlyChart = null;

// Inisialisasi chart pertama kali
function initializeCharts(weeklyData, monthlyData, statusData) {
    initWeeklyChart(weeklyData);
    initStatusChart(statusData);
    initMonthlyChart(monthlyData);
}

// Update chart yang sudah ada
function updateCharts(weeklyData, monthlyData, statusData) {
    updateWeeklyChart(weeklyData);
    updateStatusChart(statusData);
    updateMonthlyChart(monthlyData);
}

function initWeeklyChart(data) {
    const ctx = document
        .getElementById("weeklyAttendanceChart")
        .getContext("2d");
    weeklyChart = new Chart(ctx, {
        type: "bar",
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { stacked: true },
                y: { stacked: true, beginAtZero: true },
            },
            plugins: { legend: { position: "top" } },
        },
    });
}

function updateWeeklyChart(data) {
    if (weeklyChart) {
        weeklyChart.data.labels = data.labels;
        weeklyChart.data.datasets = data.datasets;
        weeklyChart.update();
    }
}

function initStatusChart(data) {
    const ctx = document
        .getElementById("statusDistributionChart")
        .getContext("2d");
    statusChart = new Chart(ctx, {
        type: "doughnut",
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: "60%",
            plugins: { legend: { position: "bottom" } },
        },
    });
}

function updateStatusChart(data) {
    if (statusChart) {
        statusChart.data.labels = data.labels;
        statusChart.data.datasets = data.datasets;
        statusChart.update();
    }
}

function initMonthlyChart(data) {
    const ctx = document
        .getElementById("monthlyAttendanceChart")
        .getContext("2d");
    monthlyChart = new Chart(ctx, {
        type: "line",
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true } },
            plugins: { legend: { position: "top" } },
        },
    });
}

function updateMonthlyChart(data) {
    if (monthlyChart) {
        monthlyChart.data.labels = data.labels;
        monthlyChart.data.datasets = data.datasets;
        monthlyChart.update();
    }
}
