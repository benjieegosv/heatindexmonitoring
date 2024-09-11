document.getElementById('monitoring-nav').addEventListener('click', function() {
    document.getElementById('monitoring-section').style.display = 'block';
    document.getElementById('compare-section').style.display = 'none';
});

document.getElementById('weekly-data').addEventListener('click', function() {
    alert('Weekly Data feature is not implemented yet.');
});

document.getElementById('compare-data').addEventListener('click', function() {
    document.getElementById('compare-section').style.display = 'block';
    document.getElementById('monitoring-section').style.display = 'none';
    fetchWeatherData();  // Fetch data when the section is shown
});

document.getElementById('hourly-btn').addEventListener('click', function() {
    document.getElementById('forecast-container').innerHTML = `
        <div class="forecast-hour">
            <span>NOW</span>
            <span>28.2°C</span>
            <div class="forecast-bar safe"></div>
        </div>
        <div class="forecast-hour">
            <span>3AM</span>
            <span>28.4°C</span>
            <div class="forecast-bar safe"></div>
        </div>
        <div class="forecast-hour">
            <span>4AM</span>
            <span>29.1°C</span>
            <div class="forecast-bar caution"></div>
        </div>
        <div class="forecast-hour">
            <span>5AM</span>
            <span>28.7°C</span>
            <div class="forecast-bar safe"></div>
        </div>
        <div class="forecast-hour">
            <span>6AM</span>
            <span>29.3°C</span>
            <div class="forecast-bar caution"></div>
        </div>
        <div class="forecast-hour">
            <span>7AM</span>
            <span>30.1°C</span>
            <div class="forecast-bar caution"></div>
        </div>
        <div class="forecast-hour">
            <span>8AM</span>
            <span>30.6°C</span>
            <div class="forecast-bar caution"></div>
        </div>
        <div class="forecast-hour">
            <span>9AM</span>
            <span>32.5°C</span>
            <div class="forecast-bar extreme-caution"></div>
        </div>
        <div class="forecast-hour">
            <span>10AM</span>
            <span>32.8°C</span>
            <div class="forecast-bar extreme-caution"></div>
        </div>
        <div class="forecast-hour">
            <span>11AM</span>
            <span>34.2°C</span>
            <div class="forecast-bar danger"></div>
        </div>
    `;
});

document.getElementById('daily-btn').addEventListener('click', function() {
    document.getElementById('forecast-container').innerHTML = `
        <div class="forecast-hour">
            <span>Today</span>
            <span>31.2°C</span>
            <div class="forecast-bar caution"></div>
        </div>
        <div class="forecast-hour">
            <span>Tomorrow</span>
            <span>30.4°C</span>
            <div class="forecast-bar caution"></div>
        </div>
        <div class="forecast-hour">
            <span>Day 3</span>
            <span>29.6°C</span>
            <div class="forecast-bar safe"></div>
        </div>
        <div class="forecast-hour">
            <span>Day 4</span>
            <span>30.2°C</span>
            <div class="forecast-bar caution"></div>
        </div>
        <div class="forecast-hour">
            <span>Day 5</span>
            <span>31.0°C</span>
            <div class="forecast-bar caution"></div>
        </div>
    `;
});

function heatIndex(T, R) {
    const c1 = -8.78469475556;
    const c2 = 1.61139411;
    const c3 = 2.33854883889;
    const c4 = -0.14611605;
    const c5 = -0.012308094;
    const c6 = -0.0164248277778;
    const c7 = 0.002211732;
    const c8 = 0.00072546;
    const c9 = -0.000003582;
    
    return c1 + c2 * T + c3 * R + c4 * T * R + c5 * T ** 2 + c6 * R ** 2 + c7 * T ** 2 * R + c8 * T * R ** 2 + c9 * T ** 2 * R ** 2;
}

async function fetchWeatherData() {
    try {
        const apiKey = 'YOUR_API_KEY'; // Replace with your actual API key
        const location = 'Manila';
        const response = await fetch(`https://weather.visualcrossing.com/VisualCrossingWebServices/rest/services/weatherdata/forecast?location=${location}&aggregateHours=24&key=${apiKey}`);
        const data = await response.json();

        const temperature = data.currentConditions.temp;
        const humidity = data.currentConditions.humidity;
        const heatIndexValue = heatIndex(temperature, humidity);

        document.getElementById('externalTemperature').textContent = `${temperature}°C`;
        document.getElementById('externalHumidity').textContent = `${humidity}%`;
        document.getElementById('heatIndex').textContent = `${heatIndexValue.toFixed(2)}°C`;
        document.getElementById('updateTime').textContent = `Last updated: ${new Date(data.currentConditions.datetime).toLocaleTimeString()}`;
        document.getElementById('location').textContent = `Location: ${location}`;

    } catch (error) {
        console.error('Error fetching weather data:', error);
    }
}
