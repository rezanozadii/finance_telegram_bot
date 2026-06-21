import { useEffect, useState } from 'react';
import { Button, Cell, List, Section, Spinner, Text } from '@telegram-apps/telegram-ui';
import { api } from '../api/client';
import type { Report as ReportData } from '../types';

type Period = 'month' | 'last_month' | 'quarter' | 'year';

const PERIODS: { id: Period; label: string }[] = [
  { id: 'month',      label: 'Month' },
  { id: 'last_month', label: 'Last Month' },
  { id: 'quarter',    label: 'Quarter' },
  { id: 'year',       label: 'Year' },
];

function fmt(n: number) {
  return n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

export function Report() {
  const [period, setPeriod]   = useState<Period>('month');
  const [data, setData]       = useState<ReportData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError]     = useState('');

  useEffect(() => {
    setLoading(true);
    setError('');
    api.report(period)
      .then(setData)
      .catch((e: Error) => setError(e.message))
      .finally(() => setLoading(false));
  }, [period]);

  return (
    <List>
      {/* Period selector */}
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
                {p.label}
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
          {/* Summary */}
          <Section header={data.label}>
            <Cell
              before={<span style={{ fontSize: 22 }}>💰</span>}
              after={<Text style={{ color: '#34c759', fontWeight: 600 }}>{data.currency} {fmt(data.income)}</Text>}
            >
              Income
            </Cell>
            <Cell
              before={<span style={{ fontSize: 22 }}>💸</span>}
              after={<Text style={{ color: '#ff3b30', fontWeight: 600 }}>{data.currency} {fmt(data.expenses)}</Text>}
            >
              Expenses
            </Cell>
            <Cell
              before={<span style={{ fontSize: 22 }}>💵</span>}
              after={
                <Text style={{ color: data.net >= 0 ? '#34c759' : '#ff3b30', fontWeight: 700 }}>
                  {data.net >= 0 ? '+' : ''}{data.currency} {fmt(data.net)}
                </Text>
              }
            >
              Net
            </Cell>
          </Section>

          {/* Category breakdown */}
          {data.by_category.length > 0 && (
            <Section header="Expenses by Category">
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
                        <div style={{
                          height: '100%', borderRadius: 2,
                          width: `${cat.pct}%`,
                          background: '#007aff',
                        }} />
                      </div>
                    </div>
                  }
                >
                  {cat.name} · {cat.pct.toFixed(1)}%
                </Cell>
              ))}
            </Section>
          )}

          {/* vs previous period */}
          <Section header={`vs. ${data.prev_label}`}>
            <Cell
              before={<span style={{ fontSize: 22 }}>💰</span>}
              after={<Text>{data.income_change}</Text>}
              subtitle={`was ${data.currency} ${fmt(data.prev_income)}`}
            >
              Income
            </Cell>
            <Cell
              before={<span style={{ fontSize: 22 }}>💸</span>}
              after={<Text>{data.expense_change}</Text>}
              subtitle={`was ${data.currency} ${fmt(data.prev_expenses)}`}
            >
              Expenses
            </Cell>
          </Section>
        </>
      )}
    </List>
  );
}
