const CACHE_NAME = 'babelfree-v8';
const PRECACHE_URLS = [
  '/',
  '/services',
  '/elviajedeljaguar',
  '/juegos',
  '/contact',
  '/blog',
  '/css/footer.css',
  '/css/blog.css',
  '/luxury.css',
  '/assets/tower-logo.png',
  '/assets/logo.png',
  '/img/jaguar-hero-full.jpg',
  '/play',
  '/storymap',
  '/cuaderno',
  '/offline.html',
  '/js/jaguar-api.js',
  '/js/yaguara-engine.js',
  '/js/evidence-engine.js',
  '/js/adaptivity-engine.js',
  '/js/destination-router.js',
  '/js/practice-engine.js',
  '/js/personal-lexicon.js',
  '/js/riddle-quest.js',
  '/js/quest-journal.js',
  '/js/audio-manager.js',
  '/js/feedback-widget.js',
  '/css/yaguara-game.css',
  '/ontology/ontology-api.js',
  '/content/busqueda-riddles.json'
];

// Install: precache key pages and assets
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(PRECACHE_URLS))
      .then(() => self.skipWaiting())
  );
});

// Activate: clean old caches
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(keys =>
      Promise.all(keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k)))
    ).then(() => self.clients.claim())
  );
});

// Fetch: network-first for HTML, cache-first for static assets
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Only handle same-origin requests
  if (url.origin !== location.origin) return;

  // HTML pages: network-first with cache fallback
  if (event.request.mode === 'navigate' || event.request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
          return response;
        })
        .catch(() => caches.match('/offline.html'))
    );
    return;
  }

  // Static assets: cache-first with network fallback
  if (/\.(css|js|png|jpg|jpeg|svg|woff2?|mp3)$/i.test(url.pathname)) {
    event.respondWith(
      caches.match(event.request)
        .then(cached => {
          if (cached) return cached;
          return fetch(event.request).then(response => {
            const clone = response.clone();
            caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
            return response;
          });
        })
    );
    return;
  }

  // JSON content/ontology: network-first with cache fallback (offline play)
  if (/\.(json)$/i.test(url.pathname) && (/\/content\//.test(url.pathname) || /\/ontology\//.test(url.pathname))) {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          const clone = response.clone();
          caches.open(CACHE_NAME).then(cache => cache.put(event.request, clone));
          return response;
        })
        .catch(() => caches.match(event.request))
    );
    return;
  }
});
