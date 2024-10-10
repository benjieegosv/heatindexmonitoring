from sqlalchemy import create_engine
import pandas as pd
from statsmodels.tsa.arima.model import ARIMA
import json

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

# Step 2: Prepare the data for ARIMA
df['date'] = pd.to_datetime(df['date'])
df.set_index('date', inplace=True)

# Set frequency to daily ('D')
df = df.asfreq('D')

# Step 3: Fit ARIMA models for temperature, humidity, and heat index
orders = {
    'temperature': (1, 0, 1),
    'humidity': (1, 0, 1),
    'heatIndex': (1, 0, 1)
}

fitted_models = {}
for variable in ['temperature', 'humidity', 'heatIndex']:
    model = ARIMA(df[variable], order=orders[variable])
    fitted_models[variable] = model.fit()

# Step 4: Generate 7-day forecast for each variable
forecast_steps = 7
forecast_data = {}

for variable in ['temperature', 'humidity', 'heatIndex']:
    forecast = fitted_models[variable].forecast(steps=forecast_steps)
    forecast_data[variable] = forecast

# Step 5: Create a DataFrame with the forecasted values
forecast_dates = pd.date_range(start=df.index[-1] + pd.Timedelta(days=1), periods=forecast_steps)

forecast_df = pd.DataFrame({
    'date': forecast_dates,
    'temperature_forecast': forecast_data['temperature'],
    'humidity_forecast': forecast_data['humidity'],
    'heat_index_forecast': forecast_data['heatIndex']
})

# Step 6: Convert the date column to string format (ISO 8601)
forecast_df['date'] = forecast_df['date'].dt.strftime('%Y-%m-%d')

# Step 7: Add a location column
forecast_df['location'] = 'barangay 630'  # Fixed location

# Step 8: Output the forecast as JSON for the PHP script to consume
print(forecast_df.to_json(orient='records'))  # This will show the data structure

