import { Tabbar } from '@telegram-apps/telegram-ui';
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
    <Tabbar>
      {TABS.map((tab) => (
        <Tabbar.Item
          key={tab.id}
          text={tab.label}
          selected={active === tab.id}
          onClick={() => onChange(tab.id)}
        >
          <span style={{ fontSize: 22 }}>{tab.icon}</span>
        </Tabbar.Item>
      ))}
    </Tabbar>
  );
}
