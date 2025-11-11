/**
 * CORS Middleware Configuration
 * Allows cross-origin requests from frontend
 */

const cors = require('cors');

// Configure CORS options
const corsOptions = {
    origin: function (origin, callback) {
        // Allow requests with no origin (like mobile apps or curl requests)
        if (!origin) return callback(null, true);
        
        // Get allowed origins from environment or use defaults
        const allowedOrigins = process.env.ALLOWED_ORIGINS 
            ? process.env.ALLOWED_ORIGINS.split(',')
            : [
                'http://localhost:8080',
                'http://localhost:3000',
                'http://127.0.0.1:8080',
                'http://127.0.0.1:3000'
            ];
        
        // Check if origin is allowed
        if (allowedOrigins.indexOf(origin) !== -1 || allowedOrigins.includes('*')) {
            callback(null, true);
        } else {
            // For development, allow all origins
            if (process.env.NODE_ENV === 'development') {
                callback(null, true);
            } else {
                callback(new Error('Not allowed by CORS'));
            }
        }
    },
    credentials: true,
    methods: ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization']
};

module.exports = cors(corsOptions);
