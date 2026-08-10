<template>
  <div 
    v-html="sanitizedHtml" 
    class="prose max-w-none text-sm leading-relaxed"
    :class="[
      props.className,
      { 'text-(--rt-text-body)': true }
    ]"
    @dblclick.stop
  />
</template>

<script setup lang="ts">
import { computed } from 'vue';
import DOMPurify from 'dompurify';
import { marked } from 'marked';

interface Props {
  source: string;
  className?: string;
}

const props = defineProps<Props>();

const sanitizedHtml = computed(() => {
  const html = marked(props.source || '');
  return DOMPurify.sanitize(html, {
    ALLOWED_TAGS: [
      'p', 'br', 'hr', 'strong', 'em', 'b', 'i', 'u', 'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
      'ul', 'ol', 'li', 'blockquote', 'code', 'pre', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
      'sub', 'sup', 'del', 'ins', 'small', 'mark', 'cite', 'q'
    ],
    ALLOWED_ATTR: ['href', 'title', 'target', 'class', 'style'],
    ALLOWED_PROTOCOLS: ['http', 'https', 'mailto'],
  });
});
</script>
