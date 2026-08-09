import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

import LandingView from '@/views/LandingView.vue'
import LoginView from '@/views/auth/LoginView.vue'
import RegisterView from '@/views/auth/RegisterView.vue'
import ForgotPasswordView from '@/views/auth/ForgotPasswordView.vue'
import ResetPasswordView from '@/views/auth/ResetPasswordView.vue'
import OAuthCallbackView from '@/views/auth/OAuthCallbackView.vue'
import DashboardView from '@/views/DashboardView.vue'
import ProfileView from '@/views/ProfileView.vue'
import ChatView from '@/views/ChatView.vue'
import ChapterView from '@/views/ChapterView.vue'
import QuizView from '@/views/QuizView.vue'
import QuizzesView from '@/views/QuizzesView.vue'
import AboutView from '@/views/AboutView.vue' // <-- Imported AboutView
import AdminBookView from '@/views/admin/AdminBookView.vue'
import AdminQuizView from '@/views/admin/AdminQuizView.vue'
import AdminBookIngestionView from '@/views/admin/AdminBookIngestionView.vue'
import AdminChapterEditor from '@/views/admin/AdminChapterEditor.vue'
import NotFoundView from '@/views/NotFoundView.vue'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    name: 'landing',
    component: LandingView,
    meta: { public: true },
  },
  {
    path: '/about',               // <-- Added About Route
    name: 'about',
    component: AboutView,
    meta: { public: true },       // Accessible without logging in
  },
  {
    path: '/auth/callback',
    name: 'oauth-callback',
    component: OAuthCallbackView,
    meta: { public: true },
  },
  {
    path: '/login',
    name: 'login',
    component: LoginView,
    meta: { public: true, guestOnly: true },
  },
  {
    path: '/register',
    name: 'register',
    component: RegisterView,
    meta: { public: true, guestOnly: true },
  },
  {
    path: '/forgot-password',
    name: 'forgot-password',
    component: ForgotPasswordView,
    meta: { public: true, guestOnly: true },
  },
  {
    path: '/reset-password',
    name: 'reset-password',
    component: ResetPasswordView,
    meta: { public: true, guestOnly: true },
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: DashboardView,
    meta: { requiresAuth: true },
  },
  {
    path: '/profile',
    name: 'profile',
    component: ProfileView,
    meta: { requiresAuth: true },
  },
  {
    path: '/study',
    name: 'study',
    component: ChatView,
    meta: { requiresAuth: true, hideNav: true },
  },
  {
    path: '/study/:sessionId',
    name: 'study-session',
    component: ChatView,
    meta: { requiresAuth: true, hideNav: true },
  },
  {
    path: '/chapters/:chapterId',
    name: 'chapter',
    component: ChapterView,
    meta: { requiresAuth: true },
  },
  {
    path: '/chapters/:chapterId/quiz',
    name: 'chapter-quiz',
    component: QuizView,
    meta: { requiresAuth: true },
  },
  {
    path: '/quizzes',
    name: 'quizzes',
    component: QuizzesView,
    meta: { requiresAuth: true },
  },
  {
    path: '/admin/books',
    name: 'admin-books',
    component: AdminBookView,
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/admin/book-ingestion',
    name: 'admin-book-ingestion',
    component: AdminBookIngestionView,
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/admin/quizzes',
    name: 'admin-quizzes',
    component: AdminQuizView,
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/admin/chapters/:id',
    name: 'admin-chapter-editor',
    component: AdminChapterEditor,
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: NotFoundView,
    meta: { public: true },
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

let sessionHydrated = false

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (!sessionHydrated && auth.token && !auth.user) {
    await auth.fetchMe()
  }
  sessionHydrated = true

  if (to.meta.guestOnly && auth.isAuthenticated) {
    return { name: 'dashboard' }
  }

  if (to.meta.requiresAuth && !auth.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.requiresAdmin && !auth.isAdmin) {
    return { name: 'dashboard' }
  } 
})

// Global Error Catcher: Recovers instantly from any navigation rendering failure
router.onError((err, to) => {
  console.error('Router navigation error:', err)
  window.location.href = to.fullPath
})

export default router