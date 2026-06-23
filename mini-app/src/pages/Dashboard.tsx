import { useEffect, useState } from 'react';
import { Cell, List, Section, Spinner, Headline, Text } from '@telegram-apps/telegram-ui';
import { api } from '../api/client';
import { useLang } from '../LangContext';
import type { Account, DashPage, Me, Transaction } from '../types';

function fmt(amount: number, currency: string) {
  return `${currency} ${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function txnSign(type: Transaction['type']) {
  return type === 'income' ? '+' : type === 'expense' ? '-' : '⇄';
}

function txnColor(type: Transaction['type']): string {
  return type === 'income' ? '#34c759' : type === 'expense' ? '#ff3b30' : '#007aff';
}

interface Props {
  onNavigate?: (page: DashPage) => void;
}

export function Dashboard({ onNavigate }: Props) {
  const { t } = useLang();
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
      <Section>
        <Cell
          before={<span style={{ fontSize: 32 }}>💰</span>}
          subtitle={`${me?.account_count ?? 0} ${t('accounts')}`}
          after={
            <button
              onClick={() => onNavigate?.('settings')}
              style={{ background: 'none', border: 'none', fontSize: 20, cursor: 'pointer', color: '#888', padding: 4 }}
              title={t('settings')}
            >
              ⚙️
            </button>
          }
        >
          <Headline weight="2">
            {fmt(me?.total_balance ?? 0, me?.default_currency ?? 'USD')}
          </Headline>
        </Cell>
      </Section>

      <Section header={t('accounts')}>
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
        <Cell
          onClick={() => onNavigate?.('accounts')}
          style={{ cursor: 'pointer', color: '#007aff' }}
        >
          {t('manage_accounts')} →
        </Cell>
      </Section>

      <Section header={t('recent_transactions')}>
        {recent.length === 0 ? (
          <Cell>{t('no_transactions')}</Cell>
        ) : (
          recent.map((tx) => (
            <Cell
              key={tx.id}
              before={<span style={{ fontSize: 22 }}>{tx.category?.icon ?? txnEmoji(tx.type)}</span>}
              subtitle={tx.category?.name ?? tx.merchant ?? '—'}
              after={
                <Text style={{ color: txnColor(tx.type), fontWeight: 600 }}>
                  {txnSign(tx.type)}{fmt(tx.amount, tx.currency)}
                </Text>
              }
            >
              {tx.merchant || tx.description || tx.account?.name || tx.type}
            </Cell>
          ))
        )}
      </Section>

      <Section>
        <Cell
          before={<span style={{ fontSize: 20 }}>📂</span>}
          onClick={() => onNavigate?.('categories')}
          style={{ cursor: 'pointer' }}
        >
          {t('categories')}
        </Cell>
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
