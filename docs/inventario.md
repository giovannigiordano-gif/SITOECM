# Inventario contenuti — sito attuale corsidiformazionecm.it

Scraping eseguito il 2026-11-20 (sola lettura, nessun form inviato, nessun login) con gli script in `tools/scrape/`. Dati completi in `data/scrape/*.json`, asset in `data/scrape/assets/`, screenshot in `docs/screenshot/original/`.

Per rigenerare tutto: `cd tools/scrape && node discover.mjs && node courses.mjs && node site.mjs && node testimonianze.mjs && node docenti.mjs && node pages.mjs && node postprocess.mjs && node screenshots.mjs && node report.mjs`.

## 1. Riepilogo

| Voce | Valore |
|---|---|
| Corsi nel catalogo (/collections, 5 pagine × 6) | **27** |
| Corsi trovati fuori catalogo | nessuno (sitemap, crawl completo di tutti i link interni e pagina vendite coincidono con il catalogo) |
| Cover scaricate | 27/27 |
| Brochure PDF scaricate (iniziano con %PDF) | 27/27 |
| Corsi con patrocini | 3 (16 immagini, 9 file unici, 12 enti distinti) |
| Corsi con testimonianze proprie | 5 (18 testimonianze) |
| Docenti (/pages/docenti) | 18 (CV scaricabili: 18) |
| Testimonianze uniche (tutte le fonti, deduplicate) | 29 + 9 video Vimeo |
| Pagine /pages/* trovate | 8 |

## 2. Validazione per campo (corsi)

| Controllo | OK | Problemi |
|---|---|---|
| title | 27/27 | — |
| cover_file | 27/27 | — |
| description | 26/27 | nutrigenomica-nutrigenetica-ed-epigenetica |
| whatsapp | 27/27 | — |
| whatsapp_text_matches_title | 18/27 | metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta, alimentazione-nel-gatto-completo, privato-corso-cane-e-gatto-230, dieta-chetogenica, le-reazioni-avverse-agli-alimenti, microbiota, bundle-microbiota-ed-epigenetica-nell-alimentazione-del-cane-e-del-gatto, nutrigenomica-nutrigenetica-ed-epigenetica, caneegatto2 |
| brochure_ok | 27/27 | — |
| brochure_preview | 27/27 | — |
| faq | 27/27 | — |
| per_chi_e_adatto | 27/27 | — |
| js_dom_consistent | 27/27 | — |
| file loghi patrocinio | 16/16 | — |

## 3. Tabella corsi (ordine del catalogo)

| # | Titolo (pagina corso) | clean_slug | Path originale | Parole | Cover | Brochure | Patr. | Testim. | FAQ | Stato | ECM pagina / brochure | Ore · modalità (brochure) | Professioni citate |
|---|---|---|---|---|---|---|---|---|---|---|---|---|---|
| 1 | METABOLISMO, NUTRIZIONE E PERFORMANCE NEL CANE SPORTIVO E NEL CAVALLO ATLETA | `metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta` | `/courses/METABOLISMO,%20NUTRIZIONE%20E%20PERFORMANCE%20NEL%20CANE%20SPORTIVO%20E%20NEL%20CAVALLO%20ATLETA` | 90 | ✓ 2688×1520 | ✓ 1.3 MB, 7 pp. | 7 | 6 | 2 | pre-ordine | — / 30 | 14 h | medici, biologi, dietisti, farmacisti, veterinari, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 2 | CORSO NUTRIZIONE E DIETETICA APPLICATA NEL CANE E NEL GATTO<br>*catalogo: ALIMENTAZIONE NEL CANE & GATTO COMPLETO* | `alimentazione-nel-gatto-completo` | `/courses/alimentazione-nel-gatto-completo` | 127 | ✓ 1200×675 | ✓ 396 KB, 3 pp. | 7 | 6 | 2 | — | — / 30 | 30 h · ON DEMAND | medici, biologi, dietisti, farmacisti, veterinari, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 3 | ALIMENTAZIONE NELLO SPORTIVO<br>*catalogo: ALIMENTAZIONE NELLO SPORTIVO - 20 CREDITI* | `alimentazione-nello-sportivo` | `/courses/alimentazione-nello-sportivo` | 62 | ✓ 1080×597 | ✓ 622 KB, 3 pp. | 0 | 0 | 2 | — | 20 / etichetta senza numero | 14 h · ON DEMAND | medici, biologi, dietisti, farmacisti, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 4 | ALIMENTAZIONE VEGANA E VEGETARIANA | `alimentazione-vegana-e-vegetariana` | `/courses/alimentazione-vegana-e-vegetariana` | 157 | ✓ 1080×597 | ✓ 709 KB, 3 pp. | 0 | 0 | 2 | — | — / ? | 15 h · ON DEMAND | medici, biologi, dietisti, farmacisti, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 5 | APPARATO DIGERENTE | `apparato-digerente` | `/courses/apparato-digerente` | 35 | ✓ 1080×597 | ✓ 1.1 MB, 2 pp. | 0 | 0 | 2 | — | — / NO ECM | 8 h · ON DEMAND | medici, biologi, dietisti, farmacisti, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 6 | CORSO PRIVATO NUTRIZIONE E DIETETICA APPLICATA NEL CANE E NEL GATTO<br>*catalogo: COME ALIMENTI TUOI AMICI A 4 ZAMPE?* | `privato-corso-cane-e-gatto-230` | `/courses/privato-corso-cane-e-gatto-230` | 127 | ✓ 1920×1080 | ✓ 1.2 MB, 2 pp. | 0 | 0 | 2 | — | — / 30 | — | medici, biologi, dietisti, farmacisti, veterinari, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere, privati |
| 7 | COMPOSIZIONE CORPOREA E STATO DI NUTRIZIONE | `composizione-corporea-e-stato-di-nutrizione` | `/courses/composizione-corporea-e-stato-di-nutrizione` | 104 | ✓ 1080×597 | ✓ 424 KB, 2 pp. | 0 | 0 | 2 | — | — / ? | 8 h · ON DEMAND | medici, biologi, nutrizionisti, dietisti, farmacisti, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 8 | COUNSELING NUTRIZIONALE | `counseling-nutrizionale` | `/courses/counseling-nutrizionale` | 179 | ✓ 1080×597 | ✓ 1.0 MB, 3 pp. | 0 | 0 | 2 | — | — / 11 | 8 h · ON DEMAND | medici, biologi, dietisti, farmacisti, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 9 | CURARE L’ECCESSO PONDERALE IN ETÀ EVOLUTIVA | `curare-l-eccesso-ponderale-in-eta-evolutiva` | `/courses/curare-l-eccesso-ponderale-in-eta-evolutiva` | 276 | ✓ 1080×597 | ✓ 946 KB, 3 pp. | 0 | 0 | 2 | — | — / ? | 14 h · ON DEMAND | medici, biologi, dietisti, farmacisti, infermieri, psicologi, tecnologi alimentari, laureandi, studenti, operatori del settore benessere, professionisti sanitari |
| 10 | DALL'EUBIOSI ALLA DISBIOSI | `dall-eubiosi-alla-disbiosi` | `/courses/dall-eubiosi-alla-disbiosi` | 86 | ✓ 1080×597 | ✓ 958 KB, 2 pp. | 0 | 0 | 2 | — | — / etichetta senza numero | 5 h · ON DEMAND | medici, biologi, dietisti, farmacisti, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 11 | EVIDENCE-BASED PHYTOTHERAPY | `evidence-based-phytotherapy` | `/courses/evidence-based-phytotherapy` | 92 | ✓ 1080×597 | ✓ 872 KB, 6 pp. | 0 | 2 | 2 | — | — / ? | 56 h · ON DEMAND | medici, biologi, dietisti, farmacisti, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 12 | FITOTERAPIA APPLICATA | `fitoterapia-applicata` | `/courses/fitoterapia-applicata` | 78 | ✓ 1080×597 | ✓ 568 KB, 6 pp. | 0 | 2 | 2 | — | — / ? | 40 h · ON DEMAND | medici, biologi, dietisti, farmacisti, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 13 | FITOTERAPIA SISTEMICA | `fitoterapia-sistemica` | `/courses/fitoterapia-sistemica` | 126 | ✓ 1080×597 | ✓ 753 KB, 7 pp. | 0 | 2 | 2 | — | — / ? | 56 h · ON DEMAND | medici, biologi, dietisti, farmacisti, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 14 | L'INSULINO-RESISTENZA E LA DIABESITÀ | `l-insulino-resistenza-e-la-diabesita` | `/courses/l-insulino-resistenza-e-la-diabesita` | 162 | ✓ 1080×597 | ✓ 592 KB, 2 pp. | 0 | 0 | 2 | — | — / etichetta senza numero | 5 h · ON DEMAND | medici, biologi, dietisti, farmacisti, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 15 | LA DIETA CHETOGENICA COME SOLUZIONE TERAPEUTICA: Un approccio pratico | `dieta-chetogenica` | `/courses/dieta-chetogenica` | 148 | ✓ 1920×1080 | ✓ 1.5 MB, 2 pp. | 0 | 0 | 3 | — | — / 30 | 14 h · ON DEMAND | medici, biologi, nutrizionisti, dietisti, farmacisti, veterinari, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 16 | LE REAZIONI AVVERSE AGLI ALIMENTI | `le-reazioni-avverse-agli-alimenti` | `/courses/le-reazioni-avverse-agli-alimenti` | 112 | ✓ 1080×597 | ✓ 724 KB, 2 pp. | 0 | 0 | 2 | — | — / etichetta senza numero | 5 h · ON DEMAND | medici, biologi, dietisti, farmacisti, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 17 | L’ANALISI DEL MICROBIOTA NELLA PRATICA CLINICA E NELLA GESTIONE NUTRIZIONALE: Un approccio pratico | `microbiota` | `/courses/microbiota` | 185 | ✓ 1920×1080 | ✓ 1.9 MB, 5 pp. | 0 | 0 | 2 | — | — / 30 | 14 h · ON DEMAND | medici, biologi, dietisti, farmacisti, veterinari, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 18 | L’INVECCHIAMENTO IN SALUTE | `l-invecchiamento-in-salute` | `/courses/l-invecchiamento-in-salute` | 94 | ✓ 1080×597 | ✓ 782 KB, 2 pp. | 0 | 0 | 2 | — | — / etichetta senza numero | 5 h · ON DEMAND | medici, biologi, dietisti, farmacisti, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 19 | MASTER NUTRIZIONE E DIETETICA APPLICATA | `nutrizione-e-dietetica-applicata` | `/courses/nutrizione-e-dietetica-applicata` | 206 | ✓ 1080×597 | ✓ 904 KB, 10 pp. | 0 | 0 | 2 | — | — / ? | 112 h · ON DEMAND | medici, biologi, nutrizionisti, dietisti, farmacisti, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 20 | MICROBIOTA ED EPIGENETICA NELL'ALIMENTAZIONEDEL CANE E DEL GATTO: PRINCIPI ED APPLICAZIONI CLINICHE | `bundle-microbiota-ed-epigenetica-nell-alimentazione-del-cane-e-del-gatto` | `/courses/BUNDLE%20MICROBIOTA%20ED%20EPIGENETICA%20NELL'ALIMENTAZIONE%20DEL%20CANE%20E%20DEL%20GATTO` | 191 | ✓ 1920×1080 | ✓ 2.1 MB, 5 pp. | 2 | 0 | 3 | — | — / ? | — | medici, biologi, dietisti, farmacisti, veterinari, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 21 | NUOVO APPROCCIO ALLA PREVENZIONE E CURA DELL’OBESITÀ INFANTILE | `nuovo-approccio-alla-prevenzione-e-cura-dell-obesita-infantile` | `/courses/nuovo-approccio-alla-prevenzione-e-cura-dell-obesita-infantile` | 263 | ✓ 1080×597 | ✓ 735 KB, 3 pp. | 0 | 0 | 2 | — | — / etichetta senza numero | 12 h · ON DEMAND | medici, biologi, dietisti, farmacisti, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere, professionisti sanitari |
| 22 | NUTRIGENOMICA, NUTRIGENETICA ED EPIGENETICA | `nutrigenomica-nutrigenetica-ed-epigenetica` | `/courses/nutrigenomica-nutrigenetica-ed-epigenetica` | 0 | ✓ 1080×597 | ✓ 757 KB, 5 pp. | 0 | 0 | 2 | — | — / ? | 42 h · ON DEMAND | medici, biologi, dietisti, farmacisti, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 23 | NUTRIZIONE CLINICA<br>*catalogo: NUTRIZIONE CLINICA - 30 CREDITI* | `nutrizione-clinica` | `/courses/nutrizione-clinica` | 145 | ✓ 1080×597 | ✓ 1.1 MB, 6 pp. | 0 | 0 | 2 | — | 30 / 30 | 25 h · 5 gg · ON DEMAND | medici, biologi, dietisti, farmacisti, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 24 | NUTRIZIONE E DONNA | `nutrizione-e-donna` | `/courses/nutrizione-e-donna` | 139 | ✓ 1080×597 | ✓ 909 KB, 3 pp. | 0 | 0 | 2 | — | — / etichetta senza numero | 8 h · ON DEMAND | medici, biologi, dietisti, farmacisti, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 25 | ALIMENTAZIONE E NUTRIZIONE FUNZIONALE PER PATOLOGIA. MEDICINA DI LABORATORIO NEL CANE E NEL GATTO<br>*catalogo: PATOLOGIE E ANALISI DI LABORATORIO NEL CANE E NEL GATTO* | `caneegatto2` | `/courses/caneegatto2` | 79 | ✓ 1920×1080 | ✓ 1.5 MB, 3 pp. | 0 | 0 | 3 | — | — / 30 | ON DEMAND | medici, biologi, nutrizionisti, dietisti, farmacisti, veterinari, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 26 | PNL APPLICATA ALLA COMUNICAZIONE SANITARIA | `pnl-applicata-alla-comunicazione-sanitaria` | `/courses/pnl-applicata-alla-comunicazione-sanitaria` | 228 | ✓ 1080×597 | ✓ 717 KB, 4 pp. | 0 | 0 | 2 | — | — / ? | 36 h · ON DEMAND | medici, biologi, dietisti, farmacisti, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |
| 27 | RITMI CIRCADIANI E METABOLISMO | `ritmi-circadiani-e-metabolismo` | `/courses/ritmi-circadiani-e-metabolismo` | 421 | ✓ 1080×597 | ✓ 512 KB, 2 pp. | 0 | 0 | 2 | — | — / ? | 6 h · ON DEMAND | medici, biologi, dietisti, farmacisti, infermieri, psicologi, tecnologi alimentari, laureandi, operatori del settore benessere |

Note: "Parole" = parole della sola descrizione (sezione `description`). Le professioni sono quelle nominate esplicitamente nel testo della pagina (quasi sempre nella FAQ "Per chi…"). "ECM pagina" = campo `ecm_credits`, valorizzato solo dove il numero di crediti compare nel testo della pagina/card (solo 2 casi, nel titolo della card). "ECM brochure", ore e modalità sono ricavati dal testo dei PDF brochure (`brochure_hints`, testo completo in `data/scrape/assets/brochure-text/`) e NON sono sul sito.

### 3.1 Redirect 301 necessari

| Corso | Path originale (come in href) | Path decodificato | Path percent-encoded completo | Nuovo slug |
|---|---|---|---|---|
| 1 | `/courses/METABOLISMO,%20NUTRIZIONE%20E%20PERFORMANCE%20NEL%20CANE%20SPORTIVO%20E%20NEL%20CAVALLO%20ATLETA` | `/courses/METABOLISMO, NUTRIZIONE E PERFORMANCE NEL CANE SPORTIVO E NEL CAVALLO ATLETA` | `/courses/METABOLISMO%2C%20NUTRIZIONE%20E%20PERFORMANCE%20NEL%20CANE%20SPORTIVO%20E%20NEL%20CAVALLO%20ATLETA` | `metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta` |
| 20 | `/courses/BUNDLE%20MICROBIOTA%20ED%20EPIGENETICA%20NELL'ALIMENTAZIONE%20DEL%20CANE%20E%20DEL%20GATTO` | `/courses/BUNDLE MICROBIOTA ED EPIGENETICA NELL'ALIMENTAZIONE DEL CANE E DEL GATTO` | `/courses/BUNDLE%20MICROBIOTA%20ED%20EPIGENETICA%20NELL%27ALIMENTAZIONE%20DEL%20CANE%20E%20DEL%20GATTO` | `bundle-microbiota-ed-epigenetica-nell-alimentazione-del-cane-e-del-gatto` |

Tutti gli altri 25 slug originali sono già minuscoli/ASCII e coincidono con `clean_slug` (nessun redirect di slug necessario, a meno di rinominarli: es. `caneegatto2`, `privato-corso-cane-e-gatto-230`, `alimentazione-nel-gatto-completo` hanno slug poco coerenti col titolo).

### 3.2 Anomalie per corso (dal campo `notes`)

- **1. metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta**
  - Titolo SEO <title> diverso dal titolo pagina: "NUTRIZIONE E DIETETICA APPLICATA".
  - Testo WhatsApp precompilato NON corrisponde al titolo: "Salve, vorrei avere maggiori informazioni sul corso NUTRIZIONE E DIETETICA CANE E GATTO".
  - Domanda FAQ "per chi" formulata come: "Per chi sono adatti i nostri corsi?".
- **2. alimentazione-nel-gatto-completo**
  - Titolo SEO <title> diverso dal titolo pagina: "NUTRIZIONE E DIETETICA APPLICATA".
  - Titolo pagina diverso dal titolo nel catalogo ("ALIMENTAZIONE NEL CANE & GATTO COMPLETO").
  - Testo WhatsApp precompilato NON corrisponde al titolo: "Salve, vorrei avere maggiori informazioni sul corso NUTRIZIONE E DIETETICA CANE E GATTO".
  - Domanda FAQ "per chi" formulata come: "Per chi sono adatti i nostri corsi?".
- **3. alimentazione-nello-sportivo**
  - Titolo SEO <title> diverso dal titolo pagina: "ALIMENTAZIONE NELLO SPORTIVO - 20 CREDITI".
  - Titolo pagina diverso dal titolo nel catalogo ("ALIMENTAZIONE NELLO SPORTIVO - 20 CREDITI").
  - Il titolo contiene <br> nel markup: "ALIMENTAZIONE <br>NELLO SPORTIVO"
- **4. alimentazione-vegana-e-vegetariana**
  - Il titolo contiene <br> nel markup: "ALIMENTAZIONE VEGANA<br> E VEGETARIANA"
- **6. privato-corso-cane-e-gatto-230**
  - Titolo SEO <title> diverso dal titolo pagina: "NUTRIZIONE E DIETETICA APPLICATA".
  - Titolo pagina diverso dal titolo nel catalogo ("COME ALIMENTI TUOI AMICI A 4 ZAMPE?").
  - Testo WhatsApp precompilato NON corrisponde al titolo: "Salve, vorrei avere maggiori informazioni sul corso NUTRIZIONE E DIETETICA CANE E GATTO".
  - Domanda FAQ "per chi" formulata come: "Per chi sono adatti i nostri corsi?".
- **7. composizione-corporea-e-stato-di-nutrizione**
  - Il titolo contiene <br> nel markup: "COMPOSIZIONE CORPOREA <br>E STATO DI NUTRIZIONE"
- **9. curare-l-eccesso-ponderale-in-eta-evolutiva**
  - Il titolo contiene <br> nel markup: "CURARE L’ECCESSO PONDERALE<br> IN ETÀ EVOLUTIVA"
- **14. l-insulino-resistenza-e-la-diabesita**
  - Il titolo contiene <br> nel markup: "L'INSULINO-RESISTENZA <br>E LA DIABESITÀ"
- **15. dieta-chetogenica**
  - Titolo SEO <title> diverso dal titolo pagina: "NUTRIZIONE E DIETETICA APPLICATA".
  - Testo WhatsApp precompilato NON corrisponde al titolo: "Salve, vorrei avere maggiori informazioni sul corso NUTRIZIONE E DIETETICA CANE E GATTO".
  - Domanda FAQ "per chi" formulata come: "Per chi sono adatti i nostri corsi?".
- **16. le-reazioni-avverse-agli-alimenti**
  - Testo WhatsApp precompilato NON corrisponde al titolo: "Salve, vorrei avere maggiori informazioni sul corso NUTRIZIONE E DIETETICA CANE E GATTO".
  - Il titolo contiene <br> nel markup: "LE REAZIONI AVVERSE <br>AGLI ALIMENTI"
- **17. microbiota**
  - Titolo SEO <title> diverso dal titolo pagina: "NUTRIZIONE E DIETETICA APPLICATA".
  - Testo WhatsApp precompilato NON corrisponde al titolo: "Salve, vorrei avere maggiori informazioni sul corso NUTRIZIONE E DIETETICA CANE E GATTO".
  - Domanda FAQ "per chi" formulata come: "Per chi sono adatti i nostri corsi?".
- **19. nutrizione-e-dietetica-applicata**
  - Titolo SEO <title> diverso dal titolo pagina: "NUTRIZIONE E DIETETICA APPLICATA".
  - Il titolo contiene <br> nel markup: "MASTER NUTRIZIONE <br>E DIETETICA APPLICATA"
- **20. bundle-microbiota-ed-epigenetica-nell-alimentazione-del-cane-e-del-gatto**
  - Titolo SEO <title> diverso dal titolo pagina: "NUTRIZIONE E DIETETICA APPLICATA".
  - Testo WhatsApp precompilato NON corrisponde al titolo: "Salve, vorrei avere maggiori informazioni sul corso NUTRIZIONE E DIETETICA CANE E GATTO".
  - Domanda FAQ "per chi" formulata come: "Per chi sono adatti i nostri corsi?".
  - Titolo con parole unite: "ALIMENTAZIONEDEL" (manca spazio).
- **22. nutrigenomica-nutrigenetica-ed-epigenetica**
  - Testo WhatsApp precompilato NON corrisponde al titolo: "Salve, vorrei avere maggiori informazioni sul corso "NUTRIGENOMICA"".
  - DESCRIZIONE VUOTA sulla pagina corso (sezione description senza testo): esiste solo il testo breve della card di catalogo.
- **23. nutrizione-clinica**
  - Titolo SEO <title> diverso dal titolo pagina: "NUTRIZIONE CLINICA - 30 CREDITI".
  - Titolo pagina diverso dal titolo nel catalogo ("NUTRIZIONE CLINICA - 30 CREDITI").
- **25. caneegatto2**
  - Titolo SEO <title> diverso dal titolo pagina: "NUTRIZIONE E DIETETICA APPLICATA".
  - Titolo pagina diverso dal titolo nel catalogo ("PATOLOGIE E ANALISI DI LABORATORIO NEL CANE E NEL GATTO").
  - Testo WhatsApp precompilato NON corrisponde al titolo: "Salve, vorrei avere maggiori informazioni sul corso NUTRIZIONE E DIETETICA CANE E GATTO".
  - Domanda FAQ "per chi" formulata come: "Per chi sono adatti i nostri corsi?".
- **27. ritmi-circadiani-e-metabolismo**
  - Il titolo contiene <br> nel markup: "RITMI CIRCADIANI<br>E METABOLISMO"

Testi duplicati tra corsi:

- Estratto card di catalogo identico: alimentazione-nel-gatto-completo = microbiota; dieta-chetogenica = bundle-microbiota-ed-epigenetica-nell-alimentazione-del-cane-e-del-gatto; microbiota = alimentazione-nel-gatto-completo; bundle-microbiota-ed-epigenetica-nell-alimentazione-del-cane-e-del-gatto = dieta-chetogenica
- Descrizione identica: alimentazione-nel-gatto-completo = privato-corso-cane-e-gatto-230; privato-corso-cane-e-gatto-230 = alimentazione-nel-gatto-completo
- Estratti di catalogo palesemente sbagliati (copiati da altri corsi): `microbiota` usa l'estratto del corso cane&gatto; `bundle-microbiota-ed-epigenetica…` usa quello della dieta chetogenica. Estratti troncati a metà frase (limite Thinkific): metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta, evidence-based-phytotherapy, l-invecchiamento-in-salute.

## 4. Struttura DOM / selettori (Thinkific)

Il contenuto è stato estratto dall'HTML servito dal server (contesto Playwright con JavaScript disattivato) per evitare i cloni dei caroselli Owl; per ogni corso è stato verificato che il DOM con JS abbia la stessa sequenza di sezioni (`js_dom_check`). Le email sono offuscate da Cloudflare (`/cdn-cgi/l/email-protection`, `data-cfemail`) e sono state decodificate con lo stesso algoritmo di Cloudflare.

- Ogni pagina è una sequenza di `<section data-preview-item="…" data-section-uid="…">`.
- **Cover**: prima sezione `rich_text` senza titolo → `.fr-view img` (file su `files.cdn.thinkific.com`, originale senza parametri di resize). La card di catalogo usa un'immagine diversa su `import.cdn.thinkific.com` (scaricata in `assets/covers/cards/`).
- **Titolo + sottotitolo + descrizione + WhatsApp**: sezione `description` → `.section__heading` (h2, a volte con `<br>`), `.section__subheading`, `.section__body` (testo semplice con `<br>` e a-capo; gli a-capo NON sono visibili sul sito perché `white-space: normal`), `.section__button-group a.button-primary` (wa.me).
- **Testimonianze corso**: sezione `testimonials` → `.testimonials__list-item` con `h3.testimonial__title` (titolo/frase), `p.testimonial__author` (nome), `div` (testo).
- **Brochure**: sezione `call-to-action` "Vuoi saperne di più?" → `a.button-secondary` "BROCHURE PDF" (Google Drive).
- **Patrocini**: sezione `rich_text` con heading "Con il patrocinio di" e immagini inline (2 corsi), oppure (corso BUNDLE) sezione `description` vuota con heading "Con il Patrocinio di:" seguita da 2 sezioni `gallery` (banner compositi multi-logo). Nessun logo ha `alt`: i nomi degli enti sono stati letti dalle immagini (`logos_index.json`).
- **FAQ**: sezione `faq` → sottotitolo con email, `.faq__list-item details[open] summary strong` (domanda) + `p` (risposta). Tutte le FAQ sono già aperte (`open`).
- **Newsletter**: sezione `lead_capture` (form Thinkific `/tenant_leads`) su home, corsi e contatti.

| Corso | Sezioni nell'ordine |
|---|---|
| metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta | rich_text → description → testimonials ("Testimonianze") → call-to-action → rich_text ("Con il patrocinio di") → faq → lead_capture |
| alimentazione-nel-gatto-completo | rich_text → description → testimonials ("Testimonianze") → call-to-action → rich_text ("Con il patrocinio di") → faq → lead_capture |
| alimentazione-nello-sportivo | rich_text → description → call-to-action → faq → lead_capture |
| alimentazione-vegana-e-vegetariana | rich_text → description → call-to-action → faq → lead_capture |
| apparato-digerente | rich_text → description → call-to-action → faq → lead_capture |
| privato-corso-cane-e-gatto-230 | rich_text → description → call-to-action → faq → lead_capture |
| composizione-corporea-e-stato-di-nutrizione | rich_text → description → call-to-action → faq → lead_capture |
| counseling-nutrizionale | rich_text → description → call-to-action → faq → lead_capture |
| curare-l-eccesso-ponderale-in-eta-evolutiva | rich_text → description → call-to-action → faq → lead_capture |
| dall-eubiosi-alla-disbiosi | rich_text → description → call-to-action → faq → lead_capture |
| evidence-based-phytotherapy | rich_text → description → testimonials ("Testimonianze") → call-to-action → faq → lead_capture |
| fitoterapia-applicata | rich_text → description → testimonials ("Testimonianze") → call-to-action → faq → lead_capture |
| fitoterapia-sistemica | rich_text → description → testimonials ("Testimonianze") → call-to-action → faq → lead_capture |
| l-insulino-resistenza-e-la-diabesita | rich_text → description → call-to-action → faq → lead_capture |
| dieta-chetogenica | rich_text → description → call-to-action → faq → lead_capture |
| le-reazioni-avverse-agli-alimenti | rich_text → description → call-to-action → faq → lead_capture |
| microbiota | rich_text → description → call-to-action → faq → lead_capture |
| l-invecchiamento-in-salute | rich_text → description → call-to-action → faq → lead_capture |
| nutrizione-e-dietetica-applicata | rich_text → description → call-to-action → faq → lead_capture |
| bundle-microbiota-ed-epigenetica-nell-alimentazione-del-cane-e-del-gatto | rich_text → description → call-to-action → description → gallery → gallery → faq → lead_capture |
| nuovo-approccio-alla-prevenzione-e-cura-dell-obesita-infantile | rich_text → description → call-to-action → faq → lead_capture |
| nutrigenomica-nutrigenetica-ed-epigenetica | rich_text → description → call-to-action → faq → lead_capture |
| nutrizione-clinica | rich_text → description → call-to-action → faq → lead_capture |
| nutrizione-e-donna | rich_text → description → call-to-action → faq → lead_capture |
| caneegatto2 | rich_text → description → call-to-action → faq → lead_capture |
| pnl-applicata-alla-comunicazione-sanitaria | rich_text → description → call-to-action → faq → lead_capture |
| ritmi-circadiani-e-metabolismo | rich_text → description → call-to-action → faq → lead_capture |

Corsi con patrocini: `metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta` (7), `alimentazione-nel-gatto-completo` (7), `bundle-microbiota-ed-epigenetica-nell-alimentazione-del-cane-e-del-gatto` (2). Corsi con testimonianze: `metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta` (6), `alimentazione-nel-gatto-completo` (6), `evidence-based-phytotherapy` (2), `fitoterapia-applicata` (2), `fitoterapia-sistemica` (2).

### 4.1 Enti patrocinanti (letti dai loghi)

| sha1 | File | Ente/i | Usato da |
|---|---|---|---|
| 75be18e8 | `data/scrape/assets/logos/metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta/01-logo-ordine-nitido-pg.png` | Ordine dei Medici Veterinari della Provincia di Perugia | metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta#1, alimentazione-nel-gatto-completo#5 |
| a4c1734c | `data/scrape/assets/logos/metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta/02-vbo.png` | Ordine Veterinari della Provincia del Verbano-Cusio-Ossola | metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta#2, alimentazione-nel-gatto-completo#1 |
| 3c0fa1be | `data/scrape/assets/logos/metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta/03-logo-ordine-veterinari-mantova.jpg` | Ordine dei Medici Veterinari della Provincia di Mantova | metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta#3, alimentazione-nel-gatto-completo#4 |
| 69a95df7 | `data/scrape/assets/logos/metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta/04-unnamed.jpg` | Ordine dei Medici Veterinari della Provincia di Ancona | metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta#4, alimentazione-nel-gatto-completo#3 |
| af059fc5 | `data/scrape/assets/logos/metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta/05-logo-onb-sicilia.jpg` | Ordine Biologi Sicilia | metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta#5, alimentazione-nel-gatto-completo#2 |
| 76fe8723 | `data/scrape/assets/logos/metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta/06-logo-ordine-veterinari-latina.png` | Ordine dei Medici Veterinari della Provincia di Latina | metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta#6, alimentazione-nel-gatto-completo#7 |
| 26eea860 | `data/scrape/assets/logos/metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta/07-logo-ordine-biologi-piemonte-liguria-e-valle-daosta-solo-rot.png` | Ordine dei Biologi Piemonte – Liguria – Valle d'Aosta | metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta#7, alimentazione-nel-gatto-completo#6 |
| e5181d19 | `data/scrape/assets/logos/bundle-microbiota-ed-epigenetica-nell-alimentazione-del-cane-e-del-gatto/01-jnrfa4dytpqpmiowcsg7-1.png` | Ordine dei Medici Veterinari Caserta; Ordine dei Medici Veterinari della Provincia di Parma; Ordine dei Medici Veterinari della Provincia di Ancona; Ordine dei Medici Veterinari della Provincia di Mantova; Ordine dei Biologi Piemonte – Liguria – Valle d'Aosta; Ordine dei Medici Veterinari della Provincia di Reggio Emilia *(banner composito)* | bundle-microbiota-ed-epigenetica-nell-alimentazione-del-cane-e-del-gatto#1 |
| a5c2d3d6 | `data/scrape/assets/logos/bundle-microbiota-ed-epigenetica-nell-alimentazione-del-cane-e-del-gatto/02-iqeqynfsqhcyhi5mw6ti-2.png` | Ordine Veterinari della Provincia di Bologna; Ordine Biologi Sicilia; Ordine dei Medici Veterinari della Provincia di Perugia; Ordine dei Medici Veterinari della Provincia di Torino; Ordine Veterinari della Provincia del Verbano-Cusio-Ossola *(banner composito)* | bundle-microbiota-ed-epigenetica-nell-alimentazione-del-cane-e-del-gatto#2 |

## 5. Differenze rispetto a quanto noto (brief)

- Catalogo: 27 corsi su 5 pagine (6+6+6+6+3); `?page=6` è vuota. Nessun corso pubblicato fuori catalogo; esiste anche `/collections/courses` (stessa lista) e `/courses` fa 301 verso `/collections`.
- Le descrizioni NON sono tutte 100–530 parole: vanno da 0 (`nutrigenomica-nutrigenetica-ed-epigenetica`, descrizione vuota) e 35 (`apparato-digerente`) a 421 (`ritmi-circadiani-e-metabolismo`). Nessun grassetto/elenco nelle descrizioni: solo testo con `<br>` e a-capo.
- Il pulsante WhatsApp esiste in tutti i corsi, ma il testo precompilato corrisponde al titolo solo in 18/27 corsi: 9 corsi non corrispondono: 8 usano "…sul corso NUTRIZIONE E DIETETICA CANE E GATTO" senza virgolette (copia-incolla, anche su corsi non veterinari), 1 usa il titolo abbreviato "NUTRIGENOMICA". Numero sempre 393394846020. Etichette pulsante: "Clicca per contattarci su WhatsApp", "☎ Clicca per contattarci su WhatsApp", "Clicca per contattarci su Whatsapp".
- Il titolo nella pagina corso spesso differisce dal titolo della card in catalogo (5 casi: es. card "ALIMENTAZIONE NEL CANE & GATTO COMPLETO" → pagina "CORSO NUTRIZIONE E DIETETICA APPLICATA NEL CANE E NEL GATTO"; "- 20 CREDITI"/"- 30 CREDITI" solo nella card). Anche i `<title>` SEO sono spesso copiati da altri corsi (es. METABOLISMO ha `<title>` "NUTRIZIONE E DIETETICA APPLICATA").
- Patrocini: solo 3 corsi (METABOLISMO, ALIMENTAZIONE NEL CANE & GATTO COMPLETO = 7 loghi ciascuno; BUNDLE = 2 banner compositi con 11 loghi). Intestazione "Con il patrocinio di" / "Con il Patrocinio di:".
- Testimonianze nelle pagine corso: solo 5 corsi (2 cane/gatto con 6 ciascuno, 3 fitoterapia con 2 ciascuno); sono le stesse testimonianze ripetute tra corsi.
- FAQ: la domanda "per chi" è formulata in due modi ("Per chi è adatto questo corso?" in 20 corsi, "Per chi sono adatti i nostri corsi?" in 7 corsi: metabolismo-nutrizione-e-performance-nel-cane-sportivo-e-nel-cavallo-atleta, alimentazione-nel-gatto-completo, privato-corso-cane-e-gatto-230, dieta-chetogenica, microbiota, bundle-microbiota-ed-epigenetica-nell-alimentazione-del-cane-e-del-gatto, caneegatto2); la risposta ha solo 2 varianti (con o senza "Veterinari,"). La FAQ "È possibile pagare con Bonifico Bancario?" ha 6 risposte diverse; in 8 corsi la risposta pubblica IBAN e intestatario (persona fisica). 3 corsi hanno una terza FAQ "É possibile pagare a rate?" (dieta-chetogenica, bundle-microbiota-ed-epigenetica-nell-alimentazione-del-cane-e-del-gatto, caneegatto2). Sottotitolo FAQ sempre "Per qualsiasi informazione inviare una mail: responsabile@corsidiformazionecm.it".
- Contatti: l'email della segreteria è info@corsidiformazionecm.it ("eMail Segreteria Organizzativa") e quella studenti responsabile@corsidiformazionecm.it ("eMail Supporto Studenti"); WhatsApp "Cell. 339 4846020" è solo testo (nessun link). NON c'è un form sulla pagina: "Contatta ora" porta a un Google Form (forms.gle/PRtiYheGg1FAqnKe8) con campi Nome*, Cognome*, Email*, Numero di telefono*, Richiesta crediti ECM, Vorresti partecipare ai corsi in presenza o online?, Messaggio.
- Footer: "Termini e Condizioni" punta a corsidiformazionecm.thinkific.com/pages/terms (301 → /pages/terms, che contiene solo un link al PDF Termini_e_Condizioni.pdf su CloudFront); "Cookie & Privacy Policy" è un PDF su Google Drive. Nessun indirizzo/ragione sociale: solo "© Copyright Corsi di Formazionecm 2022 - PARTITA IVA: 02297020469". Etichetta screen-reader del social "Facebooke" (refuso).
- Newsletter: form nativo Thinkific (POST /tenant_leads), non un servizio esterno. Campi: email (obbligatoria, placeholder "Digita il tuo indirizzo e-mail") + checkbox obbligatoria "Acconsento al trattamento dei miei dati e alla ricezione di e-mail"; pulsante "Invia".
- Home: nessuna sezione numeri/statistiche e nessun logo patrocinio. Sezioni: gallery (immagine hero "homepage claudia"), "Percorsi di Formazione" (15 corsi in evidenza + pulsante "Vedi tutti i Corsi"), "Testimonianze e Recensioni" (10 recensioni, nessun rating, avatar = logo), Newsletter.
- Pagina testimonianze: sales.corsidiformazionecm.it è un funnel **GoHighLevel** (header `x-ghl-*`, asset su assets.cdn.filesafe.space), non Thinkific. Contiene 12 recensioni testuali uniche (5 stelle, ripetute nel marquee: 42 card nel DOM), 1 citazione (Marco Neri) con video, 9 video-testimonianze Vimeo, più una sezione Chi siamo con statistiche (200+ ore di corsi, 14 docenti esperti, 10k+ corsisti), Mission/Vision riscritte, citazione della fondatrice Dott.ssa Claudia Petrazzuolo e un elenco di 26 docenti (8 in più rispetto a /pages/docenti).
- Docenti: 18 su /pages/docenti, tutti con foto e pulsante CV ("VEDI IL CV" per i primi 12, "Vedi il CV" per gli ultimi 6). 16 CV sono PDF su Google Drive, 2 sono documenti Google Docs (Candellone, Aulicino) esportati in PDF. Titoli "Dottor/Dottoressa" anche per professori ordinari (sulla pagina vendite gli stessi sono "Prof.").
- Pagine extra non linkate dal menu ma in sitemap: `/pages/nutrizione-e-dietetica-applicata-nel-cane-e-nel-gatto` (landing vecchia con brochure), `/pages/offerta-corso-allergie-e-intolleranze-200-coupon-sconto` (pulsante "Acquista €49" → carrello Thinkific, non cliccato), `/pages/richiesta-informazioni-ecm` (banner segnaposto "Add a concise subheading…"), `/pages/privacy` (vuota), `/pages/terms`.
- Nessun prezzo nelle pagine corso, nessun pulsante di acquisto/iscrizione (solo WhatsApp + brochure). Unico prezzo trovato: €49 nella pagina offerta.
- Header: logo + TUTTI I CORSI, CONTATTI, DOCENTI, TESTIMONIANZE (apre la pagina vendite in nuova scheda) + ACCEDI (`/users/sign_in`). Le voci sono in maiuscolo via CSS (sorgente: "Tutti i corsi", "Contatti", "DOCENTI", "TESTIMONIANZE", "Accedi"). "CHI SIAMO" è solo nel footer.
- Cookie banner iubenda (siteId 2782689, cookiePolicyId 13950599) con pulsanti Rifiuta/Accetta/"Scopri di più e personalizza".

## 6. Docenti

Intro pagina: **Docenti** — "Ecco qui sotto la lista dei docenti con relativi CV relatori dei nostri corsi"

| # | Intestazione esatta | Nome | Titolo | Parole bio | Foto | CV | Tipo CV | Scaricabile |
|---|---|---|---|---|---|---|---|---|
| 1 | Dottoressa Grazia Angrisani | Grazia Angrisani | Dottoressa | 23 | ✓ 600×502 | 3 pp., 274 KB | drive-pdf | ✓ |
| 2 | Dottor Nino Carlo Battistini | Nino Carlo Battistini | Dottor | 24 | ✓ 600×502 | 3 pp., 242 KB | drive-pdf | ✓ |
| 3 | Dottor Benvenuto Cestaro | Benvenuto Cestaro | Dottor | 28 | ✓ 600×502 | 2 pp., 278 KB | drive-pdf | ✓ |
| 4 | Dottor Giancarlo Demontis | Giancarlo Demontis | Dottor | 20 | ✓ 600×502 | 2 pp., 134 KB | drive-pdf | ✓ |
| 5 | Dottor Bergamaschi Gianguglielmo | Bergamaschi Gianguglielmo | Dottor | 23 | ✓ 600×502 | 3 pp., 364 KB | drive-pdf | ✓ |
| 6 | Dottor Giovanni Battista Gidaro | Giovanni Battista Gidaro | Dottor | 40 | ✓ 600×502 | 3 pp., 474 KB | drive-pdf | ✓ |
| 7 | Dottor Marco Domenicali | Marco Domenicali | Dottor | 40 | ✓ 600×502 | 4 pp., 228 KB | drive-pdf | ✓ |
| 8 | Dottoressa Maria Grazia Spalluto | Maria Grazia Spalluto | Dottoressa | 18 | ✓ 600×502 | 3 pp., 460 KB | drive-pdf | ✓ |
| 9 | Dottor Mauro Favruzzo | Mauro Favruzzo | Dottor | 21 | ✓ 600×502 | 5 pp., 91 KB | drive-pdf | ✓ |
| 10 | Dottoressa Milza Patrizia | Milza Patrizia | Dottoressa | 52 | ✓ 600×502 | 2 pp., 123 KB | drive-pdf | ✓ |
| 11 | Dottor Vincenzo Calderone | Vincenzo Calderone | Dottor | 96 | ✓ 600×502 | 5 pp., 141 KB | drive-pdf | ✓ |
| 12 | Dottor Samir Sukkar | Samir Sukkar | Dottor | 7 | ✓ 600×502 | 4 pp., 201 KB | drive-pdf | ✓ |
| 13 | Dottor Spagnulo Stefano | Spagnulo Stefano | Dottor | 42 | ✓ 800×670 | 20 pp., 706 KB | drive-pdf | ✓ |
| 14 | Dottoressa Giulia Sabato | Giulia Sabato | Dottoressa | 8 | ✓ 3669×3072 | 11 pp., 327 KB | drive-pdf | ✓ |
| 15 | Dottor Alexander Bertuccioli | Alexander Bertuccioli | Dottor | 68 | ✓ 1219×1019 | 7 pp., 443 KB | drive-pdf | ✓ |
| 16 | Dottor Loprete Fortunato | Loprete Fortunato | Dottor | 57 | ✓ 1219×1019 | 5 pp., 1.3 MB | drive-pdf | ✓ |
| 17 | Dottoressa Alessia Candellone | Alessia Candellone | Dottoressa | 181 | ✓ 500×418 | 3 pp., 77 KB | google-doc | ✓ |
| 18 | Dottor Stefano Aulicino | Stefano Aulicino | Dottor | 123 | ✓ 500×418 | 3 pp., 111 KB | google-doc | ✓ |

Confronto con l'elenco docenti della pagina vendite (26 nomi): tutti i 18 docenti di /pages/docenti compaiono anche lì; solo sulla pagina vendite: Prof. Marco Biagi, Prof.ssa Monica Montopoli, Prof.ssa Lara Testai, Prof.ssa Elisabetta Miraldi, Dott.ssa Silvia Tempucci, Prof.ssa Giuseppina Menduno, Dott.ssa Rita Tanas, Prof. Pietro Morini.

Nota: i nomi "Dottor Bergamaschi Gianguglielmo", "Dottoressa Milza Patrizia", "Dottor Spagnulo Stefano", "Dottor Loprete Fortunato" sono scritti cognome-nome sul sito (mantenuti così).

## 7. Testimonianze

| Fonte | Numero |
|---|---|
| Pagina vendite GHL — recensioni testuali uniche | 12 (+1 citazione Marco Neri) |
| Pagina vendite — card nel DOM (marquee duplicato) | 42 |
| Pagina vendite — video Vimeo | 9 (Claudia Reel 2 ottobre; reel 6 dicembre Claudia; reel Claudia 18 settembre; Recensione microbiota 1; Recensione microbiota 2; Recensione microbiota 3; Recensione microbiota 4; Recensione microbiota 5; Recensione microbiota 6) |
| Home ("Testimonianze e Recensioni") | 10 |
| Pagine corso | 18 voci (8 testi distinti) |
| **Uniche dopo deduplica** | **29** |
| Presenti in più fonti | 8 (sales∩home: 0) |

Le recensioni della pagina vendite hanno tutte 5 stelle; nessuna data. Le 10 recensioni della home non hanno rating né data, e nessuna coincide con quelle della pagina vendite. Clizia D’Angelo e Francesca Morganti compaiono sia sulla pagina vendite sia nelle pagine corso cane/gatto (testo leggermente diverso: conservato per fonte in `variants`). Home: il nome "Francesco L.Marco " è così nel sorgente (probabile refuso).

## 8. Informazioni globali

| Voce | Valore |
|---|---|
| eMail Segreteria Organizzativa | info@corsidiformazionecm.it |
| eMail Supporto Studenti | responsabile@corsidiformazionecm.it |
| Email nelle FAQ dei corsi | responsabile@corsidiformazionecm.it |
| WhatsApp | Cell. 339 4846020 → link usato nei corsi `https://wa.me/393394846020?text=…` |
| Telefono/indirizzo | nessuno oltre al cellulare WhatsApp |
| Social | Instagram https://www.instagram.com/corsidiformazionecm/ · Facebook https://www.facebook.com/corsidiformazionecm · YouTube https://www.youtube.com/channel/UCooF9-KhcMD6aNzHepZZSNA?sub_confirmation=1 |
| P.IVA | 02297020469 ("© Copyright Corsi di Formazionecm 2022 - PARTITA IVA: 02297020469") |
| Login | https://www.corsidiformazionecm.it/users/sign_in (etichetta "ACCEDI") |
| Termini e Condizioni | https://corsidiformazionecm.thinkific.com/pages/terms → https://www.corsidiformazionecm.it/pages/terms; PDF: https://d2saw6je89goi1.cloudfront.net/uploads/digital_asset/file/1072338/Termini_e_Condizioni.pdf (8 pp., scaricato in `data/scrape/assets/legal/termini-e-condizioni.pdf`) |
| Cookie & Privacy Policy | https://drive.google.com/file/d/1whvBhSjjck-_1FK-CixobW-uXPFI-G3i/view?usp=sharing (8 pp., `data/scrape/assets/legal/cookie-privacy-policy.pdf`) |
| Newsletter | Thinkific nativo (lead capture): POST /tenant_leads con data-remote (Rails UJS) — i contatti finiscono nella sezione Leads/Marketing di Thinkific; nessun Mailchimp/Brevo/ActiveCampaign rilevato nel markup. |
| Testo consenso newsletter | "Acconsento al trattamento dei miei dati e alla ricezione di e-mail" |
| Cookie banner | iubenda (Rifiuta/Accetta) |
| Logo | `data/scrape/assets/brand/logo-header.png`, `data/scrape/assets/brand/logo-icon-apple-touch.png`, `data/scrape/assets/brand/logo-iubenda-banner.png` (solo PNG, nessun SVG; header 4956×784) |
| Favicon | `data/scrape/assets/brand/favicon.png` (PNG 1029×1029 servito come /favicon.ico) |
| Colori | rosso brand #BE1522 (link menu/footer, bordo pulsante secondario, iubenda); testo #212326; titoli #000000; sfondo header/footer #F2F2F2; sfondo sezioni #F1F1F1; pulsante primario (WhatsApp) sfondo #1E1C27 testo bianco, radius 100px; badge card nero (testo bianco, radius 4px); sfondo rosa #FBE4E7 in alcune aree della home. Logo (campionato): grigio scuro ≈#333333 e rosso ≈#BB1329 |
| Font | Titoli "Libre Baskerville" 400 (h2 36px, card 20px, testimonianze 22px); testo "Source Sans Pro" 18px (menu 15.75px 500 uppercase); icone Font Awesome 4.7 |

## 9. Refusi evidenti nei testi dei corsi (NON corretti nei dati)

| Corso | Campo | Testo esatto | Suggerimento | Verificato nel dato |
|---|---|---|---|---|
| alimentazione-nello-sportivo | description_text | `non posso essere viste` | non possono essere viste | ✓ |
| alimentazione-nello-sportivo | catalog_excerpt | `non posso essere viste` | non possono essere viste | ✓ |
| alimentazione-nello-sportivo | description_text | `ma bensì` | "ma" oppure "bensì" (pleonasmo) | ✓ |
| alimentazione-vegana-e-vegetariana | description_text | `discussine` | discussione | ✓ |
| alimentazione-vegana-e-vegetariana | description_text | `nel trattamento della patologie` | nel trattamento delle patologie | ✓ |
| alimentazione-vegana-e-vegetariana | description_text | `così detto` | cosiddetto | ✓ |
| alimentazione-vegana-e-vegetariana | description_text | `L’analisi delle posizioni delle più importanti società scientifiche di nutrizione permetteranno` | … permetterà (concordanza soggetto/verbo) | ✓ |
| alimentazione-vegana-e-vegetariana | description_text | `verranno presi in considerazione le modalità` | verranno prese in considerazione le modalità | ✓ |
| alimentazione-vegana-e-vegetariana | description_text | `L’obbiettivo` | L’obiettivo (variante meno comune) | ✓ |
| nutrizione-e-donna | description_text | `obbiettivo` | obiettivo (variante meno comune) | ✓ |
| nutrizione-e-donna | description_text | `dall’ azione` | dall’azione | ✓ |
| nutrizione-e-donna | description_text | `donna,ma` | donna, ma | ✓ |
| composizione-corporea-e-stato-di-nutrizione | faq | `si dovra' inviare` | si dovrà inviare | ✓ |
| curare-l-eccesso-ponderale-in-eta-evolutiva | description_text | `responsabiltà` | responsabilità | ✓ |
| dall-eubiosi-alla-disbiosi | subtitle | `macrobiota` | microbiota (probabile refuso) | ✓ |
| dieta-chetogenica | description_text | `alcuni un marker` | alcuni marker | ✓ |
| dieta-chetogenica | faq | `É possibile pagare a rate?` | È possibile pagare a rate? (accento) | ✓ |
| bundle-microbiota-ed-epigenetica-nell-alimentazione-del-cane-e-del-gatto | title | `NELL'ALIMENTAZIONEDEL` | NELL'ALIMENTAZIONE DEL | ✓ |
| bundle-microbiota-ed-epigenetica-nell-alimentazione-del-cane-e-del-gatto | faq | `É possibile pagare a rate?` | È possibile pagare a rate? | ✓ |
| caneegatto2 | faq | `É possibile pagare a rate?` | È possibile pagare a rate? | ✓ |
| counseling-nutrizionale | description_text | `esplicativo- affermativo` | esplicativo-affermativo | ✓ |
| nutrizione-clinica | description_text | `mirata al biotipologia` | mirata alla biotipologia | ✓ |
| l-invecchiamento-in-salute | description_text | `adeguati strategie` | adeguate strategie | ✓ |
| l-invecchiamento-in-salute | description_text | `ecc)` | ecc.) | ✓ |
| ritmi-circadiani-e-metabolismo | description_text | `de sincronizzato` | desincronizzato | ✓ |
| ritmi-circadiani-e-metabolismo | description_text | `E’ ormai noto` | È ormai noto | ✓ |
| ritmi-circadiani-e-metabolismo | description_text | `presente nel suprachiasmatico` | presente nel nucleo suprachiasmatico | ✓ |
| ritmi-circadiani-e-metabolismo | description_text | `la posologia di un principio dovrebbe essere condotto` | … dovrebbero essere condotti | ✓ |
| nuovo-approccio-alla-prevenzione-e-cura-dell-obesita-infantile | description_text | `È tempo di cambiare ormai la via` | È tempo di cambiare: ormai la via | ✓ |
| le-reazioni-avverse-agli-alimenti | description_text | `posseduto;` | il paragrafo successivo inizia in minuscolo ("attualmente") dopo un punto e virgola e una riga vuota | ✓ |
| nutrizione-e-dietetica-applicata | description_text | `La conoscenza per lavorare con successo, è lo scopo` | virgola tra soggetto e verbo | ✓ |
| pnl-applicata-alla-comunicazione-sanitaria | description_text | `efﬁcace` | contiene la legatura tipografica Unicode ﬁ (U+FB01) incollata da PDF: "efﬁcace", "ﬁgura", "ﬁno", "efﬁciente", "modiﬁcherà", "ﬁnale" — da normalizzare in fi/ff | ✓ |
| tutti | faq | `Laureandi nelle su dette categorie` | suddette (in tutte le FAQ "Per chi…") | ✓ |
| tutti | faq | `Sì, La ricevuta` | Sì, la ricevuta (maiuscola dopo virgola) | ✓ |

Caratteri particolari:

- corso l-insulino-resistenza-e-la-diabesita (descrizione): 1× LINE SEPARATOR U+2028
- corso pnl-applicata-alla-comunicazione-sanitaria (descrizione): 2× LINE SEPARATOR U+2028, 7× legatura fi U+FB01
- corso pnl-applicata-alla-comunicazione-sanitaria (estratto catalogo): 1× LINE SEPARATOR U+2028, 2× legatura fi U+FB01
- docente Dottor Alexander Bertuccioli (bio): 1× ZERO WIDTH SPACE U+200B
- docente Dottor Loprete Fortunato (bio): 1× ZERO WIDTH SPACE U+200B
- pagina chi-siamo: 4× NBSP U+00A0

## 10. Download falliti

Nessuno: tutte le cover, brochure (27/27, tutte `%PDF`), loghi, foto docenti e CV (18/18) sono stati scaricati. Le brochure sono state scaricate da `drive.google.com/uc?export=download` senza interstitial antivirus.

## 11. Elementi mancanti o ambigui

- `nutrigenomica-nutrigenetica-ed-epigenetica`: descrizione vuota sulla pagina e card di catalogo senza estratto → testo da recuperare dalla brochure.
- Corsi cane/gatto: `alimentazione-nel-gatto-completo` e `privato-corso-cane-e-gatto-230` hanno descrizione identica (professionale), ma la card del secondo ("COME ALIMENTI TUOI AMICI A 4 ZAMPE?") parla a proprietari/allevatori: contenuto da chiarire.
- Stato corsi: l'unico segnale esplicito è il badge "Pre-ordine disponibile ora!" (card `card--presell`) sul corso METABOLISMO; nessun "in arrivo"/"sold out". Non ci sono date, durate, prezzi o modalità (online/presenza) nelle pagine corso: sono solo nelle brochure.
- Crediti ECM: sul sito indicati solo in 2 titoli di card (20 e 30 crediti). Dalle brochure: 8 corsi con crediti indicati, 1 con "NO CREDITI ECM", 18 senza numero estraibile: 7 hanno l’etichetta "CREDITI ECM" ma il numero non è nel testo del PDF (probabilmente è grafico): alimentazione-nello-sportivo, dall-eubiosi-alla-disbiosi, l-insulino-resistenza-e-la-diabesita, le-reazioni-avverse-agli-alimenti, l-invecchiamento-in-salute, nuovo-approccio-alla-prevenzione-e-cura-dell-obesita-infantile, nutrizione-e-donna; nessuna menzione: alimentazione-vegana-e-vegetariana, composizione-corporea-e-stato-di-nutrizione, curare-l-eccesso-ponderale-in-eta-evolutiva, evidence-based-phytotherapy, fitoterapia-applicata, fitoterapia-sistemica, nutrizione-e-dietetica-applicata, bundle-microbiota-ed-epigenetica-nell-alimentazione-del-cane-e-del-gatto, nutrigenomica-nutrigenetica-ed-epigenetica, pnl-applicata-alla-comunicazione-sanitaria, ritmi-circadiani-e-metabolismo. Valori brochure da verificare a mano (estrazione automatica).
- Le immagini dei patrocini non hanno alt: i nomi sono stati letti visivamente dai loghi (vedi `tools/scrape/logo-names.json`).
- Gli a-capo nelle descrizioni/FAQ/bio non sono visibili sul sito attuale; `*_html` li interpreta (riga vuota = paragrafo; a-capo dopo punteggiatura = `<br>`; altrimenti spazio). Il sorgente esatto è in `description_raw_html`, `answer_raw`, `bio_raw`, `text_raw`; il testo esattamente come visualizzato oggi è in `description_rendered_text`.
- Copertine: le cover delle pagine corso sono già gli originali caricati (nessun parametro di resize su files.cdn.thinkific.com); per 6 corsi la cover è 1920×1080/2688×1520, per gli altri 1080×597 o 1200×675. Le immagini card del catalogo sono spesso versioni più piccole (800×450).
- Pagina /pages/richiesta-informazioni-ecm è un segnaposto con testo di default Thinkific in inglese; /pages/privacy è vuota (la privacy reale è il PDF su Drive).
- La pagina vendite GHL ha contenuti (Chi siamo esteso, statistiche, 8 docenti in più, video) che non esistono sul sito Thinkific: decidere se migrarli. Foto dei docenti GHL non scaricate (solo URL in `_sales_page.json`).

## 12. File prodotti

- `data/scrape/courses.json` — 27 corsi completi
- `data/scrape/site.json` — header, footer, brand, home, newsletter, contatti, legali
- `data/scrape/docenti.json` — 18 docenti + confronto pagina vendite
- `data/scrape/pages.json` — chi-siamo (mission/vision), contatti e altre /pages/*
- `data/scrape/testimonianze.json` — testimonianze deduplicate per fonte
- `data/scrape/logos_index.json` — loghi patrocinio unici (sha1)
- `data/scrape/_discovery.json` — catalogo, sitemap, crawl, link esterni
- `data/scrape/_sales_page.json` — dump strutturato della pagina vendite GHL
- `data/scrape/validation.json` — esito controlli
- `data/scrape/assets/` — covers (+cards), logos, brochures (PDF, esclusi da git), brochure-previews, brand, home, docenti, cv, legal, testimonianze, pages
- `docs/screenshot/original/` — home.png, catalogo-pagina-1.png, corso-lungo-metabolismo-cane-cavallo.png, corso-breve-apparato-digerente.png, chi-siamo.png, docenti.png, contatti.png, testimonianze-sales.png
