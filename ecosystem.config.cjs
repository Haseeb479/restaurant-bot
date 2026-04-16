module.exports = {
    apps: [
        {
            name: 'restaurant-bot',
            script: 'bot-waiter-v2.js',
            interpreter: 'node',
            cwd: 'C:\\Users\\Seeb\\restaurant-bot\\restaurant-bot',
            watch: false,
            autorestart: true,
            restart_delay: 5000,
            max_restarts: 10,
            env: {
                NODE_ENV: 'production'
            },
            log_date_format: 'YYYY-MM-DD HH:mm:ss',
            error_file: 'logs/bot-error.log',
            out_file: 'logs/bot-out.log',
            merge_logs: true
        },
        {
            name: 'laravel-server',
            script: 'artisan',
            interpreter: 'php',
            args: 'serve --host=127.0.0.1 --port=8000',
            cwd: 'C:\\Users\\Seeb\\restaurant-bot\\restaurant-bot',
            watch: false,
            autorestart: true,
            restart_delay: 3000,
            max_restarts: 10,
            log_date_format: 'YYYY-MM-DD HH:mm:ss',
            error_file: 'logs/laravel-error.log',
            out_file: 'logs/laravel-out.log',
            merge_logs: true
        }
    ]
};
