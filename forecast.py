from sqlalchemy import create_engine 
import pandas as pd
from statsmodels.tsa.arima.model import ARIMA
import numpy as np
import json
import matplotlib.pyplot as plt

# Step 1: Create the SQLAlchemy engine
engine = create_engine('mysql+pymysql://root:12345678@localhost/db_heatindex')

# Query to get the last 5 years of data from the external_heat_index_data table
query = """
SELECT date, temperature, humidity, heatIndex
FROM external_heat_index_data
WHERE date >= DATE_SUB((SELECT MAX(date) FROM external_heat_index_data), INTERVAL 5 YEAR)
ORDER BY date ASC
"""

# Use the SQLAlchemy engine to read the SQL query into a Pandas DataFrame
df = pd.read_sql(query, engine)

# Step 2: Prepare the data for ARIMAX
df['date'] = pd.to_datetime(df['date'])
df.set_index('date', inplace=True)

# Set frequency to daily ('D') and fill missing dates with interpolation
df = df.asfreq('D').interpolate(method='time')

# Step 3: Fit ARIMAX model for heat index with temperature and humidity as exogenous variables
order = (1, 0, 1)  # Non-differenced model
exogenous_vars = df[['temperature', 'humidity']]

# Fit ARIMAX model for heat index
model = ARIMA(df['heatIndex'], exog=exogenous_vars, order=order)
fitted_model = model.fit()

# Step 4: Generate 7-day forecast for heat index
forecast_steps = 7

# Prepare future exogenous variables for forecasting
last_temperature = df['temperature'].iloc[-1]
last_humidity = df['humidity'].iloc[-1]

# Prepare the forecast exogenous DataFrame
forecast_exog = pd.DataFrame(index=pd.date_range(start=df.index[-1] + pd.Timedelta(days=1), periods=forecast_steps))

# Adding reduced variability to forecasted temperature and humidity
temperature_forecast = []
humidity_forecast = []

# Set a random seed for reproducibility
np.random.seed(42)

# Adjust the variability to be smaller for more accuracy
for _ in range(forecast_steps):
    # Introduce random fluctuations around the last observed values
    temperature_variation = np.random.uniform(-0.5, 0.5)  # Reduced random variation between -0.5 and 0.5
    humidity_variation = np.random.uniform(-1, 1)  # Reduced random variation between -1 and 1
    
    forecast_temp = last_temperature + temperature_variation
    forecast_humidity = last_humidity + humidity_variation
    forecast_humidity = max(forecast_humidity, 0)  # Ensure humidity does not go below zero
    
    temperature_forecast.append(forecast_temp)
    humidity_forecast.append(forecast_humidity)

# Assign the generated temperature and humidity forecasts to the exogenous variables
forecast_exog['temperature'] = temperature_forecast
forecast_exog['humidity'] = humidity_forecast

# Perform the forecast for heat index
forecast_heat_index = fitted_model.forecast(steps=forecast_steps, exog=forecast_exog)

# Prepare forecast dates
forecast_dates = pd.date_range(start=df.index[-1] + pd.Timedelta(days=1), periods=forecast_steps)

# Step 5: Create a DataFrame with the forecasted values
forecast_df = pd.DataFrame({
    'date': forecast_dates,
    'temperature_forecast': temperature_forecast,
    'humidity_forecast': humidity_forecast,
    'heat_index_forecast': forecast_heat_index
})

# Step 6: Convert the date column to string format (ISO 8601)
forecast_df['date'] = forecast_df['date'].dt.strftime('%Y-%m-%d')

# Step 7: Add a location column
forecast_df['location'] = 'barangay 630'  # Fixed location

# Step 8: Select relevant columns for JSON output
json_output = forecast_df[['date', 'temperature_forecast', 'humidity_forecast', 'heat_index_forecast', 'location']]

# Step 9: Output the forecast as JSON for the PHP script to consume
print(json_output.to_json(orient='records'))  # Output the JSON

# Step 10: Visualization
plt.figure(figsize=(14, 6))

# Scatter plot for Temperature vs Heat Index
plt.subplot(1, 2, 1)
plt.scatter(df['temperature'], df['heatIndex'], color='orange', label='Historical Data', alpha=0.6)
plt.scatter(forecast_df['temperature_forecast'], forecast_df['heat_index_forecast'], color='maroon', label='Forecasted Data', alpha=0.6)
plt.title('Temperature vs Heat Index')
plt.xlabel('Temperature (°C)')
plt.ylabel('Heat Index (°C)')
plt.legend()
plt.grid()

# Scatter plot for Humidity vs Heat Index
plt.subplot(1, 2, 2)
plt.scatter(df['humidity'], df['heatIndex'], color='#ADD8E6', label='Historical Data', alpha=0.6)
plt.scatter(forecast_df['humidity_forecast'], forecast_df['heat_index_forecast'], color='maroon', label='Forecasted Data', alpha=0.6)
plt.title('Humidity vs Heat Index')
plt.xlabel('Humidity (%)')
plt.ylabel('Heat Index (°C)')
plt.legend()
plt.grid()

plt.tight_layout()  # Adjusts subplots to fit into figure area.

# Save the plot
plt.savefig('Images/heat_index_plot.png')  # Adjust the path as needed
plt.close()
