// Function to render charts for Date Range Report
function renderDateRangeChart(dateRangeLabels, dateRangeTemperatureData, dateRangeHumidityData, dateRangeHeatIndexData) {
    const ctx = document.getElementById('reportChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dateRangeLabels,
            datasets: [
                {
                    label: 'Temperature (°C)',
                    data: dateRangeTemperatureData,
                    borderColor: 'rgba(255, 165, 0, 1)',
                    backgroundColor: 'rgba(255, 165, 0, 0.2)',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Humidity (%)',
                    data: dateRangeHumidityData,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Heat Index (°C)',
                    data: dateRangeHeatIndexData,
                    borderColor: 'rgba(128, 0, 0, 1)',
                    backgroundColor: 'rgba(128, 0, 0, 0.2)',
                    fill: false,
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Weather Report for the Selected Date Range'
                }
            },
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Values'
                    }
                }
            }
        }
    });
}

// Function to render charts for Next 7 Days Forecast Report
function renderNext7DaysChart(next7DaysLabels, next7DaysTemperatureData, next7DaysHumidityData, next7DaysHeatIndexData) {
    const ctx = document.getElementById('forecastChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: next7DaysLabels,
            datasets: [
                {
                    label: 'Temperature (°C)',
                    data: next7DaysTemperatureData,
                    borderColor: 'rgba(255, 165, 0, 1)',
                    backgroundColor: 'rgba(255, 165, 0, 0.2)',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Humidity (%)',
                    data: next7DaysHumidityData,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Heat Index (°C)',
                    data: next7DaysHeatIndexData,
                    borderColor: 'rgba(128, 0, 0, 1)',
                    backgroundColor: 'rgba(128, 0, 0, 0.2)',
                    fill: false,
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Value'
                    }
                }
            }
        }
    });
}

