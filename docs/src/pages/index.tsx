import type {ReactNode} from 'react';
import clsx from 'clsx';
import Link from '@docusaurus/Link';
import useDocusaurusContext from '@docusaurus/useDocusaurusContext';
import Layout from '@theme/Layout';
import HomepageFeatures from '@site/src/components/HomepageFeatures';
import Heading from '@theme/Heading';

import styles from './index.module.css';

function HomepageHeader() {
  const {siteConfig} = useDocusaurusContext();
  return (
    <header className={styles.heroBanner}>
      <div className={styles.heroGradient} aria-hidden="true" />
      <div className="container">
        <div className={styles.heroInner}>
          <span className={styles.heroEyebrow}>SolidInvoice Documentation</span>
          <Heading as="h1" className={styles.heroTitle}>
            Everything you need to bill clients{' '}
            <span className={styles.heroTitleAccent}>your way.</span>
          </Heading>
          <p className={styles.heroSubtitle}>{siteConfig.tagline}. Run it on your own server, or use the hosted version — no per-client limits, ever.</p>
          <div className={styles.buttons}>
            <Link className="button button--primary button--lg" to="/intro">
              Get Started →
            </Link>
            <Link
              className="button button--secondary button--lg"
              href="https://github.com/SolidInvoice/SolidInvoice">
              View on GitHub
            </Link>
          </div>
          <div className={styles.heroBadges}>
            <span className={styles.heroBadge}>
              <span className={styles.heroBadgeDot} /> MIT licensed
            </span>
            <span className={styles.heroBadge}>
              <span className={styles.heroBadgeDot} /> Self-hostable
            </span>
            <span className={styles.heroBadge}>
              <span className={styles.heroBadgeDot} /> Symfony 7 + PHP 8.4
            </span>
          </div>
        </div>
      </div>
    </header>
  );
}

export default function Home(): ReactNode {
  const {siteConfig} = useDocusaurusContext();
  return (
    <Layout
      title={siteConfig.title}
      description="Documentation for SolidInvoice — open-source invoicing for freelancers and small businesses.">
      <HomepageHeader />
      <main>
        <HomepageFeatures />
      </main>
    </Layout>
  );
}
