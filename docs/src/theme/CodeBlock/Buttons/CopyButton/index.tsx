import React, {type ReactNode, useCallback} from 'react';
import CopyButton from '@theme-original/CodeBlock/Buttons/CopyButton';
import type CopyButtonType from '@theme/CodeBlock/Buttons/CopyButton';
import type {WrapperProps} from '@docusaurus/types';
import {track} from '@site/src/lib/analytics';

type Props = WrapperProps<typeof CopyButtonType>;

export default function CopyButtonWrapper(props: Props): ReactNode {
  const handleClick = useCallback(() => {
    if (typeof window === 'undefined') return;
    track({
      event: 'docs_copy_code',
      language: props.language ?? 'unknown',
      source_page: window.location.pathname,
    });
  }, [props.language]);

  return (
    <span onClickCapture={handleClick}>
      <CopyButton {...props} />
    </span>
  );
}
