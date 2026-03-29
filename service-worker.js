const CACHE_NAME = 'babelfree-v10';

// Only cache ASSETS (fast loading) — NOT game content (requires connection)
const PRECACHE_URLS = [
  '/luxury.css',
  '/assets/tower-logo.png',
  '/assets/logo.png',
  '/img/jaguar-hero-full.jpg',
  '/js/jaguar-api.js',
  '/js/yaguara-engine.js',
  '/js/evidence-engine.js',
  '/js/adaptivity-engine.js',
  '/js/destination-router.js',
  '/js/practice-engine.js',
  '/js/personal-lexicon.js',
  '/js/template-generator.js',
  '/js/riddle-quest.js',
  '/js/quest-journal.js',
  '/js/composition-builder.js',
  '/js/aventura-tab.js',
  '/js/audio-manager.js',
  '/js/tts-fallback.js',
  '/js/feedback-widget.js',
  '/js/soul.js',
  '/js/ad-manager.js',
  '/css/yaguara-game.css',
  '/css/placeholder-illustrations.css',
  '/ontology/ontology-api.js',
  '/offline.html'
];

// Install: precache assets only
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

// Fetch strategy:
// - Static assets (CSS/JS/images): cache-first (fast loading)
// - HTML pages: network-first, offline.html fallback
// - Game content JSON: network-ONLY (requires connection for ads + tracking)
// - API calls: network-only (always need server)
self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);

  // Only handle same-origin
  if (url.origin !== location.origin) return;

  // API calls: always network
  if (url.pathname.startsWith('/api/')) return;

  // Game content JSON: NETWORK ONLY — no offline play
  // This ensures students must be online (ads load, progress syncs)
  if (/\/content\/.*\.json$/i.test(url.pathname)) {
    event.respondWith(
      fetch(event.request).catch(() => {
        return new Response(JSON.stringify({
          error: 'offline',
          message: 'Necesitas conexión a internet para jugar.'
        }), {
          status: 503,
          headers: { 'Content-Type': 'application/json' }
        });
      })
    );
    return;
  }

  // HTML pages: network-first with offline.html fallback
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

  // Static assets: cache-first (CSS, JS, images, fonts)
  if (/\.(css|js|png|jpg|jpeg|svg|webp|woff2?|mp3|ico)$/i.test(url.pathname)) {
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
});
