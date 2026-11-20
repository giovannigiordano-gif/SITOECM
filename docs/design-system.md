# Design system — "La Lente"

Concept del restyling di corsidiformazionecm.it. Documento di riferimento per tema WordPress `ecm`, immagini e motion.

> **Lettura del brief.** Sito brand per un ente ECM rivolto a professionisti sanitari italiani; linguaggio scientifico-editoriale con motion da agenzia (livello Awwwards), realizzato come tema WordPress custom con CSS nativo, GSAP + ScrollTrigger e Lenis.
>
> **Dials (taste skill):** DESIGN_VARIANCE 8 · MOTION_INTENSITY 7 · VISUAL_DENSITY 4 (catalogo e schede sono più densi: 5).

---

## 1. Idea

Il logo esistente ha un solo elemento forte: **il cerchio rosso con la "e"**. Nel mondo di questo ente il cerchio è ovunque:

| Cerchio | Significato |
|---|---|
| il **piatto** | nutrizione umana e animale |
| la **capsula di Petri** | microbiota, genomica |
| il **campo del microscopio** | metodo scientifico, evidence based |
| la **cellula** | biologia, metabolismo |
| il **sigillo / timbro** | accreditamento ECM, patrocini |

"La Lente" trasforma il cerchio del logo nel dispositivo di tutto il sito: le immagini si aprono come una lente che mette a fuoco, le transizioni di pagina sono un cerchio che si espande dal punto toccato, i bottoni si riempiono con un disco, gli avatar dei docenti sono anelli tipografici. Un solo gesto forte, ripetuto con disciplina.

**Il memorabile è uno solo:** la lente della home che, scorrendo, si ingrandisce fino a riempire lo schermo e diventa il "vetrino" in cui scorrono le cinque aree tematiche.

### Perché non è un default di categoria
- Sanità → bianco + teal: rifiutato. E-learning → colori giocosi: rifiutato.
- Secondo livello ("sanità ma non teal" → crema + serif editoriale): rifiutato.
- La palette nasce dalla **colorazione ematossilina-eosina (H&E)**, la colorazione istologica che ogni medico, biologo e veterinario ha visto al microscopio: viola-inchiostro e rosa-rosso. Il rosso è già quello del logo. La stessa coppia di colori esiste nel cibo (barbabietola, melograno, cavolo rosso, mirtillo, basilico viola), quindi fotografia e interfaccia parlano la stessa lingua.

### Scena fisica e tema
Una nutrizionista legge descrizioni lunghe di giorno, in studio, vicino a una finestra, o in treno sul telefono. **Tema chiaro**, superfici porcellana fredde, testo inchiostro; blocchi inchiostro pieni per i momenti istituzionali (patrocini, newsletter, footer). Tema unico per tutto il sito: niente dark mode automatica, perché i loghi istituzionali dei patrocini e le copertine reali dei corsi sono progettati su fondo chiaro. Scelta documentata.

---

## 2. Colore

Strategia: **Committed**. L'inchiostro ematossilina porta il 30-40% della superficie (sezioni piene, footer, testo); il cremisi del logo è il segno (CTA, anelli, stati, focus); il porcellana è la superficie di lettura.

| Token | OKLCH | HEX | Ruolo |
|---|---|---|---|
| `--c-ink` · Ematossilina | `oklch(0.235 0.055 285)` | `#1c1937` | testo, sezioni piene |
| `--c-ink-2` | `oklch(0.32 0.07 285)` | `#302d55` | superfici scure secondarie, hover su ink |
| `--c-ink-soft` | `oklch(0.47 0.04 285)` | `#585871` | testo secondario (6.4:1 su paper) |
| `--c-crimson` · Eosina / logo | `oklch(0.512 0.198 25.4)` | `#be1522` | CTA, anello lente, accenti (5.9:1 su paper) |
| `--c-crimson-deep` | `oklch(0.42 0.165 22)` | `#92071f` | hover/pressed su cremisi |
| `--c-blush` · Eosina pallida | `oklch(0.935 0.028 15)` | `#fce2e3` | superfici calde, badge pre-ordine |
| `--c-paper` · Porcellana | `oklch(0.977 0.005 285)` | `#f7f7fb` | fondo pagina |
| `--c-paper-2` | `oklch(0.955 0.008 285)` | `#efeff5` | fondo sezioni alternate, campi |
| `--c-line` | `oklch(0.88 0.012 285)` | `#d6d6df` | filetti, bordi |
| `--c-on-ink` | `oklch(0.97 0.008 285)` | `#f3f2fa` | testo su inchiostro |

Regole:
- Mai `#000`/`#fff`: tutti i neutri sono tinti verso l'hue 285 dell'inchiostro.
- Un solo accento (cremisi) su tutto il sito. Il verde WhatsApp non entra nella palette: l'icona WhatsApp basta a riconoscere il canale.
- Gli stati non si leggono solo dal colore: ogni badge ha testo esplicito.
- Contrasti verificati: ink/paper 15.8:1, crimson/paper 5.9:1, ink-soft/paper 6.4:1, on-ink/ink 15:1.

---

## 3. Tipografia

Procedura impeccable: tre parole di voce → **"preciso, caldo, da laboratorio"**. Oggetti fisici: etichetta di un erbario, targhetta di uno strumento scientifico, frontespizio di un manuale universitario.

Riflessi scartati: Inter, Fraunces, Playfair, DM Sans (lista reflex-reject). Serif editoriale scartato (corsia satura).

**Una sola famiglia: Mona Sans (variabile, assi `wght` 200-900 e `wdth` 75-125)**, self-hosted in WOFF2. La personalità nasce dall'asse di larghezza:

| Ruolo | Larghezza | Peso | Note |
|---|---|---|---|
| Display (hero, titoli di sezione, titoli corso) | `font-stretch: 120%` (expanded) | 620-680 | tracking -0.035em, interlinea 0.98-1.02 |
| Titoli h3/h4 | 108% | 600 | tracking -0.015em |
| Corpo | 100% | 400 / 560 per grassetti | 17-19px, interlinea 1.62, misura max 66ch |
| Etichette, meta, bottoni | 112% | 600 | 13-15px, sentence case, tracking +0.01em |
| Numeri (crediti, contatori) | 125% | 700 | `font-variant-numeric: tabular-nums` |

Il gesto tipografico del sito: **i titoli "respirano"**, entrando da condensati (75%) a espansi (120%) mentre salgono dalla maschera di riga. È coerente col tema (metabolismo, respiro) e possibile solo con un font variabile.

Scala modulare (rapporto ~1.333, fluida con `clamp`):

```
--fs-xs   13px            meta, legali
--fs-sm   15px            etichette, bottoni
--fs-base 17 → 19px       corpo
--fs-lg   20 → 24px       lead, h4
--fs-xl   24 → 34px       h3
--fs-2xl  32 → 52px       h2
--fs-3xl  40 → 80px       titolo corso, titoli pagina
--fs-display 48 → 136px   hero home
```

Divieti: niente maiuscolo tracciato sopra ogni sezione (max un'etichetta ogni tre sezioni), niente parola singola colorata nel titolo, niente trattino lungo (em dash) nei testi di interfaccia.

---

## 4. Griglia e spazi

- Griglia a **12 colonne**, contenitore max 1480px, margini pagina `clamp(16px, 4.5vw, 72px)`, gutter `clamp(16px, 2vw, 32px)`.
- Mobile (< 768px): sempre una colonna, margini 16-20px; le composizioni asimmetriche collassano esplicitamente.
- Scala spazi base 4: `4 8 12 16 24 32 48 64 96 128 176`. Padding sezione `clamp(80px, 11vw, 176px)`, variato per ritmo (sezioni istituzionali più compatte, sezioni narrative più ariose).
- Allineamento: testo a sinistra; centratura solo per citazioni singole e per la CTA finale.

```
HOME, HERO (desktop)
┌──────────────────────────────────────────────────────────────┐
│ logo        Corsi  Docenti  Chi siamo  Testimonianze  Contatti  [Accedi] │
├──────────────────────────────────────────────────────────────┤
│ Formazione ECM                         ╭───────────╮          │
│ per chi si prende                     │  LENTE     │          │
│ cura della salute.                    │  (cerchio) │          │
│ sub ≤ 20 parole                        ╰───────────╯          │
│ [Esplora i corsi]  (Scrivici)                                 │
└──────────────────────────────────────────────────────────────┘
scroll → la lente si fissa e cresce fino a coprire lo schermo → aree tematiche

SCHEDA CORSO (desktop)
┌──────────────────────────────┬───────────────────────────────┐
│ badge stato · area           │  ┌─────────────────────────┐  │
│ TITOLO CORSO (3xl, expanded) │  │ copertina reale in       │  │
│ crediti · destinatari        │  │ cornice porcellana       │  │
│ [WhatsApp] [Brochure]        │  └─────────────────────────┘  │
├──────────────────────────────┴───────────────────────────────┤
│ Descrizione (66ch)                    │ aside sticky: brochure │
│                                       │ anteprima PDF, peso    │
├──────────────────────────────────────────────────────────────┤
│ ███ Patrocini: fascia inchiostro, loghi su tessere porcellana ███ │
│ Testimonianze · Per chi è adatto · FAQ · Corsi correlati      │
└──────────────────────────────────────────────────────────────┘
[barra CTA sticky: titolo breve | WhatsApp | Brochure]
```

---

## 5. Forme, elevazione, livelli

Regola dei raggi (documentata e unica):
- **Cerchio**: lente, avatar docenti, indicatori di stato, bottoni-icona.
- **Pillola** (`999px`): tutti i bottoni, i chip filtro, i badge.
- **22px**: cornici immagine, card corso, pannelli.
- **14px**: campi form.

Ombre tinte d'inchiostro, mai nere:
```
--shadow-1:    0 1px 2px oklch(0.235 0.055 285 / .06), 0 10px 30px -14px oklch(0.235 0.055 285 / .22)
--shadow-lift: 0 40px 80px -40px oklch(0.235 0.055 285 / .45)
```

Scala z-index: contenuto 0 · elementi rialzati 10 · header 100 · barra CTA 110 · menu mobile 200 · banner cookie 300 · tenda di transizione 400.

---

## 6. Componenti

- **Header**: 72px, logo a sinistra, navigazione su una riga, "Accedi" come bottone pillola contornato (icona ingresso). Si nasconde scorrendo verso il basso e ricompare verso l'alto; sopra la hero è trasparente, poi porcellana. Mobile: logo + Accedi + bottone menu; menu a pannello pieno inchiostro con voci grandi.
- **Bottoni**: primario cremisi (testo on-ink), secondario contornato inchiostro, terziario link sottolineato. Hover: un disco si espande dal lato d'ingresso del puntatore; press: scala 0.98. Etichette 1-3 parole, mai su due righe.
- **Card corso**: copertina reale 16:9 (le copertine originali hanno formati e stili eterogenei: la cornice e il passe-partout porcellana le uniformano), badge stato, area, titolo (expanded 108%), estratto 2-3 righe, freccia in disco. Hover: zoom 1.04 della copertina, anello cremisi, disco freccia che cresce.
- **Badge stato**: Disponibile (inchiostro), Pre-ordine (blush + testo cremisi), In arrivo (contorno inchiostro), Concluso (grigio, barrato no). Sempre con testo.
- **Chip filtro**: pillole; l'attivo ha un indicatore che scivola (Flip).
- **Accordion FAQ**: `<details>`/`<button aria-expanded>`, apertura via `grid-template-rows 0fr → 1fr`, icona + che ruota in −.
- **Blocco brochure**: anteprima della prima pagina del PDF (renderizzata dal PDF reale) inclinata in una cornice, peso file e pagine, bottone Scarica.
- **Fascia patrocini**: sfondo inchiostro, loghi su tessere porcellana di altezza ottica uniforme, scala di grigi → colore all'hover/focus.
- **Avatar tipografico docente**: iniziali in Mona Sans 125%/700 dentro doppio anello (citazione del logo), quando la foto manca.
- **Barra CTA sticky**: desktop flottante in basso al centro (titolo breve + WhatsApp + Brochure), compare dopo la hero e si ritira vicino al footer; mobile barra piena a due bottoni con safe-area.
- **Form**: etichetta sopra, aiuto sotto, errore sotto in cremisi con icona, stato di invio, successo inline. Consenso privacy obbligatorio con link.

Icone: **Phosphor** (peso regular, 1.5px a 24px), sprite SVG inline. Nessuna emoji come icona.

---

## 7. Motion

Personalità **Premium**: nessun rimbalzo, curve esponenziali, durate generose ma mai bloccanti.

| Token | Valore | Uso |
|---|---|---|
| `--ease-out` (firma) | `cubic-bezier(0.16, 1, 0.3, 1)` | 80% delle animazioni, entrate |
| `--ease-in` | `cubic-bezier(0.7, 0, 0.84, 0)` | uscite |
| `--ease-inout` | `cubic-bezier(0.65, 0, 0.35, 1)` | movimenti sullo schermo, transizioni di pagina |
| `--dur-1` | 160ms | hover, press |
| `--dur-2` | 420ms | stati UI, accordion, filtri |
| `--dur-3` | 900ms | reveal |
| `--dur-4` | 1400ms | coreografia della hero |

Pattern d'entrata unico: **riga che sale dalla maschera + respiro della larghezza** per i titoli; **apertura circolare** (`clip-path: circle()`) per le immagini; dissolvenza + 16px per i paragrafi.

Inventario (ogni animazione ha un motivo):
1. **Caricamento home** (storytelling): l'anello cremisi si disegna, la lente si apre sull'immagine, il titolo respira riga per riga. ≤ 1.6s, contenuto già nel DOM (SEO e no-JS ok).
2. **Scroll home** (storytelling): la lente si fissa e cresce fino a riempire il viewport, poi diventa il vetrino delle 5 aree: a ogni area l'immagine cambia con una tendina circolare e il nome dell'area respira.
3. **Contatori** (gerarchia): numeri reali (corsi, docenti, ordini patrocinanti) contano all'ingresso, dentro una frase, non come "hero metric".
4. **Marquee patrocini** (ampiezza): l'unico marquee del sito; pausa a hover/focus.
5. **Card e bottoni** (feedback): disco che si espande, zoom copertina, anello.
6. **Filtri catalogo** (cambio di stato): GSAP Flip riordina la griglia, i corsi esclusi escono rimpicciolendo, URL aggiornato senza reload.
7. **Transizioni di pagina** (continuità): View Transitions API cross-document; un cerchio cremisi si espande dal punto del click e rivela la nuova pagina. Senza supporto: caricamento normale.
8. **Copertina corso** (gerarchia): si apre dalla lente e ha un leggero parallasse in scroll.
9. **Barra CTA sticky** (orientamento): entra dal basso quando la hero esce.
10. **Smooth scroll** Lenis su desktop con puntatore fine; scroll nativo su touch.

`prefers-reduced-motion: reduce`: niente Lenis, niente pin/scrub, niente transizioni di pagina, contatori al valore finale, reveal istantanei, marquee statico. Nessuna animazione blocca input o lettura; le CTA sono sempre visibili senza attendere animazioni.

Tecnica: solo `transform`, `opacity`, `clip-path`, `font-variation-settings`; GSAP + ScrollTrigger + Flip + Lenis caricati localmente dal tema; nessun listener `scroll` manuale; `gsap.matchMedia()` per separare desktop/mobile/reduced.

---

## 8. Direzione delle immagini (Higgsfield)

**"Specimen still life."** Nature morte scientifiche fotografate dall'alto, come reperti su un tavolo di laboratorio luminoso.

- Superficie: carta/porcellana grigio freddo chiarissimo (mai crema).
- Luce: diurna diffusa da nord, ombre nette e delicate.
- Palette dentro l'immagine: cremisi (melograno, barbabietola, ribes, ibisco), viola (cavolo rosso, mirtillo, basilico viola, salvia), porcellana; verde solo come contrappunto naturale.
- Composizione circolare e centrata (piatto, capsula di Petri, corona d'erbe, gatto acciambellato) con molto spazio negativo, così l'immagine vive dentro la lente.
- Ottica: medio formato, macro 100mm, f/8, nitidissima. Niente testo, loghi, mani in primo piano, camici, sorrisi stock. Nessun ritratto inventato di persone reali (i docenti hanno foto vere o avatar tipografico).

Set previsto:
| Immagine | Soggetto | Uso |
|---|---|---|
| lens-nutrizione-umana | piatto di porcellana con melograno, barbabietola, cavolo rosso, fico | hero, area |
| lens-microbiota | capsula di Petri con colture cremisi/viola | hero, area |
| lens-fitoterapia | corona d'erbe officinali (echinacea, salvia viola, lavanda, biancospino) | hero, area |
| lens-nutrizione-animale | gatto tigrato acciambellato a cerchio, vista dall'alto | hero, area |
| lens-comunicazione | due tazze di ibisco una di fronte all'altra, taccuino e penna | hero, area |
| chi-siamo | microscopio, ciotola di frutti rossi, taccuino, luce del mattino | chi siamo |
| docenti | pila di testi scientifici e riviste in cremisi/viola, occhiali | pagina docenti |
| texture-he | astratto tipo vetrino istologico H&E, rosa e viola | newsletter, CTA |
| empty-plate | piatto vuoto di porcellana | pagina 404, stati vuoti |

Ottimizzazione: export AVIF + WebP a 480/800/1200/1600/2000px, `<picture>` con `srcset`/`sizes`, `loading="lazy"` sotto la piega, `fetchpriority="high"` sull'immagine LCP.

Copertine dei corsi e loghi dei patrocini: **quelli reali**, mai rigenerati.

---

## 9. Accessibilità e qualità (sintesi)

WCAG 2.2 AA: contrasti verificati, focus visibile (anello cremisi 2px, offset 3px), skip link, landmark e titoli in ordine, target ≥ 44px (24px minimo WCAG 2.5.8), etichette form vere, errori annunciati (`aria-live`), menu e accordion con stati ARIA, alternative testuali scritte, nessuna informazione solo a colore, motion ridotto rispettato, zoom non bloccato.
