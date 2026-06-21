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

export function LangProvider({ children }: { children: ReactNode }) {
  const [lang, setLang] = useState<Lang>('en');
  const dir = lang === 'fa' ? 'rtl' : 'ltr';
  const t = (key: keyof typeof i18n.en): string => i18n[lang][key];
  return (
    <LangContext.Provider value={{ lang, setLang, t, dir }}>
      {children}
    </LangContext.Provider>
  );
}

export const useLang = () => useContext(LangContext);
