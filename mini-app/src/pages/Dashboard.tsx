import { useEffect, useState } from 'react';
import { Cell, List, Section, Spinner, Headline, Text } from '@telegram-apps/telegram-ui';
import { api } from '../api/client';
import type { Account, Me, Transaction } from '../types';

function fmt(amount: number, currency: string) {
  return `${currency} ${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function txnSign(type: Transaction['type']) {
  return type === 'income' ? '+' : type === 'expense' ? '-' : '⇄';
}

function txnColor(type: Transaction['type']): string {
  return type === 'income' ? '#34c759' : type === 'expense' ? '#ff3b30' : '#007aff';
}

export function Dashboard() {
  const [me, setMe] = useState<Me | null>(null);
  const [accounts, setAccounts] = useState<Account[]>([]);
  const [recent, setRecent] = useState<Transaction[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    Promise.all([
      api.me(),
      api.accounts(),
      api.transactions({ limit: 5 }),
    ])
      .then(([meData, acctData, txnData]) => {
        setMe(meData);
        setAccounts(acctData);
        setRecent(txnData.data);
      })
      .catch((e: Error) => setError(e.message))
      .finally(() => setLoading(false));
  }, []);

  if (loading) return <div style={{ display: 'flex', justifyContent: 'center', padding: 40 }}><Spinner size="m" /></div>;
  if (error)   return <div style={{ padding: 20, color: 'red' }}>{error}</div>;

  return (
    <List>
      {/* Balance card */}
      <Section>
        <Cell
          before={<span style={{ fontSize: 32 }}>💰</span>}
          subtitle={`${me?.account_count ?? 0} account(s)`}
        >
          <Headline weight="2">
            {fmt(me?.total_balance ?? 0, me?.default_currency ?? 'USD')}
          </Headline>
        </Cell>
      </Section>

      {/* Accounts */}
      {accounts.length > 0 && (
        <Section header="Accounts">
          {accounts.map((a) => (
            <Cell
              key={a.id}
              before={<span style={{ fontSize: 22 }}>{acctIcon(a.type)}</span>}
              after={<Text style={{ color: a.balance < 0 ? '#ff3b30' : undefined }}>{fmt(a.balance, a.currency)}</Text>}
              subtitle={a.type}
            >
              {a.name}
            </Cell>
          ))}
        </Section>
      )}

      {/* Recent transactions */}
      <Section header="Recent transactions">
        {recent.length === 0 ? (
          <Cell>No transactions yet</Cell>
        ) : (
          recent.map((t) => (
            <Cell
              key={t.id}
              before={<span style={{ fontSize: 22 }}>{t.category?.icon ?? txnEmoji(t.type)}</span>}
              subtitle={t.category?.name ?? t.merchant ?? '—'}
              after={
                <Text style={{ color: txnColor(t.type), fontWeight: 600 }}>
                  {txnSign(t.type)}{fmt(t.amount, t.currency)}
                </Text>
              }
            >
              {t.merchant || t.description || t.account?.name || t.type}
            </Cell>
          ))
        )}
      </Section>
    </List>
  );
}

function acctIcon(type: string) {
  return { cash: '💵', card: '💳', bank: '🏦', 'e-wallet': '📱', credit: '💳' }[type] ?? '💰';
}

function txnEmoji(type: string) {
  return { income: '💚', expense: '🔴', transfer: '🔵' }[type] ?? '⚪';
}
