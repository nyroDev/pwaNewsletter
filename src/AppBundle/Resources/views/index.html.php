<!doctype html>
<html>
    <head>
        <title>PWA Newsletter</title>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link rel="stylesheet" href="<?php echo $view['assets']->getUrl('css/global.css') ?>" />
        
        <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $view['assets']->getUrl('favicons/apple-touch-icon.png') ?>">
        <link rel="icon" type="image/png" href="<?php echo $view['assets']->getUrl('favicons/favicon-32x32.png') ?>" sizes="32x32">
        <link rel="icon" type="image/png" href="<?php echo $view['assets']->getUrl('favicons/favicon-16x16.png') ?>" sizes="16x16">
        <link rel="manifest" href="<?php echo $view['router']->path('manifest') ?>">
        <link rel="mask-icon" href="<?php echo $view['assets']->getUrl('favicons/safari-pinned-tab.svg') ?>" color="#96bef9">
        <link rel="shortcut icon" href="<?php echo $view['assets']->getUrl('favicons/favicon.ico') ?>">
        <meta name="msapplication-config" content="<?php echo $view['assets']->getUrl('favicons/browserconfig.xml') ?>">
        <meta name="theme-color" content="#ffffff">
        
    </head>
    <body>
        <p>Please enter your email address to receive great news from us!</p>
        <?php echo $view['form']->form($form) ?>
        <a id="nbCached" href="#"></a>
        <script id="js"
            data-sw="<?php echo $view['router']->path('sw') ?>"
            data-cacheurl="<?php echo $view['assets']->getUrl('swNbCache.json') ?>"
            src="<?php echo $view['assets']->getUrl('js/global.js') ?>"></script>
    </body>
</html>