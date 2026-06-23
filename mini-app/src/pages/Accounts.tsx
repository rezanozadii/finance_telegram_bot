import { useEffect, useState } from 'react';
import { Cell, List, Section, Spinner, Button, Input, Select } from '@telegram-apps/telegram-ui';
import { api } from '../api/client';
import { useLang } from '../LangContext';
import type { Account } from '../types';

function fmt(amount: number, currency: string) {
  return `${currency} ${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function acctIcon(type: string) {
  return { cash: '💵', card: '💳', bank: '🏦', 'e-wallet': '📱', credit: '💳' }[type] ?? '💰';
}

interface Props {
  onBack: () => void;
}

export function Accounts({ onBack }: Props) {
  const { t } = useLang();
  const [accounts, setAccounts] = useState<Account[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [showForm, setShowForm] = useState(false);
  const [saving, setSaving] = useState(false);

  const [name, setName] = useState('');
  const [type, setType] = useState<'cash' | 'card' | 'bank' | 'e-wallet' | 'credit'>('cash');
  const [currency, setCurrency] = useState('USD');
  const [balance, setBalance] = useState('0');
  const [formError, setFormError] = useState('');

  useEffect(() => {
    api.accounts()
      .then(setAccounts)
      .catch((e: Error) => setError(e.message))
      .finally(() => setLoading(false));
  }, []);

  async function handleAdd() {
    if (!name.trim()) { setFormError(t('account_name')); return; }
    setSaving(true);
    setFormError('');
    try {
      const acc = await api.createAccount({
        name: name.trim(),
        type,
        currency: currency.trim().toUpperCase(),
        balance: parseFloat(balance) || 0,
      });
      setAccounts((prev) => [...prev, acc]);
      setName(''); setBalance('0'); setShowForm(false);
    } catch (e: unknown) {
      setFormError(e instanceof Error ? e.message : 'Error');
    } finally {
      setSaving(false);
    }
  }

  const ACCOUNT_TYPES: { value: typeof type; labelKey: string }[] = [
    { value: 'cash',     labelKey: 'type_cash' },
    { value: 'card',     labelKey: 'type_card' },
    { value: 'bank',     labelKey: 'type_bank' },
    { value: 'e-wallet', labelKey: 'type_ewallet' },
    { value: 'credit',   labelKey: 'type_credit' },
  ];

  return (
    <List>
      <Section>
        <Cell
          before={
            <button
              onClick={onBack}
              style={{ background: 'none', border: 'none', fontSize: 22, cursor: 'pointer', color: '#007aff', padding: 0, lineHeight: 1 }}
            >
              ←
            </button>
          }
        >
          <span style={{ fontWeight: 600, fontSize: 17 }}>{t('manage_accounts')}</span>
        </Cell>
      </Section>

      {loading && <div style={{ display: 'flex', justifyContent: 'center', padding: 40 }}><Spinner size="m" /></div>}
      {error && <Section><Cell style={{ color: '#ff3b30' }}>{error}</Cell></Section>}

      {!loading && !error && (
        <>
          {accounts.length === 0 && !showForm && (
            <Section><Cell subtitle={t('add_account')}>{t('no_accounts')}</Cell></Section>
          )}

          {accounts.length > 0 && (
            <Section header={t('accounts')}>
              {accounts.map((a) => (
                <Cell
                  key={a.id}
                  before={<span style={{ fontSize: 22 }}>{acctIcon(a.type)}</span>}
                  subtitle={`${a.type} · ${a.currency}`}
                  after={
                    <span style={{ color: a.balance < 0 ? '#ff3b30' : '#34c759', fontWeight: 600 }}>
                      {fmt(a.balance, a.currency)}
                    </span>
                  }
                >
                  {a.name}
                </Cell>
              ))}
            </Section>
          )}

          {showForm ? (
            <Section header={t('add_account')}>
              <Cell>
                <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
                  <Input
                    placeholder={t('account_name')}
                    value={name}
                    onChange={(e) => setName(e.target.value)}
                  />
                  <Select value={type} onChange={(e) => setType(e.target.value as typeof type)}>
                    {ACCOUNT_TYPES.map((at) => (
                      <option key={at.value} value={at.value}>{t(at.labelKey as any)}</option>
                    ))}
                  </Select>
                  <Input
                    placeholder={t('default_currency')}
                    value={currency}
                    onChange={(e) => setCurrency(e.target.value)}
                    style={{ textTransform: 'uppercase' }}
                  />
                  <Input
                    placeholder={t('initial_balance')}
                    value={balance}
                    type="number"
                    onChange={(e) => setBalance(e.target.value)}
                  />
                  {formError && <span style={{ color: '#ff3b30', fontSize: 13 }}>{formError}</span>}
                  <div style={{ display: 'flex', gap: 8 }}>
                    <Button size="s" mode="filled" onClick={handleAdd} disabled={saving} style={{ flex: 1 }}>
                      {saving ? '…' : t('save')}
                    </Button>
                    <Button size="s" mode="outline" onClick={() => { setShowForm(false); setFormError(''); }} style={{ flex: 1 }}>
                      {t('cancel')}
                    </Button>
                  </div>
                </div>
              </Cell>
            </Section>
          ) : (
            <Section>
              <Cell>
                <Button size="s" mode="filled" onClick={() => setShowForm(true)} style={{ width: '100%' }}>
                  {t('add_account')}
                </Button>
              </Cell>
            </Section>
          )}
        </>
      )}
    </List>
  );
}
