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

  url: 'https://docs.solidinvoice.co',
  baseUrl: '/',

  organizationName: 'SolidInvoice',
  projectName: 'SolidInvoice',

  onBrokenLinks: 'throw',
  markdown: {
    hooks: {
      onBrokenMarkdownLinks: 'throw',
    },
  },

  i18n: {
    defaultLocale: 'en',
    locales: ['en'],
  },

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
        src: 'img/logo.svg',
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
          // Phase 3 will replace these once content is migrated:
          // Installation → /installation-guide
          // Companies → /companies/overview
          // Integrations → /integrations/sentry
          title: 'Documentation',
          items: [
            {
              label: 'Get Started',
              to: '/intro',
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
