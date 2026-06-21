import { useEffect, useRef, useState } from 'react';
import { Button, Cell, List, Modal, Section, Select, Spinner, Text } from '@telegram-apps/telegram-ui';
import { api } from '../api/client';
import type { Account, Category, Transaction } from '../types';

function fmt(amount: number, currency: string) {
  return `${currency} ${amount.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

const SIGN = { income: '+', expense: '-', transfer: '⇄' } as const;
const COLOR = { income: '#34c759', expense: '#ff3b30', transfer: '#007aff' } as const;

export function Transactions() {
  const [items, setItems]       = useState<Transaction[]>([]);
  const [total, setTotal]       = useState(0);
  const [accounts, setAccounts] = useState<Account[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading]   = useState(true);
  const [addOpen, setAddOpen]   = useState(false);
  const [filterType, setFilterType] = useState('');
  const offset = useRef(0);
  const LIMIT = 20;

  const load = (reset = false) => {
    const off = reset ? 0 : offset.current;
    api.transactions({ limit: LIMIT, offset: off, type: filterType || undefined })
      .then((r) => {
        setItems((prev) => reset ? r.data : [...prev, ...r.data]);
        setTotal(r.meta.total);
        offset.current = off + r.data.length;
      })
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    setLoading(true);
    offset.current = 0;
    load(true);
    api.accounts().then(setAccounts);
    api.categories().then(setCategories);
  }, [filterType]);

  const handleDelete = (id: number) => {
    api.deleteTransaction(id).then(() =>
      setItems((prev) => prev.filter((t) => t.id !== id))
    );
  };

  return (
    <>
      <List>
        <Section>
          <Cell>
            <div style={{ display: 'flex', gap: 8, alignItems: 'center' }}>
              <Select value={filterType} onChange={(e) => setFilterType(e.target.value)} style={{ flex: 1 }}>
                <option value="">All types</option>
                <option value="income">Income</option>
                <option value="expense">Expense</option>
                <option value="transfer">Transfer</option>
              </Select>
              <Button size="s" onClick={() => setAddOpen(true)}>+ Add</Button>
            </div>
          </Cell>
        </Section>

        {loading && <div style={{ display: 'flex', justifyContent: 'center', padding: 20 }}><Spinner size="m" /></div>}

        <Section header={`Transactions (${total})`}>
          {items.map((t) => (
            <Cell
              key={t.id}
              before={<span style={{ fontSize: 22 }}>{t.category?.icon ?? '📦'}</span>}
              subtitle={`${t.account?.name ?? ''} · ${new Date(t.occurred_at).toLocaleDateString()}`}
              after={
                <div style={{ display: 'flex', alignItems: 'center', gap: 8 }}>
                  <Text style={{ color: COLOR[t.type], fontWeight: 600 }}>
                    {SIGN[t.type]}{fmt(t.amount, t.currency)}
                  </Text>
                  <span
                    style={{ cursor: 'pointer', color: '#ff3b30', fontSize: 18 }}
                    onClick={() => handleDelete(t.id)}
                  >×</span>
                </div>
              }
            >
              {t.merchant || t.description || t.category?.name || t.type}
            </Cell>
          ))}
        </Section>

        {offset.current < total && (
          <Section>
            <Cell onClick={() => load()}>
              <Button mode="outline" stretched onClick={() => load()}>Load more</Button>
            </Cell>
          </Section>
        )}
      </List>

      <Modal open={addOpen} onOpenChange={setAddOpen} header={<Modal.Header>Add Transaction</Modal.Header>}>
        <AddForm
          accounts={accounts}
          categories={categories}
          onSave={(txn) => { setItems((p) => [txn, ...p]); setTotal((n) => n + 1); setAddOpen(false); }}
          onCancel={() => setAddOpen(false)}
        />
      </Modal>
    </>
  );
}

interface AddFormProps {
  accounts: Account[];
  categories: Category[];
  onSave: (t: Transaction) => void;
  onCancel: () => void;
}

function AddForm({ accounts, categories, onSave, onCancel }: AddFormProps) {
  const [type, setType]         = useState<'income' | 'expense'>('expense');
  const [amount, setAmount]     = useState('');
  const [accountId, setAccountId] = useState(accounts[0]?.id?.toString() ?? '');
  const [categoryId, setCategoryId] = useState('');
  const [description, setDesc]  = useState('');
  const [saving, setSaving]     = useState(false);
  const [error, setError]       = useState('');

  const filteredCats = categories.filter((c) => c.type === type);
  const account      = accounts.find((a) => a.id === Number(accountId));

  const submit = () => {
    if (!amount || !accountId) { setError('Amount and account are required'); return; }
    setSaving(true);
    api.createTransaction({
      account_id:  Number(accountId),
      type,
      amount:      parseFloat(amount),
      currency:    account?.currency ?? 'USD',
      category_id: categoryId ? Number(categoryId) : null,
      description,
    })
      .then(onSave)
      .catch((e: Error) => setError(e.message))
      .finally(() => setSaving(false));
  };

  const inputStyle: React.CSSProperties = {
    width: '100%', padding: '10px 12px', borderRadius: 8, border: '1px solid var(--tg-theme-hint-color, #ccc)',
    background: 'transparent', color: 'inherit', fontSize: 15, boxSizing: 'border-box',
  };
  const labelStyle: React.CSSProperties = { display: 'block', marginBottom: 4, fontSize: 13, color: 'var(--tg-theme-hint-color, #888)' };

  return (
    <div style={{ padding: '0 16px 24px' }}>
      {error && <p style={{ color: '#ff3b30', marginBottom: 8 }}>{error}</p>}

      <div style={{ marginBottom: 12 }}>
        <label style={labelStyle}>Type</label>
        <Select value={type} onChange={(e) => setType(e.target.value as 'income' | 'expense')} style={inputStyle}>
          <option value="expense">Expense</option>
          <option value="income">Income</option>
        </Select>
      </div>

      <div style={{ marginBottom: 12 }}>
        <label style={labelStyle}>Amount</label>
        <input
          type="number" min="0.01" step="0.01" placeholder="0.00"
          value={amount} onChange={(e) => setAmount(e.target.value)}
          style={inputStyle}
        />
      </div>

      <div style={{ marginBottom: 12 }}>
        <label style={labelStyle}>Account</label>
        <Select value={accountId} onChange={(e) => setAccountId(e.target.value)} style={inputStyle}>
          {accounts.map((a) => <option key={a.id} value={a.id}>{a.name} ({a.currency})</option>)}
        </Select>
      </div>

      <div style={{ marginBottom: 12 }}>
        <label style={labelStyle}>Category</label>
        <Select value={categoryId} onChange={(e) => setCategoryId(e.target.value)} style={inputStyle}>
          <option value="">Uncategorized</option>
          {filteredCats.map((c) => <option key={c.id} value={c.id}>{c.icon} {c.name}</option>)}
        </Select>
      </div>

      <div style={{ marginBottom: 20 }}>
        <label style={labelStyle}>Description</label>
        <input
          type="text" placeholder="Optional note"
          value={description} onChange={(e) => setDesc(e.target.value)}
          style={inputStyle}
        />
      </div>

      <div style={{ display: 'flex', gap: 8 }}>
        <Button mode="outline" stretched onClick={onCancel}>Cancel</Button>
        <Button stretched onClick={submit} disabled={saving}>
          {saving ? <Spinner size="s" /> : 'Save'}
        </Button>
      </div>
    </div>
  );
}
