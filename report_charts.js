
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

// Function to render charts for Monthly Report
function renderMonthlyCharts(monthlyLabels, monthlyMinTemperature, monthlyMaxTemperature, monthlyAvgTemperature, monthlyMinHumidity, monthlyMaxHumidity, monthlyAvgHumidity, monthlyMinHeatIndex, monthlyMaxHeatIndex, monthlyAvgHeatIndex) {
    // Temperature Chart
    const tempCtx = document.getElementById('temperatureChart').getContext('2d');
    new Chart(tempCtx, {
        type: 'line',
        data: {
            labels: monthlyLabels,
            datasets: [
                {
                    label: 'Min Temperature (°C)',
                    data: monthlyMinTemperature,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Max Temperature (°C)',
                    data: monthlyMaxTemperature,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Avg Temperature (°C)',
                    data: monthlyAvgTemperature,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
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
                        text: 'Months'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Temperature (°C)'
                    }
                }
            }
        }
    });

    // Humidity Chart
    const humidityCtx = document.getElementById('humidityChart').getContext('2d');
    new Chart(humidityCtx, {
        type: 'line',
        data: {
            labels: monthlyLabels,
            datasets: [
                {
                    label: 'Min Humidity (%)',
                    data: monthlyMinHumidity,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Max Humidity (%)',
                    data: monthlyMaxHumidity,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Avg Humidity (%)',
                    data: monthlyAvgHumidity,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
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
                        text: 'Months'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Humidity (%)'
                    }
                }
            }
        }
    });

    // Heat Index Chart
    const heatIndexCtx = document.getElementById('heatIndexChart').getContext('2d');
    new Chart(heatIndexCtx, {
        type: 'line',
        data: {
            labels: monthlyLabels,
            datasets: [
                {
                    label: 'Min Heat Index (°C)',
                    data: monthlyMinHeatIndex,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Max Heat Index (°C)',
                    data: monthlyMaxHeatIndex,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Avg Heat Index (°C)',
                    data: monthlyAvgHeatIndex,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
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
                        text: 'Months'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Heat Index (°C)'
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
