import { useEffect, useState } from 'react';
import { Cell, List, Section, Spinner, Text } from '@telegram-apps/telegram-ui';
import { api } from '../api/client';
import { useLang } from '../LangContext';
import type { Forecast as ForecastData } from '../types';

interface Props {
  onBack?: () => void;
  defaultCurrency?: string;
}

function fmt(n: number, currency: string) {
  return `${currency} ${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

export function Forecast({ defaultCurrency = 'USD' }: Props) {
  const { t } = useLang();
  const [data, setData] = useState<ForecastData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    api.forecast(defaultCurrency)
      .then(setData)
      .catch((e: Error) => setError(e.message))
      .finally(() => setLoading(false));
  }, [defaultCurrency]);

  if (loading) return <div style={{ display: 'flex', justifyContent: 'center', padding: 40 }}><Spinner size="m" /></div>;
  if (error)   return <div style={{ padding: 20, color: '#ff3b30' }}>{error}</div>;
  if (!data)   return null;

  const currency = data.currency || defaultCurrency;

  return (
    <List>
      <Section header={t('month_end_balance')}>
        <Cell
          before={<span style={{ fontSize: 22 }}>🔮</span>}
          after={
            <Text style={{ fontWeight: 700, color: data.projected_eom_balance >= 0 ? '#34c759' : '#ff3b30' }}>
              {fmt(data.projected_eom_balance, currency)}
            </Text>
          }
        >
          {t('month_end_balance')}
        </Cell>
        <Cell
          before={<span style={{ fontSize: 22 }}>💰</span>}
          after={<Text style={{ color: '#34c759' }}>{fmt(data.projected_monthly_income, currency)}</Text>}
        >
          {t('projected_income')}
        </Cell>
        <Cell
          before={<span style={{ fontSize: 22 }}>💸</span>}
          after={<Text style={{ color: '#ff3b30' }}>{fmt(data.projected_monthly_expense, currency)}</Text>}
        >
          {t('projected_expenses')}
        </Cell>
        <Cell
          before={<span style={{ fontSize: 22 }}>💵</span>}
          after={
            <Text style={{ color: data.savings_potential >= 0 ? '#34c759' : '#ff3b30' }}>
              {fmt(data.savings_potential, currency)}
            </Text>
          }
        >
          {t('savings_potential')}
        </Cell>
      </Section>

      {data.overspending_risk && (
        <Section>
          <Cell before={<span style={{ fontSize: 22 }}>⚠️</span>} style={{ background: '#fff3cd' }}>
            <Text style={{ color: '#856404', fontWeight: 600 }}>{t('overspending_warning')}</Text>
          </Cell>
        </Section>
      )}

      {data.goal_forecasts && data.goal_forecasts.length > 0 && (
        <Section header={t('goal_forecasts')}>
          {data.goal_forecasts.map((gf, i) => (
            <Cell key={i} before={<span style={{ fontSize: 22 }}>🎯</span>}>
              {gf.name} — {gf.months_remaining !== null ? `${gf.months_remaining} ${t('months_to_complete')}` : '—'}
            </Cell>
          ))}
        </Section>
      )}
    </List>
  );
}
