// Screenshot di verifica: node shoot.mjs <outDir> <path1> [path2 ...] [--vp=desktop,tablet,mobile] [--full] [--scroll=Y1,Y2] [--reduced]
// Base URL da env ECM_BASE (default: sito di staging).
import { chromium } from 'playwright';
import { mkdirSync } from 'node:fs';
import { join } from 'node:path';

const BASE = process.env.ECM_BASE || 'https://soothing-khaki-okapi-74.hostingersite.com';
const args = process.argv.slice(2);
const out = args.shift();
const flags = Object.fromEntries(args.filter((a) => a.startsWith('--')).map((a) => { const [k, v] = a.slice(2).split('='); return [k, v ?? true]; }));
const paths = args.filter((a) => !a.startsWith('--'));
const VPS = { desktop: { width: 1440, height: 900 }, tablet: { width: 820, height: 1180 }, mobile: { width: 390, height: 844 } };
const vps = (flags.vp ? String(flags.vp).split(',') : ['desktop']).map((k) => [k, VPS[k]]);
const scrolls = flags.scroll ? String(flags.scroll).split(',').map(Number) : null;
mkdirSync(out, { recursive: true });

const browser = await chromium.launch();
const errors = [];
for (const [name, vp] of vps) {
	const ctx = await browser.newContext({ viewport: vp, deviceScaleFactor: name === 'mobile' ? 2 : 1, reducedMotion: flags.reduced ? 'reduce' : 'no-preference', isMobile: name === 'mobile', hasTouch: name !== 'desktop' });
	await ctx.addCookies([{ name: 'ecm_consent', value: encodeURIComponent(JSON.stringify({ necessary: true, statistics: false, marketing: false, t: Date.now(), v: 1 })), url: BASE }]);
	const page = await ctx.newPage();
	page.on('console', (m) => m.type() === 'error' && errors.push(`[${name}] ${m.text()}`));
	page.on('pageerror', (e) => errors.push(`[${name}] pageerror ${e.message}`));
	for (const p of paths) {
		const slug = (p.replace(/^\/|\/$/g, '').replace(/[^a-z0-9]+/gi, '-') || 'home').slice(0, 60);
		await page.goto(BASE + p, { waitUntil: 'networkidle', timeout: 60000 });
		await page.waitForTimeout(2200);
		if (scrolls) {
			for (const y of scrolls) {
				await page.evaluate((yy) => window.scrollTo(0, yy), y);
				await page.waitForTimeout(1600);
				await page.screenshot({ path: join(out, `${slug}-${name}-y${y}.png`) });
			}
		} else if (flags.full) {
			// Scorre tutta la pagina per attivare i reveal, poi screenshot completo.
			const h = await page.evaluate(() => document.documentElement.scrollHeight);
			for (let y = 0; y < h; y += 500) {
				await page.evaluate((yy) => window.scrollTo(0, yy), y);
				await page.waitForTimeout(120);
			}
			await page.waitForTimeout(1200);
			await page.evaluate(() => window.scrollTo(0, 0));
			await page.waitForTimeout(600);
			await page.screenshot({ path: join(out, `${slug}-${name}-full.jpg`), fullPage: true, type: 'jpeg', quality: 72 });
		} else {
			await page.screenshot({ path: join(out, `${slug}-${name}.png`) });
		}
	}
	await ctx.close();
}
await browser.close();
console.log(errors.length ? errors.join('\n') : 'no console errors');
