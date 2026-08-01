<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useChatStore } from '@/stores/chat';
import { useBooksStore } from '@/stores/books';

// --- Pinia Stores & Routing ---
const chatStore = useChatStore();
const booksStore = useBooksStore();
const route = useRoute();
const router = useRouter();

// --- Layout & Sidebar Collapse States ---
const isSidebarOpen = ref(true);
const sidebarWidth = ref(300);
const isDraggingLeft = ref(false);

const isAiSidebarOpen = ref(true);
const aiSidebarWidth = ref(380);
const isDraggingRight = ref(false);

// --- Chapter Accordion States ---
const expandedChapters = ref<Record<string, boolean>>({});

const toggleChapterCollapse = (chapterId: string) => {
  expandedChapters.value[chapterId] = !expandedChapters.value[chapterId];
};

// --- Left Sidebar Dragging ---
const startDragLeft = () => {
  isDraggingLeft.value = true;
  document.addEventListener('mousemove', onDragLeft);
  document.addEventListener('mouseup', stopDragLeft);
};

const onDragLeft = (e: MouseEvent) => {
  if (!isDraggingLeft.value) return;
  let newWidth = e.clientX;
  if (newWidth < 220) newWidth = 220;
  if (newWidth > 500) newWidth = 500;
  sidebarWidth.value = newWidth;
};

const stopDragLeft = () => {
  isDraggingLeft.value = false;
  document.removeEventListener('mousemove', onDragLeft);
  document.removeEventListener('mouseup', stopDragLeft);
};

// --- Right Sidebar Dragging ---
const startDragRight = () => {
  isDraggingRight.value = true;
  document.addEventListener('mousemove', onDragRight);
  document.addEventListener('mouseup', stopDragRight);
};

const onDragRight = (e: MouseEvent) => {
  if (!isDraggingRight.value) return;
  let newWidth = window.innerWidth - e.clientX;
  if (newWidth < 280) newWidth = 280;
  if (newWidth > 600) newWidth = 600;
  aiSidebarWidth.value = newWidth;
};

const stopDragRight = () => {
  isDraggingRight.value = false;
  document.removeEventListener('mousemove', onDragRight);
  document.removeEventListener('mouseup', stopDragRight);
};

// --- Edge Copilot Text Selection ---
const selectionPopup = ref({
  visible: false,
  x: 0,
  y: 0,
  text: ''
});

const handleTextSelection = () => {
  const selection = window.getSelection();
  if (!selection || selection.isCollapsed || !selection.toString().trim()) {
    selectionPopup.value.visible = false;
    return;
  }

  const text = selection.toString().trim();
  const range = selection.getRangeAt(0);
  const rect = range.getBoundingClientRect();

  selectionPopup.value = {
    visible: true,
    x: rect.left + rect.width / 2,
    y: rect.top - 10,
    text: text
  };
};

const askAiAboutSelection = () => {
  const snippet = selectionPopup.value.text;
  chatInput.value = `Can you explain this section from the book: "${snippet}"`;
  selectionPopup.value.visible = false;
  window.getSelection()?.removeAllRanges();
  isAiSidebarOpen.value = true;
  nextTick(() => {
    const inputEl = document.querySelector('input[placeholder="Ask anything..."]') as HTMLInputElement;
    inputEl?.focus();
  });
};

// --- Lifecycle & Actions ---
onMounted(async () => {
  await Promise.all([
    chatStore.loadSessions(1),
    booksStore.loadBooks()
  ]);

  // Autoload the first chapter
  const books = booksStore.books;
  if (books && books.length > 0) {
    const chapters = books[0].chapters?.data || books[0].chapters;
    if (chapters && chapters.length > 0) {
      await booksStore.loadChapter(chapters[0].id);
    }
  }

  const sessionId = route.params.sessionId as string;
  if (sessionId) {
    await chatStore.loadSession(sessionId);
  }

  document.addEventListener('mouseup', handleTextSelection);
});

onUnmounted(() => {
  document.removeEventListener('mousemove', onDragLeft);
  document.removeEventListener('mouseup', stopDragLeft);
  document.removeEventListener('mousemove', onDragRight);
  document.removeEventListener('mouseup', stopDragRight);
  document.removeEventListener('mouseup', handleTextSelection);
});

const chatInput = ref('');
const currentMessages = computed(() => chatStore.currentSession?.messages || []);

const reloadPage = () => {
  window.location.reload();
};

const startNewChat = async () => {
  await chatStore.createSession();
  if (chatStore.currentSession) {
    router.push({ name: 'study-session', params: { sessionId: chatStore.currentSession.id } });
  }
  chatInput.value = '';
};

const switchSession = async (sessionId: string) => {
  await chatStore.loadSession(sessionId);
  router.push({ name: 'study-session', params: { sessionId } });
};

const loadBookChapter = async (chapterId: string) => {
  await booksStore.loadChapter(chapterId);
};

const sendMessage = async () => {
  const text = chatInput.value.trim();
  if (!text || chatStore.streaming) return;
  
  if (!chatStore.currentSession) {
    await startNewChat();
  }

  chatInput.value = '';
  
  if (chatStore.currentSession) {
    await chatStore.sendMessage(chatStore.currentSession.id, text);
  }
};
</script>

<template>
  <div class="flex h-screen w-full bg-brand-50 text-brand-500 overflow-hidden font-sans select-text" :class="{ 'select-none': isDraggingLeft || isDraggingRight }">
    
    <!-- 1. LEFT COLLAPSIBLE ICON RAIL (When Sidebar is Closed) -->
    <div v-show="!isSidebarOpen" class="w-16 bg-brand-100 border-r border-brand-200 flex flex-col items-center py-4 gap-6 z-20">
      <button @click="isSidebarOpen = true" class="p-2.5 bg-white hover:bg-brand-50 text-brand-500 rounded-xl shadow-sm transition-colors" title="Open Sidebar">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
      </button>
      <button @click="startNewChat; isSidebarOpen = true;" class="p-2.5 bg-brand-400 text-white rounded-xl shadow-md hover:bg-brand-500 transition-colors" title="New AI Session">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
      </button>
      <div class="w-8 h-px bg-brand-200"></div>
      <button @click="isSidebarOpen = true" class="p-2 text-brand-400 hover:text-brand-500 hover:bg-white rounded-xl transition-colors" title="Chapters">
        📚
      </button>
    </div>

    <!-- 1. LEFT RESIZABLE SIDEBAR -->
    <aside 
      v-show="isSidebarOpen"
      class="flex-shrink-0 bg-brand-100 border-r border-brand-200 flex flex-col relative h-full shadow-sm z-20 transition-all duration-300"
      :style="{ width: sidebarWidth + 'px' }"
    >
      <div class="p-4 border-b border-brand-200 flex flex-col gap-3">
         <div class="flex items-center justify-between">
           <div @click="reloadPage" class="flex items-center gap-2 cursor-pointer group">
             <svg class="w-6 h-6 text-brand-500 group-hover:scale-105 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
             <span class="font-bold text-brand-500 text-lg">Smart Adama</span>
           </div>
           <button @click="isSidebarOpen = false" class="p-1.5 text-brand-400 hover:text-brand-500 hover:bg-white rounded-lg transition-colors">
             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
           </button>
         </div>
         
         <button @click="startNewChat" class="w-full py-2 px-3 bg-white hover:bg-brand-50 border border-brand-200 text-brand-500 rounded-xl flex items-center justify-center gap-2 transition-all text-sm font-semibold shadow-sm active:scale-95">
            <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            New AI Session
         </button>
      </div>
      
      <div class="flex-1 overflow-y-auto p-4 space-y-6">
        <div>
          <h3 class="text-xs font-bold text-brand-400 uppercase tracking-wider mb-3 px-2">Course Content</h3>
          
          <div v-if="booksStore.loading" class="px-2 text-brand-400 text-xs font-medium animate-pulse">
            Loading book content...
          </div>
          <div v-else-if="!booksStore.books || booksStore.books.length === 0" class="px-2 text-red-400 text-xs italic">
            No course content found. Check database.
          </div>

          
          <!-- Iterate Over All Books & Chapters -->
          <div v-for="book in booksStore.books" :key="book.id" class="mb-6 space-y-2">
            <div 
              v-for="chapter in (book.chapters?.data || book.chapters || [])" 
              :key="chapter.id" 
              class="bg-white/60 rounded-xl border border-brand-200 overflow-hidden shadow-sm"
            >
              <!-- Chapter Header -->
              <div class="flex items-center justify-between p-2.5 hover:bg-white cursor-pointer transition-colors" @click="loadBookChapter(chapter.id)">
                <span class="text-xs font-semibold text-brand-500 truncate max-w-[170px]">{{ chapter.title }}</span>
                <button @click.stop="toggleChapterCollapse(chapter.id)" class="p-1 text-brand-400 hover:text-brand-500 rounded">
                  <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': expandedChapters[chapter.id] }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
              </div>

              <!-- Nested Sections (Topics Accordion) -->
              <div v-show="expandedChapters[chapter.id]" class="bg-brand-50/50 px-3 py-2 border-t border-brand-200 space-y-1">
                <div v-for="section in (chapter.sections?.data || chapter.sections || [])" :key="section.id" class="text-xs text-brand-400 py-1 px-2 rounded hover:bg-brand-100 cursor-pointer truncate">
                  • {{ section.title }}
                </div>
                <div v-if="!(chapter.sections?.data || chapter.sections)?.length" class="text-[11px] text-brand-300 italic px-2">
                  No subsections found.
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="pt-4 border-t border-brand-200">
          <h3 class="text-xs font-bold text-brand-400 uppercase tracking-wider mb-3 px-2">Recent Chats</h3>
          <ul class="space-y-1 text-sm text-brand-500 font-medium">
            <li 
              v-for="session in chatStore.sessions" 
              :key="session.id"
              @click="switchSession(session.id)"
              class="px-3 py-2 rounded-xl hover:bg-white cursor-pointer truncate transition-colors text-xs"
              :class="{'bg-white font-semibold shadow-sm': chatStore.currentSession?.id === session.id}"
            >
              {{ session.title || 'New Conversation' }}
            </li>
          </ul>
        </div>
      </div>

      <div @mousedown="startDragLeft" class="absolute top-0 right-0 w-1.5 h-full cursor-col-resize hover:bg-brand-400/40 transition-colors z-10 flex items-center justify-center group">
        <div class="w-0.5 h-8 bg-brand-300 group-hover:bg-brand-500 rounded-full"></div>
      </div>
    </aside>

    <!-- 2. MAIN CENTER BOOK READER -->
    <main class="flex-1 flex flex-col relative min-w-0 bg-brand-50">
      
      <header class="h-16 flex items-center justify-between px-6 border-b border-brand-200 bg-brand-100 shadow-sm z-10">
        <div class="flex items-center gap-4">
          <button @click="isSidebarOpen = !isSidebarOpen" class="p-2 text-brand-500 hover:bg-white rounded-xl transition-colors shadow-sm">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
          </button>
          <div class="flex items-center text-sm font-medium text-brand-400">
            <router-link to="/" class="hover:text-brand-500 transition-colors">Home</router-link>
            <svg class="w-4 h-4 mx-2 text-brand-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            <span class="text-brand-500 truncate max-w-[280px] font-semibold">{{ booksStore.currentChapter?.title || 'Select a chapter' }}</span>
          </div>
        </div>
        
        <div class="flex items-center gap-3">
           <button @click="isAiSidebarOpen = !isAiSidebarOpen" class="flex items-center gap-2 px-4 py-2 bg-white hover:bg-brand-50 text-brand-500 rounded-xl text-sm font-medium transition-colors border border-brand-200 shadow-sm">
             <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
             {{ isAiSidebarOpen ? 'Hide AI' : 'Show AI' }}
           </button>
        </div>
      </header>

      <div class="flex-1 relative flex items-center justify-center p-6 overflow-hidden">
        <button class="absolute left-6 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center bg-white border border-brand-200 rounded-full shadow-lg text-brand-400 hover:text-brand-500 hover:bg-brand-50 transition-all z-10 focus:outline-none">
           <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
        </button>

        <!-- Main Reading Pane -->
        <div class="w-full max-w-4xl h-full bg-white rounded-2xl shadow-sm border border-brand-200 p-12 overflow-y-auto relative">
          <div class="max-w-2xl mx-auto">
            <h1 class="text-4xl font-bold text-brand-500 mb-8">
              {{ booksStore.currentChapter?.title || 'Select a chapter to read' }}
            </h1>
            
            <div class="prose prose-brand max-w-none text-brand-500/80 leading-relaxed whitespace-pre-wrap text-lg select-text">
              <!-- Render Sections Dynamically -->
              <div v-if="(booksStore.currentChapter?.sections?.data || booksStore.currentChapter?.sections)?.length > 0">
                <div v-for="sec in (booksStore.currentChapter?.sections?.data || booksStore.currentChapter.sections)" :key="sec.id" class="mb-8">
                  <h3 class="text-2xl font-semibold text-brand-500 mb-3">{{ sec.title }}</h3>
                  <p>{{ sec.raw_text }}</p>
                </div>
              </div>
              
              <!-- Fallback -->
              <div v-else>
                Please select a chapter from the left sidebar to begin reading. Highlight any text in this reader to instantly ask the AI assistant about it!
              </div>
            </div>
          </div>

          <div class="absolute bottom-4 right-8 text-xs font-mono text-brand-300">
            Page {{ booksStore.currentChapter?.order || 1 }} of 451
          </div>
        </div>

        <button class="absolute right-6 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center bg-white border border-brand-200 rounded-full shadow-lg text-brand-400 hover:text-brand-500 hover:bg-brand-50 transition-all z-10 focus:outline-none">
           <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
        </button>
      </div>
    </main>

    <!-- 3. RESIZABLE RIGHT AI SIDEBAR -->
    <aside 
      v-show="isAiSidebarOpen" 
      class="flex-shrink-0 bg-brand-100 border-l border-brand-200 flex flex-col relative shadow-xl z-20 h-full transition-all duration-300"
      :style="{ width: aiSidebarWidth + 'px' }"
    >
      <div @mousedown="startDragRight" class="absolute top-0 left-0 w-1.5 h-full cursor-col-resize hover:bg-brand-400/40 transition-colors z-10 flex items-center justify-center group -ml-[3px]">
        <div class="w-0.5 h-8 bg-brand-300 group-hover:bg-brand-500 rounded-full"></div>
      </div>

      <div class="p-4 border-b border-brand-200 flex justify-between items-center bg-brand-100">
        <h3 class="font-bold text-brand-500 flex items-center gap-2">
          <span class="p-1.5 bg-white text-brand-500 rounded-xl shadow-sm">
             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
          </span>
          AI Assistant
        </h3>
        <button @click="isAiSidebarOpen = false" class="p-1.5 text-brand-400 hover:text-brand-500 hover:bg-white rounded-xl transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
      </div>
      
      <div class="flex-1 overflow-y-auto p-4 space-y-6">
        <div v-if="currentMessages.length === 0" class="bg-white border border-brand-200 p-4 rounded-2xl shadow-sm text-sm text-brand-500">
           Hello! 👋<br>
           How can I help you learn from <strong>{{ booksStore.currentChapter?.title || 'this book' }}</strong> today?
        </div>

        <div v-for="msg in currentMessages" :key="msg.id" class="flex flex-col gap-1.5">
          <div v-if="msg.role === 'user'" class="bg-brand-400 p-3.5 rounded-2xl rounded-tr-sm text-sm text-white w-fit max-w-[85%] ml-auto shadow-sm">
            {{ msg.content }}
          </div>
          <div v-else class="flex flex-col w-full max-w-[95%]">
            <span class="text-xs font-bold text-brand-500 mb-1 ml-1 flex items-center gap-1">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
              Smart Adama
            </span>
            <div class="bg-white border border-brand-200 p-4 rounded-2xl rounded-tl-sm text-sm text-brand-500 shadow-sm leading-relaxed">
              {{ msg.content }}
            </div>
          </div>
        </div>

        <div v-if="chatStore.streaming" class="flex flex-col w-full max-w-[95%]">
           <span class="text-xs font-bold text-brand-500 mb-1 ml-1 flex items-center gap-1">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
              Smart Adama
           </span>
           <div class="bg-white border border-brand-200 p-4 rounded-2xl rounded-tl-sm text-sm text-brand-500 shadow-sm leading-relaxed">
             {{ chatStore.streamingContent }}<span class="animate-pulse text-brand-400">▋</span>
           </div>
        </div>

        <div v-if="chatStore.error" class="bg-red-50 border border-red-100 text-red-500 p-3 rounded-xl text-sm mt-4">
           <span class="font-bold">Error:</span> {{ chatStore.error }}
        </div>
      </div>

      <!-- Quick Action Buttons -->
      <div v-if="currentMessages.length === 0" class="px-4 pb-2 flex flex-col gap-2">
         <button @click="chatInput = 'Explain this section'" class="text-left px-4 py-2.5 bg-white border border-brand-200 text-brand-500 text-sm font-medium rounded-xl hover:bg-brand-50 transition-colors shadow-sm flex items-center gap-2">
           <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
           Explain this section
         </button>
         <button @click="chatInput = 'Summarize this page'" class="text-left px-4 py-2.5 bg-white border border-brand-200 text-brand-500 text-sm font-medium rounded-xl hover:bg-brand-50 transition-colors shadow-sm flex items-center gap-2">
           <svg class="w-4 h-4 text-brand-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
           Summarize this page
         </button>
      </div>

      <div class="p-4 border-t border-brand-200">
        <div class="relative flex items-center">
          <input 
            v-model="chatInput"
            @keydown.enter.prevent="sendMessage"
            :disabled="chatStore.streaming"
            type="text"
            placeholder="Ask anything..." 
            class="w-full bg-white border border-brand-200 rounded-full pl-5 pr-14 py-3.5 focus:outline-none focus:ring-2 focus:ring-brand-400 focus:border-brand-400 transition-shadow text-brand-500 text-sm placeholder-brand-400 shadow-sm"
          />
          <button @click="sendMessage" :disabled="chatStore.streaming" class="absolute right-2 p-2 bg-brand-400 hover:bg-brand-500 text-white rounded-full transition-colors disabled:opacity-50 shadow-md">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
          </button>
        </div>
      </div>
    </aside>

    <!-- FLOATING TEXT SELECTION POPOVER -->
    <div 
      v-if="selectionPopup.visible"
      :style="{ top: `${selectionPopup.y}px`, left: `${selectionPopup.x}px` }"
      class="absolute -translate-x-1/2 -translate-y-full z-50 bg-brand-500 text-white px-3 py-1.5 rounded-xl shadow-xl flex items-center gap-2 text-xs font-medium animate-fade-in"
    >
      <span>Ask Copilot AI</span>
      <button @click="askAiAboutSelection" class="bg-brand-400 hover:bg-white hover:text-brand-500 px-2 py-0.5 rounded-lg text-white transition-colors font-bold">
        Ask →
      </button>
    </div>

  </div>
</template>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translate(-50%, -80%) scale(0.95); }
  to { opacity: 1; transform: translate(-50%, -100%) scale(1); }
}
.animate-fade-in {
  animation: fadeIn 0.15s ease-out forwards;
}

::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}
::-webkit-scrollbar-track {
  background: transparent;
}
::-webkit-scrollbar-thumb {
  background: #B1C9EF;
  border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
  background: #8AAEE0;
}
</style>