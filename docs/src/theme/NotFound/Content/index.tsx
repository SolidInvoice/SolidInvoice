import type {ReactNode} from 'react';
import clsx from 'clsx';
import Link from '@docusaurus/Link';
import Heading from '@theme/Heading';
import SearchBar from '@theme/SearchBar';
import type {Props} from '@theme/NotFound/Content';

import styles from './styles.module.css';

const POPULAR_LINKS = [
  {to: '/intro', label: 'Documentation overview'},
  {to: '/installation-guide', label: 'Installation Guide'},
  {to: '/installation-guide/quick-install', label: 'Quick install'},
  {to: '/companies/overview', label: 'Companies overview'},
  {to: '/installation-guide/distribution-package/cron-job-setup', label: 'Cron job setup'},
  {to: '/integrations/sentry', label: 'Sentry integration'},
];

export default function NotFoundContent({className}: Props): ReactNode {
  return (
    <main className={clsx(styles.notFound, className)}>
      <div className={styles.gridBackground} aria-hidden="true" />
      <div className="container">
        <div className={styles.inner}>
          <span className={styles.eyebrow}>404 — Page not found</span>
          <Heading as="h1" className={styles.title}>
            We couldn't find that page.
          </Heading>
          <p className={styles.subtitle}>
            The link may be broken, or the page may have moved. Try a search,
            or pick one of the popular pages below.
          </p>

          <div className={styles.searchWrap}>
            <SearchBar />
          </div>

          <div className={styles.popularSection}>
            <h2 className={styles.popularLabel}>Popular pages</h2>
            <ul className={styles.popularList}>
              {POPULAR_LINKS.map(({to, label}) => (
                <li key={to}>
                  <Link to={to} className={styles.popularLink}>
                    {label}
                    <span className={styles.arrow} aria-hidden="true">
                      →
                    </span>
                  </Link>
                </li>
              ))}
            </ul>
          </div>

          <p className={styles.footnote}>
            If you arrived here from an external link,{' '}
            <a
              href="https://github.com/SolidInvoice/SolidInvoice/issues/new/choose"
              target="_blank"
              rel="noopener noreferrer">
              let us know
            </a>{' '}
            so we can fix it.
          </p>
        </div>
      </div>
    </main>
  );
}
