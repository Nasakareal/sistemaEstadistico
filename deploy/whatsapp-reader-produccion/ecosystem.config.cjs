module.exports = {
    apps: [
        {
            name: 'sistema-estadistico-whatsapp-reader',
            script: './index.js',
            cwd: __dirname,
            autorestart: true,
            restart_delay: 5000,
            max_restarts: 20,
            min_uptime: '30s',
            max_memory_restart: '500M',
            kill_timeout: 15000,
            time: true,
        },
    ],
};
