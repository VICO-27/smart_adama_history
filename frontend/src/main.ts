import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from '@/router'
import { scrollRevealDirective } from '@/composables/useScrollReveal'
import { i18n } from './i18n' // <-- Import i18n
import App from './App.vue'
import './style.css'

const app = createApp(App)

app.use(createPinia())
app.use(router)
app.use(i18n) // <-- Register i18n

app.directive('reveal', scrollRevealDirective)

app.mount('#app')