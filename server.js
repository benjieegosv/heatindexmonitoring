// Import necessary modules
const express = require('express');
const WebSocket = require('ws');
const path = require('path');
const mysql = require('mysql2');
const fs = require('fs');
const nodemailer = require('nodemailer'); // Include nodemailer for email sending

// Create a MySQL connection
const db = mysql.createConnection({
  host: 'localhost',
  user: 'root',
  password: '12345678',  // Replace with your MySQL root password
  database: 'db_heatindex', // Adjust to your actual database name
});

// Check MySQL connection
db.connect((err) => {
  if (err) {
    console.error('Failed to connect to MySQL:', err);
    throw err;
  }
  console.log('Connected to MySQL database');
});

// Set up express app
const app = express();
const wss = new WebSocket.Server({ port: 8080 });

// Serve static files from the 'public' directory
app.use(express.static(path.join(__dirname, 'public')));

// Create the transporter for nodemailer (replace with your SMTP settings)
const transporter = nodemailer.createTransport({
  service: 'gmail',
  auth: {
    user: 'kazeynaval0329@gmail.com', // Your Gmail address
    pass: 'htszjykecyxlclhg', // Your Gmail app password (use app password if 2FA enabled)
  },
});

// Function to send Heat Index Alert email
function sendHeatIndexAlert(emails, classification, heatIndex, description, safetyPrecautions, location) {
  const mailOptions = {
    from: 'kazeynaval0329@gmail.com', // Sender address
    to: emails.join(','), // Recipient list
    subject: 'Heat Index Danger Alert',
    html: `
      <h2>Heat Index Danger Alert for Location: ${location}</h2>
      <p><strong>Heat Index:</strong> ${heatIndex}</p>
      <p><strong>Classification:</strong> ${classification}</p>
      <p><strong>Description:</strong> ${description}</p>
      <p><strong>Safety Precautions:</strong> ${safetyPrecautions}</p>
    `,
  };

  // Send the email
  transporter.sendMail(mailOptions, (error, info) => {
    if (error) {
      console.error('Failed to send email:', error);
    } else {
      console.log('Email sent:', info.response);
    }
  });
}

// WebSocket event handling
wss.on('connection', (ws) => {
  console.log('New WebSocket connection');

  ws.on('message', (data) => {
    try {
      const jsonData = JSON.parse(data);
      const { deviceId, temperature, humidity, heat_index } = jsonData;

      console.log(`Data from ${deviceId} - Temp: ${temperature}, Humidity: ${humidity}, Heat Index: ${heat_index}`);

      // Insert data into MySQL
      const query = 'INSERT INTO monitoring (deviceId, temperature, humidity, heat_index) VALUES (?, ?, ?, ?)';
      db.query(query, [deviceId, temperature, humidity, heat_index], (err, result) => {
        if (err) {
          console.error('Failed to insert data into MySQL:', err);
          logDataToFile(jsonData); // Log to file if DB fails
          return;
        }
        console.log('Data inserted into database');

        // Check the heat index and send email if the classification is "danger" or "extreme danger"
        const queryClassification = `
          SELECT classification, description, safety_precautions 
          FROM heat_index_levels 
          WHERE ? BETWEEN minHeatIndex AND maxHeatIndex
          LIMIT 1
        `;
        db.query(queryClassification, [heat_index], (err, rows) => {
          if (err || rows.length === 0) {
            console.error('Error fetching classification:', err || 'No classification found');
            return;
          }

          const { classification, description, safety_precautions } = rows[0];
          console.log(`Heat Index Classification: ${classification}`);

          // Send email only if classification is exactly "danger" or "extreme danger"
          if (classification.toLowerCase() === 'danger' || classification.toLowerCase() === 'extreme danger') {
            // Get location from the device_info table based on deviceId
            const locationQuery = 'SELECT location FROM device_info WHERE deviceId = ?';
            db.query(locationQuery, [deviceId], (err, locationRows) => {
              const location = locationRows.length > 0 ? locationRows[0].location : 'Unknown Location';

              // Fetch staff and guest emails
              const emailQuery = 'SELECT email FROM staff_account UNION SELECT email FROM guest_account';
              db.query(emailQuery, (err, emailRows) => {
                if (err || emailRows.length === 0) {
                  console.error('Error fetching emails:', err || 'No emails found');
                  return;
                }

                const emails = emailRows.map(row => row.email);
                sendHeatIndexAlert(emails, classification, heat_index, description, safety_precautions, location);
              });
            });
          }
        });
      });

      // Broadcast data to all connected clients
      wss.clients.forEach((client) => {
        if (client.readyState === WebSocket.OPEN) {
          client.send(data); // Send the original data to all connected clients
        }
      });
    } catch (error) {
      console.error('Error parsing JSON data:', error);
    }
  });

  ws.on('close', () => {
    console.log('WebSocket connection closed');
  });
});

// Function to log data to a file if the database fails
function logDataToFile(data) {
  const logEntry = `${new Date().toISOString()} - Device ID: ${data.deviceId}, Temperature: ${data.temperature}, Humidity: ${data.humidity}, Heat Index: ${data.heat_index}\n`;
  fs.appendFile('sensor_data_backup.log', logEntry, (err) => {
    if (err) {
      console.error('Error writing to file:', err);
    } else {
      console.log('Data logged to file:', logEntry);
    }
  });
}

// Serve the frontend at the root URL '/'
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// Start the HTTP server on port 3000
app.listen(3000, () => {
  console.log('Server running on http://localhost:3000');
});
