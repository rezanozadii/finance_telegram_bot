import { useEffect, useState } from 'react';
import { Cell, List, Section, Spinner, Text } from '@telegram-apps/telegram-ui';
import { api } from '../api/client';
import { useLang } from '../LangContext';
import type { AiInsight } from '../types';

export function DailyInsights(_props: { onBack?: () => void }) {
  const { t } = useLang();
  const [insights, setInsights] = useState<AiInsight[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  function load() {
    setLoading(true);
    setError('');
    api.aiInsights()
      .then(setInsights)
      .catch((e: Error) => setError(e.message))
      .finally(() => setLoading(false));
  }

  useEffect(load, []);

  if (loading) return <div style={{ display: 'flex', justifyContent: 'center', padding: 40 }}><Spinner size="m" /></div>;
  if (error)   return <div style={{ padding: 20, color: '#ff3b30' }}>{error}</div>;

  return (
    <List>
      <Section>
        {insights.length === 0 ? (
          <>
            <Cell>
              <div style={{ textAlign: 'center', padding: '20px 0' }}>
                <div style={{ fontSize: 40 }}>💡</div>
                <Text style={{ color: '#888' }}>{t('no_insights')}</Text>
              </div>
            </Cell>
            <Cell onClick={load} style={{ cursor: 'pointer' }}>
              <Text style={{ color: '#007aff', textAlign: 'center', fontWeight: 600 }}>🔄 {t('refresh')}</Text>
            </Cell>
          </>
        ) : (
          insights.map((insight) => (
            <Cell
              key={insight.id}
              before={<span style={{ fontSize: 22 }}>💡</span>}
              subtitle={insight.insights_date}
              multiline
            >
              <span style={{ whiteSpace: 'pre-wrap', lineHeight: 1.5 }}>{insight.content}</span>
            </Cell>
          ))
        )}
      </Section>

      {insights.length > 0 && (
        <Section>
          <Cell onClick={load} style={{ cursor: 'pointer' }}>
            <Text style={{ color: '#007aff', textAlign: 'center', fontWeight: 600 }}>🔄 {t('refresh')}</Text>
          </Cell>
        </Section>
      )}
    </List>
  );
}
