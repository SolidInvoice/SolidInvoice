/**
 * Client module that fires a `docs_external_link_click` event whenever a
 * visitor clicks a link to an external host. Loaded once per page-load via
 * `clientModules` in docusaurus.config.ts. Uses event delegation so it
 * costs one listener regardless of how many links are on the page.
 */

import {track} from '../lib/analytics';

if (typeof window !== 'undefined') {
  document.addEventListener(
    'click',
    (event) => {
      const target = event.target as Element | null;
      if (!target) return;
      const anchor = target.closest('a') as HTMLAnchorElement | null;
      if (!anchor || !anchor.href) return;

      let url: URL;
      try {
        url = new URL(anchor.href, window.location.origin);
      } catch {
        return;
      }

      // Same-origin links don't count as external. Mailto/tel/anchor-only
      // links also skipped.
      if (url.origin === window.location.origin) return;
      if (url.protocol !== 'http:' && url.protocol !== 'https:') return;

      track({
        event: 'docs_external_link_click',
        target_url: url.href,
        source_page: window.location.pathname,
      });
    },
    {capture: true},
  );
}
