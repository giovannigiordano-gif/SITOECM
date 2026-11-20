// Genera le immagini art-directed con MuAPI (REST, nessun CLI necessario).
// La chiave arriva SOLO dall'ambiente: MUAPI_API_KEY. Non scriverla mai in file del progetto.
// Uso: MUAPI_API_KEY=... node tools/images/muapi-generate.mjs [chiave ...]
// Prompt in tools/images/muapi-prompts.json; immagini scaricate subito in tools/images/src-ai/ (gli URL scadono).
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const KEY = process.env.MUAPI_API_KEY;
if (!KEY) {
	console.error('Manca MUAPI_API_KEY nell\'ambiente.');
	process.exit(1);
}
const API = 'https://api.muapi.ai/api/v1';
const H = { 'x-api-key': KEY, 'Content-Type': 'application/json' };
const cfg = JSON.parse(readFileSync(join(here, 'muapi-prompts.json'), 'utf8'));
const only = process.argv.slice(2);
const jobs = Object.entries(cfg.images).filter(([k]) => !only.length || only.includes(k));
const OUT = join(here, 'src-ai');
mkdirSync(OUT, { recursive: true });

const payload = (img) => ({ prompt: `${img.prompt} ${cfg.style}`, aspect_ratio: img.aspect_ratio || '16:9', resolution: cfg.resolution || '4k' });

// 1. Preventivo
let total = 0;
for (const [k, img] of jobs) {
	const r = await fetch(`${API}/models/${cfg.model}/estimate-cost`, { method: 'POST', headers: H, body: JSON.stringify(payload(img)) });
	const j = await r.json();
	total += Number(j.cost || 0);
}
const bal = await (await fetch(`${API}/account/balance`, { headers: H })).json();
console.log(`preventivo: ${jobs.length} immagini, ${total.toFixed(3)} USD (saldo ${bal.balance} USD)`);
if (total > 1) {
	console.error('Oltre 1 USD: serve conferma esplicita prima di generare.');
	process.exit(2);
}
if (total > Number(bal.balance)) {
	console.error('Saldo insufficiente.');
	process.exit(3);
}

// 2. Invio (una immagine per prompt) e attesa
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const results = await Promise.all(
	jobs.map(async ([k, img]) => {
		const sub = await (await fetch(`${API}/${cfg.model}`, { method: 'POST', headers: H, body: JSON.stringify(payload(img)) })).json();
		const id = sub.request_id || sub.id;
		if (!id) return { k, error: JSON.stringify(sub).slice(0, 200) };
		const deadline = Date.now() + 8 * 60 * 1000;
		while (Date.now() < deadline) {
			await sleep(4000);
			const res = await (await fetch(`${API}/predictions/${id}/result`, { headers: H })).json();
			if (res.status === 'completed') {
				const o = (res.outputs || [])[0];
				const url = typeof o === 'string' ? o : o && (o.url || o.image_url);
				if (!url) return { k, error: 'nessun output' };
				const bin = Buffer.from(await (await fetch(url)).arrayBuffer());
				const ext = bin[0] === 0x89 ? 'png' : bin[0] === 0xff ? 'jpg' : 'webp';
				writeFileSync(join(OUT, `${k}.${ext}`), bin);
				return { k, file: `${k}.${ext}`, bytes: bin.length };
			}
			if (res.status === 'failed') return { k, error: res.error || 'failed' };
		}
		return { k, error: 'timeout', id };
	})
);
results.forEach((r) => console.log(r.k, r.error ? `ERRORE ${r.error}` : `${r.file} ${(r.bytes / 1024).toFixed(0)} KB`));
const after = await (await fetch(`${API}/account/balance`, { headers: H })).json();
console.log(`modello ${cfg.model}, saldo dopo: ${after.balance} USD (prima ${bal.balance})`);
