import { Cell, List, Section, Headline } from '@telegram-apps/telegram-ui';
import { useLang } from '../LangContext';
import type { AiPage } from '../types';

interface Props {
  onNavigate: (page: AiPage) => void;
}

interface HubItem {
  page: AiPage;
  icon: string;
  titleKey: string;
  subKey: string;
}

const ITEMS: HubItem[] = [
  { page: 'chat',          icon: '🤖', titleKey: 'ai_chat',        subKey: 'ai_chat_sub' },
  { page: 'health',        icon: '❤️', titleKey: 'health_score',   subKey: 'health_score_sub' },
  { page: 'goals',         icon: '🎯', titleKey: 'goals',          subKey: 'goals_sub' },
  { page: 'budgets',       icon: '💼', titleKey: 'budgets',        subKey: 'budgets_sub' },
  { page: 'forecast',      icon: '🔮', titleKey: 'forecast',       subKey: 'forecast_sub' },
  { page: 'subscriptions', icon: '🔄', titleKey: 'subscriptions',  subKey: 'subscriptions_sub' },
  { page: 'insights',      icon: '💡', titleKey: 'daily_insights', subKey: 'daily_insights_sub' },
];

export function AiHub({ onNavigate }: Props) {
  const { t } = useLang();

  return (
    <List>
      <Section>
        <Cell>
          <Headline weight="2">🤖 {t('ai_hub_title' as any)}</Headline>
        </Cell>
      </Section>
      <Section>
        {ITEMS.map((item) => (
          <Cell
            key={item.page}
            before={<span style={{ fontSize: 26 }}>{item.icon}</span>}
            subtitle={t(item.subKey as any)}
            onClick={() => onNavigate(item.page)}
            style={{ cursor: 'pointer' }}
          >
            {t(item.titleKey as any)}
          </Cell>
        ))}
      </Section>
    </List>
  );
}
