/**
 * Typed wrapper around `window.dataLayer.push` for docs-specific events.
 *
 * GTM is loaded automatically by Cloudflare on the solidinvoice.co domain
 * (including /docs/*), so there's nothing to install or initialise here —
 * the events pushed by `track()` are picked up by GTM triggers configured
 * in the existing container and forwarded to GA4.
 *
 * GA4 setup that pairs with this:
 * - Custom dimension `Site Section` (event-scoped, parameter `site_section`)
 *   set on the GA4 Configuration tag in GTM via a derived variable
 *   (window.location.pathname.startsWith('/docs') ? 'docs' : 'marketing').
 * - One Custom Event trigger in GTM matching `docs_*` plus a single GA4
 *   Event tag that forwards the event name + all parameters.
 */

export type DocsEvent =
  | { event: 'docs_search'; query: string }
  | { event: 'docs_search_no_results'; query: string }
  | {
      event: 'docs_search_result_click';
      query: string;
      result_url: string;
      position: number;
    }
  | {
      event: 'docs_page_feedback';
      page: string;
      sentiment: 'up' | 'down';
    }
  | { event: 'docs_external_link_click'; target_url: string; source_page: string }
  | { event: 'docs_copy_code'; language: string; source_page: string }
  | { event: 'docs_ask_ai_question'; query: string };

interface WindowWithDataLayer extends Window {
  dataLayer?: Record<string, unknown>[];
}

export function track(payload: DocsEvent): void {
  if (typeof window === 'undefined') return;
  const w = window as WindowWithDataLayer;
  w.dataLayer = w.dataLayer || [];
  w.dataLayer.push({...payload});
}
