import { useEffect, useState } from 'react';
import { Cell, List, Section, Spinner } from '@telegram-apps/telegram-ui';
import { api } from '../api/client';
import { useLang } from '../LangContext';
import type { Habit } from '../types';

interface Props {
  onBack: () => void;
  defaultCurrency?: string;
}

function fmt(amount: number, currency: string) {
  return `${currency} ${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

export function Habits({ onBack, defaultCurrency = 'USD' }: Props) {
  const { t } = useLang();
  const [habits, setHabits] = useState<Habit[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    api.habits(defaultCurrency)
      .then((res) => setHabits(res.habits))
      .catch((e: Error) => setError(e.message))
      .finally(() => setLoading(false));
  }, [defaultCurrency]);

  const totalMonthly = habits.reduce((s, h) => s + h.monthly_cost, 0);
  const currency = habits[0]?.currency ?? defaultCurrency;

  function frequencyLabel(freq: number): string {
    return `${freq.toFixed(1)} ${t('times_per_month')}`;
  }

  return (
    <List>
      {loading && <div style={{ display: 'flex', justifyContent: 'center', padding: 40 }}><Spinner size="m" /></div>}
      {error && <Section><Cell style={{ color: '#ff3b30' }}>{error}</Cell></Section>}

      {!loading && !error && habits.length === 0 && (
        <Section><Cell>{t('no_habits')}</Cell></Section>
      )}

      {habits.length > 0 && (
        <>
          <Section>
            <Cell
              before={<span style={{ fontSize: 28 }}>🧠</span>}
              subtitle={`${t('total_monthly_cost')}: ${fmt(totalMonthly, currency)}`}
            >
              <span style={{ fontWeight: 600 }}>{habits.length} {t('habits')}</span>
            </Cell>
          </Section>

          <Section header={t('habits')}>
            {habits.map((h, i) => (
              <Cell
                key={i}
                before={<span style={{ fontSize: 22 }}>🛍️</span>}
                subtitle={`${frequencyLabel(h.frequency)} · ${t('avg_amount')}: ${fmt(h.avg_amount, h.currency)}`}
                after={
                  <div style={{ textAlign: 'right' }}>
                    <div style={{ fontWeight: 600, color: '#ff3b30' }}>{fmt(h.monthly_cost, h.currency)}{t('per_month')}</div>
                    <div style={{ fontSize: 12, color: '#888' }}>{fmt(h.yearly_cost, h.currency)}{t('per_year')}</div>
                  </div>
                }
              >
                {h.merchant}
              </Cell>
            ))}
          </Section>
        </>
      )}
    </List>
  );
}
