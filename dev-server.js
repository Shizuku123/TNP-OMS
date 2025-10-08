// Simple HTTPS development server for Firebase Storage compatibility
const express = require('express');
const https = require('https');
const fs = require('fs');
const path = require('path');

const app = express();

// Serve static files from current directory
app.use(express.static('.'));

// Create a self-signed certificate for development
const options = {
  key: fs.readFileSync('dev-key.pem'),
  cert: fs.readFileSync('dev-cert.pem')
};

const PORT = 3001;

https.createServer(options, app).listen(PORT, () => {
  console.log(`🔒 HTTPS Development server running at https://localhost:${PORT}`);
  console.log('📁 Serving files from:', __dirname);
  console.log('🔥 Firebase Storage should work with HTTPS');
});

// Generate self-signed certificates if they don't exist
if (!fs.existsSync('dev-key.pem') || !fs.existsSync('dev-cert.pem')) {
  console.log('📜 Generating self-signed certificates for development...');
  console.log('Run these commands in your terminal:');
  console.log('openssl req -newkey rsa:2048 -new -nodes -x509 -days 3650 -keyout dev-key.pem -out dev-cert.pem');
}