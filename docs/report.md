# Report — Restyling corsidiformazionecm.it su WordPress

Data: 20 novembre 2026 · Sito di staging: **https://soothing-khaki-okapi-74.hostingersite.com** · Repository: GitHub `giovannigiordano-gif/SITOECM`, branch `restyling-wordpress`.

> **Aggiornamento (seconda iterazione, stesso giorno):** home e pagine rifatte in chiave scenografica, hero con sfondo su tutte le pagine, testimonianze ricostruite come sul sito originale, immagini 3D realizzate con Blender. Dettagli in §12; le righe della tabella qui sotto sono aggiornate.

## 1. In sintesi

| Area | Stato |
|---|---|
| Tema custom `ecm` "La Lente" (a codice, niente page builder) | Fatto, attivo sullo staging |
| Plugin `ecm-core` (corsi, docenti, testimonianze, opzioni globali, redirect, SEO, form, importazione) | Fatto, attivo |
| Scraping completo del sito attuale + inventario | Fatto (`docs/inventario.md`) |
| Migrazione ripetibile e importazione | Fatto: 27 corsi, 18 docenti, 23 testimonianze, 128 file, 5 pagine, menu, opzioni, 8 redirect |
| Verifica dei 27 corsi rispetto all'originale | **27/27** (`docs/verifica-corsi.md`) |
| Accessibilità (axe, WCAG 2.2 AA) | **0 violazioni** su 9 pagine × desktop e mobile (anche dopo la seconda iterazione) |
| Lighthouse mobile | Home 94, Testimonianze 96, Chi siamo 96, Corsi 96, Docenti 95, scheda corso 92; Accessibilità 100, Best practices 100, SEO 100, CLS 0 |
| Immagini | Generazione AI ancora **bloccata** (Higgsfield in periodo di grazia, nessuna chiave MuAPI): al loro posto 9 render 3D fatti con Blender e 3 immagini ricavate dalle foto reali del sito (vedi §12) |
| Hero con sfondo su tutte le pagine, testimonianze come l'originale | Fatto (§12) |

## 2. Ambiente verificato (Novamira)

- Novamira 1.12.16 installato, attivo e raggiungibile; lettura e scrittura testate (file scritto, riletto, cancellato).
- WordPress 7.1.5, PHP 8.3.37, MariaDB 11.8, LiteSpeed, locale it_IT, Imagick con AVIF/WebP, ZipArchive.
- Installazione nuova creata il 20/11: tema iniziale Twenty Twenty-Five; plugin Hostinger AI, Hostinger Hub, Hostinger Reach, Hostinger Tools, Novamira. Nessun page builder né plugin di campi.
- Il profilo CLI Novamira disponibile puntava a un altro sito (Duiliu's Camiceria) e il secondo (Formula Speed) non era autenticato: ho usato la connessione MCP Novamira già autorizzata per questo sito. Gli altri siti collegati (altri clienti) non sono stati toccati.

## 3. Cosa è stato fatto

```
docs/            design-system.md, PRODUCT.md, inventario.md, contract-ecm-core.md, import-bundle-format.md,
                 verifica-corsi.md/.json, report.md, screenshot/ (original, desktop, tablet, mobile, admin)
wp-content/themes/ecm/        tema (template PHP, CSS a token in assets/css/src, motion in assets/js/app.js)
wp-content/plugins/ecm-core/  plugin dati
tools/scrape/    scraping Playwright del sito attuale (npm run all)
tools/migrate/   build-bundle.mjs (+ overrides.json): dati estratti → bundle per l'importer
tools/build/     build.mjs (font, GSAP/Lenis, icone, CSS), logo.mjs, images.mjs (AVIF/WebP)
tools/test/      shoot.mjs (screenshot), verify-courses.mjs, forms-filters.mjs, a11y.mjs, perf.mjs
tools/deploy/    pack.sh + README (deploy via Novamira) · tools/images/ prompt e config immagini
```

**Tema** (`ecm`): template per home, catalogo `/corsi/` (anche pagine area `/area/{slug}/`), scheda corso unica, Docenti, Chi siamo, Testimonianze, Contatti, pagina generica, 404. Font Mona Sans, GSAP 3.15 (ScrollTrigger, SplitText, Flip) e Lenis serviti dal tema; icone Phosphor in sprite inline; nessuna risorsa esterna.

**Plugin** (`ecm-core`): CPT `corso` (URL `/courses/{slug}`, archivio `/corsi/`), `docente`, `testimonianza`, più `Richieste contatto` e `Iscritti newsletter` privati; tassonomie `area_tematica` e `destinatario`; meta box con ripetitori (patrocini, testimonianze, FAQ), brochure PDF sostituibile con anteprima, stato, crediti, link iscrizione, "in evidenza", ordine manuale, SEO; pagina **Impostazioni ECM** a schede con tutte le opzioni globali (nessun URL, email o numero scritto nei template); redirect 301; meta, Open Graph, schema.org `Organization`, `Course`, `BreadcrumbList`; sitemap core; form REST (contatti e newsletter) con honeypot, time trap, rate limit; importer ripetibile con pagina admin e barra di avanzamento.

## 4. Decisioni di design

Documento completo: `docs/design-system.md`.

- **Concept "La Lente".** Il cerchio rosso della "e" del logo diventa il dispositivo di tutto il sito: il piatto (nutrizione), la capsula di Petri (microbiota), il campo del microscopio (metodo). Le immagini si aprono come una lente, le transizioni di pagina sono un cerchio che si espande dal punto del click, i bottoni si riempiono con un disco, gli avatar dei docenti senza foto sono anelli tipografici.
- **Palette ematossilina-eosina.** Viola inchiostro `#1c1937` + il cremisi esatto del logo `#be1522` + porcellana fredda `#f7f7fb`: i colori della colorazione istologica che ogni medico, biologo e veterinario riconosce, e che esistono anche nel cibo (barbabietola, melograno, cavolo rosso). Scartati di proposito: "sanità = bianco e teal", "e-learning = colori giocosi", crema + serif editoriale.
- **Una sola famiglia tipografica**, Mona Sans variabile: titoli larghi (asse `wdth` 120%) che "respirano" entrando da condensati; corpo a 17-19 px, interlinea 1,62, misura massima 66 caratteri. I titoli originali dei corsi sono tutti in maiuscolo: li ho lasciati come sono, con corpo e larghezza calibrati per lunghezza.
- **Tema chiaro** (lettura di testi lunghi in studio e in mobilità) con fasce inchiostro piene per i momenti istituzionali (patrocini, newsletter, footer). Niente dark mode automatica: loghi dei patrocini e copertine reali sono pensati su fondo chiaro.
- **Motion** (personalità "premium", curve esponenziali, nessun rimbalzo): home con la lente che si ingrandisce fino a diventare il palcoscenico delle 5 aree tematiche (storytelling a scroll, versione dedicata su mobile); reveal tipografici; contatori sui numeri reali; un solo nastro di loghi; filtri del catalogo con transizioni Flip senza ricaricare; copertina del corso che si apre dalla lente con leggero parallasse; barra CTA che compare quando i bottoni della hero escono; transizioni di pagina con View Transitions API; smooth scroll Lenis solo con mouse. Con `prefers-reduced-motion` tutto diventa statico e immediato.
- **Scheda corso**: hero con copertina in cornice porcellana e anello cremisi, badge di stato e area, crediti; descrizione editoriale; colonna laterale sticky con anteprima reale della brochure (prima pagina del PDF), peso e pagine; "Per chi è adatto" con le professioni; fascia patrocini su inchiostro con loghi in scala di grigi e colore all'hover, ad altezza ottica uniforme; testimonianze; FAQ ad accordion; corsi correlati per area; barra sticky WhatsApp + Brochure su desktop e mobile.

### Revisione critica finale (impeccable + taste) e correzioni fatte
- Bug del titolo nelle card (markup visibile) e indicatore dei filtri fuori posto: corretti.
- Disco della CTA finale che lasciava il testo bianco su fondo chiaro a inizio scroll: rifatto come velatura che si apre su un fondo cremisi reale (contrasto sempre verificabile).
- Numeri di "Chi siamo" sovrapposti, email lunghe spezzate male, riga orfana nei patrocini, nome d'area che usciva dal pannello: corretti.
- Etichette CTA uniformate per intenzione ("Scrivici su WhatsApp", "Esplora i corsi"); nessun trattino lungo nei testi dell'interfaccia; affermazione "corsi accreditati" sostituita con "corsi ECM" (non tutti i corsi hanno crediti, uno è per privati).
- Virgolette e trattini "tipografici" automatici di WordPress disattivati, per avere testi identici all'originale.
- **Cosa resta sotto il livello voluto:** le immagini art-directed. Senza di esse la home e le pagine Chi siamo/Docenti mostrano un segnaposto grafico curato ma non la fotografia prevista. È l'unico punto che separa il sito dal risultato finale (vedi §6).

## 5. Architettura dei contenuti e migrazione

- **Aree tematiche (5)** decise dopo aver letto tutte le descrizioni: Nutrizione umana (15 corsi), Nutrizione animale (5), Fitoterapia (3), Microbiota e genomica (4), Comunicazione sanitaria (2). Microbiota e il Bundle cane/gatto sono in due aree.
- **Destinatari (9)**: Medici, Biologi, Dietisti, Farmacisti, Veterinari, Infermieri, Psicologi, Tecnologi alimentari, Privati, assegnati dal testo "Per chi è adatto" di ogni corso. Il corso "privato" cane e gatto ha solo "Privati" (vedi dubbi).
- **Stato**: "Pre-ordine" sul corso Metabolismo (unico segnale sul sito), "Disponibile" sugli altri. **Crediti**: 20 (Alimentazione nello sportivo) e 30 (Nutrizione clinica), gli unici indicati sul sito.
- **In evidenza in home**: Metabolismo cane/cavallo, Bundle microbiota cane/gatto, Master Nutrizione e dietetica applicata, Microbiota, Fitoterapia sistemica (modificabile da admin).
- **WhatsApp**: testo generato dal titolo (`Salve, vorrei avere maggiori informazioni sul corso "TITOLO"`). Sul sito originale 9 corsi avevano un testo sbagliato (8 "NUTRIZIONE E DIETETICA CANE E GATTO", 1 "NUTRIGENOMICA"): ora sono tutti corretti.
- **FAQ**: "Per chi è adatto" diventa una sezione propria; l'email di contatto e il bonifico sono FAQ comuni nelle opzioni. Il bonifico ha 6 risposte diverse sul sito: la più frequente è quella comune, le altre restano sul singolo corso e la sostituiscono.
- **Brochure**: tutte e 27 scaricate da Drive, caricate nella Media Library e servite dal sito (stesso peso in byte dell'originale), con anteprima della prima pagina.
- **Termini e Privacy**: i due PDF (Termini su CloudFront, Cookie & Privacy su Drive) sono ora nella Media Library e collegati dal footer e dai form.
- **Meta description**: quelle di Thinkific erano spesso copiate da altri corsi; non le ho importate, il plugin le genera dal testo. Unica eccezione scritta a mano: Nutrigenomica (descrizione vuota), con dati presi dalla brochure.
- **Script ripetibile**: `node tools/migrate/build-bundle.mjs` rigenera `dist/ecm-import/` dai dati estratti; caricato in `wp-content/uploads/ecm-import/` si importa da **Impostazioni ECM › Importazione** (o step per step, vedi `tools/deploy/README.md`). Ogni rilancio aggiorna senza duplicare.

### Refusi evidenti corretti (ortografia/battitura, mai riscritture)
| Dove | Prima | Dopo |
|---|---|---|
| Alimentazione nello sportivo | non posso essere viste | non possono essere viste |
| Alimentazione vegana e vegetariana | discussine · nel trattamento della patologie · verranno presi in considerazione le modalità | discussione · delle patologie · verranno prese in considerazione |
| Nutrizione e donna | dall’ azione · donna,ma | dall’azione · donna, ma |
| Curare l’eccesso ponderale | responsabiltà | responsabilità |
| Bundle microbiota cane/gatto (titolo) | NELL'ALIMENTAZIONEDEL | NELL'ALIMENTAZIONE DEL |
| Counseling nutrizionale | esplicativo- affermativo | esplicativo-affermativo |
| Nutrizione clinica | mirata al biotipologia | mirata alla biotipologia |
| L’invecchiamento in salute | adeguati strategie | adeguate strategie |
| Ritmi circadiani | de sincronizzato · E’ ormai noto | desincronizzato · È ormai noto |
| FAQ (vari corsi) | É possibile · si dovra' inviare · Sì, La ricevuta · nelle su dette categorie | È possibile · si dovrà inviare · Sì, la ricevuta · nelle suddette categorie |
| Chi siamo | dell’ alta · del compartimenti medico · Con la collaborazioni | dell’alta · del comparto medico · Con la collaborazione |
| Docenti | Dottor/Dottoressa | Prof./Prof.ssa dove la pagina vendite lo indica, altrimenti Dott./Dott.ssa |

Normalizzati anche i caratteri incollati da PDF (legature "ﬁ", separatori di riga invisibili, spazi a larghezza zero). Non corretti perché stilistici o di contenuto: "obbiettivo", "così detto", "ma bensì", alcune concordanze, "presente nel suprachiasmatico" (probabile parola mancante). Elenco completo nell'inventario §9.

## 6. Immagini (Higgsfield)

> Superato dalla seconda iterazione: i segnaposto sono stati sostituiti da render 3D e da immagini ricavate dalle foto reali (§12). Il testo sotto descrive la situazione della prima consegna.

- **Nessuna immagine generata.** Dal primo tentativo, Higgsfield ha risposto a ogni richiesta, su ogni modello, *"You've reached the daily generation limit for your grace period"* (l'account risulta in periodo di grazia, con 1185 crediti visibili). Ho riprovato più volte durante la sessione, senza esito, e ti ho mandato una notifica.
- È comparsa una skill MuAPI per la generazione a basso costo, ma non c'è alcuna chiave `MUAPI_API_KEY` configurata: non era utilizzabile.
- Il tema mostra al posto delle foto un **segnaposto grafico coerente** (composizione circolare tipo capsula di Petri con i colori del marchio), mai un riquadro vuoto; le copertine dei corsi e i loghi dei patrocini sono quelli reali.
- **Pronto per il completamento**: direzione visiva "specimen still life" e 9 prompt in `tools/images/README.md` (5 lenti per le aree tematiche usate in hero e storytelling, Chi siamo, Docenti, texture H&E per la newsletter, piatto vuoto per 404 e stato vuoto). Una volta generate: salvarle in `tools/images/src/`, `node tools/build/images.mjs` (AVIF + WebP 480-2000 px, testi alternativi già scritti), ridistribuire il tema. Nessun template da toccare.

## 7. Guida per il cliente (wp-admin)

**Aggiungere un corso**: Corsi › Aggiungi nuovo corso.
1. Titolo e descrizione (editor classico: grassetti, elenchi, a capo).
2. *Copertina del corso* (riquadro a destra): carica l'immagine di copertina.
3. *Aree tematiche* e *Destinatari*: spunta le voci (si possono creare nuove aree e professioni).
4. *Stato, crediti e iscrizione*: stato (Disponibile, Pre-ordine, In arrivo, Concluso), crediti ECM (solo il numero, vuoto se non previsti), link di iscrizione alla piattaforma (se compilato appare il bottone "Iscriviti al corso"), "Corso in evidenza" per mostrarlo in home.
5. *Brochure PDF*: "Carica" e scegli il PDF; l'anteprima è facoltativa (WordPress la genera da solo quando può).
6. *Per chi è adatto*, *Patrocini* (logo + nome ente, trascinabili per cambiare ordine), *Testimonianze del corso*, *Domande frequenti del corso*: compila solo se servono; le sezioni vuote non compaiono sul sito.
7. *Ordine nel catalogo* (numero: più basso = prima) e *SEO* (facoltativo). Pubblica.

**Sostituire una brochure**: apri il corso › riquadro *Brochure PDF* › **Sostituisci** › carica il nuovo PDF › Aggiorna. Il pulsante sul sito punta subito al nuovo file. Se il vecchio PDF non serve più, puoi eliminarlo dalla Media Library.

**Altro**: Docenti (nome, qualifica, bio, foto, CV in PDF o link; senza foto appare l'avatar con le iniziali); Testimonianze (nome, testo, corso di riferimento); **Impostazioni ECM** per email, WhatsApp, link "Accedi", social, P.IVA, link legali, FAQ comuni, testi della home, numeri dichiarati, newsletter e codice analytics (caricato solo dopo il consenso cookie); *Richieste contatto* e *Iscritti newsletter* (con esportazione CSV) sotto Impostazioni ECM; menu in Aspetto › Menu.

## 8. Verifiche

- **27 corsi** (`tools/test/verify-courses.mjs` → `docs/verifica-corsi.md`): pagina 200, titolo identico, testo della descrizione identico, copertina caricata, brochure servita dal sito con peso identico all'originale, numero di patrocini e testimonianze uguale all'originale, testo WhatsApp esatto, "Per chi è adatto", title/meta/schema Course, 301 dai due vecchi URL con spazi. **27/27.**
- **Redirect 301**: `/collections` (anche `?page=N` e `/collections/courses`), `/courses`, `/pages/chi-siamo`, `/pages/docenti`, `/pages/contatti`, `/pages/terms` e `/pages/privacy` (ai PDF), `/pages/richiesta-informazioni-ecm`, i due slug con spazi e maiuscole. In più: i percorsi della piattaforma (`/courses/take/…`, `/users/…`, `/enrollments`, `/account`, `/checkout`…) vengono inoltrati al dominio della piattaforma con lo stesso percorso, così i link dei corsisti continueranno a funzionare dopo il cambio di dominio.
- **Funzionali** (`tools/test/forms-filters.mjs`, 16/16): filtri per area e professione senza ricaricare, URL condivisibili e applicati anche lato server, ricerca, stato vuoto, reset; modulo contatti con errori inline e focus sul primo campo, validazione server in italiano (non ho inviato richieste valide per non scrivere alla segreteria reale); iscrizione newsletter di prova riuscita e poi cancellata; link "Accedi" verso Thinkific; nessun errore JavaScript.
- **Accessibilità** (`tools/test/a11y.mjs`): 0 violazioni WCAG 2.2 AA; skip link, focus visibile, menu mobile con trappola del focus ed Esc, accordion con stati ARIA, etichette sui campi, errori annunciati, contrasti verificati, motion ridotto rispettato.
- **Performance** (`tools/test/perf.mjs`, Lighthouse mobile simulato): Home 96, Catalogo 95, Corso 93, Docenti 96; CLS 0; TBT ≤ 120 ms; LCP di laboratorio 2,6-3,2 s (le copertine originali sono grandi PNG; in WebP generato da WordPress). Font self-hosted con preload, immagini responsive WebP con lazy load, nessuna risorsa di terze parti.
- **Screenshot**: `docs/screenshot/desktop|tablet|mobile/` (pagine intere + fotogrammi della storia in home), `docs/screenshot/admin/` (modifica corso, elenco, impostazioni), `docs/screenshot/original/` (sito attuale).

## 9. Dubbi e contenuti mancanti

1. **Immagini art-directed** da generare (§6).
2. **Nutrigenomica, nutrigenetica ed epigenetica**: descrizione vuota anche sul sito attuale; la scheda rimanda alla brochure. Serve un testo dal cliente.
3. **Crediti ECM**: sul sito solo 2 corsi; dalle brochure risultano crediti per altri 8 e "NO CREDITI ECM" per 1, ma l'estrazione automatica va verificata a mano prima di pubblicarli.
4. **Corso "privato" cane e gatto** e "Alimentazione nel gatto completo" hanno la stessa descrizione professionale; il primo è rivolto ai privati (card "Come alimenti i tuoi amici a 4 zampe?"): andrebbe riscritto dal cliente.
5. **IBAN** di una persona fisica pubblicato nelle FAQ sul bonifico di 8 corsi: l'ho mantenuto perché contenuto reale, valutare se toglierlo o spostarlo nella brochure/email.
6. **Docenti**: i profili non hanno un campo "qualifica" separato (lasciato vuoto, compilabile); la pagina vendite cita 8 docenti in più, non presenti sul sito principale e non importati.
7. **Testimonianze video** (9 su Vimeo nella pagina vendite) non importate; titoli diversi tra card e pagina corso (vale il titolo della pagina).
8. **Privacy**: la policy in PDF non menziona i nuovi moduli (contatti, newsletter) e il nuovo banner cookie: da aggiornare con il consulente privacy. Oggi il sito Thinkific usa iubenda; il nuovo banner è nativo del tema (consenso granulare, preferenze riapribili dal footer). Se si preferisce iubenda, basta incollarne il codice al posto del banner.
9. **Email del modulo contatti**: arrivano a info@ (opzione modificabile). Consigliato un plugin SMTP (es. WP Mail SMTP con le credenziali della casella) per evitare lo spam.

## 10. Checklist per la messa online

**Piattaforma Thinkific su sottodominio**
1. Scegliere il sottodominio (es. `academy.corsidiformazionecm.it`).
2. Thinkific › Settings › Site › Custom domain: impostare il nuovo sottodominio (Thinkific indica il record da creare, di norma un CNAME verso il dominio `*.thinkific.com` del sito) e attendere il certificato SSL.
3. DNS: creare il CNAME `academy` come indicato da Thinkific; verificare login, lezioni ed email transazionali sul nuovo indirizzo; aggiornare in Thinkific i link nei template email e nelle pagine che puntano a `www`.

**WordPress sul dominio principale**
4. Hostinger: collegare `corsidiformazionecm.it` e `www` all'hosting del sito (record A/CNAME indicati da hPanel), attivare SSL, impostare `www` come principale con redirect dall'apex.
5. Cambiare l'indirizzo del sito da staging al dominio (strumento di migrazione Hostinger o search-replace sul database) e svuotare le cache (OPcache, LiteSpeed, CDN Hostinger).
6. **Impostazioni ECM › URL login piattaforma** → `https://academy.corsidiformazionecm.it/users/sign_in` (da questa opzione dipendono il bottone "Accedi" e l'inoltro dei percorsi della piattaforma).
7. Verificare `robots.txt`: sul dominio temporaneo Hostinger blocca Googlebot; sul dominio reale deve consentire la scansione (Impostazioni › Lettura: "Scoraggia i motori di ricerca" disattivato).
8. Google Search Console: verificare il dominio, inviare `https://www.corsidiformazionecm.it/wp-sitemap.xml`, controllare i 301 dei vecchi URL nel report "Pagine".
9. Ripetere i test: `ECM_BASE=https://www.corsidiformazionecm.it node tools/test/verify-courses.mjs` e `forms-filters.mjs`.

**Servizi**
10. Newsletter: oggi i contatti sono raccolti da WordPress (Iscritti newsletter, export CSV). Consigliato **Brevo** (azienda e server UE, adatto al GDPR, piano gratuito per volumi ridotti; verificare i limiti attuali): creare la lista, inserire API key e ID lista in Impostazioni ECM › Newsletter; importare in Brevo gli iscritti esistenti di Thinkific (Leads) e quelli raccolti nel frattempo. In alternativa Mailchimp è già supportato.
11. SMTP per le email del sito; prova di invio del modulo contatti.
12. Analytics (se usati): incollare il codice in Impostazioni ECM › Newsletter e cookie (si attiva solo dopo il consenso "Statistici").
13. Hosting: lasciare il blocco "ECM Cache" nell'`.htaccess` (impedisce al browser di tenere in cache l'HTML per una settimana, impostazione di default Hostinger); backup automatico attivo.
14. Pulizia: cancellare `wp-content/uploads/ecm-import/` (bundle di importazione, già protetto da accesso diretto) quando non serve più; decidere se mantenere l'accesso Novamira.
15. Immagini Higgsfield (§6) prima della pubblicazione, per l'effetto completo.

## 11. Note tecniche

- Deploy: `bash tools/deploy/pack.sh [theme|plugin]` → upload via Novamira → estrazione con lint e `opcache_reset()` (procedura in `tools/deploy/README.md`).
- Build tema: `node tools/build/build.mjs` (CSS da `assets/css/src`, vendor, icone); i sorgenti CSS non vengono distribuiti sul server.
- Il tema dipende dal plugin solo tramite le funzioni helper documentate in `docs/contract-ecm-core.md`; se il plugin è disattivato il tema non va in errore e mostra un avviso in admin.
- Disattivando il plugin i dati restano nel database (nessuna disinstallazione distruttiva).

## 12. Seconda iterazione: scenografia, hero, testimonianze

Richiesta: home troppo povera, immagini mancanti (generarle con MuAPI), hero con sfondo e testo in basso a sinistra su
tutte le sezioni, testimonianze come sul sito originale, tutto più scenografico; Blender 3D ammesso.

### Immagini: perché 3D e foto reali invece dell'AI

- **MuAPI**: nessuna chiave sul PC (né variabile `MUAPI_API_KEY` utente/sistema né configurazione del CLI o di Claude).
  Per usarla: `setx MUAPI_API_KEY "chiave"` in PowerShell; la chiave non va scritta in nessun file del progetto.
- **Higgsfield**: saldo visibile 1185 crediti, piano Ultimate, ma ogni richiesta risponde ancora *"daily generation limit for
  your grace period"* (12 richieste respinte, 0 crediti spesi).
- **Soluzione adottata**: un "mondo della lente" 3D fatto con Blender 5.2 (Cycles, GPU), riproducibile da script
  (`tools/render/lens_world.py`, `tools/render/render-all.sh`). L'anello cremisi laccato del logo, con il secondo anello
  sottile, è sospeso nel buio viola-inchiostro e attraversato da un flusso diverso per ogni sezione:
  globuli rossi (home), arilli di melograno (nutrizione umana), batteri (microbiota), foglie e gocce d'olio (fitoterapia),
  crocchette (nutrizione animale), anelli intrecciati (comunicazione, contatti), un insieme di tutto (catalogo).
  Il soggetto sta sempre a destra, il buio a sinistra ospita il titolo.
- **Foto reali del sito** dove contano le persone: Chi siamo usa la foto di un corso dal vivo (ritagliata dentro la vecchia
  cornice grafica), Testimonianze la stessa foto della hero originale, Docenti un mosaico delle 18 foto dei docenti
  (senza la cornice rossa stampata sopra), in bianco e nero e a colori dentro la lente.
- Le immagini si sostituiscono senza toccare i template (`tools/images/README.md`): appena l'AI sarà disponibile si
  possono affiancare o sostituire con fotografie generate.

### Hero su tutte le pagine

Componente unico `ecm_hero()` (`inc/hero.php`): immagine a tutto schermo (variante verticale per smartphone in home),
titolo in basso a sinistra, velatura per la leggibilità, header chiaro sopra la hero finché non si scorre.
Il segno distintivo è una **lente WebGL** scritta a mano (nessuna libreria): ingrandisce l'immagine sotto il cursore con
aberrazione cromatica sul bordo, seguita dall'anello con il testo che ruota; sui dispositivi touch la lente si muove
da sola, lentamente. Si ferma quando la hero non è visibile; senza WebGL o con "riduci movimento" resta l'immagine statica.
Applicata a: home, Corsi e pagine area, Docenti, Chi siamo, Testimonianze, Contatti, pagine generiche, 404; nella
scheda corso lo sfondo è l'immagine dell'area e la copertina reale sta nella colonna destra (lì la lente è spenta per
non coprirla).

### Home scenografica

1. **Hero** a tutto schermo con la lente.
2. **Manifesto**: i numeri reali (200+ ore, 10k+ corsisti, 27 corsi, 18 docenti, 12 enti) in una frase che si accende
   parola per parola allo scroll (cambio di colore, sempre leggibile: contrasto 4,5:1 anche "spento"), poi il nastro
   dei patrocini.
3. **Aree tematiche**: palcoscenico fissato; si apre da una lente al centro con l'anello, poi ogni area si apre come un
   cerchio a tutto schermo, con navigazione a punti.
4. **Corsi in evidenza** in scorrimento orizzontale guidato dallo scroll (desktop), con parallasse delle copertine e una
   scheda finale "Tutti i 27 corsi"; su mobile si scorrono col dito.
5. **Docenti** come elenco tipografico: sul desktop il ritratto segue il cursore in una lente; su mobile c'è l'avatar.
6. **Testimonianze e recensioni** come sul sito originale (sotto).
7. Newsletter con sfondo 3D, invito finale.

### Testimonianze come l'originale

La pagina segue la struttura di `sales.corsidiformazionecm.it/testimonianze`:

- hero con la foto originale, titolo "Testimonianze", lo stesso sottotitolo e il pulsante "Leggi le testimonianze";
- recensioni con **5 stelle, nome e professione** su due nastri che scorrono in direzioni opposte, con pulsante di
  pausa (WCAG 2.2.2) e "Leggi tutte le recensioni" per l'elenco completo;
- la citazione di **Marco Neri** con professione e ritratto;
- **"Testimonianze dei nostri eventi dal vivo"**: i 9 video Vimeo su fondo cremisi, con anteprime salvate nella
  Libreria media; il lettore Vimeo (senza tracciamento) si carica solo quando si preme play;
- "Formazione continua in ambito medico e scientifico" con i numeri e la foto di un corso dal vivo;
- la **fondatrice**, Dott.ssa Claudia Petrazzuolo, con la sua citazione.

Dati aggiunti al plugin e alla migrazione ripetibile:
- campi "Professione" e "Video" e immagine in evidenza nelle testimonianze (professioni lette dalle schede originali);
- scheda **Impostazioni ECM › Testimonianze & fondatrice**: video degli eventi, nome, ruolo, citazione e foto della
  fondatrice, foto del corso dal vivo;
- segnaposto `{media_id:…}` nell'importatore.

In home le stesse recensioni scorrono sotto "Testimonianze e recensioni", seguite dalla citazione di Marco Neri.

**Da sapere**: il video della citazione di Marco Neri è in un lettore GoHighLevel che non espone l'indirizzo del video
(ho provato anche a cliccarlo da browser automatico). Ho mostrato citazione e ritratto; incollando l'indirizzo Vimeo
nel campo "Video" della sua testimonianza compare da solo il pulsante play.

### Verifiche dopo la seconda iterazione

- 27/27 corsi verificati (il test ora legge il titolo nella nuova hero), form e filtri 16/16, axe 0 violazioni.
- Lighthouse mobile: home 94 (LCP 2,8 s, TBT 80-100 ms, CLS 0), Testimonianze 96, Chi siamo 96, Corsi 96, Docenti 95,
  scheda corso 92; accessibilità, best practices e SEO 100.
- Per tenere la home veloce: l'ingresso del titolo è in CSS (non aspetta il JavaScript), zoom e parallasse della hero
  avvengono nello shader, le scene sotto la piega si preparano quando il browser è libero dopo il caricamento.
- Deploy reso più sicuro: `tools/deploy/deploy-staged.php.txt` estrae in una cartella temporanea, controlla la sintassi
  di ogni file PHP e sostituisce la versione online solo se è tutto pulito.

### Da fare per chiudere

- Generazione AI: chiave MuAPI o sblocco di Higgsfield, se si vogliono fotografie al posto (o accanto) dei render 3D.
- Indirizzo del video di Marco Neri.
- Cartella vuota `C:\tools\images\src` creata per errore da Blender sul PC (percorso relativo risolto dalla radice del
  disco): i file sono stati spostati nel progetto, la cancellazione della cartella vuota è stata bloccata dal controllo
  di sicurezza e va fatta a mano.

### Aggiornamento successivo (stesso giorno)

- **Sito bianco risolto**: WordPress risultava sul tema `hostinger-ai-theme` invece di `ecm`. Due plugin Hostinger
  installati possono cambiare tema (`hostinger-easy-onboarding` e `hostinger-ai-assistant`); la causa più probabile è che
  uno dei due abbia visto per un istante la cartella del tema mancante durante un deploy fatto rinominando la cartella.
  Ho riattivato `ecm` (menu e impostazioni intatti) e svuotato LiteSpeed e la CDN Hostinger (serviva copie vecchie).
  Il deploy (`tools/deploy/deploy-staged.php.txt`) ora aggiorna i file al loro posto, verifica che il tema attivo sia
  `ecm` e svuota anche la CDN. Consiglio: non avviare l'onboarding o l'assistente AI di Hostinger su questo sito, oppure
  disattivare i due plugin se non servono.
- **Effetto del mouse più fine**: al posto della lente d'ingrandimento con l'anello e il testo, una luce radente
  morbida che segue il cursore con un leggero ritardo (la scena resta in penombra, schiarisce dove passa la luce) e una
  parallasse appena percettibile; nella pagina Docenti la luce riporta il colore nei ritratti.
- **Patrocini più leggibili (home)**: due nastri obliqui che si incrociano e scorrono in direzioni opposte seguendo lo
  scroll: bianco con i loghi a colori più grandi, cremisi con "Con il patrocinio di · 12 enti e ordini professionali".
- **Schede corso**: patrocini subito sotto la hero con gli stessi nastri e un badge "Con il patrocinio di N enti" nella
  hero; riga di dati chiave (area, stato, crediti se presenti, professioni, brochure, patrocini); più respiro prima
  della descrizione. La vecchia griglia di loghi grigi in fondo è stata tolta.
- Verifiche: axe 0 violazioni; Lighthouse mobile home 95, scheda corso 94 (accessibilità 100, CLS 0-0,012).
- **MuAPI**: `npm install -g muapi-cli` non funziona su Windows (il pacchetto scarica un eseguibile che nelle release non
  esiste, errore 404; il ripiego `pip` richiede Python, assente). Uso quindi direttamente l'API REST.

### Fotografie MuAPI, aree e patrocini (seconda revisione)

- **Fotografie generate con MuAPI**: 9 immagini (hero della home orizzontale e verticale, catalogo, contatti, 5 aree)
  con `nano-banana-2` in 4k, preventivo prima della generazione; costo totale **0,198 USD** (saldo da 10,00 a 9,802 USD).
  Sostituiscono i render 3D, che restano rigenerabili con Blender. Script: `tools/images/muapi-generate.mjs`.
  La chiave è stata usata solo nei comandi, mai scritta in un file; essendo stata incollata in chat conviene rigenerarla.
- **Aree tematiche**: tolto l'anello con il testo. L'apertura è tipografica (titolo e indice delle 5 aree con il numero di
  corsi, ogni voce è un link) e la prima area si apre con un cerchio pulito dal centro.
- **Patrocini**: tolti i nastri obliqui. Griglia dritta di schede chiare con il logo a colori più grande e il nome
  dell'ente sotto, ultima riga centrata. In home ci sono tutti gli enti (anche i due banner con più loghi); nelle schede
  corso la sezione è subito sotto la hero, che ora ha lo sfondo più scuro perché il soggetto sia la copertina.
- Verifiche: axe 0 violazioni; Lighthouse mobile home 94, scheda corso 94, catalogo 96 (accessibilità 100).

### Loghi singoli, Chi siamo, video (terza revisione)

- **Banner dei patrocini divisi**: i due banner del corso Bundle (6 e 5 loghi in un'unica immagine) sono stati
  ritagliati in loghi singoli con `tools/migrate/split-banners.mjs` (individua i loghi dalle colonne vuote e controlla
  che il numero coincida con gli enti letti). Gli enti che avevano già un logo singolo in altri corsi usano quello
  originale; i 5 nuovi (Caserta, Parma, Reggio Emilia, Bologna, Torino) sono i ritagli. Il corso Bundle ora mostra
  11 loghi, il sito 12 enti in tutto (home, Chi siamo, conteggio). La migrazione è aggiornata e ripetibile; il test dei
  corsi conta un logo per ente.
- **Chi siamo rifatta**, solo con testi reali della pagina e delle impostazioni: hero con la foto del corso dal vivo e la
  prima frase come sottotitolo; "Cosa facciamo" con gli altri paragrafi; Mission e Vision affiancate in grande su
  fondo scuro; numeri e foto del corso; fondatrice con ritratto e citazione; i volti dei 18 docenti (link alla scheda
  di ciascuno); i 12 patrocini; invito finale.
- **Video degli eventi dal vivo**: doppio slider infinito su due file che scorrono in direzioni opposte, come
  nell'originale; pausa al passaggio del mouse, con il pulsante e automaticamente quando parte un video.

## 13. Trasferimento sul WordPress definitivo (27/11/2026)

Il sito è stato rifatto da zero su **https://wvup5sgt7n.wpdns.site** (WordPress 7.1.5, PHP 8.3, Apache dietro
Cloudflare, hosting Rocket.net con il plugin LeadConnector), tramite il server MCP `novamira-wvup5sgt7n-wpdns`.

- **Pacchetti**: plugin, tema e bundle dei contenuti (70 MB, caricato in 14 parti da 5 MB perché Cloudflare rifiuta i
  corpi grandi e va in 504 sulle richieste lunghe; riunito sul server con controllo md5).
- **Importazione**: nuovo comando `wp ecm import` (plugin, `includes/class-ecm-cli.php`) eseguito una volta sola in
  background: 14 termini, 143 media, 27 corsi, 18 docenti, 23 testimonianze, 5 pagine, 2 menu, impostazioni e 8
  redirect, 0 errori. `wp ecm status` mostra l'avanzamento dell'ultima importazione.
- **Impostazioni** come sulla staging: italiano, Europe/Rome, data `j F Y`, permalink `/%postname%/`, home statica,
  blocco "ECM Cache" nell'`.htaccess`. L'indicizzazione è attiva (su
  richiesta, il 27/11/2026 è stato tolto "Scoraggia i motori di ricerca": questo è il sito principale).
- **Approvazioni Novamira 2**: il sito chiede conferma per le azioni con punteggio da 8 in su (installare plugin,
  cambiare tema, scrivere molti file); si regola da wp-admin › AI Activity.
- **Cache**: Cloudflare tiene in cache per 30 giorni anche le pagine HTML. Il deploy ora svuota la CDN con il metodo del
  mu-plugin dell'hosting (`cdn-cache-management`), e le immagini del tema hanno un'impronta nell'URL (`?v=`) così una foto
  sostituita con lo stesso nome non resta vecchia. Il mu-plugin svuota la cache anche quando si pubblica un contenuto o si cambia tema; non è ancora verificato che una
  semplice modifica a una pagina già pubblicata aggiorni subito l'HTML in cache: controllarlo alla prima modifica.
- Verifiche: 27/27 corsi, filtri e moduli 16/16, axe 0 violazioni (desktop e mobile), Lighthouse 93-98 con
  accessibilità e best practice 100 (SEO 66-69 solo per il noindex del dominio temporaneo).

### Hero della home con cane e gatto

- Nuova fotografia MuAPI (`nano-banana-2`, 4k, 2 immagini, 0,044 USD): golden retriever e gatto tigrato argentato su
  una lastra d'ardesia con melograno, salvia e ciotola di porcellana, nella stessa luce delle altre immagini; versione
  orizzontale (soggetti a destra) e verticale per smartphone e tablet (soggetti in alto).
- **Titolo bicolore**: la parte tra asterischi del titolo esce in cremisi chiaro (`--c-crimson-bright`, contrasto 5,6:1
  sul fondo scuro): "*Formazione ECM* per chi si prende cura della salute." Si cambia da Impostazioni ECM › Home ›
  Titolo principale; vale per tutte le hero che ricevono un titolo con asterischi.

### SEO di base con Yoast SEO (27/11/2026)

- **Yoast SEO 28.9** installato in italiano. Il modulo SEO di ECM Core si spegne da solo quando trova Yoast (nessun meta
  doppio: un titolo, una descrizione, un canonical, un solo blocco JSON-LD per pagina) e passa a Yoast i propri dati
  (`class-ecm-seo.php`, sezione "Ponte verso Yoast SEO"):
  - descrizione: quella scritta in Yoast, altrimenti l'estratto del corso, il testo breve dell'area, la descrizione del
    catalogo o il testo della pagina; in home il testo della hero;
  - schema: il nodo `Course` nelle schede corso; dati dell'azienda (P. IVA, email, indirizzo, contatto WhatsApp, profili
    social) nell'`Organization` di Yoast;
  - immagini social in JPEG/PNG originale invece della versione WebP;
  - pagine dei destinatari fuori dalla sitemap (il catalogo filtrato è la pagina di riferimento).
- **Impostazioni**: separatore "–"; titoli "Corso – Corso ECM online – Corsi di Formazione ECM", "Catalogo dei corsi
  ECM online – …", "Corsi ECM di {area} – …"; home "Corsi di Formazione ECM – Formazione ECM online per i professionisti
  della salute"; azienda "Corsi di FormazionEcm" (ragione sociale) con il marchio quadrato come logo; immagine di
  condivisione 1200×630 con logo, cane e gatto; archivi autore, data e allegati disattivati; tracciamento di Yoast spento.
- **Sitemap**: `/sitemap_index.xml` con pagine (6), corsi (27 + catalogo) e aree (5). Articolo di esempio "Ciao mondo!"
  spostato nel cestino.
- **robots.txt**: WordPress/Yoast generano quello giusto (escluso solo `/wp-admin/`, con la sitemap). All'indirizzo
  `/robots.txt` del dominio temporaneo però risponde ancora "Disallow: /" direttamente Cloudflare (nessuna richiesta
  arriva a WordPress, resta uguale dopo gli svuotamenti della CDN): è il blocco che l'hosting mette sui domini
  temporanei `*.wpdns.site`. Sparisce collegando il dominio vero; finché c'è, Lighthouse segna SEO 69 ("pagina non
  scansionabile") e Google non indicizza il dominio temporaneo.
- Da fare alla messa online: collegare il dominio, controllare `/robots.txt` e inviare `/sitemap_index.xml` in
  Google Search Console.
