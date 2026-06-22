import { useEffect, useRef, useState } from 'react';
import { Spinner } from '@telegram-apps/telegram-ui';
import { api } from '../api/client';
import { useLang } from '../LangContext';
import type { ChatMessage } from '../types';

interface Props {
  onBack: () => void;
  defaultCurrency?: string;
}

export function AiChat({ defaultCurrency = 'USD' }: Props) {
  const { t } = useLang();
  const [messages, setMessages] = useState<ChatMessage[]>([]);
  const [input, setInput] = useState('');
  const [loading, setLoading] = useState(false);
  const bottomRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    bottomRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [messages, loading]);

  async function send() {
    const text = input.trim();
    if (!text || loading) return;
    setInput('');
    setMessages((prev) => [...prev, { role: 'user', content: text }]);
    setLoading(true);
    try {
      const res = await api.aiChat(text, defaultCurrency);
      setMessages((prev) => [...prev, { role: 'ai', content: res.response }]);
    } catch {
      setMessages((prev) => [...prev, { role: 'ai', content: '⚠️ Error getting response.' }]);
    } finally {
      setLoading(false);
    }
  }

  function onKey(e: React.KeyboardEvent) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
  }

  return (
    <div style={{ display: 'flex', flexDirection: 'column', height: 'calc(100dvh - 130px)' }}>
      <div style={{ flex: 1, overflowY: 'auto', padding: '12px 16px', display: 'flex', flexDirection: 'column', gap: 10 }}>
        {messages.length === 0 && (
          <div style={{ textAlign: 'center', color: '#888', marginTop: 40 }}>
            <div style={{ fontSize: 48, marginBottom: 12 }}>🤖</div>
            <div>Ask me anything about your finances</div>
          </div>
        )}
        {messages.map((msg, i) => (
          <div
            key={i}
            style={{
              alignSelf: msg.role === 'user' ? 'flex-end' : 'flex-start',
              maxWidth: '80%',
              background: msg.role === 'user' ? '#007aff' : 'var(--tg-theme-secondary-bg-color, #f0f0f0)',
              color: msg.role === 'user' ? '#fff' : 'inherit',
              borderRadius: msg.role === 'user' ? '18px 18px 4px 18px' : '18px 18px 18px 4px',
              padding: '10px 14px',
              fontSize: 14,
              lineHeight: 1.5,
              whiteSpace: 'pre-wrap',
              wordBreak: 'break-word',
            }}
          >
            {msg.content}
          </div>
        ))}
        {loading && (
          <div style={{ alignSelf: 'flex-start', display: 'flex', alignItems: 'center', gap: 8, color: '#888', fontSize: 13 }}>
            <Spinner size="s" /> {t('ai_thinking')}
          </div>
        )}
        <div ref={bottomRef} />
      </div>

      <div style={{
        padding: '10px 12px',
        borderTop: '1px solid var(--tg-theme-hint-color, #ddd)',
        display: 'flex',
        gap: 8,
        background: 'var(--tg-theme-bg-color, #fff)',
      }}>
        <textarea
          value={input}
          onChange={(e) => setInput(e.target.value)}
          onKeyDown={onKey}
          placeholder={t('type_message')}
          rows={1}
          style={{
            flex: 1,
            resize: 'none',
            border: '1px solid var(--tg-theme-hint-color, #ccc)',
            borderRadius: 20,
            padding: '8px 14px',
            fontSize: 14,
            fontFamily: 'inherit',
            background: 'var(--tg-theme-secondary-bg-color, #f8f8f8)',
            color: 'inherit',
            outline: 'none',
          }}
        />
        <button
          onClick={send}
          disabled={loading || !input.trim()}
          style={{
            background: '#007aff',
            color: '#fff',
            border: 'none',
            borderRadius: '50%',
            width: 40,
            height: 40,
            fontSize: 18,
            cursor: 'pointer',
            opacity: loading || !input.trim() ? 0.5 : 1,
            flexShrink: 0,
          }}
        >
          ➤
        </button>
      </div>
    </div>
  );
}
