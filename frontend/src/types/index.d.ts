declare namespace App {
  // ── Auth ──────────────────────────────────────────────────────────────────
  interface User {
    id: string
    name: string
    email: string
    role: 'learner' | 'admin'
    avatar_url: string | null
    created_at: string
  }

  interface UserProfile extends User {
    locale: string
    notify_badges: boolean
    progress_summary: ProgressSummary
  }

  // ── Books ─────────────────────────────────────────────────────────────────
  interface Book {
    id: string
    title: string
    status: 'draft' | 'published'
    chapters: Chapter[]
  }

  interface Chapter {
    id: string
    book_id: string
    title: string
    order: number
    ingestion_status: 'draft' | 'queued' | 'processing' | 'ready' | 'failed'
    sections: Section[]
  }

  interface Section {
    id: string
    chapter_id: string
    title: string
    order: number
    raw_text: string | null
  }

  interface ChapterProgress {
    is_completed: boolean
    best_quiz_score_pct: number | null
    last_read_at: string | null
  }

  // ── Chat ──────────────────────────────────────────────────────────────────
  interface ChatSession {
    id: string
    title: string
    last_activity_at: string
    created_at: string
    messages?: ChatMessage[]
  }

  interface ChatMessageFeedback {
    id: string
    chat_message_id: string
    user_id: string
    feedback: 'like' | 'dislike'
    created_at: string
  }

  interface ChatMessage {
    id: string
    chat_session_id: string
    role: 'user' | 'assistant'
    content: string
    created_at: string
    sources?: ChatMessageSource[]
    feedback?: ChatMessageFeedback | null
  }

  interface ChatMessageSource {
    id?: string
    chunk_id: string
    chapter_title: string
    section_title: string
    excerpt: string
    similarity: number
    chunk_text?: string
  }

  interface ChatMessageStreamDone {
    message_id: string
    grounded: boolean
    citations: ChatMessageSource[]
  }

  // ── Quiz ──────────────────────────────────────────────────────────────────
  interface Quiz {
    id: string
    chapter_id: string
    title: string
    passing_score_pct: number
    status: 'draft' | 'published'
    questions: QuizQuestion[]
  }

  interface QuizQuestion {
    id: string
    question_text: string
    type: 'single' | 'multiple' | 'true_false'
    order: number
    options: QuizOption[]
  }

  interface QuizOption {
    id: string
    option_text: string
    order: number
    // is_correct intentionally absent — not sent to learners
  }

  interface QuizAnswer {
    question_id: string
    selected_option_ids: string[]
  }

  interface QuizAttemptSummary {
    id: string
    quiz_id: string
    quiz_title: string | null
    score_pct: number | null
    passed: boolean
    started_at: string
    submitted_at: string | null
  }

  interface GradedAttempt {
    id: string
    quiz_id: string
    score_pct: number
    passed: boolean
    total_questions: number
    correct_count: number
    submitted_at: string
    per_question: PerQuestionResult[]
  }

  interface PerQuestionResult {
    question_id: string
    question_text: string
    is_correct: boolean
    selected_option_ids: string[]
    correct_option_ids: string[]
    explanation: string | null
  }

  // ── Progress & Gamification ───────────────────────────────────────────────
  interface ProgressSummary {
    total_chapters: number
    completed_chapters: number
    completion_pct: number
    average_quiz_score: number | null
  }

  interface Badge {
    id: string
    code: string
    name: string
    description: string
    icon: string
    earned: boolean
    awarded_at: string | null
    progress: { current: number; required: number } | null
  }

  interface Streak {
    current_streak: number
    longest_streak: number
    last_activity_date: string | null
  }

  interface Dashboard {
    completion_pct: number
    total_chapters: number
    completed_chapters: number
    quizzes_passed: number
    average_quiz_score: number | null
    current_streak: number
    total_chat_sessions: number
    earned_badge_count: number
  }

  // ── Shared ────────────────────────────────────────────────────────────────
  interface PaginationMeta {
    current_page: number
    last_page: number
    total: number
  }
}
