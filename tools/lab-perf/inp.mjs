// INP in the lab: Lighthouse timespan around scripted interactions (mobile emulation, 4x CPU throttling).
import puppeteer from 'puppeteer-core';
import { startFlow } from 'lighthouse';
const browser = await puppeteer.launch({ executablePath: process.env.CHROME_PATH || '/tmp/chromium', headless: 'shell', args: ['--no-sandbox', '--disable-dev-shm-usage', '--disable-gpu'] });
const scenarios = {
  '/': async (page) => { for (let i = 0; i < 3; i++) { await page.click('[data-theme-toggle]'); await sleep(300); } await page.click('[data-nav-toggle]'); await sleep(300); await page.click('[data-nav-toggle]'); await sleep(300); },
  '/learn-programming-for-beginners/': async (page) => { await page.click('[data-theme-toggle]'); await sleep(300); await page.click('[data-nav-toggle]'); await sleep(300); await page.click('[data-nav-toggle]'); await sleep(300); },
  '/contact/': async (page) => { await page.click('input[name="name"]'); await page.keyboard.type('سجاد معصومی', { delay: 40 }); await page.click('input[name="email"]'); await page.keyboard.type('test@example.com', { delay: 40 }); await page.click('textarea[name="message"]'); await page.keyboard.type('سلام، این یک پیام آزمایشی است.', { delay: 40 }); await sleep(300); },
};
const sleep = (ms) => new Promise(r => setTimeout(r, ms));
for (const [path, act] of Object.entries(scenarios)) {
  const vals = [];
  for (let run = 0; run < 3; run++) {
    const page = await browser.newPage();
    const flow = await startFlow(page, { config: { extends: 'lighthouse:default', settings: { onlyCategories: ['performance'] } } });
    await page.goto('http://localhost:8100' + path, { waitUntil: 'networkidle0' });
    await flow.startTimespan();
    await act(page);
    await flow.endTimespan();
    const r = await flow.createFlowResult();
    const a = r.steps[0].lhr.audits;
    vals.push({ inp: a['interaction-to-next-paint']?.numericValue ?? null, cls: a['cumulative-layout-shift']?.numericValue ?? null, tbt: a['total-blocking-time']?.numericValue ?? null });
    await page.close();
  }
  vals.sort((x, y) => (x.inp ?? 0) - (y.inp ?? 0));
  console.log(path.padEnd(36), 'INP runs (ms):', vals.map(v => v.inp === null ? 'n/a' : Math.round(v.inp)).join(', '), '| CLS during interactions:', vals.map(v => (v.cls ?? 0).toFixed(3)).join(', '));
}
await browser.close();
