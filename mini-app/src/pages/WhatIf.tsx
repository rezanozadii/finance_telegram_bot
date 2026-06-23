import { useState } from 'react';
import { Cell, List, Section, Button, Input } from '@telegram-apps/telegram-ui';
import { api } from '../api/client';
import { useLang } from '../LangContext';
import type { WhatIfResult } from '../types';

interface Props {
  onBack: () => void;
  defaultCurrency?: string;
}

type Scenario = 'reduce_category' | 'salary_increase' | 'save_fixed' | 'cancel_subscription';

function fmt(amount: number, currency: string) {
  return `${currency} ${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

export function WhatIf({ onBack, defaultCurrency = 'USD' }: Props) {
  const { t } = useLang();
  const [scenario, setScenario] = useState<Scenario>('reduce_category');
  const [param1, setParam1] = useState('');
  const [param2, setParam2] = useState('10');
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<WhatIfResult | null>(null);
  const [error, setError] = useState('');

  const SCENARIOS: { value: Scenario; labelKey: string; icon: string }[] = [
    { value: 'reduce_category',    labelKey: 'scenario_reduce',  icon: '✂️' },
    { value: 'salary_increase',    labelKey: 'scenario_salary',  icon: '📈' },
    { value: 'save_fixed',         labelKey: 'scenario_save',    icon: '🏦' },
    { value: 'cancel_subscription', labelKey: 'scenario_cancel', icon: '🚫' },
  ];

  function buildParams(): Record<string, unknown> {
    switch (scenario) {
      case 'reduce_category':    return { category_name: param1, reduction_pct: parseFloat(param2) || 10 };
      case 'salary_increase':    return { increase_pct: parseFloat(param2) || 10 };
      case 'save_fixed':         return { amount: parseFloat(param1) || 0 };
      case 'cancel_subscription': return { merchant: param1 };
    }
  }

  async function simulate() {
    setLoading(true); setError(''); setResult(null);
    try {
      const res = await api.whatIf(scenario, buildParams(), defaultCurrency);
      if (res.error) { setError(res.error); } else { setResult(res); }
    } catch (e: unknown) {
      setError(e instanceof Error ? e.message : 'Error');
    } finally {
      setLoading(false);
    }
  }

  function renderParam1() {
    switch (scenario) {
      case 'reduce_category':     return <Input placeholder={t('category_name')} value={param1} onChange={(e) => setParam1(e.target.value)} />;
      case 'save_fixed':          return <Input placeholder={t('save_amount')} type="number" value={param1} onChange={(e) => setParam1(e.target.value)} />;
      case 'cancel_subscription': return <Input placeholder={t('merchant_name')} value={param1} onChange={(e) => setParam1(e.target.value)} />;
      default:                    return null;
    }
  }

  function renderParam2() {
    switch (scenario) {
      case 'reduce_category': return <Input placeholder={t('reduction_pct')} type="number" value={param2} onChange={(e) => setParam2(e.target.value)} />;
      case 'salary_increase': return <Input placeholder={t('increase_pct')} type="number" value={param2} onChange={(e) => setParam2(e.target.value)} />;
      default: return null;
    }
  }

  const currency = result?.currency ?? defaultCurrency;

  return (
    <List>
      <Section header={t('whatif')}>
        {SCENARIOS.map((s) => (
          <Cell
            key={s.value}
            before={<span style={{ fontSize: 20 }}>{s.icon}</span>}
            onClick={() => { setScenario(s.value); setResult(null); setParam1(''); setParam2('10'); }}
            after={scenario === s.value ? <span style={{ color: '#007aff' }}>✓</span> : null}
            style={{ cursor: 'pointer' }}
          >
            {t(s.labelKey as any)}
          </Cell>
        ))}
      </Section>

      <Section header={t('simulate')}>
        <Cell>
          <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
            {renderParam1()}
            {renderParam2()}
            {error && <span style={{ color: '#ff3b30', fontSize: 13 }}>{error}</span>}
            <Button size="s" mode="filled" onClick={simulate} disabled={loading} style={{ width: '100%' }}>
              {loading ? '…' : t('simulate')}
            </Button>
          </div>
        </Cell>
      </Section>

      {result && !result.error && (
        <Section header="Results">
          {result.monthly_savings !== undefined && (
            <Cell after={<span style={{ color: '#34c759', fontWeight: 600 }}>{fmt(result.monthly_savings, currency)}</span>}>
              {t('monthly_savings')}
            </Cell>
          )}
          {result.yearly_savings !== undefined && (
            <Cell after={<span style={{ color: '#34c759', fontWeight: 600 }}>{fmt(result.yearly_savings, currency)}</span>}>
              {t('yearly_savings')}
            </Cell>
          )}
          {result.monthly_income_increase !== undefined && (
            <Cell after={<span style={{ color: '#34c759', fontWeight: 600 }}>{fmt(result.monthly_income_increase, currency)}</span>}>
              {t('monthly_savings')}
            </Cell>
          )}
          {result.yearly_income_increase !== undefined && (
            <Cell after={<span style={{ color: '#34c759', fontWeight: 600 }}>{fmt(result.yearly_income_increase, currency)}</span>}>
              {t('yearly_savings')}
            </Cell>
          )}
          {result.monthly_cost !== undefined && (
            <Cell after={<span style={{ fontWeight: 600 }}>{fmt(result.monthly_cost, currency)}</span>}>
              {t('monthly_savings')}
            </Cell>
          )}
          {result.yearly_savings_from_cancel !== undefined && (
            <Cell after={<span style={{ color: '#34c759', fontWeight: 600 }}>{fmt(result.yearly_savings_from_cancel, currency)}</span>}>
              {t('yearly_savings')}
            </Cell>
          )}

          {result.goal_impact && result.goal_impact.length > 0 && (
            <>
              <Cell style={{ fontWeight: 600 }}>{t('goal_impact')}</Cell>
              {result.goal_impact.map((g, i) => (
                <Cell
                  key={i}
                  before={<span style={{ fontSize: 16 }}>🎯</span>}
                  subtitle={`${t('months_now')}: ${g.months_now ?? '—'} → ${t('months_after')}: ${g.months_with_change ?? '—'}`}
                >
                  {g.goal}
                </Cell>
              ))}
            </>
          )}
        </Section>
      )}

      {!result && !loading && (
        <Section><Cell style={{ color: '#888' }}>{t('no_result')}</Cell></Section>
      )}
    </List>
  );
}
