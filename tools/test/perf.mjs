// Lighthouse (mobile, throttling simulato) sulle pagine chiave, con Chromium avviato da Playwright.
import { chromium } from 'playwright';
import lighthouse from 'lighthouse';
const BASE = process.env.ECM_BASE || 'https://soothing-khaki-okapi-74.hostingersite.com';
const paths = process.argv.slice(2).length ? process.argv.slice(2) : ['/', '/corsi/', '/courses/microbiota/', '/docenti/'];
const browser = await chromium.launch({ args: ['--remote-debugging-port=9229'] });
for (const p of paths) {
	const r = await lighthouse(`${BASE}${p}${p.includes('?') ? '&' : '?'}lh=${Date.now()}`, { port: 9229, output: 'json', logLevel: 'error', onlyCategories: ['performance', 'accessibility', 'best-practices', 'seo'] });
	const c = r.lhr.categories, a = r.lhr.audits;
	console.log(p.padEnd(26), Object.entries(c).map(([k, v]) => `${k.slice(0, 4)} ${Math.round(v.score * 100)}`).join(' | '), '| LCP', a['largest-contentful-paint'].displayValue, '| CLS', a['cumulative-layout-shift'].displayValue, '| TBT', a['total-blocking-time'].displayValue);
	const opp = Object.values(a).filter((x) => x.details && x.details.type === 'opportunity' && x.score !== null && x.score < 0.9).map((x) => `${x.id} ${x.displayValue || ''}`);
	if (opp.length) console.log('   opportunità:', opp.join('; '));
	const fails = Object.values(a).filter((x) => x.score === 0 && ['seo', 'best'].some(() => true) && !x.details?.type?.includes('opportunity')).slice(0, 6).map((x) => x.id);
	if (fails.length) console.log('   audit a 0:', fails.join(', '));
}
await browser.close();
