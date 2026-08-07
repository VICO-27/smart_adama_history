<template>
  <div
    class="flex fixed inset-0 z-50 h-screen w-full bg-(--rt-bg) text-(--rt-text-body) overflow-hidden font-sans select-text transition-colors duration-300"
    :style="themeVars"
    :class="{ 'select-none': isDraggingLeft || isDraggingRight || isDraggingReader }"
  >

    <div v-if="isMobile && isSidebarOpen" @click="isSidebarOpen = false" class="fixed inset-0 bg-black/40 z-30 lg:hidden backdrop-blur-sm transition-opacity"></div>
    <div v-if="isMobile && isAiSidebarOpen" @click="isAiSidebarOpen = false" class="fixed inset-0 bg-black/40 z-30 lg:hidden backdrop-blur-sm transition-opacity"></div>

    <div v-show="!isSidebarOpen && !isFullscreen" class="hidden lg:flex w-16 shrink-0 bg-(--rt-surface-2) border-r border-(--rt-border) flex-col items-center py-4 gap-6 z-20">
      <button @click="isSidebarOpen = true" class="p-2.5 bg-(--rt-surface) hover:bg-(--rt-bg) text-(--rt-text) rounded-xl shadow-sm transition-colors border border-(--rt-border)" title="Open Chapters">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
      </button>

      <button @click="startNewChatAndOpen" class="p-2.5 bg-(--rt-accent) text-(--rt-accent-text) rounded-xl shadow-md hover:bg-(--rt-accent-hover) transition-colors" title="New AI Session">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
      </button>
    </div>

    <aside
      v-show="isSidebarOpen && !isFullscreen"
      class="shrink-0 bg-(--rt-surface-2) border-r border-(--rt-border) flex flex-col h-full shadow-2xl lg:shadow-sm z-40 transition-all duration-300 absolute lg:relative left-0"
      :class="isMobile ? 'w-[85vw] max-w-[340px]' : ''"
      :style="{ width: isMobile ? undefined : sidebarWidth + 'px' }"
    >
      <div class="p-4 border-b border-(--rt-border) flex flex-col gap-3">
         <div class="flex items-center justify-between">
           <div @click="reloadPage" class="flex items-center gap-2 cursor-pointer group">
             <svg class="w-6 h-6 text-(--rt-text) group-hover:scale-105 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
             <span class="font-bold text-(--rt-text) text-lg">{{ $t('nav.brand') }}</span>
           </div>
           
           <button @click="isSidebarOpen = false" class="p-1.5 text-(--rt-muted) hover:text-(--rt-text) hover:bg-(--rt-surface) rounded-lg transition-colors">
             <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
           </button>
         </div>

         <button @click="startNewChat" class="w-full py-2 px-3 bg-(--rt-surface) hover:bg-(--rt-bg) border border-(--rt-border) text-(--rt-text) rounded-xl flex items-center justify-center gap-2 transition-all text-sm font-semibold shadow-sm active:scale-95">
            <svg class="w-4 h-4 text-(--rt-text)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            {{ $t('chapter.new_session') }}
         </button>
      </div>

      <div class="flex-1 overflow-y-auto p-4 space-y-6">
        <div class="mb-2">
          <RouterLink to="/quizzes" class="flex items-center gap-2.5 px-3 py-2.5 bg-indigo-50/50 border border-indigo-100/50 text-indigo-700 text-sm font-bold rounded-xl hover:bg-indigo-100 transition-colors shadow-sm">
            <div class="p-1.5 bg-indigo-500 rounded-lg text-white shadow-sm">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
            </div>
            {{ $t('chapter.quizzes') }}
          </RouterLink>
        </div>

        <div>
          <h3 class="text-xs font-bold text-(--rt-muted) uppercase tracking-wider mb-3 px-2">{{ $t('chapter.course_content') }}</h3>

          <div class="mb-6 space-y-2">
            <div
              v-for="chapter in allVisibleChapters"
              :key="chapter.id"
              class="bg-(--rt-surface)/60 rounded-xl border border-(--rt-border) overflow-hidden shadow-sm"
            >
              <div class="flex items-center justify-between p-2.5 hover:bg-(--rt-surface) cursor-pointer transition-colors" @click="loadBookChapter(chapter.id)">
                <span class="text-xs font-semibold text-(--rt-text) truncate max-w-[200px]">{{ chapter.title }}</span>
                <button @click.stop="toggleChapterCollapse(chapter.id)" class="p-1 text-(--rt-muted) hover:text-(--rt-text) rounded">
                  <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': expandedChapters[chapter.id] }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
              </div>

              <div v-show="expandedChapters[chapter.id]" class="bg-(--rt-bg)/50 px-3 py-2 border-t border-(--rt-border) space-y-1">
                <template v-if="chapter.title === 'Introduction & Preface'">
                  <div
                    @click="loadBookChapter(chapter.id)"
                    class="text-xs text-(--rt-muted) py-1 px-2 rounded hover:bg-(--rt-border) hover:text-(--rt-text) cursor-pointer truncate transition-colors"
                    :class="{ 'bg-(--rt-border) text-(--rt-text) font-semibold': booksStore.currentChapter?.id === chapter.id }"
                  >
                    • Overview
                  </div>
                </template>
                <template v-else>
                  <div
                    v-for="section in (chapter.sections?.data || chapter.sections || [])"
                    :key="section.id"
                    @click="jumpToSection(chapter.id, section.id)"
                    class="text-xs text-(--rt-muted) py-1 px-2 rounded hover:bg-(--rt-border) hover:text-(--rt-text) cursor-pointer truncate transition-colors"
                    :class="{ 'bg-(--rt-border) text-(--rt-text) font-semibold': booksStore.currentChapter?.id === chapter.id && sectionToPageMap.get(section.id) === currentPage - 1 }"
                  >
                    • {{ section.title }}
                  </div>
                  
                  <div class="mt-1.5 pt-1.5 border-t border-(--rt-border)/50">
                    <RouterLink :to="`/chapters/${chapter.id}/quiz`" class="flex items-center gap-1.5 text-xs font-semibold text-indigo-600 py-1.5 px-2 rounded hover:bg-indigo-50 transition-colors">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                      {{ $t('chapter.take_quiz') }}
                    </RouterLink>
                  </div>
                </template>
              </div>
            </div>

            <button
              v-if="hasHiddenChapters || isShowingAll"
              @click="toggleShowAll"
              class="w-full text-center text-xs font-semibold text-(--rt-muted) hover:text-(--rt-text) py-2 rounded-lg hover:bg-(--rt-surface)/60 transition-colors"
            >
              {{ isShowingAll ? $t('chapter.show_less') : $t('chapter.show_more') }}
            </button>
          </div>
        </div>

        <div class="pt-4 border-t border-(--rt-border)">
          <h3 class="text-xs font-bold text-(--rt-muted) uppercase tracking-wider mb-3 px-2">{{ $t('chapter.recent_chats') }}</h3>
          <ul class="space-y-1 text-sm text-(--rt-text) font-medium">
            <li
              v-for="session in visibleSessions"
              :key="session.id"
              @click="switchSession(session.id)"
              class="px-3 py-2 rounded-xl hover:bg-(--rt-surface) cursor-pointer truncate transition-colors text-xs"
              :class="{'bg-(--rt-surface) font-semibold shadow-sm': chatStore.currentSession?.id === session.id}"
            >
              {{ session.title || 'New Conversation' }}
            </li>
          </ul>
        </div>
      </div>

      <div @mousedown="startDragLeft" class="absolute top-0 right-0 w-1.5 h-full cursor-col-resize hover:bg-(--rt-accent)/40 transition-colors z-10 hidden lg:flex items-center justify-center group">
        <div class="w-0.5 h-8 bg-(--rt-border) group-hover:bg-(--rt-text) rounded-full"></div>
      </div>
    </aside>

    <main class="flex-1 flex flex-col relative min-w-0 transition-colors duration-300 bg-(--rt-bg)">
      <div v-if="viewMode === 'reading'" class="h-1 w-full bg-(--rt-border) shrink-0">
        <div class="h-full bg-(--rt-accent) transition-all duration-300" :style="{ width: readingProgress + '%' }"></div>
      </div>

      <header class="h-14 flex items-center justify-between px-3 sm:px-6 border-b border-(--rt-border) bg-(--rt-surface-2) shadow-sm z-10 shrink-0">
        <div class="flex items-center gap-2 sm:gap-3 min-w-0">
          <router-link v-if="!isFullscreen" to="/profile" class="p-1.5 text-(--rt-text) hover:bg-(--rt-bg) rounded-xl transition-colors shadow-sm shrink-0 border border-(--rt-border) bg-(--rt-surface)">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
          </router-link>
          
          <div class="flex items-center text-sm font-medium text-(--rt-muted) min-w-0">
            <router-link v-if="!isFullscreen" to="/dashboard" class="hover:text-(--rt-text) transition-colors shrink-0 hidden sm:block">{{ $t('nav.home') }}</router-link>
            <svg v-if="!isFullscreen" class="w-4 h-4 mx-2 text-(--rt-muted) shrink-0 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            <span class="text-(--rt-text) truncate font-semibold max-w-[120px] sm:max-w-xs">{{ booksStore.currentChapter?.title || 'Select a chapter' }}</span>
          </div>
        </div>

        <div class="flex items-center gap-2 sm:gap-3 shrink-0">
           <div class="hidden sm:flex items-center bg-(--rt-surface) border border-(--rt-border) rounded-xl p-1 shadow-sm gap-1">
             <button @click="viewMode = 'reading'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors" :class="viewMode === 'reading' ? 'bg-(--rt-accent) text-(--rt-accent-text)' : 'text-(--rt-text) hover:bg-(--rt-bg)'">{{ $t('chapter.reading') }}</button>
             <button @click="viewMode = 'pdf'" class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors" :class="viewMode === 'pdf' ? 'bg-(--rt-accent) text-(--rt-accent-text)' : 'text-(--rt-text) hover:bg-(--rt-bg)'">{{ $t('chapter.pdf') }}</button>
           </div>

           <template v-if="viewMode === 'reading'">
             <div class="hidden md:flex items-center gap-1 bg-(--rt-surface) border border-(--rt-border) rounded-xl shadow-sm">
               <button @click="prevPage" :disabled="currentPage <= 1" class="p-2 text-(--rt-muted) hover:text-(--rt-text) disabled:opacity-30 disabled:hover:text-(--rt-muted)">
                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
               </button>
               <div class="flex items-center px-1.5 py-1 text-xs font-medium gap-1.5">
                 <input
                   v-model="jumpPageInput"
                   @keyup.enter="jumpToPage"
                   @blur="jumpToPage"
                   type="text"
                   class="w-8 text-center font-bold text-(--rt-text) bg-(--rt-bg) rounded border border-(--rt-border) focus:outline-none focus:ring-1 focus:ring-(--rt-accent)"
                 />
                 <span class="text-(--rt-muted)">/ {{ totalPages }}</span>
               </div>
               <button @click="nextPage" :disabled="currentPage >= totalPages" class="p-2 text-(--rt-muted) hover:text-(--rt-text) disabled:opacity-30 disabled:hover:text-(--rt-muted)">
                 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
               </button>
             </div>

             <!-- FONT MENU -->
             <div class="hidden sm:block relative" ref="fontMenuRef">
               <button
                 @click.stop="isFontMenuOpen = !isFontMenuOpen; isThemeMenuOpen = false"
                 class="flex items-center gap-1.5 px-2.5 py-2 bg-(--rt-surface) hover:bg-(--rt-bg) text-(--rt-text) rounded-xl text-xs font-semibold transition-colors border border-(--rt-border) shadow-sm"
               >
                 <span class="font-serif font-bold text-sm leading-none">Aa</span>
                 <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
               </button>
               <div
                 v-if="isFontMenuOpen"
                 class="absolute right-0 mt-2 w-32 bg-(--rt-surface) border border-(--rt-border) rounded-xl shadow-lg overflow-hidden z-30 py-1"
               >
                 <button
                   @click="setFont('sans')"
                   class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-(--rt-text) hover:bg-(--rt-bg) transition-colors font-sans"
                   :class="{ 'font-bold': readerFont === 'sans' }"
                 >
                   Sans-serif
                 </button>
                 <button
                   @click="setFont('serif')"
                   class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-(--rt-text) hover:bg-(--rt-bg) transition-colors font-serif"
                   :class="{ 'font-bold': readerFont === 'serif' }"
                 >
                   Serif
                 </button>
               </div>
             </div>

             <!-- THEME MENU -->
             <div class="hidden sm:block relative" ref="themeMenuRef">
               <button
                 @click.stop="isThemeMenuOpen = !isThemeMenuOpen; isFontMenuOpen = false"
                 class="flex items-center gap-1.5 px-2.5 py-2 bg-(--rt-surface) hover:bg-(--rt-bg) text-(--rt-text) rounded-xl text-xs font-semibold transition-colors border border-(--rt-border) shadow-sm"
               >
                 <span class="w-3 h-3 rounded-full border border-black/10" :style="{ background: THEMES[readerTheme].vars['--rt-surface'] }"></span>
                 <span class="hidden md:inline">{{ THEMES[readerTheme].label }}</span>
                 <svg class="w-3 h-3 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
               </button>
               <div
                 v-if="isThemeMenuOpen"
                 class="absolute right-0 mt-2 w-40 bg-(--rt-surface) border border-(--rt-border) rounded-xl shadow-lg overflow-hidden z-30 py-1"
               >
                 <button
                   v-for="(t, key) in THEMES"
                   :key="key"
                   @click="setTheme(key as any)"
                   class="w-full flex items-center gap-2 px-3 py-2 text-xs font-medium text-(--rt-text) hover:bg-(--rt-bg) transition-colors"
                   :class="{ 'font-bold': readerTheme === key }"
                 >
                   <span class="w-3 h-3 rounded-full border border-black/10 shrink-0" :style="{ background: t.vars['--rt-surface'] }"></span>
                   {{ t.label }}
                 </button>
               </div>
             </div>
           </template>

           <button @click="toggleFullscreen" class="hidden sm:block p-2 bg-(--rt-surface) hover:bg-(--rt-bg) text-(--rt-text) rounded-xl text-sm font-medium transition-colors border border-(--rt-border) shadow-sm">
             <svg v-if="!isFullscreen" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"></path></svg>
             <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 9V4m0 5H4m5 0L3 3m12 6V4m0 5h5m-5 0l6-6M9 15v5m0-5H4m5 0l-6 6m12-6v5m0-5h5m-5 0l6 6"></path></svg>
           </button>

           <button v-if="!isFullscreen" @click="isAiSidebarOpen = !isAiSidebarOpen" class="flex items-center gap-2 px-3 sm:px-4 py-2 bg-(--rt-surface) hover:bg-(--rt-bg) text-(--rt-text) rounded-xl text-sm font-medium transition-colors border border-(--rt-border) shadow-sm">
             <svg class="w-4 h-4 text-(--rt-text)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
             <span class="hidden sm:inline">{{ $t('chapter.ask_ai') }}</span>
           </button>
        </div>
      </header>

      <div v-if="viewMode === 'reading'" ref="scrollAreaRef" @wheel="handleReaderWheel" class="flex-1 relative flex items-start justify-center overflow-y-auto py-6 md:py-10 px-2 sm:px-10">
        <div
          ref="readerContainerRef"
          class="relative flex flex-col items-center transition-[width] duration-75"
          :class="isMobile ? 'w-full' : ''"
          :style="{ width: isMobile ? '100%' : readerWidth + 'px', minWidth: '320px', maxWidth: '100%' }"
        >
          <div @mousedown="startReaderDrag" class="hidden lg:flex absolute left-0 top-0 bottom-0 w-4 cursor-ew-resize hover:bg-(--rt-accent)/20 z-30 -ml-5 rounded-l-2xl items-center justify-center group transition-colors">
            <div class="w-1 h-12 bg-(--rt-border) rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
          </div>

          <div
            class="w-full rounded-md shadow-[0_1px_3px_rgba(0,0,0,0.08),0_8px_24px_rgba(20,30,60,0.06)] border border-black/5 transition-colors duration-300 bg-(--rt-surface) flex flex-col"
            :class="readerFont === 'serif' ? 'font-serif' : 'font-sans'"
            :style="{ fontSize: readerScale + '%', minHeight: isMobile ? 'calc(100vh - 8rem)' : '800px' }"
          >
            <div class="px-6 sm:px-14 py-8 sm:py-14 wrap-break-word flex flex-col flex-1">
              <div class="flex-1 w-full">
                <template v-if="booksStore.currentChapter?.title === 'Introduction & Preface'">
                  <IntroductionPreface :current-page="currentPage" @go-to-chapter="handleTocClick" />
                </template>
                
                <template v-else>
                  <div v-if="currentPageData && currentPageData.sections.length > 1" class="flex flex-wrap gap-2 mb-6 pb-5 border-b border-(--rt-border)">
                    <button
                      v-for="sec in currentPageData.sections"
                      :key="sec.id"
                      @click="scrollToSection(sec.id)"
                      class="text-[11px] px-2.5 py-1 rounded-full border border-(--rt-border) hover:bg-(--rt-bg) transition-colors text-(--rt-text-body) opacity-70 hover:opacity-100"
                    >
                      {{ sec.title }}
                    </button>
                  </div>

                  <div v-if="currentPageData" class="space-y-6">
                    <div v-for="sec in currentPageData.sections" :key="sec.id" :id="`sec-${sec.id}`">
                      <h2 class="text-[1.7em] font-bold mb-3 leading-snug text-(--rt-text)">{{ sec.title }}</h2>
                      <template v-for="(block, bi) in formatContent(sec.raw_text)" :key="bi">
                        <p v-if="block.type === 'p'" class="text-[1.05em] leading-[1.85] tracking-[0.005em] mb-3 last:mb-0 text-(--rt-text-body)">{{ block.text }}</p>
                        <div v-else class="flex gap-2.5 mb-3 last:mb-0 text-[1.05em] leading-[1.75] text-(--rt-text-body)">
                          <span class="mt-2.5 w-1.5 h-1.5 rounded-full bg-current opacity-50 shrink-0"></span>
                          <span><strong v-if="block.label" class="font-semibold">{{ block.label }}: </strong>{{ block.text }}</span>
                        </div>
                      </template>
                    </div>
                  </div>
                </template>
              </div>

              <div v-if="currentPageData || booksStore.currentChapter?.title === 'Introduction & Preface'" class="mt-16 pt-8 border-t border-(--rt-border) flex items-center justify-between w-full">
                <button 
                  @click="prevPage" 
                  :disabled="currentPage <= 1"
                  class="px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl font-bold text-sm transition-all flex items-center gap-2 shrink-0"
                  :class="currentPage <= 1 ? 'opacity-50 cursor-not-allowed text-(--rt-muted) bg-(--rt-surface-2)' : 'text-(--rt-text) bg-(--rt-surface-2) hover:bg-(--rt-border)'"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
                  <span class="hidden sm:inline">{{ $t('chapter.prev') }}</span>
                </button>

                <div v-if="currentPage >= totalPages && booksStore.currentChapter?.title !== 'Introduction & Preface'" class="hidden md:flex flex-1 justify-center px-4">
                  <RouterLink 
                    :to="`/chapters/${booksStore.currentChapter?.id}/quiz`" 
                    class="px-5 py-2.5 rounded-xl font-bold text-sm bg-indigo-50 text-indigo-600 hover:bg-indigo-100 hover:shadow-md transition-all flex items-center gap-2 border border-indigo-200"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                    {{ $t('chapter.take_quiz') }}
                  </RouterLink>
                </div>

                <button 
                  v-if="currentPage < totalPages"
                  @click="nextPage" 
                  class="px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl font-bold text-sm bg-(--rt-accent) text-(--rt-accent-text) hover:bg-(--rt-accent-hover) transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5 flex items-center gap-2 shrink-0"
                >
                  <span class="hidden sm:inline">{{ $t('chapter.next') }}</span>
                  <span class="sm:hidden">Next</span>
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                </button>
                
                <button 
                  v-else
                  @click="markCompleteAndNextChapter"
                  class="px-4 sm:px-6 py-2.5 sm:py-3 rounded-xl font-bold text-sm bg-emerald-500 text-white hover:bg-emerald-600 transition-all shadow-md hover:shadow-lg hover:-translate-y-0.5 flex items-center gap-2 shrink-0 cursor-pointer"
                >
                  <span class="hidden sm:inline">{{ $t('chapter.finish') }}</span>
                  <span class="sm:hidden">Finish ✓</span>
                  <svg class="w-4 h-4 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                </button>
              </div>

            </div>
          </div>

          <div @mousedown="startReaderDrag" class="hidden lg:flex absolute right-0 top-0 bottom-0 w-4 cursor-ew-resize hover:bg-(--rt-accent)/20 z-30 -mr-5 rounded-r-2xl items-center justify-center group transition-colors">
            <div class="w-1 h-12 bg-(--rt-border) rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
          </div>
        </div>
      </div>

      <div v-else class="flex-1 relative overflow-hidden bg-(--rt-bg)">
        <template v-if="pdfUrl">
          <iframe :src="pdfUrl" class="w-full h-full border-0" title="Original PDF"></iframe>
          <div class="absolute bottom-6 left-1/2 -translate-x-1/2 z-10 flex flex-col items-center">
             <button @click="isAiSidebarOpen = true" class="px-5 py-2.5 bg-(--rt-accent) text-(--rt-accent-text) font-bold rounded-full shadow-lg hover:bg-(--rt-accent-hover) transition-transform hover:-translate-y-1 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                {{ $t('chapter.ask_ai') }}
             </button>
          </div>
        </template>
        <div v-else class="w-full h-full flex flex-col items-center justify-center text-(--rt-muted) text-sm text-center px-8 gap-2">
          <span>{{ $t('chapter.no_pdf') }}</span>
        </div>
      </div>
    </main>

    <div v-show="!isAiSidebarOpen && !isFullscreen" class="hidden lg:flex w-16 shrink-0 bg-(--rt-surface-2) border-l border-(--rt-border) flex-col items-center py-4 gap-6 z-20">
      <button @click="isAiSidebarOpen = true" class="p-2.5 bg-(--rt-surface) hover:bg-(--rt-bg) text-(--rt-text) rounded-xl shadow-sm transition-colors border border-(--rt-border)" title="Open AI Assistant">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path></svg>
      </button>
    </div>

    <aside
      v-show="isAiSidebarOpen && !isFullscreen"
      class="shrink-0 bg-(--rt-surface-2) border-l border-(--rt-border) flex flex-col relative h-full shadow-2xl lg:shadow-xl z-40 transition-all duration-300 absolute lg:relative right-0"
      :class="isMobile ? 'w-[85vw] max-w-[380px]' : ''"
      :style="{ width: isMobile ? undefined : aiSidebarWidth + 'px' }"
    >
      <div @mousedown="startDragRight" class="absolute top-0 left-0 w-1.5 h-full cursor-col-resize hover:bg-(--rt-accent)/40 transition-colors z-10 hidden lg:flex items-center justify-center group -ml-0.75">
        <div class="w-0.5 h-8 bg-(--rt-border) group-hover:bg-(--rt-text) rounded-full"></div>
      </div>

      <div class="p-4 border-b border-(--rt-border) flex justify-between items-center bg-(--rt-surface-2)">
        <h3 class="font-bold text-(--rt-text) flex items-center gap-2">
          <span class="p-1.5 bg-(--rt-surface) text-(--rt-text) rounded-xl shadow-sm">
             <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
          </span>
          AI Assistant
        </h3>
        <button @click="isAiSidebarOpen = false" class="p-1.5 text-(--rt-muted) hover:text-(--rt-text) hover:bg-(--rt-surface) rounded-xl transition-colors">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7"></path></svg>
        </button>
      </div>

      <div class="flex-1 overflow-y-auto p-4 space-y-6">
        <div v-if="currentMessages.length === 0" class="bg-(--rt-surface) border border-(--rt-border) p-4 rounded-2xl shadow-sm text-sm text-(--rt-text)">
           Hello! 👋<br>
           How can I help you learn from <strong>{{ booksStore.currentChapter?.title || 'this book' }}</strong> today?
        </div>

        <div v-for="msg in currentMessages" :key="msg.id" class="flex flex-col gap-1.5">
          <div v-if="msg.role === 'user'" class="bg-(--rt-accent) p-3.5 rounded-2xl rounded-tr-sm text-sm text-(--rt-accent-text) w-fit max-w-[85%] ml-auto shadow-sm">
            {{ msg.content }}
          </div>
          <div v-else class="flex flex-col w-full max-w-[95%]">
            <span class="text-xs font-bold text-(--rt-text) mb-1 ml-1 flex items-center gap-1">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
              Smart Adama
            </span>
            <div class="bg-(--rt-surface) border border-(--rt-border) p-4 rounded-2xl rounded-tl-sm text-sm text-(--rt-text) shadow-sm leading-relaxed">
              <div v-html="msg.content" class="ai-response-content"></div>
              
              <!-- Feedback Buttons -->
              <div class="flex items-center gap-2 mt-3 pt-3 border-t border-(--rt-border)">
                <button 
                  @click="toggleFeedback(msg, 'helpful')"
                  :class="msg.feedback === 'helpful' ? 'bg-emerald-100 text-emerald-700 border-emerald-300' : 'bg-(--rt-bg) text-(--rt-muted) border-(--rt-border)'"
                  class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors hover:bg-emerald-50 hover:text-emerald-600"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
                  </svg>
                  <span>{{ msg.feedback === 'helpful' ? 'Helpful' : 'Helpful?' }}</span>
                </button>
                
                <button 
                  @click="toggleFeedback(msg, 'not_helpful')"
                  :class="msg.feedback === 'not_helpful' ? 'bg-red-100 text-red-700 border-red-300' : 'bg-(--rt-bg) text-(--rt-muted) border-(--rt-border)'"
                  class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors hover:bg-red-50 hover:text-red-600"
                >
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14H5.236a2 2 0 01-1.789-2.894l3.5-7A2 2 0 018.736 3h4.018a2 2 0 01.485.06l3.76.94m-7 10v5a2 2 0 002 2h.096c.5 0 .905-.405.905-.904 0-.715.211-1.413.608-2.008L17 13V4m-7 10h2m5-10h2a2 2 0 012 2v6a2 2 0 01-2 2h-2.5"></path>
                  </svg>
                  <span>{{ msg.feedback === 'not_helpful' ? 'Not helpful' : 'Not helpful?' }}</span>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div v-if="chatStore.streaming" class="flex flex-col w-full max-w-[95%]">
           <div class="bg-(--rt-surface) border border-(--rt-border) p-4 rounded-2xl rounded-tl-sm text-sm text-(--rt-text) shadow-sm leading-relaxed">
             {{ chatStore.streamingContent }}<span class="animate-pulse text-(--rt-muted)">▋</span>
           </div>
        </div>
      </div>

      <div v-if="currentMessages.length === 0" class="px-4 pb-2 flex flex-col gap-2">
         <button @click="chatInput = 'Explain this section'; sendMessage()" class="text-left px-4 py-2.5 bg-(--rt-surface) border border-(--rt-border) text-(--rt-text) text-sm font-medium rounded-xl hover:bg-(--rt-bg) transition-colors shadow-sm flex items-center gap-2">
           <svg class="w-4 h-4 text-(--rt-text)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
           {{ $t('chapter.explain') }}
         </button>
         <button @click="chatInput = 'Summarize this page'; sendMessage()" class="text-left px-4 py-2.5 bg-(--rt-surface) border border-(--rt-border) text-(--rt-text) text-sm font-medium rounded-xl hover:bg-(--rt-bg) transition-colors shadow-sm flex items-center gap-2">
           <svg class="w-4 h-4 text-(--rt-text)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
           {{ $t('chapter.summarize') }}
         </button>
      </div>

      <div class="p-4 border-t border-(--rt-border)">
        <div class="relative flex items-center">
          <input
            v-model="chatInput"
            @keydown.enter.prevent="sendMessage"
            :disabled="chatStore.streaming"
            type="text"
            :placeholder="$t('chapter.ask_placeholder')"
            class="w-full bg-(--rt-surface) border border-(--rt-border) rounded-full pl-5 pr-14 py-3.5 focus:outline-none focus:ring-2 focus:ring-(--rt-accent) focus:border-(--rt-accent) transition-shadow text-(--rt-text) text-sm placeholder:text-(--rt-muted) shadow-sm"
          />
          <button @click="sendMessage" :disabled="chatStore.streaming" class="absolute right-2 p-2 bg-(--rt-accent) hover:bg-(--rt-accent-hover) text-(--rt-accent-text) rounded-full transition-colors disabled:opacity-50 shadow-md">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
          </button>
        </div>
      </div>
    </aside>

    <div
      v-if="selectionPopup.visible"
      :style="{ top: `${selectionPopup.y}px`, left: `${selectionPopup.x}px` }"
      class="fixed -translate-x-1/2 -translate-y-full z-50 bg-(--rt-text) text-(--rt-bg) px-3 py-1.5 rounded-xl shadow-xl flex items-center gap-2 text-xs font-medium animate-fade"
    >
      <span>Ask Smart AI</span>
      <button @click="askAiAboutSelection" class="bg-(--rt-accent) hover:bg-(--rt-bg) hover:text-(--rt-text) px-2 py-0.5 rounded-lg text-(--rt-accent-text) transition-colors font-bold">
        Ask →
      </button>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, computed, nextTick, watch, onMounted, onUnmounted } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import { useChatStore } from '@/stores/chat';
import { useBooksStore } from '@/stores/books';
import { useProgressStore } from '@/stores/progress';
import apiClient from '@/api/client';
import IntroductionPreface from '@/components/IntroductionPreface.vue';
import { useI18n } from 'vue-i18n';

const chatStore = useChatStore();
const booksStore = useBooksStore();
const progressStore = useProgressStore();
const route = useRoute();
const router = useRouter();
const { t } = useI18n();

const isSidebarOpen = ref(true);
const sidebarWidth = ref(300);
const isDraggingLeft = ref(false);

const isAiSidebarOpen = ref(true);
const aiSidebarWidth = ref(380);
const isDraggingRight = ref(false);

const windowWidth = ref(window.innerWidth);
const isMobile = computed(() => windowWidth.value < 1024);
let wasMobile = window.innerWidth < 1024;

const handleResize = () => {
  windowWidth.value = window.innerWidth;
  const currentlyMobile = windowWidth.value < 1024;
  if (currentlyMobile && !wasMobile) {
    isSidebarOpen.value = false;
    isAiSidebarOpen.value = false;
  }
  wasMobile = currentlyMobile;
};

const isFullscreen = ref(false);
const readerScale = ref<number>(100);
const currentPage = ref<number>(1);
const jumpPageInput = ref<string>('1');

const viewMode = ref<'reading' | 'pdf'>('reading');

const LOCAL_BOOK_FALLBACK_URL = '/books/SA-Book.pdf';

const pdfUrl = computed(() => {
  const chapter = booksStore.currentChapter as any;
  const book = (booksStore as any).currentBook;
  return (
    chapter?.pdf_url ||
    chapter?.file_url ||
    chapter?.source_pdf ||
    book?.pdf_url ||
    book?.file_url ||
    LOCAL_BOOK_FALLBACK_URL
  );
});

const preFullscreenState = ref({ sidebar: true, aiSidebar: true });

const readerWidth = ref(1000);
const isDraggingReader = ref(false);
const readerContainerRef = ref<HTMLElement | null>(null);
const scrollAreaRef = ref<HTMLElement | null>(null);
let readerDragCenter = 0;

const expandedChapters = ref<Record<string, boolean>>({});
const toggleChapterCollapse = (chapterId: string) => {
  expandedChapters.value[chapterId] = !expandedChapters.value[chapterId];
};

const isShowingAll = ref(false);
const toggleShowAll = () => {
  isShowingAll.value = !isShowingAll.value;
};

// SORTING LOGIC: Forces Introduction to the top, System Context to the bottom
const getSortedChapters = (book: any) => {
  if (!book) return [];
  let chapters = [...(book.chapters?.data || book.chapters || [])];
  return chapters.sort((a, b) => {
    const aIntro = a.title === 'Introduction & Preface';
    const bIntro = b.title === 'Introduction & Preface';
    const aSys = a.title.includes('System Context');
    const bSys = b.title.includes('System Context');

    if (aIntro) return -1;
    if (bIntro) return 1;
    if (aSys) return 1;
    if (bSys) return -1;
    
    return a.order - b.order;
  });
};

// Get all chapters from the book with the most content (real book, not test data)
const allSortedChapters = computed(() => {
  if (!booksStore.books || booksStore.books.length === 0) return [];
  
  // Find the book with the most total content (real book has long sections)
  const bookWithMostContent = booksStore.books.reduce((prev, current) => {
    // Calculate total content length for each book
    const prevContent = (prev.chapters?.data || prev.chapters || []).reduce((sum: number, ch: any) => {
      return sum + (ch.sections?.data || ch.sections || []).reduce((s: number, sec: any) => {
        return s + (sec.raw_text?.length || 0);
      }, 0);
    }, 0);
    
    const currentContent = (current.chapters?.data || current.chapters || []).reduce((sum: number, ch: any) => {
      return sum + (ch.sections?.data || ch.sections || []).reduce((s: number, sec: any) => {
        return s + (sec.raw_text?.length || 0);
      }, 0);
    }, 0);
    
    return currentContent > prevContent ? current : prev;
  }, booksStore.books[0]);
  
  return getSortedChapters(bookWithMostContent);
});

// Show first 5 chapters or all based on toggle
const allVisibleChapters = computed(() => {
  const chapters = allSortedChapters.value;
  return isShowingAll.value ? chapters : chapters.slice(0, 5);
});

// Check if there are hidden chapters
const hasHiddenChapters = computed(() => {
  return allSortedChapters.value.length > 5;
});

const showAllChats = ref(false);
const visibleSessions = computed(() => {
  const sessions = chatStore.sessions || [];
  return showAllChats.value ? sessions : sessions.slice(0, 6);
});
const hiddenChatsCount = computed(() => Math.max(0, (chatStore.sessions || []).length - 6));

const currentChapterPages = computed(() => {
  return booksStore.currentChapter?.sections?.data || booksStore.currentChapter?.sections || [];
});

const READING_PAGE_MIN_CHARS = 900;

const mergedPages = computed(() => {
  const sections = currentChapterPages.value;
  const pages: { sections: typeof sections }[] = [];
  let bucket: typeof sections = [];
  let bucketLen = 0;

  for (const sec of sections) {
    bucket.push(sec);
    bucketLen += (sec.raw_text || '').length;
    if (bucketLen >= READING_PAGE_MIN_CHARS) {
      pages.push({ sections: bucket });
      bucket = [];
      bucketLen = 0;
    }
  }
  if (bucket.length) pages.push({ sections: bucket });
  return pages;
});

const totalPages = computed<number>(() => {
  if (booksStore.currentChapter?.title === 'Introduction & Preface') return 3;
  return Math.max(1, mergedPages.value.length);
});

const currentPageData = computed(() => {
  const pages = mergedPages.value;
  if (pages.length === 0) return null;
  const idx = Math.min(Math.max(currentPage.value - 1, 0), pages.length - 1);
  return pages[idx];
});

const sectionToPageMap = computed(() => {
  const map = new Map<string, number>();
  mergedPages.value.forEach((page, pageIdx) => {
    page.sections.forEach((sec: any) => map.set(sec.id, pageIdx));
  });
  return map;
});

const readingProgress = computed(() => {
  if (totalPages.value <= 1) return 100;
  return Math.round((currentPage.value / totalPages.value) * 100);
});

type ContentBlock = { type: 'p' | 'bullet'; text: string; label?: string | null };

function splitSentences(text: string): string[] {
  const matches = text.match(/[^.!?]+[.!?]+(\s+|$)/g);
  if (matches) return matches.map(s => s.trim()).filter(Boolean);
  return text.trim() ? [text.trim()] : [];
}

function paragraphize(text: string, perPara = 3): string[] {
  const sentences = splitSentences(text);
  const paras: string[] = [];
  for (let i = 0; i < sentences.length; i += perPara) {
    paras.push(sentences.slice(i, i + perPara).join(' ').trim());
  }
  return paras.filter(Boolean);
}

function formatContent(raw: string | undefined | null): ContentBlock[] {
  if (!raw) return [];
  const cleaned = raw.replace(/\r\n/g, '\n').replace(/\n+/g, ' ').replace(/\s+/g, ' ').trim();
  const blocks: ContentBlock[] = [];

  if (cleaned.includes('•')) {
    const parts = cleaned.split('•').map(p => p.trim()).filter(Boolean);
    const intro = parts.shift();
    if (intro) paragraphize(intro).forEach(p => blocks.push({ type: 'p', text: p }));

    parts.forEach(item => {
      const colonIdx = item.indexOf(':');
      if (colonIdx > 0 && colonIdx < 60) {
        blocks.push({ type: 'bullet', label: item.slice(0, colonIdx).trim(), text: item.slice(colonIdx + 1).trim() });
      } else {
        blocks.push({ type: 'bullet', label: null, text: item });
      }
    });
  } else {
    paragraphize(cleaned).forEach(p => blocks.push({ type: 'p', text: p }));
  }
  return blocks;
}

const scrollToSection = (sectionId: string) => {
  nextTick(() => {
    document.getElementById(`sec-${sectionId}`)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
};

const READ_POSITION_KEY = 'smart-adama-read-position';
const LAST_CHAPTER_KEY = 'smart-adama-last-chapter';

const saveReadPosition = (chapterId: string, page: number) => {
  try {
    const raw = localStorage.getItem(READ_POSITION_KEY);
    const store = raw ? JSON.parse(raw) : {};
    store[chapterId] = page;
    localStorage.setItem(READ_POSITION_KEY, JSON.stringify(store));
  } catch { }
};

const getReadPosition = (chapterId: string): number | null => {
  try {
    const raw = localStorage.getItem(READ_POSITION_KEY);
    if (!raw) return null;
    const store = JSON.parse(raw);
    return typeof store[chapterId] === 'number' ? store[chapterId] : null;
  } catch {
    return null;
  }
};

const checkChapterCompletion = async (chapterId: string, page: number) => {
  if (page >= totalPages.value) {
    try {
      if (typeof booksStore.markChapterRead === 'function') {
        await booksStore.markChapterRead(chapterId);
      } else {
        try {
          await apiClient.post(`/chapters/${chapterId}/read`);
        } catch {
          await apiClient.post(`/progress/chapters/${chapterId}`);
        }
      }
      await progressStore.loadAll();
    } catch (e) {
      console.warn('Backend sync failed:', e);
    }
  }
};

watch(() => booksStore.currentChapter?.id, async (newId) => {
  if (!newId) return;
  localStorage.setItem(LAST_CHAPTER_KEY, newId);
  const saved = getReadPosition(newId);
  currentPage.value = saved ?? 1;
  jumpPageInput.value = currentPage.value.toString();

  await nextTick();
  checkChapterCompletion(newId, currentPage.value);
});

let landAtBottomNext = false;

watch(currentPage, (page) => {
  const chapterId = booksStore.currentChapter?.id;
  if (chapterId) {
    saveReadPosition(chapterId, page);
    checkChapterCompletion(chapterId, page);
  }

  nextTick(() => {
    const el = scrollAreaRef.value;
    if (!el) return;
    if (landAtBottomNext) {
      el.scrollTop = el.scrollHeight;
      landAtBottomNext = false;
    } else {
      el.scrollTop = 0;
    }
  });
});

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

const startReaderDrag = (e: MouseEvent) => {
  isDraggingReader.value = true;
  if (readerContainerRef.value) {
    const rect = readerContainerRef.value.getBoundingClientRect();
    readerDragCenter = rect.left + (rect.width / 2);
  } else {
    readerDragCenter = window.innerWidth / 2;
  }
  document.addEventListener('mousemove', onReaderDrag);
  document.addEventListener('mouseup', stopReaderDrag);
};
const onReaderDrag = (e: MouseEvent) => {
  if (!isDraggingReader.value) return;
  let newWidth = Math.abs(e.clientX - readerDragCenter) * 2;
  if (newWidth < 480) newWidth = 480;
  if (newWidth > 1400) newWidth = 1400; 
  readerWidth.value = newWidth;
};
const stopReaderDrag = () => {
  isDraggingReader.value = false;
  document.removeEventListener('mousemove', onReaderDrag);
  document.removeEventListener('mouseup', stopReaderDrag);
};

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    currentPage.value++;
    jumpPageInput.value = currentPage.value.toString();
  }
};

const prevPage = () => {
  if (currentPage.value > 1) {
    currentPage.value--;
    jumpPageInput.value = currentPage.value.toString();
  }
};

const jumpToPage = () => {
  const p = parseInt(jumpPageInput.value);
  if (!isNaN(p) && p >= 1 && p <= totalPages.value) {
    currentPage.value = p;
  } else {
    jumpPageInput.value = currentPage.value.toString();
  }
};

const markCompleteAndNextChapter = async () => {
  const currentId = booksStore.currentChapter?.id;
  if (currentId) {
    await booksStore.markChapterRead(currentId);
    await progressStore.loadAll();
  }
  const chapters = allSortedChapters.value;
  if (chapters.length > 0) {
    const currentIndex = chapters.findIndex((c: any) => c.id === currentId);
    if (currentIndex !== -1 && currentIndex + 1 < chapters.length) {
      const nextChap = chapters[currentIndex + 1];
      await loadBookChapter(nextChap.id);
      currentPage.value = 1;
      jumpPageInput.value = '1';
      if (scrollAreaRef.value) scrollAreaRef.value.scrollTop = 0;
    } else {
      router.push('/dashboard'); 
    }
  }
};

const jumpToSection = async (chapterId: string, sectionId: string) => {
  if (booksStore.currentChapter?.id !== chapterId) {
    await loadBookChapter(chapterId);
    await nextTick();
  }
  const pageIdx = sectionToPageMap.value.get(sectionId);
  if (pageIdx !== undefined) {
    currentPage.value = pageIdx + 1;
    jumpPageInput.value = currentPage.value.toString();
  }
  if (isMobile.value) isSidebarOpen.value = false;
};

const handleTocClick = async (chapterNum: string) => {
  const chapters = allSortedChapters.value;
  const target = chapters.find((c: any) => {
    const regex = new RegExp(`^chapter\\s+${chapterNum}\\b`, 'i');
    return regex.test(c.title);
  });
  if (target) {
    await loadBookChapter(target.id);
    currentPage.value = 1;
    jumpPageInput.value = '1';
    if (isMobile.value) isSidebarOpen.value = false;
  }
};

const OVERSCROLL_THRESHOLD = 260;
const OVERSCROLL_RESET_MS = 350;
const PAGE_TURN_COOLDOWN_MS = 500;
const overscrollAmount = ref(0);
const overscrollDir = ref<'up' | 'down' | null>(null);
let overscrollAccum = 0;
let overscrollResetTimer: number | null = null;
let isTurningPage = false;

const clearOverscroll = () => {
  overscrollAccum = 0;
  overscrollAmount.value = 0;
  overscrollDir.value = null;
  if (overscrollResetTimer) {
    clearTimeout(overscrollResetTimer);
    overscrollResetTimer = null;
  }
};

const armOverscrollResetTimer = () => {
  if (overscrollResetTimer) clearTimeout(overscrollResetTimer);
  overscrollResetTimer = window.setTimeout(clearOverscroll, OVERSCROLL_RESET_MS);
};

const handleReaderWheel = (e: WheelEvent) => {
  if (isTurningPage || viewMode.value !== 'reading') return;
  const el = scrollAreaRef.value;
  if (!el) return;

  const atTop = el.scrollTop <= 2;
  const atBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 2;

  const wantsUp = e.deltaY < 0 && atTop;
  const wantsDown = e.deltaY > 0 && atBottom;

  if (!wantsUp && !wantsDown) {
    clearOverscroll();
    return;
  }

  const dir: 'up' | 'down' = wantsUp ? 'up' : 'down';
  if (overscrollDir.value !== dir) {
    overscrollAccum = 0;
    overscrollDir.value = dir;
  }

  overscrollAccum += Math.abs(e.deltaY);
  overscrollAmount.value = Math.min(1, overscrollAccum / OVERSCROLL_THRESHOLD);
  armOverscrollResetTimer();

  if (overscrollAccum >= OVERSCROLL_THRESHOLD) {
    isTurningPage = true;
    if (dir === 'up' && currentPage.value > 1) {
      landAtBottomNext = true;
      prevPage();
    } else if (dir === 'down' && currentPage.value < totalPages.value) {
      nextPage();
    }
    clearOverscroll();
    window.setTimeout(() => { isTurningPage = false; }, PAGE_TURN_COOLDOWN_MS);
  }
};

const toggleFullscreen = () => {
  if (!isFullscreen.value) {
    preFullscreenState.value = { sidebar: isSidebarOpen.value, aiSidebar: isAiSidebarOpen.value };
    isSidebarOpen.value = false;
    isAiSidebarOpen.value = false;
    isFullscreen.value = true;
  } else {
    isSidebarOpen.value = preFullscreenState.value.sidebar;
    isAiSidebarOpen.value = preFullscreenState.value.aiSidebar;
    isFullscreen.value = false;
  }
};

type ThemeKey = 'light' | 'sepia' | 'dark' | 'green' | 'brown';
const THEMES: Record<ThemeKey, { label: string; vars: Record<string, string> }> = {
  light: { label: 'Light', vars: { '--rt-bg': '#F0F3FA', '--rt-surface': '#FFFFFF', '--rt-surface-2': '#D5DEEF', '--rt-border': '#B1C9EF', '--rt-text': '#395886', '--rt-text-body': '#1e293b', '--rt-muted': '#628ECB', '--rt-accent': '#8AAEE0', '--rt-accent-hover': '#628ECB', '--rt-accent-text': '#FFFFFF' } },
  sepia: { label: 'Sepia', vars: { '--rt-bg': '#EAE0C8', '--rt-surface': '#F4ECD8', '--rt-surface-2': '#EADBB8', '--rt-border': '#DCCBA8', '--rt-text': '#4A3728', '--rt-text-body': '#5B4636', '--rt-muted': '#9C876C', '--rt-accent': '#8A5A32', '--rt-accent-hover': '#734723', '--rt-accent-text': '#FFFFFF' } },
  dark: { label: 'Dark', vars: { '--rt-bg': '#0B0D10', '--rt-surface': '#1E2128', '--rt-surface-2': '#15171B', '--rt-border': '#2C303A', '--rt-text': '#EDEFF3', '--rt-text-body': '#D8DEE9', '--rt-muted': '#8890A0', '--rt-accent': '#5B7FDB', '--rt-accent-hover': '#7093EE', '--rt-accent-text': '#0B0D10' } },
  green: { label: 'Forest', vars: { '--rt-bg': '#E4EEE0', '--rt-surface': '#F2F8EF', '--rt-surface-2': '#DCE9D6', '--rt-border': '#C6DABF', '--rt-text': '#22381F', '--rt-text-body': '#33492F', '--rt-muted': '#748C6C', '--rt-accent': '#3F7D46', '--rt-accent-hover': '#356B3B', '--rt-accent-text': '#FFFFFF' } },
  brown: { label: 'Walnut', vars: { '--rt-bg': '#DFCBB2', '--rt-surface': '#EEDFC9', '--rt-surface-2': '#D6C2A6', '--rt-border': '#CDB48C', '--rt-text': '#3B2A1A', '--rt-text-body': '#4E3823', '--rt-muted': '#8A7256', '--rt-accent': '#6B4423', '--rt-accent-hover': '#59371B', '--rt-accent-text': '#FFFFFF' } },
};

const READER_THEME_KEY = 'smart-adama-reader-theme';
const READER_FONT_KEY = 'smart-adama-reader-font';
const readerTheme = ref<ThemeKey>((localStorage.getItem(READER_THEME_KEY) as ThemeKey) || 'light');
const readerFont = ref<'serif' | 'sans'>((localStorage.getItem(READER_FONT_KEY) as 'serif' | 'sans') || 'serif');
const themeVars = computed(() => THEMES[readerTheme.value].vars);

const isThemeMenuOpen = ref(false);
const isFontMenuOpen = ref(false);
const themeMenuRef = ref<HTMLElement | null>(null);
const fontMenuRef = ref<HTMLElement | null>(null);

const setTheme = (key: ThemeKey) => {
  readerTheme.value = key;
  isThemeMenuOpen.value = false;
  try { localStorage.setItem(READER_THEME_KEY, key); } catch { }
};

const setFont = (key: 'serif' | 'sans') => {
  readerFont.value = key;
  isFontMenuOpen.value = false;
  try { localStorage.setItem(READER_FONT_KEY, key); } catch { }
};

const handleClickOutsideMenus = (e: MouseEvent) => {
  if (isThemeMenuOpen.value && themeMenuRef.value && !themeMenuRef.value.contains(e.target as Node)) {
    isThemeMenuOpen.value = false;
  }
  if (isFontMenuOpen.value && fontMenuRef.value && !fontMenuRef.value.contains(e.target as Node)) {
    isFontMenuOpen.value = false;
  }
};

const handleKeydown = (e: KeyboardEvent) => {
  const target = e.target as HTMLElement;
  const isTyping = target.tagName === 'INPUT' || target.tagName === 'TEXTAREA';
  if (isTyping) return;
  if (e.key === 'ArrowRight') nextPage();
  else if (e.key === 'ArrowLeft') prevPage();
  else if (e.key === 'Escape' && isFullscreen.value) toggleFullscreen();
};

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
  chatInput.value = `Can you explain this excerpt from chapter page ${currentPage.value}: "${snippet}"`;
  selectionPopup.value.visible = false;
  window.getSelection()?.removeAllRanges();
  isAiSidebarOpen.value = true;
  nextTick(() => { sendMessage(); });
};

onMounted(async () => {
  window.addEventListener('resize', handleResize);
  await Promise.all([
    chatStore.loadSessions(1),
    booksStore.loadBooks()
  ]);
  const chapters = allSortedChapters.value;
  if (chapters.length > 0) {
    const savedChapterId = localStorage.getItem(LAST_CHAPTER_KEY);
    // Fallback defaults to Intro & Preface which is now guaranteed to be index 0
    const targetChapter = chapters.find((c: any) => c.id === savedChapterId) || chapters[0];
    await booksStore.loadChapter(targetChapter.id);
  }
  const sessionId = route.params.sessionId as string;
  if (sessionId) {
    await chatStore.loadSession(sessionId);
  }
  document.addEventListener('mouseup', handleTextSelection);
  document.addEventListener('keydown', handleKeydown);
  document.addEventListener('click', handleClickOutsideMenus);
});

onUnmounted(() => {
  window.removeEventListener('resize', handleResize);
  document.removeEventListener('mousemove', onDragLeft);
  document.removeEventListener('mouseup', stopDragLeft);
  document.removeEventListener('mousemove', onDragRight);
  document.removeEventListener('mouseup', stopDragRight);
  document.removeEventListener('mousemove', onReaderDrag);
  document.removeEventListener('mouseup', stopReaderDrag);
  document.removeEventListener('mouseup', handleTextSelection);
  document.removeEventListener('keydown', handleKeydown);
  document.removeEventListener('click', handleClickOutsideMenus);
  clearOverscroll();
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

const startNewChatAndOpen = async () => {
  await startNewChat();
  isAiSidebarOpen.value = true;
};

const switchSession = async (sessionId: string) => {
  await chatStore.loadSession(sessionId);
  router.push({ name: 'study-session', params: { sessionId } });
  if (isMobile.value) isSidebarOpen.value = false;
};

const loadBookChapter = async (chapterId: string) => {
  await booksStore.loadChapter(chapterId);
  if (isMobile.value) isSidebarOpen.value = false;
};

const toggleFeedback = async (message: any, feedbackType: 'helpful' | 'not_helpful') => {
  // Toggle feedback: if already selected, clear it; otherwise set it
  const newFeedback = message.feedback === feedbackType ? null : feedbackType;
  
  // Update the message object to trigger Vue reactivity
  const messageIndex = currentMessages.value.findIndex(m => m.id === message.id);
  if (messageIndex !== -1) {
    // Replace the entire message object to trigger reactivity
    currentMessages.value[messageIndex] = {
      ...currentMessages.value[messageIndex],
      feedback: newFeedback
    };
  }
  
  // Optional: Send feedback to backend
  try {
    // await apiClient.post(`/chat/messages/${message.id}/feedback`, { feedback: newFeedback });
  } catch (error) {
    console.error('Failed to save feedback:', error);
  }
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

<style scoped>
@keyframes simpleFade {
  from { opacity: 0; transform: translateY(4px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fade {
  animation: simpleFade 0.25s ease-out forwards;
}

::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}
::-webkit-scrollbar-track {
  background: transparent;
}
::-webkit-scrollbar-thumb {
  background: var(--rt-border, #B1C9EF);
  border-radius: 10px;
}
::-webkit-scrollbar-thumb:hover {
  background: var(--rt-accent, #8AAEE0);
}
</style>

<style>
/* AI Response Markdown Styling - ChatGPT/Claude Style */
/* MUST be unscoped because v-html content doesn't get scoped attributes */
.ai-response-content {
  line-height: 1.7;
  color: var(--rt-text-body);
}

/* Headings */
.ai-response-content h1,
.ai-response-content h2,
.ai-response-content h3,
.ai-response-content h4 {
  font-weight: 700;
  margin-top: 1.5em;
  margin-bottom: 0.5em;
  color: var(--rt-text);
  line-height: 1.3;
}

.ai-response-content h1:first-child,
.ai-response-content h2:first-child,
.ai-response-content h3:first-child {
  margin-top: 0;
}

.ai-response-content h1 { font-size: 1.5em; }
.ai-response-content h2 { font-size: 1.3em; }
.ai-response-content h3 { font-size: 1.1em; }
.ai-response-content h4 { font-size: 1em; }

/* Paragraphs */
.ai-response-content p {
  margin: 0.75em 0;
  line-height: 1.7;
}

/* Strong and Em */
.ai-response-content strong {
  font-weight: 700;
  color: var(--rt-text);
}

.ai-response-content em {
  font-style: italic;
}

/* Lists */
.ai-response-content ul,
.ai-response-content ol {
  margin: 0.75em 0;
  padding-left: 1.75em;
}

.ai-response-content ul {
  list-style-type: disc;
}

.ai-response-content ol {
  list-style-type: decimal;
}

.ai-response-content li {
  margin: 0.35em 0;
  line-height: 1.6;
}

.ai-response-content li > p {
  margin: 0.25em 0;
}

/* Nested lists */
.ai-response-content ul ul,
.ai-response-content ol ul {
  list-style-type: circle;
}

.ai-response-content ol ol,
.ai-response-content ul ol {
  list-style-type: lower-alpha;
}

/* Code */
.ai-response-content code {
  background: var(--rt-bg);
  padding: 0.15em 0.4em;
  border-radius: 0.25rem;
  font-size: 0.9em;
  font-family: 'Monaco', 'Menlo', 'Courier New', monospace;
  color: var(--rt-text);
  border: 1px solid var(--rt-border);
}

.ai-response-content pre {
  background: var(--rt-bg);
  padding: 1rem;
  border-radius: 0.5rem;
  overflow-x: auto;
  margin: 1em 0;
  border: 1px solid var(--rt-border);
}

.ai-response-content pre code {
  background: none;
  padding: 0;
  border: none;
  font-size: 0.85em;
  line-height: 1.5;
}

/* Links */
.ai-response-content a {
  color: var(--rt-accent);
  text-decoration: underline;
  font-weight: 500;
}

.ai-response-content a:hover {
  color: var(--rt-accent-hover);
}

/* Blockquotes */
.ai-response-content blockquote {
  border-left: 4px solid var(--rt-accent);
  padding-left: 1em;
  margin: 1em 0;
  font-style: italic;
  color: var(--rt-muted);
}

/* Horizontal Rule */
.ai-response-content hr {
  border: none;
  border-top: 1px solid var(--rt-border);
  margin: 1.5em 0;
}

/* Tables */
.ai-response-content table {
  border-collapse: collapse;
  width: 100%;
  margin: 1em 0;
}

.ai-response-content th,
.ai-response-content td {
  border: 1px solid var(--rt-border);
  padding: 0.5em 0.75em;
  text-align: left;
}

.ai-response-content th {
  background: var(--rt-bg);
  font-weight: 600;
}
</style>