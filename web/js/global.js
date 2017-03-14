if ('serviceWorker' in navigator) {
    window.addEventListener('load', function() {
        var js = document.getElementById('js');
        if (js && js.dataset.sw) {
            
            // Register Service Worker with URL stored in data-sw
            navigator.serviceWorker.register(js.dataset.sw)
                .then(function() {
                    // Registration was successful

                    var nbCached = document.getElementById('nbCached'),
                        emailForm = document.getElementById('emailForm'),
                        form_email = document.getElementById('form_email'),
                        cacheChecking = false;

                    var checkCache = function() {
                        // Check cache by calling a JSON request handled by the SW
                        if (cacheChecking) {
                            return;
                        }
                        cacheChecking = true;
                        nbCached.classList.add('loading');
                        fetch(js.dataset.cacheurl)
                            .then(function(response) {
                                cacheChecking = false;
                                return response.json();
                            })
                            .then(function(data) {
                                nbCached.classList.remove('loading');
                                if (data.nb) {
                                    // We have some cached data in server
                                    nbCached.innerHTML = data.nb;
                                } else {
                                    nbCached.innerHTML = '';
                                }
                            });
                    };

                    nbCached.addEventListener('click', function(event) {
                        event.preventDefault();
                        checkCache();
                    });

                    // When we go back online, check the cache
                    window.addEventListener('online',  function(event) {
                        console.log('Just went online, checkCache');
                        checkCache();
                    });

                    // SW can force cache checking after sending element
                    navigator.serviceWorker.addEventListener('message', function(event) {
                        if (event.data == 'checkCache') {
                            console.log('Cache update requested by SW');
                            checkCache();
                        }
                    });

                    // Handle form to be an AJAX request
                    emailForm.addEventListener('submit',  function(event) {
                        event.preventDefault();
                        fetch(emailForm.getAttribute('action'), {
                            method: emailForm.getAttribute('method'),
                            body: new FormData(emailForm)
                        }).then(function(response) {
                            return response.json();
                        }).then(function(data) {
                            form_email.value = '';
                        });
                    });
                    
                    // Force check cache on load in case we have some cached elements
                    checkCache();
                    
                    function registerAppInstall() {
                        // from https://developers.google.com/web/fundamentals/engage-and-retain/app-install-banners/
                        var deferredPrompt,
                            btnAppInstall;

                        window.addEventListener('beforeinstallprompt', function(e) {
                            console.log('beforeinstallprompt Event fired');
                            e.preventDefault();

                            // Stash the event so it can be triggered later.
                            deferredPrompt = e;

                            if (!btnAppInstall) {
                                
                                btnAppInstall = document.createElement('a');
                                btnAppInstall.setAttribute('href', '#');
                                btnAppInstall.setAttribute('id', 'btnAppInstall');
                                btnAppInstall.innerHTML = 'Add to homescreen';
                                
                                document.querySelector('body').appendChild(btnAppInstall);
                                
                                btnAppInstall.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    
                                    deferredPrompt.prompt();

                                    // Follow what the user has done with the prompt.
                                    deferredPrompt.userChoice.then(function(choiceResult) {
                                        console.log(choiceResult.outcome);

                                        if (choiceResult.outcome == 'dismissed') {
                                            console.log('User cancelled home screen install');
                                        } else {
                                            console.log('User added to home screen');
                                        }

                                        // We no longer need the prompt.  Clear it up.
                                        deferredPrompt = null;
                                        document.querySelector('body').removeChild(btnAppInstall);
                                        btnAppInstall = false;
                                    });
                                });
                            }

                            return false;
                        });
                    };
                    
                    registerAppInstall();
                }).catch(function(err) {
                    // registration failed :(
                    console.log('ServiceWorker registration failed: ', err);
                });
        }
    });
}