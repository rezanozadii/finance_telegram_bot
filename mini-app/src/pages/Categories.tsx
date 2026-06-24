import { useEffect, useState } from 'react';
import { Button, Cell, Input, List, Section, Select, Spinner } from '@telegram-apps/telegram-ui';
import { api } from '../api/client';
import { useLang } from '../LangContext';
import type { Category } from '../types';

interface Props {
  onBack: () => void;
}

export function Categories({ onBack }: Props) {
  const { t, lang } = useLang();
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading]       = useState(true);
  const [error, setError]           = useState('');
  const [showForm, setShowForm]     = useState(false);
  const [saving, setSaving]         = useState(false);
  const [formError, setFormError]   = useState('');

  const [newName, setNewName] = useState('');
  const [newType, setNewType] = useState<'income' | 'expense'>('expense');
  const [newIcon, setNewIcon] = useState('');

  useEffect(() => {
    setLoading(true);
    setError('');
    api.categories()
      .then(setCategories)
      .catch((e: Error) => setError(e.message))
      .finally(() => setLoading(false));
  }, [lang]);

  async function handleCreate() {
    if (!newName.trim()) return;
    setSaving(true);
    setFormError('');
    try {
      const created = await api.createCategory({
        name: newName.trim(),
        type: newType,
        icon: newIcon.trim() || undefined,
      });
      setCategories((prev) => [...prev, created]);
      setShowForm(false);
      setNewName('');
      setNewIcon('');
      setNewType('expense');
    } catch (e: unknown) {
      setFormError(e instanceof Error ? e.message : 'Error');
    } finally {
      setSaving(false);
    }
  }

  const income  = categories.filter((c) => c.type === 'income'  && !c.parent_id);
  const expense = categories.filter((c) => c.type === 'expense' && !c.parent_id);

  function renderCategory(cat: Category) {
    const children = categories.filter((c) => c.parent_id === cat.id);
    return (
      <div key={cat.id}>
        <Cell before={<span style={{ fontSize: 20 }}>{cat.icon ?? '📂'}</span>}>
          {cat.name}
        </Cell>
        {children.map((sub) => (
          <Cell
            key={sub.id}
            before={<span style={{ fontSize: 18, marginLeft: 16 }}>{sub.icon ?? '•'}</span>}
            style={{ paddingLeft: 32 }}
          >
            {sub.name}
          </Cell>
        ))}
      </div>
    );
  }

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
          after={
            <button
              onClick={() => { setShowForm((v) => !v); setFormError(''); }}
              style={{ background: 'none', border: 'none', fontSize: 22, cursor: 'pointer', color: '#007aff', padding: 0, lineHeight: 1 }}
            >
              {showForm ? '✕' : '＋'}
            </button>
          }
        >
          <span style={{ fontWeight: 600, fontSize: 17 }}>{t('categories')}</span>
        </Cell>
      </Section>

      {showForm && (
        <Section header={t('add_category')}>
          <Cell>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 10 }}>
              <Input
                placeholder={t('category_name')}
                value={newName}
                onChange={(e) => setNewName(e.target.value)}
              />
              <Input
                placeholder={t('category_icon')}
                value={newIcon}
                onChange={(e) => setNewIcon(e.target.value)}
                maxLength={4}
              />
              <Select
                value={newType}
                onChange={(e) => setNewType(e.target.value as 'income' | 'expense')}
              >
                <option value="expense">{t('cat_type_expense')}</option>
                <option value="income">{t('cat_type_income')}</option>
              </Select>
              {formError && (
                <div style={{ color: '#ff3b30', fontSize: 13 }}>{formError}</div>
              )}
              <div style={{ display: 'flex', gap: 8 }}>
                <Button
                  size="s"
                  mode="filled"
                  onClick={handleCreate}
                  disabled={saving || !newName.trim()}
                  style={{ flex: 1 }}
                >
                  {saving ? '…' : t('save')}
                </Button>
                <Button
                  size="s"
                  mode="outline"
                  onClick={() => { setShowForm(false); setFormError(''); }}
                  style={{ flex: 1 }}
                >
                  {t('cancel')}
                </Button>
              </div>
            </div>
          </Cell>
        </Section>
      )}

      {loading && <div style={{ display: 'flex', justifyContent: 'center', padding: 40 }}><Spinner size="m" /></div>}
      {error && <Section><Cell style={{ color: '#ff3b30' }}>{error}</Cell></Section>}

      {!loading && !error && categories.length === 0 && (
        <Section><Cell>{t('no_categories')}</Cell></Section>
      )}

      {income.length > 0 && (
        <Section header={t('income_categories')}>
          {income.map(renderCategory)}
        </Section>
      )}

      {expense.length > 0 && (
        <Section header={t('expense_categories')}>
          {expense.map(renderCategory)}
        </Section>
      )}
    </List>
  );
}
