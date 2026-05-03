import type {ReactNode} from 'react';
import clsx from 'clsx';
import Heading from '@theme/Heading';
import styles from './styles.module.css';

type FeatureItem = {
  title: string;
  icon: string;
  description: ReactNode;
};

const FeatureList: FeatureItem[] = [
  {
    title: 'Invoices & Quotes',
    icon: '📄',
    description: (
      <>
        Create professional quotes and invoices, send them to clients, and track
        their status from draft to paid. Recurring billing built in.
      </>
    ),
  },
  {
    title: 'Multi-Currency & Tax',
    icon: '💱',
    description: (
      <>
        Bill clients in their own currency with full multi-currency support.
        Configurable tax rates and discounts handle whatever your jurisdiction
        requires.
      </>
    ),
  },
  {
    title: 'Self-Hosted & Open Source',
    icon: '🔓',
    description: (
      <>
        Own your data. Run SolidInvoice on your own server, or use the hosted
        version for $8/month. MIT licensed, no per-client limits.
      </>
    ),
  },
];

function Feature({title, icon, description}: FeatureItem) {
  return (
    <div className={clsx('col col--4')}>
      <div className="text--center">
        <div className={styles.featureIcon} role="img" aria-label={title}>
          {icon}
        </div>
      </div>
      <div className="text--center padding-horiz--md">
        <Heading as="h3">{title}</Heading>
        <p>{description}</p>
      </div>
    </div>
  );
}

export default function HomepageFeatures(): ReactNode {
  return (
    <section className={styles.features}>
      <div className="container">
        <div className="row">
          {FeatureList.map((props, idx) => (
            <Feature key={idx} {...props} />
          ))}
        </div>
      </div>
    </section>
  );
}
