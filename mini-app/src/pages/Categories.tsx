import { useEffect, useState } from 'react';
import { Cell, List, Section, Spinner } from '@telegram-apps/telegram-ui';
import { api } from '../api/client';
import { useLang } from '../LangContext';
import type { Category } from '../types';

interface Props {
  onBack: () => void;
}

export function Categories({ onBack }: Props) {
  const { t } = useLang();
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    api.categories()
      .then(setCategories)
      .catch((e: Error) => setError(e.message))
      .finally(() => setLoading(false));
  }, []);

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
        >
          <span style={{ fontWeight: 600, fontSize: 17 }}>{t('categories')}</span>
        </Cell>
      </Section>

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
