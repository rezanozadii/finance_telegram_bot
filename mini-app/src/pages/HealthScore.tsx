import { useEffect, useState } from 'react';
import { Cell, List, Section, Spinner, Text } from '@telegram-apps/telegram-ui';
import { api } from '../api/client';
import { useLang } from '../LangContext';
import type { HealthScore as HealthScoreData } from '../types';

function scoreColor(score: number) {
  if (score >= 70) return '#34c759';
  if (score >= 50) return '#ff9500';
  return '#ff3b30';
}

function ProgressBar({ pct, color }: { pct: number; color: string }) {
  return (
    <div style={{ background: '#e0e0e0', borderRadius: 4, height: 8, marginTop: 6, overflow: 'hidden' }}>
      <div style={{ background: color, width: `${Math.min(100, pct)}%`, height: 8, borderRadius: 4 }} />
    </div>
  );
}

export function HealthScore(_props: { onBack?: () => void }) {
  const { t } = useLang();
  const [data, setData] = useState<HealthScoreData | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    api.healthScore()
      .then(setData)
      .catch((e: Error) => setError(e.message))
      .finally(() => setLoading(false));
  }, []);

  if (loading) return <div style={{ display: 'flex', justifyContent: 'center', padding: 40 }}><Spinner size="m" /></div>;
  if (error)   return <div style={{ padding: 20, color: '#ff3b30' }}>{error}</div>;
  if (!data)   return null;

  const color = scoreColor(data.total);

  return (
    <List>
      <Section>
        <Cell>
          <div style={{ textAlign: 'center', padding: '20px 0' }}>
            <div style={{ fontSize: 72, fontWeight: 700, color, lineHeight: 1 }}>{data.total}</div>
            <div style={{ fontSize: 14, color: '#888', marginTop: 4 }}>{t('your_score')} / 100</div>
            <ProgressBar pct={data.total} color={color} />
          </div>
        </Cell>
        <Cell before={<span style={{ fontSize: 22 }}>🧠</span>} subtitle={t('personality')}>
          <Text style={{ fontWeight: 600 }}>{data.personality}</Text>
        </Cell>
      </Section>

      <Section header={t('score_components')}>
        {Object.entries(data.components).map(([key, comp]) => (
          <Cell
            key={key}
            subtitle={
              <div>
                <ProgressBar pct={comp.score} color={scoreColor(comp.score)} />
                <div style={{ fontSize: 12, color: '#888', marginTop: 4 }}>{comp.explanation}</div>
              </div>
            }
            after={<Text style={{ color: scoreColor(comp.score), fontWeight: 600 }}>{comp.score}</Text>}
          >
            {comp.label}
          </Cell>
        ))}
      </Section>

      {data.account_age_days !== undefined && data.account_age_days < 30 && (
        <Section>
          <Cell style={{ color: '#888', fontSize: 13 }}>
            ℹ️ {t('new_account_notice').replace('{days}', String(data.account_age_days))}
          </Cell>
        </Section>
      )}
    </List>
  );
}
