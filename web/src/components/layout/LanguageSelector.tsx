import { useTranslation } from 'react-i18next'

export default function LanguageSelector({ dark = false }: { dark?: boolean }) {
  const { i18n } = useTranslation()

  const change = (lang: string) => {
    i18n.changeLanguage(lang)
    localStorage.setItem('sigfa_lang', lang)
  }

  return (
    <div className="lang-selector">
      {(['fr', 'en'] as const).map((lang) => (
        <button
          key={lang}
          className={`lang-btn${i18n.language === lang ? ' active' : ''}`}
          onClick={() => change(lang)}
          style={dark && i18n.language !== lang ? { background: 'transparent', borderColor: 'var(--gray-700)', color: 'var(--gray-400)' } : undefined}
        >
          {lang === 'fr' ? '🇫🇷' : '🇬🇧'} {lang.toUpperCase()}
        </button>
      ))}
    </div>
  )
}
