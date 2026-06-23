import { createContext, useContext, useState, type ReactNode } from 'react';
import { type Lang, i18n } from './i18n';

type Ctx = {
  lang: Lang;
  setLang: (l: Lang) => void;
  t: (key: keyof typeof i18n.en) => string;
  dir: 'ltr' | 'rtl';
};

const LangContext = createContext<Ctx>({
  lang: 'en',
  setLang: () => {},
  t: (k) => k as string,
  dir: 'ltr',
});

interface Props {
  children: ReactNode;
  initialLang?: Lang;
}

export function LangProvider({ children, initialLang = 'en' }: Props) {
  const [lang, setLang] = useState<Lang>(initialLang);
  const dir = lang === 'fa' ? 'rtl' : 'ltr';
  const t = (key: keyof typeof i18n.en): string => i18n[lang][key] ?? i18n.en[key] ?? key as string;
  return (
    <LangContext.Provider value={{ lang, setLang, t, dir }}>
      {children}
    </LangContext.Provider>
  );
}

export const useLang = () => useContext(LangContext);
