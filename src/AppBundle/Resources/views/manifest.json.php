{
    "name": "PWA Newsletter",
    "short_name": "PWA Newslet.",
    "icons": [
        {
            "src": "<?php echo $view['assets']->getUrl('favicons/android-chrome-192x192.png') ?>",
            "sizes": "192x192",
            "type": "image/png"
        },
        {
            "src": "<?php echo $view['assets']->getUrl('favicons/android-chrome-256x256.png') ?>",
            "sizes": "256x256",
            "type": "image/png"
        }
    ],
    "theme_color": "#ffffff",
    "background_color": "#ffffff",
    "display": "standalone",
    "start_url": "<?php echo $view['router']->path('homepage') ?>"
}