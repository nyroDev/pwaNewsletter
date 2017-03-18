var cacheName = 'pwa-newsletter-cache-v1',
    urlsToCache = [
        '<?php echo $view['router']->path('homepage') ?>',
        '<?php echo $view['assets']->getUrl('css/global.css') ?>',
        '<?php echo $view['assets']->getUrl('js/global.js') ?>'
    ],
    dbName = 'pwaNewsletter',
    dbCollection = 'requests',
    nbCacheUrl = '<?php echo $view['assets']->getUrl('swNbCache.json') ?>';

self.addEventListener('install', function(event) {
    // On install, just add our cache
    //event.waitUntil(self.skipWaiting()); return;
    event.waitUntil(
        caches.open(cacheName)
            .then(function(cache) {
                return cache.addAll(urlsToCache);
            })
    );
});

self.addEventListener('activate', function(event) {
    // Once SW is activated, claim all clients to be sure they are directly handled by SW to avoid page reload
    event.waitUntil(self.clients.claim());
});

// Post message to all clients
function postMessage(msg) {
    clients.matchAll()
        .then(function(clients) {
            clients.map(function(client) {
                client.postMessage(msg);
            });
        });
};

// Request clients to update their cache by sending them a message
function requestUpdateCache() {
    console.log('send cache update');
    postMessage({
        data: 'checkCache'
    });
};

// Request clients to update their cache by sending them a message
function sendCacheNb() {
    console.log('send cache nb');
    getNbCachedRequests().then(function(nb) {
        postMessage({
            data: 'cacheNb',
            nb: nb
        });
    });
};

// Request clients to increment the loading counter
function addClientLoading() {
    console.log('send addLoading');
    postMessage({
        data: 'addLoading'
    });
};

// Request clients to decreament the loading counter
function removeClientLoading() {
    console.log('send removeLoading');
    postMessage({
        data: 'removeLoading'
    });
};

// Show alert on clients
function sendAlert(alert) {
    console.log('send alert');
    postMessage({
        data: 'alert',
        alert: alert
    });
};

// Request background sync
function requestSync() {
    sendAlert('request SYNC');
    if (!self.registration || !self.registration.sync) {
        return;
    }
    self.registration.sync.register('sendCached').then(function() {
        sendAlert('registaration sync OK');
    }, function() {
        sendAlert('registaration sync FAILED');
    });
};

// Open IndexedDB as promise, init it if needed
function openDB() {
    return new Promise(function(resolve, reject) {
        var request = indexedDB.open(dbName);
        request.onerror = function(event) {
            reject(event);
        };
        request.onupgradeneeded = function(event) {
            if (!event.target.result.oldversion) {
                console.log('indexedDB init');
                var db = event.target.result;
                db.createObjectStore(dbCollection, { autoIncrement : true });
            }
        };
        request.onsuccess = function(event) {
            resolve(event.target.result);
        };
    });
};

// Get number of requests currenlty cached, as a Promise
function getNbCachedRequests() {
    return new Promise(function(resolve) {
        openDB().then(function(db) {
            var transaction = db.transaction([dbCollection]);
            var countRequest = transaction.objectStore(dbCollection).count();
            countRequest.onsuccess = function() {
                resolve(countRequest.result);
            };
        });
    });
};

// Get first element cached, as a promise.
// Reject if cache is empty or on error
function getFirstCached() {
    return new Promise(function(resolve, reject) {
        openDB().then(function(db) {
            var transaction = db.transaction([dbCollection], 'readwrite');

            transaction.onerror = function(event) {
                console.log('firstCachedError');
                reject(event);
            };

            var store = transaction.objectStore(dbCollection);
            store.openCursor().onsuccess = function(event) {
                var cursor = event.target.result;
                if (cursor) {
                    var serialized = cursor.value;
                    store.delete(cursor.key);
                    resolve(serialized);
                } else {
                    reject('EMPTY_CACHE');
                }
            };
        });
    });
};

// Add serialized request to cache
function addCached(serialized) {
    return new Promise(function(resolve, reject) {
        openDB().then(function(db) {
            var transaction = db.transaction([dbCollection], 'readwrite');

            transaction.oncomplete = function() {
                resolve(true);
                sendCacheNb();
            };

            transaction.onerror = function(event) {
                reject(event);
            };

            transaction.objectStore(dbCollection).add(serialized);
        });
    });
};

// Deserialize request
function deserialize(serialized) {
    return Promise.resolve(new Request(serialized.url, serialized));
};

// Send cached requests, one by one
function sendCached(isSync) {
    return getNbCachedRequests()
        .then(function(nb) {
            if (!nb) {
                // Nothing cached, resolve it with 0 still in cache
                return Promise.resolve(0);
            }

            var lastSerialized;
            return getFirstCached()
                    .then(function(serialized) {
                        lastSerialized = serialized;
                        if (serialized) {
                            return deserialize(serialized);
                        } else {
                            return Promise.reject(false);
                        }
                    }).then(function(request) {
                        addClientLoading();
                        return fetch(request);
                    })
                    .then(function(response) {
                        if (response && response.ok) {
                            // Clean last serialized to be sure it's not handled by next catch
                            lastSerialized = false;
                            sendCacheNb();
                            removeClientLoading();
                            return sendCached(isSync);
                        } else {;
                            requestSync();
                            return Promise.reject(false);
                        }
                    })
                    .catch(function() {
                        removeClientLoading();
                        if (lastSerialized) {
                            // Something went wrong, readd the lastSerialized request into cache
                            addCached(lastSerialized);
                        }
                        if (isSync) {
                            // In sync mode, we want to reject the promis in order to sync later
                            sendAlert('REJECT Promise');
                            return Promise.reject(false);
                        } else {
                            requestSync();
                        }
                        return Promise.resolve(nb);
                    });
        });
};

// Serialize a request, adding a X-FROM-SW header
function serialize(request) {
    var headers = {};
    for (var entry of request.headers.entries()) {
        headers[entry[0]] = entry[1];
    }
    headers['<?php echo $headerSW ?>'] = true;

    var serialized = {
        url: request.url,
        headers: headers,
        method: request.method,
        mode: request.mode,
        credentials: request.credentials,
        cache: request.cache,
        redirect: request.redirect,
        referrer: request.referrer
    };

    if (request.method !== 'GET' && request.method !== 'HEAD') {
        return request.clone().text()
            .then(function(body) {
                serialized.body = body;
                return Promise.resolve(serialized);
            });
    }

    return Promise.resolve(serialized);
};

self.addEventListener('fetch', function(event) {
    if (event.request.method == 'POST') {
        // This is a form sending, handle it by adding it to cache and then try to send it asynchronously
        event.respondWith(new Response(
            JSON.stringify({
                caching: true
            }), {
                headers: { 'Content-Type': 'application/json' }
            }
        ));

        serialize(event.request)
            .then(function(serialized) {
                addCached(serialized)
                    .then(function() {
                        sendCached();
                    });
            });
    } else if (event.request.url.indexOf(nbCacheUrl) > -1) {
        // We requested the cache number, try to send it and then return the response
        
        if (event.request.url.indexOf('requestSend') > -1) {
            sendCached();
        }
        
        event.respondWith(getNbCachedRequests().then(function(nb) {
            return new Response(
                JSON.stringify({
                    nb: nb
                }), {
                    headers: { 'Content-Type': 'application/json' }
                }
            );
        }));
    } else {
        // Any other request, try cache first then network
        event.respondWith(
            caches.match(event.request)
                .then(function(response) {
                    // Cache hit - return response
                    if (response) {
                      return response;
                    }
                    return fetch(event.request);
                })
        );
    }
});

self.addEventListener('sync', function(event) {
    console.log('sync', event);
    if (event.tag == 'sendCached' || event.tag == 'test-tag-from-devtools') {
        console.log('sync requested');
        sendAlert('SYYYYNC start waiting');
        event.waitUntil(sendCached(true));
    }
});
