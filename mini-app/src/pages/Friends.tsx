import { useEffect, useState } from 'react';
import { Cell, List, Section, Spinner } from '@telegram-apps/telegram-ui';
import { api } from '../api/client';
import type { Friend } from '../types';

function formatBalances(balances: Record<string, number>): { text: string; color: string } {
  const entries = Object.entries(balances).filter(([, v]) => Math.abs(v) >= 0.01);
  if (entries.length === 0) return { text: '✅ Settled', color: '#34c759' };

  const parts = entries.map(([currency, amount]) => {
    if (amount > 0) return `they owe you ${currency} ${Math.abs(amount).toFixed(2)}`;
    return `you owe ${currency} ${Math.abs(amount).toFixed(2)}`;
  });

  const allPositive = entries.every(([, v]) => v > 0);
  const allNegative = entries.every(([, v]) => v < 0);
  const color = allPositive ? '#34c759' : allNegative ? '#ff3b30' : '#007aff';
  return { text: parts.join(' · '), color };
}

export function Friends() {
  const [friends, setFriends] = useState<Friend[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError]     = useState('');

  useEffect(() => {
    api.friends()
      .then(setFriends)
      .catch((e: Error) => setError(e.message))
      .finally(() => setLoading(false));
  }, []);

  if (loading) return <div style={{ display: 'flex', justifyContent: 'center', padding: 40 }}><Spinner size="m" /></div>;
  if (error)   return <div style={{ padding: 20, color: '#ff3b30' }}>{error}</div>;

  // Separate friends with open balances from settled ones
  const withBalance  = friends.filter((f) => Object.values(f.balances).some((v) => Math.abs(v) >= 0.01));
  const settled      = friends.filter((f) => !withBalance.includes(f));

  return (
    <List>
      {friends.length === 0 && (
        <Section>
          <Cell subtitle="Use /addfriend in the bot to add friends">No friends yet</Cell>
        </Section>
      )}

      {withBalance.length > 0 && (
        <Section header="Open balances">
          {withBalance.map((f) => {
            const { text, color } = formatBalances(f.balances);
            return (
              <Cell
                key={f.id}
                before={<span style={{ fontSize: 26 }}>👤</span>}
                subtitle={text}
              >
                <span style={{ color }}>{f.display_name}{f.username ? ` (@${f.username})` : ''}</span>
              </Cell>
            );
          })}
        </Section>
      )}

      {settled.length > 0 && (
        <Section header="Settled">
          {settled.map((f) => (
            <Cell
              key={f.id}
              before={<span style={{ fontSize: 26 }}>👤</span>}
              after={<span style={{ color: '#34c759' }}>✅</span>}
            >
              {f.display_name}{f.username ? ` (@${f.username})` : ''}
            </Cell>
          ))}
        </Section>
      )}
    </List>
  );
}
