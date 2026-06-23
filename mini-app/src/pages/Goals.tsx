import { useEffect, useState } from 'react';
import { Cell, List, Section, Spinner, Text } from '@telegram-apps/telegram-ui';
import { api } from '../api/client';
import { useLang } from '../LangContext';
import type { UserGoal } from '../types';

function fmt(n: number, currency: string) {
  return `${currency} ${n.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function ProgressBar({ pct }: { pct: number }) {
  const color = pct >= 100 ? '#34c759' : '#007aff';
  return (
    <div style={{ background: '#e0e0e0', borderRadius: 4, height: 8, marginTop: 4, overflow: 'hidden' }}>
      <div style={{ background: color, width: `${Math.min(100, pct)}%`, height: 8, borderRadius: 4 }} />
    </div>
  );
}

interface ModalProps {
  onClose: () => void;
  onSave: (data: { name: string; target_amount: number; currency: string; deadline?: string }) => void;
}

function AddGoalModal({ onClose, onSave }: ModalProps) {
  const { t } = useLang();
  const [name, setName] = useState('');
  const [amount, setAmount] = useState('');
  const [currency, setCurrency] = useState('USD');
  const [deadline, setDeadline] = useState('');
  const [saving, setSaving] = useState(false);

  async function submit() {
    if (!name || !amount) return;
    setSaving(true);
    try {
      await onSave({ name, target_amount: parseFloat(amount), currency, deadline: deadline || undefined });
      onClose();
    } finally {
      setSaving(false);
    }
  }

  return (
    <div style={{ position: 'fixed', inset: 0, background: 'var(--tg-theme-bg-color, #fff)', zIndex: 200, padding: 20, overflowY: 'auto' }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20 }}>
        <Text style={{ fontWeight: 700, fontSize: 18 }}>{t('add_goal')}</Text>
        <button onClick={onClose} style={{ background: 'none', border: 'none', fontSize: 22, cursor: 'pointer' }}>✕</button>
      </div>
      {(['goal_name', 'target_amount', 'deadline'] as const).map((key) => (
        <div key={key} style={{ marginBottom: 14 }}>
          <label style={{ fontSize: 13, color: '#888', display: 'block', marginBottom: 4 }}>{t(key)}</label>
          <input
            type={key === 'target_amount' ? 'number' : key === 'deadline' ? 'date' : 'text'}
            value={key === 'goal_name' ? name : key === 'target_amount' ? amount : deadline}
            onChange={(e) => {
              if (key === 'goal_name') setName(e.target.value);
              else if (key === 'target_amount') setAmount(e.target.value);
              else setDeadline(e.target.value);
            }}
            style={inputStyle}
          />
        </div>
      ))}
      <div style={{ marginBottom: 20 }}>
        <label style={{ fontSize: 13, color: '#888', display: 'block', marginBottom: 4 }}>{t('currency')}</label>
        <input value={currency} onChange={(e) => setCurrency(e.target.value.toUpperCase())} maxLength={3} style={inputStyle} />
      </div>
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

export function Goals(_props: { onBack?: () => void }) {
  const { t } = useLang();
  const [goals, setGoals] = useState<UserGoal[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [showModal, setShowModal] = useState(false);

  function load() {
    setLoading(true);
    api.goals()
      .then(setGoals)
      .catch((e: Error) => setError(e.message))
      .finally(() => setLoading(false));
  }

  useEffect(load, []);

  async function handleCreate(data: { name: string; target_amount: number; currency: string; deadline?: string }) {
    const goal = await api.createGoal(data);
    setGoals((prev) => [...prev, goal]);
  }

  async function handleDelete(id: number) {
    await api.deleteGoal(id);
    setGoals((prev) => prev.filter((g) => g.id !== id));
  }

  if (loading) return <div style={{ display: 'flex', justifyContent: 'center', padding: 40 }}><Spinner size="m" /></div>;

  return (
    <>
      {showModal && <AddGoalModal onClose={() => setShowModal(false)} onSave={handleCreate} />}
      <List>
        <Section>
          <Cell onClick={() => setShowModal(true)} style={{ cursor: 'pointer' }}>
            <Text style={{ color: '#007aff', fontWeight: 600 }}>+ {t('add_goal')}</Text>
          </Cell>
        </Section>

        {error && <Section><Cell style={{ color: '#ff3b30' }}>{error}</Cell></Section>}

        {goals.length === 0 ? (
          <Section><Cell>{t('no_goals')}</Cell></Section>
        ) : (
          <Section>
            {goals.map((goal) => {
              const pct = goal.target_amount > 0 ? (goal.current_amount / goal.target_amount) * 100 : 0;
              const remaining = goal.target_amount - goal.current_amount;
              return (
                <Cell
                  key={goal.id}
                  before={<span style={{ fontSize: 22 }}>🎯</span>}
                  subtitle={
                    <div>
                      <ProgressBar pct={pct} />
                      <div style={{ fontSize: 12, color: '#888', marginTop: 4 }}>
                        {t('progress')}: {pct.toFixed(1)}% · {t('remaining')}: {fmt(remaining, goal.currency)}
                        {goal.deadline && ` · ${goal.deadline}`}
                      </div>
                    </div>
                  }
                  after={
                    <button
                      onClick={() => handleDelete(goal.id)}
                      style={{ background: 'none', border: 'none', color: '#ff3b30', fontSize: 18, cursor: 'pointer' }}
                    >
                      🗑
                    </button>
                  }
                >
                  {goal.name} — {fmt(goal.target_amount, goal.currency)}
                </Cell>
              );
            })}
          </Section>
        )}
      </List>
    </>
  );
}
