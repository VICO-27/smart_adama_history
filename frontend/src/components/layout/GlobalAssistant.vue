<template>
  <div class="z-[100] font-sans">
    
    <!-- THE ASSISTANCE PANEL -->
    <Transition name="panel">
      <div 
        v-if="isOpen" 
        class="fixed z-[100] w-[calc(100vw-3rem)] md:w-[400px] h-[600px] max-h-[calc(100vh-8rem)] bg-white/95 backdrop-blur-2xl border border-brand-200 shadow-[0_20px_60px_-15px_rgba(0,0,0,0.1)] rounded-[2rem] overflow-hidden flex flex-col"
        :style="{
          top: pos.y > windowHeight / 2 ? 'auto' : `${pos.y + 70}px`,
          bottom: pos.y > windowHeight / 2 ? `${windowHeight - pos.y + 10}px` : 'auto',
          left: pos.x < windowWidth / 2 ? '24px' : 'auto',
          right: pos.x >= windowWidth / 2 ? '24px' : 'auto',
          transformOrigin: pos.x < windowWidth / 2 ? (pos.y > windowHeight / 2 ? 'bottom left' : 'top left') : (pos.y > windowHeight / 2 ? 'bottom right' : 'top right')
        }"
      >
        
        <!-- MODE 1: THE HUB (Default) -->
        <div v-if="mode === 'hub'" class="flex flex-col h-full">
          <div class="p-8 pb-4">
            <div class="w-12 h-12 rounded-2xl bg-brand-50 border border-brand-100 flex items-center justify-center text-xl mb-6 shadow-sm">
              ✨
            </div>
            <h3 class="text-2xl font-display font-bold text-slate-900 mb-2">How can we help?</h3>
            <p class="text-slate-500 text-sm leading-relaxed">Choose an option below or ask the AI assistant for contextual guidance.</p>
          </div>

          <div class="flex flex-col px-4 pb-4 gap-2 flex-grow">
            <button @click="navigateToAbout" class="group flex items-center gap-4 p-4 rounded-2xl hover:bg-brand-50 transition-colors text-left border border-transparent hover:border-brand-100">
              <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">👨‍💻</div>
              <div>
                <h4 class="font-bold text-slate-900 text-sm">Meet the developers</h4>
                <p class="text-xs text-slate-500 mt-0.5">The story behind Smart Adama.</p>
              </div>
            </button>

            <button @click="mode = 'help'" class="group flex items-center gap-4 p-4 rounded-2xl hover:bg-brand-50 transition-colors text-left border border-transparent hover:border-brand-100">
              <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
              </div>
              <div>
                <h4 class="font-bold text-slate-900 text-sm">Help & Guidance</h4>
                <p class="text-xs text-slate-500 mt-0.5">Contextual tips for this page.</p>
              </div>
            </button>

            <button @click="mode = 'ai'" class="group flex items-center gap-4 p-4 rounded-2xl bg-slate-900 text-white hover:bg-slate-800 transition-colors text-left mt-2 shadow-md hover:shadow-xl hover:-translate-y-0.5">
              <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">🤖</div>
              <div>
                <h4 class="font-bold text-white text-sm">Ask AI Assistant</h4>
                <p class="text-xs text-slate-300 mt-0.5">Smart, context-aware answers.</p>
              </div>
            </button>
          </div>
        </div>

        <!-- MODE 2: CONTEXTUAL HELP -->
        <div v-else-if="mode === 'help'" class="flex flex-col h-full bg-slate-50/50">
          <div class="px-6 py-4 flex items-center gap-3 border-b border-brand-100 bg-white">
            <button @click="mode = 'hub'" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-brand-50 text-slate-500 transition-colors">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
            </button>
            <h3 class="font-bold text-slate-900">Quick Help</h3>
          </div>
          
          <div class="flex-grow overflow-y-auto p-6 space-y-6">
            <div>
              <span class="text-[10px] font-bold tracking-widest uppercase text-brand-500 mb-3 block">Relevant to your current page</span>
              <div class="space-y-3">
                <details v-for="(item, idx) in contextualFaqs" :key="idx" class="group bg-white rounded-xl border border-brand-100 shadow-sm overflow-hidden open:border-brand-300 transition-colors">
                  <summary class="px-4 py-3 font-bold text-sm text-slate-900 cursor-pointer list-none flex justify-between items-center group-open:bg-brand-50/50 transition-colors select-none">
                    {{ item.q }}
                    <span class="text-brand-300 group-open:rotate-45 transition-transform">+</span>
                  </summary>
                  <div class="px-4 pb-4 pt-1 text-sm text-slate-500 leading-relaxed border-t border-brand-50">
                    {{ item.a }}
                  </div>
                </details>
              </div>
            </div>
          </div>

          <div class="p-6 bg-white border-t border-brand-100 mt-auto">
            <p class="text-xs text-slate-500 mb-3 font-medium text-center">Didn't find what you need?</p>
            <button @click="mode = 'ai'" class="w-full py-3 rounded-xl bg-brand-100 text-brand-600 hover:bg-brand-200 hover:text-brand-700 font-bold text-sm transition-colors flex justify-center items-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
              Ask AI about this page
            </button>
          </div>
        </div>

        <!-- MODE 3: AI ASSISTANT -->
        <div v-else-if="mode === 'ai'" class="flex flex-col h-full bg-white">
          <div class="px-6 py-4 flex items-center justify-between border-b border-brand-100 bg-white/80 backdrop-blur-md sticky top-0 z-10">
            <div class="flex items-center gap-3">
              <button @click="mode = 'hub'" class="w-8 h-8 rounded-full flex items-center justify-center hover:bg-brand-50 text-slate-500 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
              </button>
              <div>
                <h3 class="font-bold text-slate-900 leading-tight">AI Assistant</h3>
                <p class="text-[10px] text-brand-500 font-mono tracking-widest uppercase flex items-center gap-1">
                  <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Context Aware
                </p>
              </div>
            </div>
          </div>

          <!-- Chat History -->
          <div ref="chatContainer" class="flex-grow overflow-y-auto p-6 space-y-6 bg-slate-50/30">
            
            <div v-if="messages.length === 0" class="flex flex-col items-center justify-center h-full text-center opacity-80 mt-10">
              <div class="w-16 h-16 rounded-3xl bg-brand-50 flex items-center justify-center text-3xl mb-4 border border-brand-100">🤖</div>
              <p class="text-sm font-bold text-slate-900 mb-1">I'm here whenever you get stuck.</p>
              <p class="text-xs text-slate-500 mb-6 max-w-[200px]">Ask me anything about the content on your screen or Smart Adama.</p>
              
              <div class="flex flex-col gap-2 w-full max-w-[250px]">
                <button v-for="prompt in suggestedPrompts" :key="prompt" @click="sendSuggested(prompt)" class="px-4 py-2.5 rounded-xl border border-brand-200 bg-white text-xs font-medium text-brand-600 hover:bg-brand-50 hover:border-brand-300 transition-all text-left shadow-sm">
                  "{{ prompt }}"
                </button>
              </div>
            </div>

            <div v-for="(msg, index) in messages" :key="index" :class="['flex w-full', msg.role === 'user' ? 'justify-end' : 'justify-start']">
              <div 
                :class="[
                  'max-w-[85%] rounded-2xl px-5 py-3.5 text-sm leading-relaxed shadow-sm',
                  msg.role === 'user' ? 'bg-slate-900 text-white rounded-br-sm whitespace-pre-wrap' : 'bg-white border border-brand-100 text-slate-800 rounded-bl-sm ai-formatted-response'
                ]"
                v-html="msg.role === 'ai' ? parseMarkdown(msg.content) : msg.content"
              >
              </div>
            </div>

            <!-- Thinking State -->
            <div v-if="isThinking" class="flex justify-start">
              <div class="bg-white border border-brand-100 rounded-2xl rounded-bl-sm px-5 py-4 shadow-sm flex items-center gap-2">
                <div class="w-2 h-2 bg-brand-300 rounded-full animate-bounce"></div>
                <div class="w-2 h-2 bg-brand-300 rounded-full animate-bounce" style="animation-delay: 0.15s"></div>
                <div class="w-2 h-2 bg-brand-300 rounded-full animate-bounce" style="animation-delay: 0.3s"></div>
              </div>
            </div>
          </div>

          <!-- Input Area -->
          <div class="p-4 bg-white border-t border-brand-100">
            <div class="relative flex items-end bg-brand-50 rounded-2xl border border-brand-200 focus-within:border-brand-400 focus-within:ring-2 focus-within:ring-brand-500/20 transition-all">
              <textarea 
                v-model="userInput" 
                @keydown="handleKeydown"
                rows="1"
                placeholder="Ask about this page..." 
                class="w-full bg-transparent border-none focus:ring-0 resize-none max-h-32 min-h-[52px] py-3.5 pl-4 pr-12 text-sm text-slate-900 placeholder:text-slate-400 no-scrollbar"
              ></textarea>
              <button 
                @click="sendMessage" 
                :disabled="!userInput.trim() || isThinking"
                class="absolute right-2 bottom-2 w-9 h-9 rounded-xl flex items-center justify-center transition-all"
                :class="userInput.trim() && !isThinking ? 'bg-brand-500 text-white shadow-md hover:bg-brand-600 hover:scale-105' : 'bg-brand-200 text-brand-400 cursor-not-allowed'"
              >
                <svg class="w-4 h-4 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
              </button>
            </div>
            <div class="text-center mt-2">
              <span class="text-[9px] text-slate-400 font-medium tracking-wide">Enter to send • Shift+Enter for new line</span>
            </div>
          </div>
        </div>

      </div>
    </Transition>

    <!-- DRAGGABLE FLOATING TOGGLE BUTTON -->
    <button 
      @pointerdown.stop.prevent="onPointerDown"
      @pointermove.stop.prevent="onPointerMove"
      @pointerup.stop.prevent="onPointerUp"
      @pointercancel.stop.prevent="onPointerUp"
      class="fixed z-[100] group w-14 h-14 md:w-16 md:h-16 rounded-full flex items-center justify-center shadow-[0_8px_30px_rgb(0,0,0,0.15)] hover:shadow-[0_8px_30px_rgb(0,0,0,0.25)] focus:outline-none select-none touch-none"
      :class="[
        isOpen ? 'bg-slate-900 scale-95' : 'bg-white hover:scale-105',
        isDragging ? 'cursor-grabbing scale-95 transition-none' : 'cursor-grab transition-all duration-300'
      ]"
      :style="{ left: `${pos.x}px`, top: `${pos.y}px` }"
      aria-label="Toggle Global Assistant"
      draggable="false"
    >
      <!-- Subtle Pulse Rings (Only when closed) -->
      <div v-if="!isOpen && !isDragging" class="absolute inset-0 rounded-full border border-brand-400/50 animate-ping opacity-20 group-hover:opacity-0 transition-opacity pointer-events-none"></div>
      
      <!-- Icons -->
      <div class="relative w-6 h-6 flex items-center justify-center text-xl transition-transform duration-500 pointer-events-none" :class="isOpen ? 'rotate-180 text-white' : 'text-slate-900'">
        <svg v-if="isOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" draggable="false"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        <span v-else class="transform group-hover:scale-110 transition-transform block select-none" draggable="false">✨</span>
      </div>

      <!-- Tooltip -->
      <div v-if="!isOpen && !isDragging && pos.x > windowWidth / 2" class="absolute right-full mr-4 top-1/2 -translate-y-1/2 px-4 py-2 bg-slate-900 text-white text-xs font-bold rounded-xl opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-lg pointer-events-none select-none">
        Need a hand?
        <div class="absolute right-[-4px] top-1/2 -translate-y-1/2 w-2 h-2 bg-slate-900 rotate-45"></div>
      </div>
      <div v-else-if="!isOpen && !isDragging && pos.x < windowWidth / 2" class="absolute left-full ml-4 top-1/2 -translate-y-1/2 px-4 py-2 bg-slate-900 text-white text-xs font-bold rounded-xl opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-lg pointer-events-none select-none">
        Need a hand?
        <div class="absolute left-[-4px] top-1/2 -translate-y-1/2 w-2 h-2 bg-slate-900 rotate-45"></div>
      </div>
    </button>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, watch, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { marked } from 'marked'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const isOpen = ref(false)
const mode = ref<'hub' | 'help' | 'ai'>('hub')

// Configure marked to sanitize HTML and interpret line breaks properly
marked.setOptions({
  breaks: true,
  gfm: true
})

// Helper function to parse markdown securely
const parseMarkdown = (rawText: string) => {
  return marked.parse(rawText) as string
}

// --- DRAG LOGIC ---
const windowWidth = ref(window.innerWidth)
const windowHeight = ref(window.innerHeight)

// Default position (bottom right)
const pos = ref({ x: window.innerWidth - 88, y: window.innerHeight - 88 })
const isDragging = ref(false)
let hasMoved = false
let startPoint = { x: 0, y: 0 }
let startPos = { x: 0, y: 0 }

const onPointerDown = (e: PointerEvent) => {
  hasMoved = false
  startPoint = { x: e.clientX, y: e.clientY }
  startPos = { x: pos.value.x, y: pos.value.y }
  const target = e.currentTarget as HTMLElement
  target.setPointerCapture(e.pointerId)
}

const onPointerMove = (e: PointerEvent) => {
  if (!e.buttons) return
  const dx = e.clientX - startPoint.x
  const dy = e.clientY - startPoint.y
  
  if (Math.abs(dx) > 3 || Math.abs(dy) > 3) {
    hasMoved = true
    isDragging.value = true
  }
  
  if (hasMoved) {
    let newX = startPos.x + dx
    let newY = startPos.y + dy
    // Clamp to screen edges
    newX = Math.max(16, Math.min(windowWidth.value - 80, newX))
    newY = Math.max(16, Math.min(windowHeight.value - 80, newY))
    pos.value = { x: newX, y: newY }
  }
}

const onPointerUp = (e: PointerEvent) => {
  isDragging.value = false
  const target = e.currentTarget as HTMLElement
  target.releasePointerCapture(e.pointerId)
  
  if (hasMoved) {
    // Snap cleanly to the left or right edge of the screen
    if (pos.value.x < windowWidth.value / 2) {
      pos.value.x = 24 // snap to left
    } else {
      pos.value.x = windowWidth.value - 88 // snap to right
    }
  } else {
    // Treat as click if we didn't drag it
    isOpen.value = !isOpen.value
    if (!isOpen.value) mode.value = 'hub'
  }
  hasMoved = false
}

const handleResize = () => {
  windowWidth.value = window.innerWidth
  windowHeight.value = window.innerHeight
  // Re-snap on window resize so it doesn't get lost off-screen
  if (pos.value.x > windowWidth.value / 2) {
    pos.value.x = windowWidth.value - 88
  }
  pos.value.y = Math.min(pos.value.y, windowHeight.value - 88)
}

onMounted(() => {
  window.addEventListener('resize', handleResize)
})
onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})

// --- HUB LOGIC ---
const navigateToAbout = () => {
  isOpen.value = false
  router.push('/about')
}

// --- HELP LOGIC (Context Aware) ---
const contextualFaqs = computed(() => {
  const path = route.path
  
  if (path.includes('/study') || path.includes('/chapter')) {
    return [
      { q: 'How does Study Mode work?', a: 'Study mode allows you to interact with the text. You can chat with the AI about specific paragraphs, ask for summaries, and track your reading progress at the bottom of the page.' },
      { q: 'How do I take a chapter quiz?', a: 'Quizzes are unlocked at the end of each chapter. Pass with a 70% or higher to mark the chapter as complete and earn badges.' },
      { q: 'Why isn\'t my progress saving?', a: 'Reading progress saves when you click "Finish & Next". To officially complete a chapter, you must pass its quiz.' }
    ]
  }
  
  if (path.includes('/dashboard')) {
    return [
      { q: 'What is the daily streak?', a: 'Your streak increases for every consecutive day you sign in and interact with study materials or quizzes.' },
      { q: 'How do I earn badges?', a: 'Badges are awarded automatically by the AI system when you achieve high scores on quizzes or maintain long study streaks.' }
    ]
  }

  // Default Global Fallback
  return [
    { q: 'How do I get started?', a: 'Navigate to the Dashboard to see your progress, or jump straight into the "Study" section to read the Smart Adama book.' },
    { q: 'How does the AI Assistant work?', a: 'The AI understands the page you are currently viewing. Just ask it to summarize, explain, or clarify anything you see.' },
    { q: 'How do I update my profile?', a: 'Click on your avatar in the top right corner of the navigation bar to access your Profile Settings.' }
  ]
})

// --- AI ASSISTANT LOGIC ---
const userInput = ref('')
const messages = ref<{role: 'user' | 'ai', content: string}[]>([])
const isThinking = ref(false)
const chatContainer = ref<HTMLElement | null>(null)

const suggestedPrompts = computed(() => {
  if (route.path.includes('/study')) {
    return ['Summarize this chapter', 'Explain the key concepts here', 'Quiz me on this topic']
  }
  if (route.path.includes('/dashboard')) {
    return ['How can I improve my score?', 'What should I study next?']
  }
  if (route.path.includes('/about')) {
    return ['Who is Ashenafi Deresa?', 'Tell me about the intern team']
  }
  return ['What is Smart Adama?', 'What page am I on right now?']
})

const sendSuggested = (prompt: string) => {
  userInput.value = prompt
  sendMessage()
}

const handleKeydown = (e: KeyboardEvent) => {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault()
    if (userInput.value.trim() && !isThinking.value) {
      sendMessage()
    }
  }
}

const scrollToBottom = async () => {
  await nextTick()
  if (chatContainer.value) {
    chatContainer.value.scrollTop = chatContainer.value.scrollHeight
  }
}

// REAL BACKEND API CALL TO GROQ
const sendMessage = async () => {
  if (!userInput.value.trim()) return

  const text = userInput.value.trim()
  const currentHistory = [...messages.value]
  
  messages.value.push({ role: 'user', content: text })
  userInput.value = ''
  isThinking.value = true
  scrollToBottom()

  try {
    const response = await fetch('http://localhost:8000/api/v1/global-chat', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${authStore.token}`
      },
      body: JSON.stringify({
        message: text,
        route: route.path, // Sending current route for context!
        history: currentHistory
      })
    })

    if (!response.ok) throw new Error('API Request Failed')

    const data = await response.json()
    
    messages.value.push({ 
      role: 'ai', 
      content: data.reply
    })
  } catch (error) {
    console.error('Chat error:', error)
    messages.value.push({ role: 'ai', content: "Something went wrong communicating with the server. Please try again." })
  } finally {
    isThinking.value = false
    scrollToBottom()
  }
}

watch(isOpen, (val) => {
  if (!val) setTimeout(() => mode.value = 'hub', 300)
})
</script>

<style scoped>
.panel-enter-active,
.panel-leave-active {
  transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.panel-enter-from,
.panel-leave-to {
  opacity: 0;
  transform: scale(0.95);
  pointer-events: none;
}

.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}

details > summary {
  list-style: none;
}
details > summary::-webkit-details-marker {
  display: none;
}

/* --- AI MARKDOWN FORMATTING --- */
.ai-formatted-response :deep(p) {
  margin-bottom: 0.75rem;
}
.ai-formatted-response :deep(p:last-child) {
  margin-bottom: 0;
}

/* Bold Text */
.ai-formatted-response :deep(strong) {
  color: var(--color-brand-500, #395886);
  font-weight: 700;
}

/* Lists */
.ai-formatted-response :deep(ul),
.ai-formatted-response :deep(ol) {
  padding-left: 1.25rem;
  margin-top: 0.5rem;
  margin-bottom: 0.75rem;
}
.ai-formatted-response :deep(ul) {
  list-style-type: disc;
}
.ai-formatted-response :deep(ol) {
  list-style-type: decimal;
}
.ai-formatted-response :deep(li) {
  margin-bottom: 0.25rem;
  color: #334155; /* Slate 700 */
}
.ai-formatted-response :deep(li::marker) {
  color: var(--color-brand-400, #638ECB);
}

/* Code Blocks & Inline Code */
.ai-formatted-response :deep(code) {
  background-color: #f1f5f9; /* Slate 100 */
  padding: 0.15rem 0.3rem;
  border-radius: 0.25rem;
  font-family: monospace;
  font-size: 0.85em;
  color: #c026d3; /* Fuchsia 600 for contrast */
}
.ai-formatted-response :deep(pre) {
  background-color: #1e293b; /* Slate 800 */
  padding: 1rem;
  border-radius: 0.5rem;
  overflow-x: auto;
  margin-top: 0.5rem;
  margin-bottom: 0.75rem;
}
.ai-formatted-response :deep(pre code) {
  background-color: transparent;
  padding: 0;
  color: #f8fafc; /* Slate 50 */
}

/* Headings inside AI response */
.ai-formatted-response :deep(h1),
.ai-formatted-response :deep(h2),
.ai-formatted-response :deep(h3) {
  font-weight: 700;
  color: #0f172a; /* Slate 900 */
  margin-top: 1rem;
  margin-bottom: 0.5rem;
}
.ai-formatted-response :deep(h3) {
  font-size: 1.1em;
}
</style>