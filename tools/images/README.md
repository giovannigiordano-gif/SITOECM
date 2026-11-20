# Immagini del tema

Tutte le immagini del tema stanno in `wp-content/themes/ecm/assets/img/` (AVIF + WebP, 480-2560 px) con il
`manifest.json` letto da `ecm_img()`. Le sorgenti ad alta risoluzione vanno in `tools/images/src/` (non versionata:
si rigenera) e si ottimizzano con:

```sh
node tools/build/images.mjs     # crea AVIF/WebP + manifest da tools/images/src/ e images.json
bash tools/deploy/pack.sh theme # poi deploy (tools/deploy/README.md)
```

## Da dove vengono (stato al 20/11/2026)

La generazione AI non era disponibile: Higgsfield risponde ancora "daily generation limit for your grace period"
(anche con 1185 crediti in saldo) e su questo PC non c'è una chiave MuAPI (`MUAPI_API_KEY`). Le immagini sono quindi:

| Chiave | Origine | Uso |
|---|---|---|
| `hero-home`, `hero-home-m` | render 3D Blender, scena `home` (orizzontale e verticale) | hero della home |
| `hero-corsi` | render 3D, scena `corsi` | catalogo, 404, fallback |
| `hero-contatti` | render 3D, scena `contatti` | Contatti, pagine generiche |
| `lens-nutrizione-umana`, `lens-microbiota`, `lens-fitoterapia`, `lens-nutrizione-animale`, `lens-comunicazione` | render 3D, una scena per area | aree in home, pagine area, sfondo delle schede corso, newsletter |
| `hero-chi-siamo` | foto reale di un corso dal vivo (pagina testimonianze originale), ritagliata dentro la vecchia cornice grafica | Chi siamo |
| `hero-testimonianze` | foto della hero della pagina testimonianze originale | Testimonianze |
| `hero-docenti` | mosaico delle foto reali dei 18 docenti, senza la cornice rossa stampata sopra | Docenti (in bianco e nero, a colori dentro la lente) |

### Render 3D: "il mondo della lente"

Scena procedurale in `tools/render/lens_world.py` (Blender 5.x, Cycles su GPU): l'anello cremisi laccato del logo,
con il secondo anello sottile, sospeso nel buio viola-inchiostro e attraversato da un flusso di soggetti diversi per
sezione (globuli rossi, arilli di melograno, batteri, foglie e gocce d'olio essenziale, crocchette, anelli intrecciati).
Il soggetto sta nella metà destra: la metà sinistra resta in ombra per il titolo in basso a sinistra.

```sh
bash tools/render/render-all.sh                 # tutte le scene (circa 1 minuto l'una su RTX 5070 Ti)
bash tools/render/render-all.sh microbiota      # una sola scena
"/c/Program Files/Blender Foundation/Blender 5.2/blender.exe" -b -P tools/render/lens_world.py -- --scene home --preview --out /percorso/prova.png
```

### Foto reali

```sh
node tools/images/compose.mjs   # hero-chi-siamo, hero-testimonianze, hero-docenti da data/scrape/assets
```

## Sostituirle con fotografie generate (quando l'AI sarà disponibile)

Basta salvare in `tools/images/src/` un file con la stessa chiave (es. `hero-home.png`, 16:9, almeno 2560 px; per
`hero-home-m` 9:16) e rilanciare `images.mjs`: i template non cambiano. Direzione per i prompt: fotografia still life
cinematografica, chiaroscuro con una sola luce da destra in alto, fondo viola-inchiostro quasi nero, accenti cremisi,
bianco porcellana, soggetto nella metà destra e metà sinistra in ombra; niente testo, loghi, mani o volti riconoscibili.
