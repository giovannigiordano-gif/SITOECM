=== ECM Core ===
Requires at least: 6.9
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later

Dati del sito Corsi di Formazione ECM. Il tema `ecm` legge i dati solo tramite le funzioni helper (vedi docs/contract-ecm-core.md).

== Contenuto ==

* CPT: corso (/courses/{slug}, archivio /corsi/), docente, testimonianza, ecm_richiesta, ecm_iscritto.
* Tassonomie: area_tematica (/area/), destinatario (/destinatari/) con meta _ecm_order, _ecm_image_key, _ecm_short.
* Impostazioni ECM (menu di primo livello): contatti, FAQ comuni, social e legale, home, newsletter e cookie, redirect, registro.
* Helper per il tema: ecm_opt, ecm_whatsapp_url, ecm_course_whatsapp_url, ecm_stati, ecm_course, ecm_courses, ecm_areas,
  ecm_destinatari, ecm_docenti, ecm_testimonianze, ecm_stats, ecm_human_filesize, ecm_privacy_urls
  (extra: ecm_form_hidden_fields, ecm_form_endpoint, ecm_plain_title, ecm_core_initials).
* Redirect 301 dal vecchio sito Thinkific, SEO di base (si disattiva con Yoast/Rank Math/SEOPress), moduli REST ecm/v1,
  script statistiche subordinati al consenso, sotto-dimensioni immagini in WebP.
* Importazione ripetibile: ECM_Importer::run( $step, $args ) e pagina Impostazioni ECM › Importazione.

== Disinstallazione ==

Il plugin NON ha un uninstall.php distruttivo: disattivandolo o eliminandolo, corsi, docenti, testimonianze, termini,
meta, opzioni (ecm_options, ecm_redirects, ecm_log), richieste e iscritti restano nel database.
Riattivandolo tutto torna visibile. Per una rimozione completa dei dati serve un intervento manuale.

== Changelog ==

= 1.0.0 =
* Prima versione.
