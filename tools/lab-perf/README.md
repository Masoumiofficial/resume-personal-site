# Lab performance measurement (development only)

Lab numbers are **not** real-user data (field data needs CrUX/RUM after launch).
These scripts produced the Phase 5 numbers in `docs/phase-5/performance.md`.

1. Snapshot rendered pages from a running copy of the site into `./site/<path>/index.html`
   with the Host header the static server will use, e.g.
   `curl -H "Host: localhost:8100" -o site/blog/index.html http://localhost:8000/blog/`
2. `node static.mjs` — serves the snapshots + `public/` assets on 127.0.0.1:8100 with gzip and
   long-lived asset caching (approximates the host's compression and `.htaccess` expiry rules;
   server response time is deliberately ~0 so only the front end is measured).
3. `CHROME_PATH=/path/to/chromium node lh.mjs mobile 3 / /blog/ …` — Lighthouse navigation runs
   (median of N by LCP); `desktop` uses Lighthouse's desktop preset.
4. `CHROME_PATH=… node inp.mjs` — Lighthouse timespan around scripted interactions (theme toggle,
   mobile menu, typing in the contact form) for INP.

`npm install` here only; nothing in this folder is needed in production.
