# Deploy su soothing-khaki-okapi-74.hostingersite.com (plugin `ecm-core` e tema `ecm`)

Accesso **solo** tramite il server MCP `novamira-soothing-khaki-o` (abilità `novamira/create-upload-link` e
`novamira/execute-php`). Non usare altri server/profili Novamira. Non toccare il plugin Novamira né l'utente 1.

## 1. ZIP in locale (barre "/" nei percorsi)

Serve il `tar.exe` di Windows (bsdtar). `Compress-Archive` di PowerShell 5.1 scrive backslash; il `tar` di Git Bash
è GNU tar e non crea ZIP. La cartella radice dello ZIP deve coincidere con lo slug.

PowerShell:

```powershell
cd "C:\Users\UTENTE\Desktop\Restyling sito ecm\wp-content\plugins"   # per il tema: ...\wp-content\themes
tar.exe -a -cf "$env:TEMP\ecm-core.zip" ecm-core                       # per il tema: ecm.zip ecm
```

Git Bash:

```sh
cd "/c/Users/UTENTE/Desktop/Restyling sito ecm/wp-content/plugins"
/c/Windows/System32/tar.exe -a -cf "C:/percorso/scratch/ecm-core.zip" ecm-core
/c/Windows/System32/tar.exe -tf "C:/percorso/scratch/ecm-core.zip" | head   # controllo: ecm-core/...
```

## 2. Upload

Abilità `novamira/create-upload-link`:

```json
{ "path": "wp-content/uploads/ecm-deploy/ecm-core.zip", "overwrite": true, "expires_in": 3600 }
```

Poi, con `upload_url`, `token_header` e `upload_token` restituiti (il token resta valido fino alla scadenza e può essere
riusato per ricaricare lo stesso percorso):

```sh
curl -sS -X PUT -H "X-Novamira-Upload-Token: <upload_token>" \
  --data-binary @"C:/percorso/scratch/ecm-core.zip" \
  "https://soothing-khaki-okapi-74.hostingersite.com/wp-json/novamira/v1/upload"
```

Risposta attesa: `{"bytes_written": ..., "created": true}`.

## 3. Estrazione, lint, attivazione

Incollare il contenuto di [`extract.php.txt`](extract.php.txt) come `code` di `novamira/execute-php`, cambiando solo i
PARAMETRI in testa (`$type`, `$slug`, `$main`, `$activate`). Lo snippet:

1. verifica lo ZIP (niente backslash, niente `..`, radice = slug) **prima** di cancellare la versione online;
2. cancella ricorsivamente `WP_PLUGIN_DIR/<slug>` (o `themes/<slug>`), estrae con `ZipArchive`, elimina lo ZIP;
3. fa il lint di ogni file PHP con `token_get_all(..., TOKEN_PARSE)` e invalida OPcache; se ci sono errori si ferma;
4. plugin non attivo: `activate_plugin()`; plugin già attivo (aggiornamento): non riattiva;
   tema: `switch_theme()` solo se `$activate = true`;
5. `delete_option('rewrite_rules')` (i permalink si rigenerano alla richiesta successiva con il codice nuovo);
6. richiesta loopback alla home: `home_status` deve essere 200 e `home_fatal` false.

Esito tipico: `{"lint_errors":[], "was_active":true, "active":true, "home_status":200, "home_fatal":false}`.

### Attenzione

* `activate_plugin()` **non** intercetta i fatal veri (es. "Cannot redeclare"): la richiesta muore con HTTP 500 e il
  plugin resta disattivato. Per leggere l'errore, ripetere l'attivazione con in testa:
  `register_shutdown_function( function () { file_put_contents( WP_CONTENT_DIR . '/uploads/ecm-deploy/fatal.txt', var_export( error_get_last(), true ) ); } );`
  e leggere il file con una seconda chiamata.
* Nella richiesta di attivazione il tema è già caricato prima del plugin: per questo tutte le funzioni helper di
  `ecm-core` sono protette da `function_exists`. Il tema non deve dichiarare senza guardia funzioni con nomi del
  plugin (oggi `ecm_initials()` del tema è senza guardia: il plugin usa quindi `ecm_core_initials()`).
* Dopo un aggiornamento le classi nuove valgono dalla richiesta successiva: per i test usare una nuova chiamata
  `execute-php`.
* `execute-php` ha un limite di 30 s e non deve mai chiamare `exit`/`die`.

## Importazione contenuti (ecm-core)

Bundle in `wp-content/uploads/ecm-import/` (`data.json` + `assets/`). Uno step per chiamata `execute-php`:

```php
return ECM_Importer::run( 'terms' );
```

Step in ordine: `terms`, `media` (a blocchi), `courses`, `docenti`, `testimonianze`, `pages`, `menus`, `options`,
`redirects`, `verify`. Per i media ripetere finché `done` è true, usando `next_offset`:

```php
return ECM_Importer::run( 'media', array( 'offset' => 0, 'limit' => 5 ) );
```

Argomenti facoltativi: `force` (riscrive anche se invariato), `preserve_manual` (non sovrascrive i campi modificati a
mano dopo l'ultima importazione), `dir` (bundle in un'altra cartella). In alternativa: wp-admin › Impostazioni ECM ›
Importazione (pulsanti per step, avanzamento media, Verifica).
