import { useEffect, useState } from 'react';
import { Cell, List, Section, Spinner } from '@telegram-apps/telegram-ui';
import { api } from '../api/client';
import { useLang } from '../LangContext';
import type { Me } from '../types';
import type { Lang } from '../i18n';

interface Props {
  onBack: () => void;
}

export function Settings({ onBack }: Props) {
  const { t, lang, setLang } = useLang();
  const [me, setMe] = useState<Me | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.me()
      .then(setMe)
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  function selectLang(l: Lang) {
    setLang(l);
  }

  return (
    <List>
      <Section>
        <Cell
          before={
            <button
              onClick={onBack}
              style={{ background: 'none', border: 'none', fontSize: 22, cursor: 'pointer', color: '#007aff', padding: 0, lineHeight: 1 }}
            >
              ←
            </button>
          }
        >
          <span style={{ fontWeight: 600, fontSize: 17 }}>{t('settings')}</span>
        </Cell>
      </Section>

      {loading
        ? <div style={{ display: 'flex', justifyContent: 'center', padding: 40 }}><Spinner size="m" /></div>
        : me && (
          <Section header={t('profile')}>
            <Cell subtitle={me.username ? `@${me.username}` : undefined}>
              {me.display_name}
            </Cell>
            <Cell subtitle={t('default_currency')} after={<span style={{ color: '#007aff', fontWeight: 600 }}>{me.default_currency}</span>}>
              {t('default_currency')}
            </Cell>
          </Section>
        )
      }

      <Section header={t('language')}>
        {(['en', 'fa'] as Lang[]).map((l) => (
          <Cell
            key={l}
            onClick={() => selectLang(l)}
            after={lang === l ? <span style={{ color: '#007aff' }}>✓</span> : null}
            style={{ cursor: 'pointer' }}
          >
            {l === 'en' ? t('lang_english') : t('lang_persian')}
          </Cell>
        ))}
      </Section>
    </List>
  );
}
