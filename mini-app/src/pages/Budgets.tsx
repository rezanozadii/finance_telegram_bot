import { useEffect, useState } from 'react';
import { Cell, List, Section, Spinner, Text } from '@telegram-apps/telegram-ui';
import { api } from '../api/client';
import { useLang } from '../LangContext';
import type { Budget, Category } from '../types';

function statusColor(status: Budget['status']) {
  return { safe: '#34c759', warning: '#ff9500', critical: '#ff9500', exceeded: '#ff3b30' }[status];
}

function ProgressBar({ pct, status }: { pct: number; status: Budget['status'] }) {
  return (
    <div style={{ background: '#e0e0e0', borderRadius: 4, height: 8, marginTop: 4, overflow: 'hidden' }}>
      <div style={{ background: statusColor(status), width: `${Math.min(100, pct)}%`, height: 8, borderRadius: 4 }} />
    </div>
  );
}

interface ModalProps {
  onClose: () => void;
  onSave: (data: { name: string; amount: number; currency: string; period: string; category_id?: number }) => Promise<void>;
  categories: Category[];
}

function AddBudgetModal({ onClose, onSave, categories }: ModalProps) {
  const { t } = useLang();
  const [name, setName] = useState('');
  const [amount, setAmount] = useState('');
  const [currency, setCurrency] = useState('USD');
  const [period, setPeriod] = useState('monthly');
  const [categoryId, setCategoryId] = useState<number | undefined>();
  const [saving, setSaving] = useState(false);

  async function submit() {
    if (!name || !amount) return;
    setSaving(true);
    try {
      await onSave({ name, amount: parseFloat(amount), currency, period, category_id: categoryId });
      onClose();
    } finally {
      setSaving(false);
    }
  }

  return (
    <div style={{ position: 'fixed', inset: 0, background: 'var(--tg-theme-bg-color, #fff)', zIndex: 200, padding: 20, overflowY: 'auto' }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 }}>
        <Text style={{ fontWeight: 700, fontSize: 18 }}>{t('add_budget')}</Text>
        <button onClick={onClose} style={{ background: 'none', border: 'none', fontSize: 22, cursor: 'pointer' }}>✕</button>
      </div>

      {[['budget_name', name, setName], ['budget_amount', amount, setAmount]].map(([label, val, setter]: any) => (
        <div key={label} style={{ marginBottom: 14 }}>
          <label style={{ fontSize: 13, color: '#888', display: 'block', marginBottom: 4 }}>{t(label)}</label>
          <input
            type={label === 'budget_amount' ? 'number' : 'text'}
            value={val}
            onChange={(e) => setter(e.target.value)}
            style={inputStyle}
          />
        </div>
      ))}

      <div style={{ marginBottom: 14 }}>
        <label style={{ fontSize: 13, color: '#888', display: 'block', marginBottom: 4 }}>Currency</label>
        <input value={currency} onChange={(e) => setCurrency(e.target.value.toUpperCase())} maxLength={3} style={inputStyle} />
      </div>

      <div style={{ marginBottom: 14 }}>
        <label style={{ fontSize: 13, color: '#888', display: 'block', marginBottom: 4 }}>{t('period')}</label>
        <select value={period} onChange={(e) => setPeriod(e.target.value)} style={{ ...inputStyle, appearance: 'auto' }}>
          <option value="monthly">{t('monthly')}</option>
          <option value="weekly">{t('weekly')}</option>
          <option value="yearly">{t('yearly')}</option>
        </select>
      </div>

      {categories.length > 0 && (
        <div style={{ marginBottom: 20 }}>
          <label style={{ fontSize: 13, color: '#888', display: 'block', marginBottom: 4 }}>Category (optional)</label>
          <select value={categoryId ?? ''} onChange={(e) => setCategoryId(e.target.value ? Number(e.target.value) : undefined)} style={{ ...inputStyle, appearance: 'auto' }}>
            <option value="">— None —</option>
            {categories.filter(c => c.type === 'expense').map((c) => (
              <option key={c.id} value={c.id}>{c.icon ? `${c.icon} ` : ''}{c.name}</option>
            ))}
          </select>
        </div>
      )}

      <button onClick={submit} disabled={saving || !name || !amount} style={btnStyle}>
        {saving ? '...' : t('save')}
      </button>
    </div>
  );
}

const inputStyle: React.CSSProperties = {
  width: '100%', padding: '10px 12px', borderRadius: 10, border: '1px solid #ddd',
  fontSize: 15, boxSizing: 'border-box', background: 'var(--tg-theme-secondary-bg-color, #f8f8f8)', color: 'inherit',
};
const btnStyle: React.CSSProperties = {
  width: '100%', padding: 14, borderRadius: 12, background: '#007aff', color: '#fff',
  border: 'none', fontSize: 16, fontWeight: 600, cursor: 'pointer',
};

export function Budgets(_props: { onBack?: () => void }) {
  const { t } = useLang();
  const [budgets, setBudgets] = useState<Budget[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [showModal, setShowModal] = useState(false);

  function load() {
    setLoading(true);
    Promise.all([api.budgets(), api.categories()])
      .then(([b, c]) => { setBudgets(b); setCategories(c); })
      .catch((e: Error) => setError(e.message))
      .finally(() => setLoading(false));
  }

  useEffect(load, []);

  async function handleCreate(data: { name: string; amount: number; currency: string; period: string; category_id?: number }) {
    const budget = await api.createBudget(data);
    setBudgets((prev) => [...prev, budget]);
  }

  async function handleDelete(id: number) {
    await api.deleteBudget(id);
    setBudgets((prev) => prev.filter((b) => b.id !== id));
  }

  const statusLabel = (s: Budget['status']) => t(('status_' + s) as any);

  if (loading) return <div style={{ display: 'flex', justifyContent: 'center', padding: 40 }}><Spinner size="m" /></div>;

  return (
    <>
      {showModal && <AddBudgetModal onClose={() => setShowModal(false)} onSave={handleCreate} categories={categories} />}
      <List>
        <Section>
          <Cell onClick={() => setShowModal(true)} style={{ cursor: 'pointer' }}>
            <Text style={{ color: '#007aff', fontWeight: 600 }}>+ {t('add_budget')}</Text>
          </Cell>
        </Section>

        {error && <Section><Cell style={{ color: '#ff3b30' }}>{error}</Cell></Section>}

        {budgets.length === 0 ? (
          <Section><Cell>{t('no_budgets')}</Cell></Section>
        ) : (
          <Section>
            {budgets.map((b) => (
              <Cell
                key={b.id}
                before={<span style={{ fontSize: 22 }}>💼</span>}
                subtitle={
                  <div>
                    <ProgressBar pct={b.pct_used} status={b.status} />
                    <div style={{ fontSize: 12, color: '#888', marginTop: 4 }}>
                      {t('spent')}: {b.currency} {b.spent_amount.toFixed(2)} / {b.amount.toFixed(2)} · {t(('period') as any)}: {t((b.period) as any)}
                    </div>
                  </div>
                }
                after={
                  <div style={{ display: 'flex', flexDirection: 'column', alignItems: 'flex-end', gap: 4 }}>
                    <Text style={{ color: statusColor(b.status), fontSize: 12, fontWeight: 600 }}>{statusLabel(b.status)}</Text>
                    <button
                      onClick={() => handleDelete(b.id)}
                      style={{ background: 'none', border: 'none', color: '#ff3b30', fontSize: 16, cursor: 'pointer' }}
                    >
                      🗑
                    </button>
                  </div>
                }
              >
                {b.name} {b.category ? `· ${b.category.name}` : ''}
              </Cell>
            ))}
          </Section>
        )}
      </List>
    </>
  );
}
