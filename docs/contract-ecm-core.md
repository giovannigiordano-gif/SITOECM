# Contratto tecnico `ecm-core` (plugin) ↔ `ecm` (tema)

Il plugin possiede i dati (CPT, tassonomie, meta, opzioni, redirect, SEO, form, importazione). Il tema possiede solo la presentazione e legge i dati **esclusivamente** tramite le funzioni helper qui sotto (con `function_exists` come guardia).

Prefisso: `ecm_` (funzioni), `ECM_` (classi), `_ecm_` (meta nascosti). Text domain: `ecm`. PHP 8.1+, WP 6.9+.

## CPT

| CPT | Slug URL | Pubblico | Supports | Editor |
|---|---|---|---|---|
| `corso` | singolo `/courses/{slug}`, archivio `/corsi/` (`has_archive => 'corsi'`) | sì | title, editor, thumbnail, excerpt, page-attributes, revisions | classico (Gutenberg disattivato) |
| `docente` | nessuna pagina singola (`publicly_queryable => false`) | no | title (nome), editor (bio), thumbnail (foto), page-attributes | classico |
| `testimonianza` | nessuna pagina singola | no | title (nome), editor (testo), page-attributes | classico |
| `ecm_richiesta` | privato (richieste dal modulo contatti) | no | title | solo lettura in admin |
| `ecm_iscritto` | privato (iscritti newsletter) | no | title (email) | solo lettura in admin |

Ordinamento manuale: `menu_order` ASC, poi titolo.

## Tassonomie (solo su `corso`)

- `area_tematica` — gerarchica, rewrite `area`, `show_in_rest`. Term meta: `_ecm_order` (int), `_ecm_image_key` (string, chiave immagine del tema, es. `nutrizione-umana`), `_ecm_short` (string, descrizione breve ≤ 160 caratteri). La descrizione lunga è la description del termine.
- `destinatario` — gerarchica (UI a checkbox), rewrite `destinatari`. Term meta `_ecm_order`.

## Meta `corso`

| Meta | Tipo | Note |
|---|---|---|
| `_ecm_brochure_id` | int (attachment PDF) | campo media "solo PDF", sostituibile |
| `_ecm_brochure_preview_id` | int (attachment immagine) | anteprima pag. 1; se la brochure viene sostituita e l'anteprima no, il plugin prova a usare l'anteprima PDF generata da WP |
| `_ecm_patrocini` | array di `{ image_id:int, name:string }` | ripetitore ordinabile |
| `_ecm_testimonianze` | array di `{ name:string, meta:string, text:string }` | ripetitore ordinabile |
| `_ecm_per_chi` | string HTML (kses post) | editor ridotto |
| `_ecm_faq` | array di `{ q:string, a:string HTML }` | solo FAQ specifiche del corso |
| `_ecm_stato` | `disponibile` · `preordine` · `in-arrivo` · `concluso` | default `disponibile` |
| `_ecm_crediti` | string (es. `20`, vuoto se assente) | |
| `_ecm_link_iscrizione` | url | vuoto di default |
| `_ecm_in_evidenza` | `'1'` o `''` | |
| `_ecm_old_paths` | array di string | path originali Thinkific (per 301) |
| `_ecm_source_slug` | string | chiave di idempotenza importazione |
| `_ecm_seo_title`, `_ecm_seo_desc` | string | override opzionali (anche su pagine) |

## Meta `docente`
`_ecm_titolo` (ruolo/qualifica), `_ecm_cv_id` (attachment), `_ecm_cv_url` (url, usato se non c'è file), `_ecm_source_key`.

## Meta `testimonianza`
`_ecm_corso_id` (int|0), `_ecm_corso_label` (string), `_ecm_fonte` (`home`|`testimonianze`|`entrambe`), `_ecm_rating` (int|''), `_ecm_source_key`.

## Opzioni globali — `get_option('ecm_options')` (array)

`login_url`, `email_segreteria`, `email_supporto`, `contact_recipient` (default = email_segreteria), `whatsapp_number` (formato internazionale solo cifre, es. `393394846020`), `whatsapp_display` (es. `339 484 6020`), `whatsapp_template` (default `Salve, vorrei avere maggiori informazioni sul corso "%s"`), `whatsapp_generic` (testo per WhatsApp senza corso), `faq_email_q`, `faq_email_a`, `faq_bonifico_q`, `faq_bonifico_a`, `instagram_url`, `facebook_url`, `youtube_url`, `piva`, `ragione_sociale`, `indirizzo`, `termini_url`, `privacy_url`, `cookie_url`, `newsletter_consent`, `contact_consent`, `newsletter_provider` (`none`|`brevo`|`mailchimp`), `brevo_api_key`, `brevo_list_id`, `mailchimp_api_key`, `mailchimp_list_id`, `analytics_head` (codice caricato solo dopo consenso statistiche), `hero_title`, `hero_text`, `newsletter_title`, `newsletter_text`, `cta_title`, `cta_text`.

Pagina admin "Impostazioni ECM" (menu di primo livello, icona `dashicons-welcome-learn-more`) a schede: Contatti & piattaforma · FAQ comuni · Social & legale · Home · Newsletter & cookie. Settings API, sanitizzazione per tipo.

## Funzioni helper (API per il tema)

```php
ecm_opt( string $key, $default = '' ): mixed
ecm_whatsapp_url( string $text = '' ): string         // https://wa.me/{numero}?text=...
ecm_course_whatsapp_url( int|WP_Post $post ): string   // template con il titolo del corso
ecm_stati(): array                                     // ['disponibile'=>'Disponibile', 'preordine'=>'Pre-ordine', 'in-arrivo'=>'In arrivo', 'concluso'=>'Concluso']
ecm_course( int|WP_Post|null $post = null ): array     // vedi forma sotto
ecm_courses( array $args = [] ): array                 // array di ecm_course(), ordinati; args: featured(bool), area(slug), exclude(int[]), limit(int)
ecm_areas(): array                                     // termini area ordinati per _ecm_order, con count, image_key, short, url (= /corsi/?area=slug)
ecm_destinatari(): array                               // termini destinatario ordinati, con count
ecm_docenti(): array                                   // [{id,name,titolo,bio_html,photo_id,cv_url,initials}]
ecm_testimonianze( array $args = [] ): array           // [{id,name,text,corso_title,corso_url,fonte,rating}] args: fonte, corso_id, limit
ecm_stats(): array                                     // ['corsi'=>int,'docenti'=>int,'patrocini_ordini'=>int (enti patrocinanti distinti), 'testimonianze'=>int]
ecm_human_filesize( int $bytes ): string               // "1,8 MB"
```

Forma di `ecm_course()`:

```php
[
  'id', 'title', 'url', 'slug',
  'cover_id' => int,                 // thumbnail
  'excerpt'  => string,              // excerpt manuale o prime ~32 parole della descrizione, senza HTML
  'content'  => string,              // HTML filtrato (the_content)
  'area'     => ?array {id,slug,name,image_key,url},   // area principale (prima per _ecm_order)
  'areas'    => array of same,
  'destinatari' => array of {id,slug,name},
  'stato'    => ['key'=>'preordine','label'=>'Pre-ordine'],
  'crediti'  => string,
  'brochure' => ?['id','url','bytes','size','pages'(int|null),'filename','preview_id'(int|0)],
  'patrocini'=> array of ['image_id','name','url'(full),'width','height'],
  'testimonianze' => array of ['name','meta','text'],
  'per_chi'  => string HTML,
  'faq'      => array of ['q','a'] // FAQ comuni (email, bonifico) dalle opzioni + _ecm_faq del corso; "Per chi è adatto" inclusa come prima voce se _ecm_per_chi non vuoto
  'whatsapp_url' => string,
  'link_iscrizione' => string,
  'in_evidenza' => bool,
]
```

## Redirect 301
- `/collections` e `/collections?page=N` e `/collections/*` → `/corsi/`
- `/pages/chi-siamo` → `/chi-siamo/`, `/pages/docenti` → `/docenti/`, `/pages/contatti` → `/contatti/`, `/pages/testimonianze` → `/testimonianze/`
- ogni valore di `_ecm_old_paths` (confronto su path decodificato, case-insensitive, con/senza slash finale) → permalink del corso
- tabella aggiuntiva nell'opzione `ecm_redirects` (array `from` → `to`) modificabile da admin (textarea "da → a" per riga)
Implementazione su `template_redirect` solo quando la richiesta sarebbe 404 (o prima del routing per i path `/pages/*` e `/collections*`).

## SEO
- `add_theme_support('title-tag')` è nel tema; il plugin filtra `pre_get_document_title` (override `_ecm_seo_title`) e stampa `<meta name="description">`, Open Graph/Twitter, JSON-LD `Organization` (tutte le pagine) e `Course` (singolo corso: name, description, url, image, provider Organization, `inLanguage: it`, `educationalCredentialAwarded` "Crediti ECM" se presenti) + `BreadcrumbList` sul corso.
- Si disattiva da solo se è attivo Yoast SEO, Rank Math o SEOPress.
- Sitemap core WP: includere `corso`, pagine, `area_tematica`; escludere utenti, `destinatario`, CPT non pubblici.

## Form (endpoint REST `ecm/v1`)
- `POST /ecm/v1/contact` — campi `nome`, `email`, `telefono` (opz.), `professione` (opz.), `corso` (opz.), `messaggio`, `privacy` (obbl.), honeypot `website` (deve essere vuoto), `_ts` (timestamp di rendering, invio < 3s = scarto), nonce `wp_rest`. Salva `ecm_richiesta`, invia `wp_mail` a `contact_recipient` con reply-to. Risposta JSON `{ok:bool, message:string, errors:{campo:messaggio}}`.
- `POST /ecm/v1/newsletter` — `email`, `privacy` (obbl.), honeypot. Salva `ecm_iscritto` (consenso + data + hash IP), poi se configurato invia a Brevo (API v3 `/contacts`) o Mailchimp. Messaggi in italiano.
- Rate limit semplice per IP (transient, es. 5 invii/10 minuti).

## Importazione ripetibile
Bundle in `wp-content/uploads/ecm-import/` (`data.json` + `assets/`), formato descritto in `docs/import-bundle-format.md`. Classe `ECM_Importer` con step idempotenti richiamabili separatamente (limite 30s per richiesta):
`ECM_Importer::run( string $step, array $args = [] ): array` con step `terms`, `media` (args `offset`,`limit`), `courses`, `docenti`, `testimonianze`, `pages`, `menus`, `options`, `redirects`, `verify`.
