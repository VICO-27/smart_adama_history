import { createApp } from 'vue'
import { createPinia } from 'pinia'
import router from '@/router'
import { scrollRevealDirective } from '@/composables/useScrollReveal'
import App from './App.vue'
import './style.css'

const app = createApp(App)

app.use(createPinia())
app.use(router)

// Global v-reveal directive for scroll-triggered fade/slide-up (Req 15.3)
app.directive('reveal', scrollRevealDirective)

app.mount('#app')
