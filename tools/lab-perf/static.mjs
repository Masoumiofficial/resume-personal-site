// Static snapshot server for lab tests: HTML from ./site, assets from the app's public/.
import http from 'http'; import fs from 'fs'; import path from 'path'; import zlib from 'zlib';
const PUB = process.env.PUBLIC_DIR || new URL('../../public', import.meta.url).pathname, SITE = process.env.SNAPSHOT_DIR || new URL('./site', import.meta.url).pathname;
const types = { '.html': 'text/html; charset=utf-8', '.css': 'text/css', '.js': 'text/javascript', '.woff2': 'font/woff2', '.svg': 'image/svg+xml', '.webp': 'image/webp', '.png': 'image/png', '.jpg': 'image/jpeg', '.ico': 'image/x-icon', '.xml': 'application/xml' };
const gzip = process.env.NOGZIP ? false : true;
http.createServer((req, res) => {
  const p = decodeURIComponent(new URL(req.url, 'http://x').pathname);
  let file = path.join(PUB, p);
  if (!(fs.existsSync(file) && fs.statSync(file).isFile())) file = path.join(SITE, p, 'index.html');
  if (!fs.existsSync(file)) { res.writeHead(404); return res.end('404'); }
  const ext = path.extname(file); let body = fs.readFileSync(file);
  const h = { 'Content-Type': types[ext] || 'application/octet-stream', 'Cache-Control': ext === '.html' ? 'no-cache' : 'public, max-age=31536000' };
  if (gzip && /html|css|javascript|svg|xml/.test(h['Content-Type']) && /gzip/.test(req.headers['accept-encoding'] || '')) { body = zlib.gzipSync(body); h['Content-Encoding'] = 'gzip'; }
  h['Content-Length'] = body.length; res.writeHead(200, h); res.end(body);
}).listen(8100, '127.0.0.1', () => console.log('static on 8100'));
