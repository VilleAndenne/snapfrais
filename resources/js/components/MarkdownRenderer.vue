<template>
    <div class="prose prose-sm max-w-none dark:prose-invert" v-html="renderedMarkdown"></div>
</template>

<script setup>
import DOMPurify from 'dompurify';
import { marked } from 'marked';
import { computed } from 'vue';

const props = defineProps({
    content: { type: String, default: '' },
});

// Rendu du Markdown
const renderedMarkdown = computed(() => {
    if (!props.content) return '<p class="text-muted-foreground">Aucun contenu</p>';
    const rawHtml = marked.parse(props.content);
    return DOMPurify.sanitize(rawHtml);
});
</script>