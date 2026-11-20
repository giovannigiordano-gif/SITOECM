// Generic, browser-side extraction of Thinkific "sections" (runs inside page.evaluate after installBrowserHelpers).
export function extractPage(opts = {}) {
  const S = window.__S;
  const cf = S.decodeCfEmails();
  const meta = (sel) => { const e = document.querySelector(sel); return e ? e.getAttribute('content') : null; };
  const skip = new Set(opts.skipTypes || ['header', 'footer']);
  const secs = S.topSections().filter((s) => !skip.has(s.getAttribute('data-preview-item')));
  const imgInfo = (i) => ({ src: i.getAttribute('src'), alt: i.getAttribute('alt'), srcset: i.getAttribute('srcset'), width_attr: i.getAttribute('width'), style: i.getAttribute('style') });
  const linkInfo = (a) => ({ label: S.flatText(a), href: a.getAttribute('href'), target: a.getAttribute('target'), class: a.getAttribute('class') });
  const sections = secs.map((s, idx) => {
    const type = s.getAttribute('data-preview-item');
    const h = s.querySelector('.section__headings .section__heading, header .section__heading');
    const sub = s.querySelector('.section__headings .section__subheading, header .section__subheading');
    const out = {
      idx, type, uid: s.getAttribute('data-section-uid'), class: (s.getAttribute('class') || '').replace(/\s+/g, ' ').trim(),
      heading: S.flatText(h), heading_html: S.innerTrim(h), subheading: S.flatText(sub), subheading_html: S.innerTrim(sub),
      images: [...s.querySelectorAll('img')].map(imgInfo),
      links: [...s.querySelectorAll('a[href]')].map(linkInfo),
      buttons: [...s.querySelectorAll('.section__button-group a, a.button')].map(linkInfo),
      text: s.innerText.replace(/ /g, ' ').trim(),
    };
    const body = s.querySelector('.section__body') || s.querySelector('.section__content') || s;
    // generic cleaned html of the body (rich text keeps images)
    if (type === 'rich_text') { out.html = S.clean(body, { keepImg: true }); out.raw_html = S.innerTrim(body); }
    else if (type === 'description') { out.html = S.richTexty(body); out.raw_html = S.innerTrim(body); }
    else out.html = S.clean(body, { keepImg: true });
    out.html_text = S.htmlToText(out.html || '');
    if (type === 'testimonials') {
      out.items = [...s.querySelectorAll('.testimonials__list-item')].map((it) => {
        const title = it.querySelector('.testimonial__title'); const author = it.querySelector('.testimonial__author');
        const img = it.querySelector('img');
        const divs = [...it.children].filter((c) => c.tagName === 'DIV' && !c.querySelector('img'));
        const b = divs[divs.length - 1] || null; const html = b ? S.richTexty(b) : null;
        return { title: S.flatText(title), author: S.flatText(author) || null, text_raw: b ? b.innerHTML.trim() : null, text_html: html, text: html ? S.htmlToText(html) : null, image: img ? imgInfo(img) : null };
      });
    }
    if (type === 'faq') {
      out.items = [...s.querySelectorAll('.faq__list-item')].map((li) => {
        const det = li.querySelector('details') || li; const html = S.richTexty(det);
        return { question: S.flatText(li.querySelector('summary')), answer_html: html, answer_text: S.htmlToText(html) };
      });
    }
    if (type === 'text-icon') {
      out.items = [...s.querySelectorAll('.text-icon__list-item')].map((li) => {
        const icon = li.querySelector('i'); const h3 = li.querySelector('h3');
        const rest = document.createElement('div'); [...li.children].filter((c) => c !== icon && c !== h3).forEach((c) => rest.appendChild(c.cloneNode(true)));
        const html = S.clean(rest);
        return { icon: icon ? icon.getAttribute('class') : null, heading: S.flatText(h3), html, text: S.htmlToText(html), links: [...li.querySelectorAll('a[href]')].map(linkInfo) };
      });
    }
    if (/course-cards|featured|products/.test(type)) {
      out.cards = [...s.querySelectorAll('a.card')].map((a) => ({
        href: a.getAttribute('href'), card_class: a.getAttribute('class'),
        badge: S.flatText(a.querySelector('.card__badge')) || null,
        name: S.flatText(a.querySelector('.card__name')), description: (a.querySelector('.card__description') || { textContent: '' }).textContent.replace(/^\s+|\s+$/g, ''),
        image: a.querySelector('img') ? imgInfo(a.querySelector('img')) : null,
      }));
    }
    if (type === 'gallery') out.gallery = [...s.querySelectorAll('img')].map(imgInfo);
    if (type === 'text-image' || type === 'image-text') {
      const contentEl = s.querySelector('.section__body') || s.querySelector('.section__content');
      out.body_html = contentEl ? S.richTexty(contentEl) : null;
      out.body_raw = contentEl ? S.innerTrim(contentEl) : null;
    }
    if (type === 'lead_capture') {
      const f = s.querySelector('form');
      out.form = f ? {
        id: f.id, action: f.getAttribute('action'), method: f.getAttribute('method'), data_remote: f.getAttribute('data-remote'),
        fields: [...f.querySelectorAll('input, select, textarea')].map((i) => ({ tag: i.tagName.toLowerCase(), type: i.getAttribute('type'), name: i.getAttribute('name'), required: i.hasAttribute('required'), placeholder: i.getAttribute('placeholder'), value: i.getAttribute('type') === 'hidden' && /token/.test(i.getAttribute('name') || '') ? '(csrf token)' : i.getAttribute('value'), label: (() => { const l = i.id ? f.querySelector(`label[for="${i.id}"]`) : null; return l ? S.flatText(l) : null; })() })),
        messages: [...f.querySelectorAll('.form__error-msg, .lead-capture__success-msg, .lead-capture__error-msg')].map((p) => ({ class: p.getAttribute('class'), text: S.flatText(p) })),
      } : null;
    }
    return out;
  });
  return {
    cf_emails_decoded: cf, url: location.href, html_title: document.title,
    meta_description: meta('meta[name="description"]'), og_title: meta('meta[property="og:title"]'), og_image: meta('meta[property="og:image"]'),
    main_inner_text: (document.querySelector('main') || document.body).innerText.replace(/ /g, ' ').trim(),
    sections,
  };
}
