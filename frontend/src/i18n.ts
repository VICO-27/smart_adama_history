import { createI18n } from 'vue-i18n'
import en from './lang/en.json'
import am from './lang/am.json'
import om from './lang/om.json'

const savedLocale = localStorage.getItem('sa_lang') || 'en'

export const i18n = createI18n({
  legacy: false, // use Composition API
  locale: savedLocale,
  fallbackLocale: 'en',
  messages: {
    en,
    am,
    om
  }
})

// Export a helper to change language and save state
export function setLanguage(lang: 'en' | 'am' | 'om') {
  i18n.global.locale.value = lang
  localStorage.setItem('sa_lang', lang)
  document.documentElement.lang = lang
}