// Client MCP minimo per i server Novamira configurati in ~/.claude.json (stesso pacchetto e stesse credenziali
// del client). Serve quando un server appena aggiunto non è ancora caricato nella sessione.
//
// Uso: node tools/deploy/mcp-call.mjs <server> <file.json> [request_id]
//   file.json = [{ "ability": "novamira/execute-php", "parameters": { "code": "..." },
//                  "review": { "attention": 3, "summary": "Cosa cambia e su cosa" } }, ...]
// Le chiamate vengono eseguite in ordine. Con le approvazioni AI di Novamira attive, ogni chiamata che può
// modificare il sito porta review (attention, summary) e un call_id casuale; se una resta in attesa di
// approvazione lo script si ferma e stampa il link della pagina AI Activity. Le credenziali non vengono stampate.
import { spawn } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { randomBytes } from 'node:crypto';

const [name, file, requestId] = process.argv.slice(2);
const cfg = JSON.parse(readFileSync(process.env.USERPROFILE + '/.claude.json', 'utf8')).mcpServers[name];
if (!cfg) {
	console.error('Server non configurato: ' + name);
	process.exit(1);
}
const calls = JSON.parse(readFileSync(file, 'utf8'));
const child = spawn([cfg.command, ...cfg.args].join(' '), { env: { ...process.env, ...cfg.env }, shell: true });
let buf = '';
let err = '';
let next = 1;
const pending = new Map();
child.stderr.on('data', (d) => (err += d));
const send = (m) => child.stdin.write(JSON.stringify(m) + '\n');
const request = (method, params) =>
	new Promise((resolve) => {
		const id = next++;
		pending.set(id, resolve);
		send({ jsonrpc: '2.0', id, method, params });
	});
child.stdout.on('data', (d) => {
	buf += d;
	const lines = buf.split('\n');
	buf = lines.pop();
	for (const line of lines) {
		let msg;
		try {
			msg = JSON.parse(line);
		} catch {
			continue;
		}
		if (msg.id && pending.has(msg.id)) {
			pending.get(msg.id)(msg);
			pending.delete(msg.id);
		}
	}
});
const killer = setTimeout(() => {
	console.error('TIMEOUT\n' + err.slice(-2000));
	child.kill();
	process.exit(1);
}, 15 * 60 * 1000);

const init = await request('initialize', { protocolVersion: '2025-03-26', capabilities: {}, clientInfo: { name: 'ecm-deploy', version: '1.0' } });
if (init.error) {
	console.error('initialize: ' + JSON.stringify(init.error) + '\n' + err.slice(-2000));
	process.exit(1);
}
send({ jsonrpc: '2.0', method: 'notifications/initialized' });
for (const [i, c] of calls.entries()) {
	const args = { ability_name: c.ability, parameters: c.parameters || {} };
	if (c.review) args.review = { ...c.review, call_id: c.call_id || randomBytes(8).toString('hex') };
	if (requestId) args.request_id = requestId;
	const tool = c.tool || 'mcp-adapter-execute-ability';
	const res = await request('tools/call', { name: tool, arguments: tool === 'mcp-adapter-execute-ability' ? args : c.parameters || {} });
	const text = res.error ? JSON.stringify(res.error) : (res.result?.content || []).map((x) => x.text || '').join('\n');
	console.log(`--- [${i}] ${c.ability || tool}${c.label ? ' (' + c.label + ')' : ''}\n${text}`);
	if (/pending_confirmation/.test(text)) {
		console.log('\nIN ATTESA DI APPROVAZIONE: le chiamate successive non sono state inviate.');
		break;
	}
}
clearTimeout(killer);
child.kill();
process.exit(0);
