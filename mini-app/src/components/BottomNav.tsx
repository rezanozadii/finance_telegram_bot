import { useLang } from '../LangContext';

export type Tab = 'dashboard' | 'transactions' | 'report' | 'friends' | 'ai';

interface Props {
  active: Tab;
  onChange: (tab: Tab) => void;
}

export function BottomNav({ active, onChange }: Props) {
  const { t } = useLang();

  const TABS: { id: Tab; icon: string; label: string }[] = [
    { id: 'dashboard',    icon: '🏠', label: t('nav_home') },
    { id: 'transactions', icon: '💳', label: t('nav_transactions') },
    { id: 'report',       icon: '📊', label: t('nav_report') },
    { id: 'friends',      icon: '👥', label: t('nav_friends') },
    { id: 'ai',           icon: '🤖', label: t('nav_ai') },
  ];

  return (
    <div
      style={{
        display: 'flex',
        width: '100%',
      }}
    >
      {TABS.map((tab) => (
        <button
          key={tab.id}
          onClick={() => onChange(tab.id)}
          style={{
            flex: 1,
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            justifyContent: 'center',
            gap: 2,
            padding: '6px 0',
            background: 'none',
            border: 'none',
            cursor: 'pointer',
            color: active === tab.id
              ? 'var(--tg-theme-button-color, #007aff)'
              : 'var(--tg-theme-hint-color, #8e8e93)',
            fontSize: 10,
            fontWeight: active === tab.id ? 600 : 400,
            transition: 'color 0.15s',
            WebkitTapHighlightColor: 'transparent',
          }}
        >
          <span style={{ fontSize: 22, lineHeight: 1 }}>{tab.icon}</span>
          <span>{tab.label}</span>
        </button>
      ))}
    </div>
  );
}
