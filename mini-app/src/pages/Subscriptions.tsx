import { useEffect, useState } from 'react';
import { Cell, List, Section, Spinner, Text } from '@telegram-apps/telegram-ui';
import { api } from '../api/client';
import { useLang } from '../LangContext';
import type { Subscription } from '../types';

function fmt(n: number, currency: string) {
  return `${currency} ${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

const freqIcon: Record<string, string> = { monthly: '📅', yearly: '📆', weekly: '🗓' };

export function Subscriptions(_props: { onBack?: () => void }) {
  const { t } = useLang();
  const [subs, setSubs] = useState<Subscription[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    api.aiSubscriptions()
      .then(setSubs)
      .catch((e: Error) => setError(e.message))
      .finally(() => setLoading(false));
  }, []);

  if (loading) return <div style={{ display: 'flex', justifyContent: 'center', padding: 40 }}><Spinner size="m" /></div>;
  if (error)   return <div style={{ padding: 20, color: '#ff3b30' }}>{error}</div>;

  const totalMonthly = subs.reduce((s, sub) => s + sub.monthly_cost, 0);
  const totalYearly  = subs.reduce((s, sub) => s + sub.yearly_cost, 0);
  const currency     = subs[0]?.currency ?? 'USD';

  return (
    <List>
      {subs.length > 0 && (
        <Section header="Summary">
          <Cell
            before={<span style={{ fontSize: 22 }}>📅</span>}
            after={<Text style={{ color: '#ff3b30', fontWeight: 600 }}>{fmt(totalMonthly, currency)}</Text>}
          >
            {t('total_monthly')}
          </Cell>
          <Cell
            before={<span style={{ fontSize: 22 }}>📆</span>}
            after={<Text style={{ color: '#ff3b30', fontWeight: 600 }}>{fmt(totalYearly, currency)}</Text>}
          >
            {t('total_yearly')}
          </Cell>
        </Section>
      )}

      <Section>
        {subs.length === 0 ? (
          <Cell>{t('no_subscriptions')}</Cell>
        ) : (
          subs.map((sub, i) => (
            <Cell
              key={i}
              before={<span style={{ fontSize: 22 }}>{freqIcon[sub.frequency] ?? '🔄'}</span>}
              subtitle={
                <span>
                  {sub.frequency} · {t('next_payment')}: {sub.next_predicted_at ?? '—'}
                </span>
              }
              after={
                <div style={{ textAlign: 'right' }}>
                  <Text style={{ fontWeight: 600, color: '#ff3b30' }}>{fmt(sub.monthly_cost, sub.currency)}/mo</Text>
                  <div style={{ fontSize: 12, color: '#888' }}>{fmt(sub.yearly_cost, sub.currency)}/yr</div>
                </div>
              }
            >
              {sub.merchant}
            </Cell>
          ))
        )}
      </Section>
    </List>
  );
}
