# Immagini art-directed (Higgsfield)

Stato al 20/11/2026: **da generare**. Higgsfield ha risposto per tutta la sessione "daily generation limit for your grace period" su ogni modello (`gpt_image_2_6`, `nano_banana_2_1`, `soul_2`), quindi nessuna immagine è stata prodotta. Il tema mostra al loro posto un segnaposto grafico coerente (cerchio del marchio), mai un riquadro vuoto.

## Come completarle (circa 15 minuti)

1. Generare con Higgsfield i 9 prompt qui sotto (formato 1:1 per le lenti, 21:9 per `docenti` e `texture-he`, 2K). Modello consigliato: `gpt_image_2_6` qualità `high` (foto realistica) oppure `nano_banana_2_1`. Scegliere tra 2-4 varianti la più nitida e coerente con la palette (cremisi, viola, porcellana).
2. Salvare ogni immagine scelta in `tools/images/src/<chiave>.png` (le chiavi sono quelle dei titoli qui sotto).
3. `node tools/build/images.mjs` crea le versioni AVIF + WebP (480-2000 px) in `wp-content/themes/ecm/assets/img/` e il `manifest.json` con i testi alternativi (da `images.json`).
4. `bash tools/deploy/pack.sh theme` e distribuire il tema (vedi `tools/deploy/README.md`). Nessuna modifica ai template: le immagini compaiono da sole al posto dei segnaposto.

Stile comune da aggiungere a ogni prompt: *top-down editorial still life, pale cool grey seamless paper surface, soft diffused north-window daylight, crisp delicate shadows, medium format, 100mm macro, f/8, ultra sharp, restricted palette of crimson red, deep violet and porcelain white with small touches of natural green, generous negative space, circular centered composition, no text, no logos, no hands*.

## Prompt

### lens-nutrizione-umana (1:1)
A single round white porcelain plate centered on the surface; on the plate a precise specimen-like arrangement of whole foods: a halved pomegranate with glistening crimson seeds, thin beetroot slices showing concentric rings, a red cabbage cross-section revealing deep violet veins, a few blueberries, a halved fig, one sprig of purple basil.

### lens-microbiota (1:1)
A single round glass petri dish centered on the surface; inside, bacterial cultures grown on translucent agar in organic branching and dotted patterns, colonies in crimson red, deep violet and soft pink like a delicate abstract painting, subtle light reflections on the glass rim. Scientific laboratory photography with an art-gallery sensibility.

### lens-fitoterapia (1:1)
Fresh medicinal plants arranged in a perfect circular wreath like a museum herbarium specimen, empty center: echinacea flowers with magenta petals, purple sage leaves, lavender sprigs, rosemary, hawthorn branches with small red berries, a few pale dried roots and chamomile heads.

### lens-nutrizione-animale (1:1)
A calm tabby cat curled up asleep in an almost perfect circle on a pale cool grey linen surface, fine fur detail, serene, high-end editorial photography.

### lens-comunicazione (1:1)
Two white porcelain cups of ruby red hibiscus infusion facing each other as if two people were in conversation, a thin wisp of steam, between them an open notebook with blank pages and a dark fountain pen, one small sprig of purple basil.

### chi-siamo (1:1)
A modern laboratory microscope on a bright white desk next to a porcelain bowl of red currants and pomegranate seeds and an open notebook with blank pages, morning light, calm and precise.

### docenti (21:9)
A long table seen from above with a few scientific textbooks and journals with crimson and deep violet covers, a pair of reading glasses and a fountain pen, soft morning light, lots of negative space.

### texture-he (21:9)
Abstract macro of a histology slide stained with hematoxylin and eosin: soft pink and deep violet cellular patterns, out-of-focus edges, painterly and calm, suitable as a dark background texture.

### empty-plate (1:1)
A single empty round white porcelain plate centered on the surface, perfectly clean, quiet and minimal.
