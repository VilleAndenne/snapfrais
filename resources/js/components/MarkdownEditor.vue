<template>
    <div class="space-y-2">
        <!-- Tabs -->
        <div class="flex gap-2 border-b border-border">
            <button
                @click="activeTab = 'write'"
                :class="[
                    'px-4 py-2 text-sm font-medium transition-colors',
                    activeTab === 'write'
                        ? 'border-b-2 border-primary text-primary'
                        : 'text-muted-foreground hover:text-foreground',
                ]"
                type="button"
            >
                Écrire
            </button>
            <button
                @click="activeTab = 'preview'"
                :class="[
                    'px-4 py-2 text-sm font-medium transition-colors',
                    activeTab === 'preview'
                        ? 'border-b-2 border-primary text-primary'
                        : 'text-muted-foreground hover:text-foreground',
                ]"
                type="button"
            >
                Aperçu
            </button>
        </div>

        <!-- Toolbar (visible uniquement en mode "write") -->
        <div v-if="activeTab === 'write'" class="flex flex-wrap gap-2 rounded border border-border bg-muted/30 p-2">
            <Button type="button" variant="ghost" size="sm" @click="insertMarkdown('**', '**', 'texte en gras')">
                <BoldIcon class="h-4 w-4" />
            </Button>
            <Button type="button" variant="ghost" size="sm" @click="insertMarkdown('*', '*', 'texte en italique')">
                <ItalicIcon class="h-4 w-4" />
            </Button>
            <Button type="button" variant="ghost" size="sm" @click="insertMarkdown('## ', '', 'Titre')">
                <Heading2Icon class="h-4 w-4" />
            </Button>
            <Button type="button" variant="ghost" size="sm" @click="insertMarkdown('- ', '', 'élément de liste')">
                <ListIcon class="h-4 w-4" />
            </Button>
            <Button type="button" variant="ghost" size="sm" @click="insertMarkdown('[', '](url)', 'texte du lien')">
                <LinkIcon class="h-4 w-4" />
            </Button>
            <Button type="button" variant="ghost" size="sm" @click="insertMarkdown('`', '`', 'code')">
                <CodeIcon class="h-4 w-4" />
            </Button>

            <div class="ml-auto">
                <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="handleImageUpload" />
                <Button type="button" variant="outline" size="sm" @click="triggerImageUpload" :disabled="uploading">
                    <Loader2Icon v-if="uploading" class="mr-2 h-4 w-4 animate-spin" />
                    <ImageIcon v-else class="mr-2 h-4 w-4" />
                    Insérer une image
                </Button>
            </div>
        </div>

        <!-- Éditeur / Preview -->
        <div class="min-h-[300px] rounded border border-border">
            <!-- Mode écriture -->
            <Textarea
                v-if="activeTab === 'write'"
                ref="textarea"
                :model-value="modelValue"
                @update:model-value="$emit('update:modelValue', $event)"
                :placeholder="placeholder"
                class="min-h-[300px] resize-none border-0 font-mono text-sm"
            />

            <!-- Mode preview -->
            <div v-else class="prose prose-sm max-w-none p-4 dark:prose-invert" v-html="renderedMarkdown"></div>
        </div>

        <!-- Aide rapide -->
        <div class="text-xs text-muted-foreground">
            <details>
                <summary class="cursor-pointer">Aide Markdown</summary>
                <div class="mt-2 space-y-1">
                    <p><code>**gras**</code> → <strong>gras</strong></p>
                    <p><code>*italique*</code> → <em>italique</em></p>
                    <p><code>## Titre</code> → Titre niveau 2</p>
                    <p><code>- élément</code> → Liste à puces</p>
                    <p><code>[lien](url)</code> → Lien hypertexte</p>
                    <p><code>`code`</code> → Code inline</p>
                    <p><code>![alt](url)</code> → Image</p>
                </div>
            </details>
        </div>
    </div>
</template>

<script setup>
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import axios from 'axios';
import DOMPurify from 'dompurify';
import {
    BoldIcon,
    CodeIcon,
    Heading2Icon,
    ImageIcon,
    ItalicIcon,
    LinkIcon,
    ListIcon,
    Loader2Icon,
} from 'lucide-vue-next';
import { marked } from 'marked';
import { computed, ref } from 'vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Écrivez votre contenu en Markdown...' },
});

const emit = defineEmits(['update:modelValue']);

const activeTab = ref('write');
const textarea = ref(null);
const fileInput = ref(null);
const uploading = ref(false);

// Rendu du Markdown
const renderedMarkdown = computed(() => {
    if (!props.modelValue) return '<p class="text-muted-foreground">Rien à prévisualiser</p>';
    const rawHtml = marked.parse(props.modelValue);
    return DOMPurify.sanitize(rawHtml);
});

// Insérer du Markdown à la position du curseur
const insertMarkdown = (before, after, placeholder) => {
    const textareaEl = textarea.value.$el;
    const start = textareaEl.selectionStart;
    const end = textareaEl.selectionEnd;
    const selectedText = props.modelValue.substring(start, end) || placeholder;
    const newText = props.modelValue.substring(0, start) + before + selectedText + after + props.modelValue.substring(end);

    emit('update:modelValue', newText);

    // Remettre le focus et sélectionner le texte inséré
    setTimeout(() => {
        textareaEl.focus();
        textareaEl.setSelectionRange(start + before.length, start + before.length + selectedText.length);
    }, 0);
};

// Déclencher l'upload d'image
const triggerImageUpload = () => {
    fileInput.value.click();
};

// Gérer l'upload d'image
const handleImageUpload = async (event) => {
    const file = event.target.files[0];
    if (!file) return;

    uploading.value = true;

    try {
        const formData = new FormData();
        formData.append('image', file);

        const response = await axios.post('/api/patch-notes/upload-image', formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        const imageUrl = response.data.url;

        // Insérer le Markdown de l'image
        const textareaEl = textarea.value.$el;
        const start = textareaEl.selectionStart;
        const imageMarkdown = `![${file.name}](${imageUrl})`;
        const newText = props.modelValue.substring(0, start) + imageMarkdown + props.modelValue.substring(start);

        emit('update:modelValue', newText);

        // Remettre le focus
        setTimeout(() => {
            textareaEl.focus();
            textareaEl.setSelectionRange(start + imageMarkdown.length, start + imageMarkdown.length);
        }, 0);
    } catch (error) {
        console.error('Erreur lors de l\'upload de l\'image:', error);
        alert('Erreur lors de l\'upload de l\'image');
    } finally {
        uploading.value = false;
        fileInput.value.value = '';
    }
};
</script>