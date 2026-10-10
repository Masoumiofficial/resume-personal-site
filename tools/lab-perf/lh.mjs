// Lighthouse navigation runs (lab only): node lh.mjs <mobile|desktop> <runs> paths...
import puppeteer from 'puppeteer-core';
import lighthouse from 'lighthouse';
import desktopConfig from 'lighthouse/core/config/desktop-config.js';
import fs from 'fs';
const [mode, runs, ...paths] = process.argv.slice(2);
const browser = await puppeteer.launch({ executablePath: process.env.CHROME_PATH || '/tmp/chromium', headless: 'shell',
  args: ['--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu', '--remote-debugging-port=9222', '--font-render-hinting=none'] });
const out = [];
for (const p of paths) {
  const rows = [];
  for (let i = 0; i < +runs; i++) {
    const r = await lighthouse('http://localhost:8100' + p, { port: 9222, output: 'json', logLevel: 'error', onlyCategories: ['performance', 'accessibility', 'best-practices', 'seo'] }, mode === 'desktop' ? desktopConfig : undefined);
    const a = r.lhr.audits, c = r.lhr.categories;
    rows.push({ perf: Math.round(c.performance.score * 100), a11y: Math.round(c.accessibility.score * 100), bp: Math.round(c['best-practices'].score * 100), seo: Math.round(c.seo.score * 100),
      fcp: a['first-contentful-paint'].numericValue, lcp: a['largest-contentful-paint'].numericValue, cls: a['cumulative-layout-shift'].numericValue,
      tbt: a['total-blocking-time'].numericValue, si: a['speed-index'].numericValue, bytes: a['total-byte-weight'].numericValue, reqs: a['network-requests'].details.items.length,
      lcpEl: a['largest-contentful-paint-element']?.details?.items?.[0]?.items?.[0]?.node?.snippet ?? '' ,
      fails: Object.values(r.lhr.audits).filter(x => x.score !== null && x.score < 0.9 && x.scoreDisplayMode !== 'informative' && x.scoreDisplayMode !== 'manual' && x.scoreDisplayMode !== 'notApplicable').map(x => x.id) });
    if (i === 0) fs.writeFileSync(`./lhr-${mode}${p.replace(/\//g, '_')}.json`, JSON.stringify(r.lhr));
  }
  rows.sort((x, y) => x.lcp - y.lcp); const m = rows[Math.floor(rows.length / 2)];   // median by LCP
  out.push({ path: p, ...m });
  console.log(`${p.padEnd(44)} perf ${m.perf} a11y ${m.a11y} bp ${m.bp} seo ${m.seo} | FCP ${Math.round(m.fcp)} LCP ${Math.round(m.lcp)} CLS ${m.cls.toFixed(3)} TBT ${Math.round(m.tbt)} SI ${Math.round(m.si)} | ${Math.round(m.bytes/1024)}KB ${m.reqs} req`);
  if (m.fails.length) console.log('   <0.9:', m.fails.join(', '));
}
fs.writeFileSync(`./summary-${mode}.json`, JSON.stringify(out, null, 1));
await browser.close();
