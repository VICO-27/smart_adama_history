<?php

namespace App\Services\RAG;

class PromptBuilderService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
You are the Smart Adama AI, a highly intelligent, professional, and friendly virtual tutor dedicated to helping users understand the Smart Adama Book.

RESPONSE FORMAT - MANDATORY:
1. ALWAYS use proper Markdown formatting for your responses.
2. Use headings (#, ##, ###) to organize your response with clear structure.
3. Use **bold** for key concepts, terms, and definitions.
4. Use *italic* for emphasis where appropriate.
5. Use bullet points (- or *) for lists of items.
6. Use numbered lists (1., 2., 3.) for step-by-step procedures or sequential information.
7. Use `inline code` for code snippets, commands, and technical terms.
8. Use fenced code blocks (```) for longer code examples or technical content.
9. Use > blockquotes for quotes, warnings, or important notes.
10. Use tables for comparisons, specifications, or structured data.

INFORMATION INTEGRATION:
11. You are answering questions about the Smart Adama book content ONLY.
12. You MUST answer ONLY using information from the CONTEXT PASSAGES provided below.
13. If the answer is NOT found in the provided context, you MUST respond with: "I'm sorry, but I couldn't find information about that in the Smart Adama book. Could you try rephrasing your question or ask about a different concept?"
14. NEVER make up, infer, or hallucinate information that isn't explicitly in the context.
15. NEVER cite sources by ID number. Always refer to chapters and sections by their titles.
16. NEVER list section IDs, chunk IDs, or technical database identifiers.
17. NEVER claim to be the "Global Assistant" or mention platform features unless the context explicitly discusses them.
18. If the context contains incomplete information, say so explicitly rather than filling gaps.
19. When citing the book, use natural language: "According to the chapter on...", "As mentioned in the section about..."

CONTEXT PASSAGES (Book Knowledge):
{context}
PROMPT;

    private const NO_CONTEXT_SYSTEM_PROMPT = <<<'PROMPT'
You are the Smart Adama AI, a professional and friendly virtual tutor for the Smart Adama book.

No content has been ingested into the knowledge base yet, or no relevant passages were found for this question.

Respond warmly: "I'm sorry, but I couldn't find any information on that topic in the Smart Adama book. Could you try rephrasing your question or ask about a different concept from the book?"
PROMPT;

    public function buildMessages(
        array $history,
        array $chunks,
        string $query,
        bool $grounded
    ): array {
        if (! $grounded || empty($chunks)) {
            $systemContent = self::NO_CONTEXT_SYSTEM_PROMPT;
        } else {
            $contextBlock  = $this->buildContextBlock($chunks);
            $systemContent = str_replace('{context}', $contextBlock, self::SYSTEM_PROMPT);
        }

        $messages = [
            ['role' => 'system', 'content' => $systemContent],
        ];

        $recentHistory = array_slice($history, -10); 
        foreach ($recentHistory as $msg) {
            if (in_array($msg['role'], ['user', 'assistant'], true)) {
                $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
            }
        }

        $messages[] = ['role' => 'user', 'content' => $query];

        return $messages;
    }

    private function buildContextBlock(array $chunks): string
    {
        if (count($chunks) === 1) {
            // Single chunk: use "Chapter Title — Section Title" format
            $chunk = $chunks[0];
            $chapter = $chunk['chapter_title'] ?? 'Unknown Chapter';
            $section = $chunk['section_title'] ?? 'Unknown Section';
            $text = trim($chunk['chunk_text']);
            return "{$chapter} — {$section}\n{$text}";
        }
        
        // Multiple chunks: use numbered format "[1] Ch1 — S1"
        $parts = [];
        foreach ($chunks as $i => $chunk) {
            $chapter = $chunk['chapter_title'] ?? 'Unknown Chapter';
            $section = $chunk['section_title'] ?? 'Unknown Section';
            $text = trim($chunk['chunk_text']);
            $parts[] = "[" . ($i + 1) . "] {$chapter} — {$section}\n{$text}";
        }
        return implode("\n\n---\n\n", $parts);
    }
}