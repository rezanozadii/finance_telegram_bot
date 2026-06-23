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
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    api.me()
      .then(setMe)
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  async function selectLang(l: Lang) {
    if (l === lang) return;
    setSaving(true);
    setLang(l);
    try {
      const updated = await api.updateMe({ language: l });
      // Reflect the auto-updated currency from server
      setMe((prev) => prev ? { ...prev, language: l, default_currency: updated.default_currency } : prev);
    } catch {
      // language still changed locally in UI
    } finally {
      setSaving(false);
    }
  }

  const defaultCurrency = me?.default_currency ?? (lang === 'fa' ? 'IRR' : 'USD');

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
            <Cell
              subtitle={t('default_currency')}
              after={<span style={{ color: '#007aff', fontWeight: 600 }}>{defaultCurrency}</span>}
            >
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
            after={
              saving && l !== lang ? null :
              lang === l ? <span style={{ color: '#007aff' }}>✓</span> : null
            }
            subtitle={l === 'fa' ? 'IRR — ریال ایران' : 'USD — US Dollar'}
            style={{ cursor: 'pointer' }}
          >
            {l === 'en' ? t('lang_english') : t('lang_persian')}
          </Cell>
        ))}
      </Section>
    </List>
  );
}
