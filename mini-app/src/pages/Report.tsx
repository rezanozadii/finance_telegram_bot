import { useEffect, useState } from 'react';
import { Button, Cell, List, Section, Spinner, Text } from '@telegram-apps/telegram-ui';
import { api } from '../api/client';
import { useLang } from '../LangContext';
import type { Report as ReportData } from '../types';

type Period = 'month' | 'last_month' | 'quarter' | 'year';

function fmt(n: number) {
  return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

export function Report() {
  const { t, lang } = useLang();
  const [period, setPeriod]   = useState<Period>('month');
  const [data, setData]       = useState<ReportData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError]     = useState('');

  const PERIODS: { id: Period; labelKey: 'period_month' | 'period_last_month' | 'period_quarter' | 'period_year' }[] = [
    { id: 'month',      labelKey: 'period_month' },
    { id: 'last_month', labelKey: 'period_last_month' },
    { id: 'quarter',    labelKey: 'period_quarter' },
    { id: 'year',       labelKey: 'period_year' },
  ];

  useEffect(() => {
    setLoading(true);
    setError('');
    api.report(period)
      .then(setData)
      .catch((e: Error) => setError(e.message))
      .finally(() => setLoading(false));
  }, [period, lang]);

  return (
    <List>
      <Section>
        <Cell>
          <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap' }}>
            {PERIODS.map((p) => (
              <Button
                key={p.id}
                size="s"
                mode={period === p.id ? 'filled' : 'outline'}
                onClick={() => setPeriod(p.id)}
              >
                {t(p.labelKey)}
              </Button>
            ))}
          </div>
        </Cell>
      </Section>

      {loading && (
        <div style={{ display: 'flex', justifyContent: 'center', padding: 40 }}>
          <Spinner size="m" />
        </div>
      )}

      {error && <Section><Cell style={{ color: '#ff3b30' }}>{error}</Cell></Section>}

      {data && !loading && (
        <>
          <Section header={data.label}>
            <Cell
              before={<span style={{ fontSize: 22 }}>💰</span>}
              after={<Text style={{ color: '#34c759', fontWeight: 600 }}>{data.currency} {fmt(data.income)}</Text>}
            >
              {t('report_income')}
            </Cell>
            <Cell
              before={<span style={{ fontSize: 22 }}>💸</span>}
              after={<Text style={{ color: '#ff3b30', fontWeight: 600 }}>{data.currency} {fmt(data.expenses)}</Text>}
            >
              {t('report_expenses')}
            </Cell>
            <Cell
              before={<span style={{ fontSize: 22 }}>💵</span>}
              after={
                <Text style={{ color: data.net >= 0 ? '#34c759' : '#ff3b30', fontWeight: 700 }}>
                  {data.net >= 0 ? '+' : ''}{data.currency} {fmt(data.net)}
                </Text>
              }
            >
              {t('report_net')}
            </Cell>
          </Section>

          {data.by_category.length > 0 && (
            <Section header={t('expenses_by_category')}>
              {data.by_category.map((cat) => (
                <Cell
                  key={cat.name}
                  before={<span style={{ fontSize: 22 }}>{cat.icon ?? '📦'}</span>}
                  after={
                    <Text style={{ color: 'var(--tg-theme-hint-color, #888)', minWidth: 80, textAlign: 'right' }}>
                      {data.currency} {fmt(cat.amount)}
                    </Text>
                  }
                  subtitle={
                    <div style={{ marginTop: 4 }}>
                      <div style={{
                        height: 4, borderRadius: 2, background: 'var(--tg-theme-hint-color, #ccc)',
                        width: '100%', overflow: 'hidden',
                      }}>
                        <div style={{ height: '100%', borderRadius: 2, width: `${cat.pct}%`, background: '#007aff' }} />
                      </div>
                    </div>
                  }
                >
                  {cat.name} · {cat.pct.toFixed(1)}%
                </Cell>
              ))}
            </Section>
          )}

          <Section header={`vs. ${data.prev_label}`}>
            <Cell
              before={<span style={{ fontSize: 22 }}>💰</span>}
              after={<Text>{data.income_change}</Text>}
              subtitle={`${data.currency} ${fmt(data.prev_income)}`}
            >
              {t('report_income')}
            </Cell>
            <Cell
              before={<span style={{ fontSize: 22 }}>💸</span>}
              after={<Text>{data.expense_change}</Text>}
              subtitle={`${data.currency} ${fmt(data.prev_expenses)}`}
            >
              {t('report_expenses')}
            </Cell>
          </Section>
        </>
      )}
    </List>
  );
}
