module.exports = {
    apps: [
        {
            name: 'sistema-estadistico-whatsapp-reader',
            script: './index.js',
            cwd: __dirname,
            autorestart: true,
            restart_delay: 5000,
            max_restarts: 20,
            time: true,
        },
    ],
};
