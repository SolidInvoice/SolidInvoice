import {themes as prismThemes} from 'prism-react-renderer';
import type {Config} from '@docusaurus/types';
import type * as Preset from '@docusaurus/preset-classic';

const config: Config = {
  title: 'SolidInvoice Docs',
  tagline: 'Open-source invoicing for freelancers and small businesses',
  favicon: 'img/favicon.ico',

  future: {
    v4: true,
  },

  url: 'https://solidinvoice.co',
  baseUrl: '/docs/',

  // The host serves every docs page at its trailing-slash URL and 307-redirects
  // the bare form. Without this, Docusaurus emits the bare URL in internal links,
  // canonical tags and the sitemap, so every page redirects and its canonical
  // points at a redirect. Keep this aligned with the host.
  trailingSlash: true,

  organizationName: 'SolidInvoice',
  projectName: 'SolidInvoice',

  onBrokenLinks: 'throw',
  markdown: {
    hooks: {
      onBrokenMarkdownLinks: 'throw',
    },
  },

  headTags: [
    // Docusaurus emits og:title/description/image/url/locale but not og:type or
    // og:site_name, which leaves the Open Graph card incomplete for crawlers.
    {
      tagName: 'meta',
      attributes: {
        property: 'og:type',
        content: 'website',
      },
    },
    {
      tagName: 'meta',
      attributes: {
        property: 'og:site_name',
        content: 'SolidInvoice Docs',
      },
    },
    {
      tagName: 'link',
      attributes: {
        rel: 'preconnect',
        href: 'https://fonts.googleapis.com',
      },
    },
    {
      tagName: 'link',
      attributes: {
        rel: 'preconnect',
        href: 'https://fonts.gstatic.com',
        crossorigin: 'anonymous',
      },
    },
    {
      tagName: 'link',
      attributes: {
        rel: 'stylesheet',
        href: 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap',
      },
    },
  ],

  i18n: {
    defaultLocale: 'en',
    locales: ['en'],
  },

  clientModules: [
    require.resolve('./src/clientModules/external-link-tracker.ts'),
  ],

  presets: [
    [
      'classic',
      {
        docs: {
          sidebarPath: './sidebars.ts',
          routeBasePath: '/',
          editUrl:
            'https://github.com/SolidInvoice/SolidInvoice/edit/3.0.x/docs/',
        },
        blog: false,
        theme: {
          customCss: './src/css/custom.css',
        },
        sitemap: {
          changefreq: 'weekly',
          priority: 0.5,
          // The search and 404 routes are noindex utility pages. Listing them
          // in the sitemap asks crawlers to index pages we tell them to skip.
          createSitemapItems: async ({defaultCreateSitemapItems, ...rest}) => {
            const items = await defaultCreateSitemapItems(rest);
            return items.filter(
              (item) => !/\/(search|404)\/?$/.test(new URL(item.url).pathname),
            );
          },
        },
      } satisfies Preset.Options,
    ],
  ],

  themeConfig: {
    image: 'img/solidinvoice-social-card.png',
    colorMode: {
      defaultMode: 'light',
      respectPrefersColorScheme: true,
    },
    navbar: {
      title: 'SolidInvoice Docs',
      logo: {
        alt: 'SolidInvoice Logo',
        src: 'img/logo.png',
        width: 32,
        height: 32,
      },
      items: [
        {
          type: 'docSidebar',
          sidebarId: 'docsSidebar',
          position: 'left',
          label: 'Documentation',
        },
        {
          to: 'https://solidinvoice.co/blog',
          label: 'Blog',
          position: 'left',
        },
        {
          to: 'https://solidinvoice.co',
          label: 'Cloud Hosted',
          position: 'left',
        },
        {
          href: 'https://github.com/SolidInvoice/SolidInvoice',
          label: 'GitHub',
          position: 'right',
        },
      ],
    },
    footer: {
      style: 'dark',
      links: [
        {
          title: 'Documentation',
          items: [
            {
              label: 'Get Started',
              to: '/intro',
            },
            {
              label: 'Installation',
              to: '/installation-guide',
            },
            {
              label: 'Companies',
              to: '/companies/overview',
            },
            {
              label: 'Integrations',
              to: '/integrations/sentry',
            },
          ],
        },
        {
          title: 'Community',
          items: [
            {
              label: 'GitHub Discussions',
              href: 'https://github.com/SolidInvoice/SolidInvoice/discussions',
            },
            {
              label: 'Report an Issue',
              href: 'https://github.com/SolidInvoice/SolidInvoice/issues',
            },
            {
              label: 'X (Twitter)',
              href: 'https://x.com/solidinvoice',
            },
          ],
        },
        {
          title: 'More',
          items: [
            {
              label: 'Main Site',
              href: 'https://solidinvoice.co',
            },
            {
              label: 'Blog',
              href: 'https://solidinvoice.co/blog',
            },
            {
              label: 'GitHub',
              href: 'https://github.com/SolidInvoice/SolidInvoice',
            },
          ],
        },
      ],
      copyright: `Copyright © ${new Date().getFullYear()} SolidInvoice. Built with Docusaurus.`,
    },
    prism: {
      theme: prismThemes.github,
      darkTheme: prismThemes.dracula,
      additionalLanguages: ['bash', 'php', 'yaml', 'json', 'nginx', 'apacheconf', 'docker', 'ini'],
    },
  } satisfies Preset.ThemeConfig,

  plugins: [
    [
      require.resolve('@easyops-cn/docusaurus-search-local'),
      {
        hashed: true,
        indexBlog: false,
        docsRouteBasePath: '/',
        highlightSearchTermsOnTargetPage: true,
        explicitSearchResultPath: true,
      },
    ],
  ],
};

export default config;
