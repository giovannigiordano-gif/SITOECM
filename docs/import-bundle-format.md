# Formato del bundle di importazione (`ecm-import`)

Generato in locale da `tools/migrate/build-bundle.mjs` a partire da `data/scrape/`, caricato sul server in `wp-content/uploads/ecm-import/` e letto da `ECM_Importer`. Ogni riesecuzione aggiorna i contenuti esistenti invece di duplicarli.

```
ecm-import/
  data.json
  assets/covers/…  assets/brochures/…  assets/previews/…  assets/logos/…  assets/docenti/…  assets/cv/…
```

## `data.json`

```jsonc
{
  "version": 1,
  "generated": "2026-11-20T12:00:00Z",

  "options": { "login_url": "…", "email_segreteria": "…", "...": "chiavi di ecm_options (vedi contratto)" },

  "terms": {
    "area_tematica": [ { "slug": "nutrizione-umana", "name": "Nutrizione umana", "description": "…", "short": "…", "order": 1, "image_key": "nutrizione-umana" } ],
    "destinatario":  [ { "slug": "biologi-nutrizionisti", "name": "Biologi nutrizionisti", "order": 1 } ]
  },

  // Ogni file da caricare nella Media Library. "key" è l'identificativo stabile (idempotenza: meta _ecm_import_key).
  "media": [
    { "key": "cover-microbiota", "file": "assets/covers/microbiota.jpg", "title": "Microbiota", "alt": "Copertina del corso Microbiota", "kind": "cover" },
    { "key": "brochure-microbiota", "file": "assets/brochures/microbiota.pdf", "title": "Brochure Microbiota", "kind": "brochure", "pages": 6 },
    { "key": "preview-microbiota", "file": "assets/previews/microbiota.jpg", "title": "Anteprima brochure Microbiota", "alt": "Prima pagina della brochure", "kind": "preview" },
    { "key": "logo-ordine-x", "file": "assets/logos/ordine-x.png", "title": "Ordine X", "alt": "Logo Ordine X", "kind": "logo" }
  ],

  "courses": [
    {
      "source_slug": "microbiota",          // slug originale Thinkific (idempotenza)
      "slug": "microbiota",                 // slug WordPress pulito
      "old_paths": ["/courses/microbiota"],
      "title": "…", "content_html": "…", "excerpt": "",
      "cover": "cover-microbiota", "brochure": "brochure-microbiota", "brochure_preview": "preview-microbiota",
      "patrocini": [ { "media": "logo-ordine-x", "name": "Ordine X" } ],
      "testimonianze": [ { "name": "…", "meta": "…", "text": "…" } ],
      "per_chi_html": "…",
      "faq": [ { "q": "…", "a_html": "…" } ],   // solo FAQ specifiche (le comuni sono nelle opzioni)
      "stato": "disponibile", "crediti": "", "link_iscrizione": "", "in_evidenza": false,
      "menu_order": 1,
      "areas": ["microbiota-genomica"], "destinatari": ["biologi-nutrizionisti", "medici"],
      "seo_title": "", "seo_desc": ""
    }
  ],

  "docenti": [ { "key": "nome-cognome", "name": "…", "titolo": "…", "bio_html": "…", "photo": "docente-nome-cognome" , "cv": "cv-nome-cognome", "cv_url": "…", "menu_order": 1 } ],

  "testimonianze": [ { "key": "hash", "name": "…", "text": "…", "corso_slug": "microbiota", "corso_label": "…", "fonte": "home", "rating": 5, "menu_order": 1 } ],

  "pages": [ { "slug": "chi-siamo", "title": "Chi siamo", "template": "", "content_html": "…", "menu_order": 2, "seo_desc": "…" } ],

  "menus": {
    "primary": [ { "label": "Corsi", "type": "archive", "target": "corso" }, { "label": "Docenti", "type": "page", "target": "docenti" } ],
    "footer":  [ { "label": "Privacy", "type": "url", "target": "https://…" } ]
  },

  "redirects": [ { "from": "/pages/faq", "to": "/contatti/" } ]
}
```

Regole dell'importer:
- `media`: crea l'allegato solo se non esiste già un allegato con `_ecm_import_key` = key; i loghi (`kind: logo`) vengono rifilati dai margini bianchi/trasparenti con Imagick prima del caricamento, se disponibile.
- `courses`: cerca per `_ecm_source_slug`; aggiorna titolo, contenuto, meta, termini, immagine in evidenza; non tocca campi modificati a mano se viene passato `args['preserve_manual'] = true` (default false).
- `docenti`/`testimonianze`: cercano per `_ecm_source_key`.
- `pages`: cercano per slug; impostano la pagina "home" come front page statica (template `front-page.php` del tema) se presente.
- `menus`: crea/aggiorna i menu `Principale` e `Footer` e li assegna alle location del tema `primary` e `footer`.
- `verify`: restituisce conteggi e un elenco di problemi (corsi senza copertina/brochure, allegati mancanti).
