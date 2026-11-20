// Step 7: full-page desktop screenshots (1440 wide) of the original site -> docs/screenshot/original/
import fs from 'node:fs';
import path from 'node:path';
import { BASE, ROOT, launch, jsContext, gotoIdle, scrollAll, rejectCookies, ensureDir, rel } from './lib.mjs';

const OUT = path.join(ROOT, 'docs', 'screenshot', 'original');
ensureDir(OUT);
const targets = [
  ['home', BASE + '/'],
  ['catalogo-pagina-1', BASE + '/collections'],
  ['corso-lungo-metabolismo-cane-cavallo', BASE + '/courses/METABOLISMO,%20NUTRIZIONE%20E%20PERFORMANCE%20NEL%20CANE%20SPORTIVO%20E%20NEL%20CAVALLO%20ATLETA'],
  ['corso-breve-apparato-digerente', BASE + '/courses/apparato-digerente'],
  ['chi-siamo', BASE + '/pages/chi-siamo'],
  ['docenti', BASE + '/pages/docenti'],
  ['contatti', BASE + '/pages/contatti'],
  ['testimonianze-sales', 'https://sales.corsidiformazionecm.it/testimonianze'],
];
const browser = await launch();
const ctx = await jsContext(browser, { viewport: { width: 1440, height: 900 }, deviceScaleFactor: 1 });
const results = [];
for (const [name, url] of targets) {
  const p = await ctx.newPage();
  await gotoIdle(p, url);
  await rejectCookies(p);
  await scrollAll(p);
  await p.waitForTimeout(1500);
  // pause CSS marquee/carousel animations for a stable capture
  await p.addStyleTag({ content: '*{animation-play-state:paused!important;transition:none!important}' }).catch(() => {});
  const png = path.join(OUT, `${name}.png`);
  await p.screenshot({ path: png, fullPage: true });
  let file = png;
  const size = fs.statSync(png).size;
  if (size > 8 * 1024 * 1024) {
    const jpg = path.join(OUT, `${name}.jpg`);
    await p.screenshot({ path: jpg, fullPage: true, type: 'jpeg', quality: 70 });
    fs.unlinkSync(png);
    file = jpg;
  }
  const h = await p.evaluate(() => document.documentElement.scrollHeight);
  results.push({ name, url, file: rel(file), bytes: fs.statSync(file).size, page_height: h });
  console.log(name, rel(file), fs.statSync(file).size, h);
  await p.close();
}
await browser.close();
fs.writeFileSync(path.join(OUT, 'index.json'), JSON.stringify({ taken_at: new Date().toISOString(), viewport_width: 1440, shots: results }, null, 2) + '\n');
