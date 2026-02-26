<template>
    <div
        :class="['wp-container', { 'wp-theme-dark': theme === 'dark' }]"
        :style="containerStyle"
    >
        <!-- Website mockup background -->
        <div class="wp-mock-site">
            <div class="wp-mock-nav">
                <div class="wp-mock-logo"></div>
                <div class="wp-mock-nav-items">
                    <div class="wp-mock-nav-item"></div>
                    <div class="wp-mock-nav-item"></div>
                    <div class="wp-mock-nav-item"></div>
                </div>
            </div>
            <div class="wp-mock-content">
                <div class="wp-mock-hero"></div>
                <div class="wp-mock-line wp-mock-line-lg"></div>
                <div class="wp-mock-line wp-mock-line-md"></div>
                <div class="wp-mock-line wp-mock-line-sm"></div>
                <div class="wp-mock-grid">
                    <div class="wp-mock-card"></div>
                    <div class="wp-mock-card"></div>
                    <div class="wp-mock-card"></div>
                </div>
            </div>
        </div>

        <!-- Widget launcher button -->
        <button
            v-if="!expanded"
            :class="['wp-launcher', positionClass]"
            :style="launcherStyle"
            @click="expanded = true"
            title="Open widget"
        >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
            </svg>
        </button>

        <!-- Expanded widget panel -->
        <div
            v-if="expanded"
            :class="['wp-panel', positionClass]"
            :style="panelStyle"
        >
            <!-- Header -->
            <div class="wp-panel-header" :style="headerStyle">
                <div class="wp-panel-header-text">
                    <span class="wp-panel-greeting">{{ config.greeting || 'Hi there! How can we help you?' }}</span>
                </div>
                <button class="wp-panel-close" @click="expanded = false" :style="{ color: headerTextColor }">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="wp-panel-body" :style="bodyStyle">
                <!-- Tab switcher -->
                <div class="wp-tabs" v-if="config.kb_enabled !== false">
                    <button
                        :class="['wp-tab', { 'wp-tab-active': activeTab === 'form' }]"
                        :style="activeTab === 'form' ? activeTabStyle : {}"
                        @click="activeTab = 'form'"
                    >
                        Contact
                    </button>
                    <button
                        :class="['wp-tab', { 'wp-tab-active': activeTab === 'kb' }]"
                        :style="activeTab === 'kb' ? activeTabStyle : {}"
                        @click="activeTab = 'kb'"
                    >
                        Help Articles
                    </button>
                </div>

                <!-- KB search tab -->
                <div v-if="activeTab === 'kb' && config.kb_enabled !== false" class="wp-kb-tab">
                    <div class="wp-kb-search-wrap">
                        <svg class="wp-kb-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8" />
                            <line x1="21" y1="21" x2="16.65" y2="16.65" />
                        </svg>
                        <input
                            type="text"
                            class="wp-kb-search-input"
                            placeholder="Search articles..."
                            v-model="kbQuery"
                            :style="inputStyle"
                        />
                    </div>
                    <div class="wp-kb-results">
                        <div class="wp-kb-article" v-for="(article, i) in mockArticles" :key="i">
                            <div class="wp-kb-article-title" :style="{ color: config.colors?.primary || '#6366f1' }">
                                {{ article.title }}
                            </div>
                            <div class="wp-kb-article-excerpt">{{ article.excerpt }}</div>
                        </div>
                    </div>
                </div>

                <!-- Contact form tab -->
                <div v-if="activeTab === 'form'" class="wp-form-tab">
                    <div
                        v-for="(field, index) in visibleFields"
                        :key="field.name + '-' + index"
                        class="wp-form-field"
                    >
                        <label class="wp-form-label" :style="labelStyle">
                            {{ field.label }}
                            <span v-if="field.required" class="wp-form-required">*</span>
                        </label>
                        <textarea
                            v-if="field.type === 'textarea'"
                            class="wp-form-input wp-form-textarea"
                            :placeholder="'Enter ' + field.label.toLowerCase() + '...'"
                            :style="inputStyle"
                            rows="3"
                            disabled
                        ></textarea>
                        <select
                            v-else-if="field.type === 'select'"
                            class="wp-form-input"
                            :style="inputStyle"
                            disabled
                        >
                            <option>Select {{ field.label.toLowerCase() }}...</option>
                        </select>
                        <input
                            v-else
                            :type="field.type || 'text'"
                            class="wp-form-input"
                            :placeholder="'Enter ' + field.label.toLowerCase() + '...'"
                            :style="inputStyle"
                            disabled
                        />
                    </div>

                    <button class="wp-form-submit" :style="submitStyle">
                        Send Message
                    </button>
                </div>
            </div>

            <!-- Footer -->
            <div class="wp-panel-footer" :style="footerStyle">
                <span class="wp-powered-by">Powered by <strong>Escalated</strong></span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, inject } from 'vue';

const isDark = inject('esc-dark', false);

const props = defineProps({
    config: {
        type: Object,
        required: true,
    },
    theme: {
        type: String,
        default: 'light',
        validator: (v) => ['light', 'dark'].includes(v),
    },
});

// ---------------------------------------------------------------------------
// State
// ---------------------------------------------------------------------------
const expanded = ref(true);
const activeTab = ref('form');
const kbQuery = ref('');

const mockArticles = [
    { title: 'Getting Started Guide', excerpt: 'Learn how to set up your account and get started quickly.' },
    { title: 'Billing & Payments FAQ', excerpt: 'Common questions about billing, invoices, and payment methods.' },
    { title: 'Troubleshooting Connectivity', excerpt: 'Steps to resolve common connection and access issues.' },
];

// ---------------------------------------------------------------------------
// Computed styles
// ---------------------------------------------------------------------------
const colors = computed(() => ({
    primary: props.config.colors?.primary || '#6366f1',
    background: props.config.colors?.background || '#ffffff',
    text: props.config.colors?.text || '#1f2937',
    launcher: props.config.colors?.launcher || '#6366f1',
}));

const positionClass = computed(() => {
    return props.config.position === 'bottom-left' ? 'wp-pos-left' : 'wp-pos-right';
});

const containerStyle = computed(() => ({
    backgroundColor: props.theme === 'dark' ? '#1a1a2e' : '#f1f5f9',
}));

/**
 * Determine whether a background color is light or dark, and return
 * an appropriate contrasting text color.
 */
function contrastText(hexBg) {
    if (!hexBg || hexBg.length < 7) return '#ffffff';
    const r = parseInt(hexBg.slice(1, 3), 16);
    const g = parseInt(hexBg.slice(3, 5), 16);
    const b = parseInt(hexBg.slice(5, 7), 16);
    const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
    return luminance > 0.55 ? '#1f2937' : '#ffffff';
}

const headerTextColor = computed(() => contrastText(colors.value.primary));

const launcherStyle = computed(() => ({
    backgroundColor: colors.value.launcher,
    color: contrastText(colors.value.launcher),
    boxShadow: '0 4px 14px rgba(0, 0, 0, 0.2)',
}));

const headerStyle = computed(() => ({
    backgroundColor: colors.value.primary,
    color: headerTextColor.value,
}));

const bodyStyle = computed(() => ({
    backgroundColor: colors.value.background,
    color: colors.value.text,
}));

const labelStyle = computed(() => ({
    color: colors.value.text,
}));

const inputBorderColor = computed(() => {
    // Create a lighter version of text color for borders
    const hex = colors.value.text;
    if (!hex || hex.length < 7) return '#d1d5db';
    const r = parseInt(hex.slice(1, 3), 16);
    const g = parseInt(hex.slice(3, 5), 16);
    const b = parseInt(hex.slice(5, 7), 16);
    return `rgba(${r}, ${g}, ${b}, 0.2)`;
});

const inputStyle = computed(() => ({
    backgroundColor: 'transparent',
    color: colors.value.text,
    borderColor: inputBorderColor.value,
}));

const submitStyle = computed(() => ({
    backgroundColor: colors.value.primary,
    color: contrastText(colors.value.primary),
}));

const activeTabStyle = computed(() => ({
    color: colors.value.primary,
    borderBottomColor: colors.value.primary,
}));

const footerStyle = computed(() => {
    // Slightly dimmed version of bg
    const bg = colors.value.background;
    return {
        backgroundColor: bg,
        borderTopColor: inputBorderColor.value,
    };
});

const panelStyle = computed(() => ({
    boxShadow: '0 8px 30px rgba(0, 0, 0, 0.15)',
}));

const visibleFields = computed(() => {
    return (props.config.form_fields || []).slice(0, 8);
});
</script>

<style scoped>
/* ===================================================================
   Container
   =================================================================== */
.wp-container {
    position: relative;
    border-radius: 10px;
    overflow: hidden;
    min-height: 520px;
    border: 1px solid #e5e7eb;
    transition: background-color 0.3s;
}

.wp-theme-dark {
    border-color: #2d2d4a;
}

/* ===================================================================
   Website mockup
   =================================================================== */
.wp-mock-site {
    padding: 16px;
    min-height: 520px;
}

.wp-mock-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
    background: rgba(0, 0, 0, 0.05);
    border-radius: 6px;
    margin-bottom: 20px;
}

.wp-theme-dark .wp-mock-nav {
    background: rgba(255, 255, 255, 0.05);
}

.wp-mock-logo {
    width: 80px;
    height: 12px;
    background: rgba(0, 0, 0, 0.12);
    border-radius: 3px;
}

.wp-theme-dark .wp-mock-logo {
    background: rgba(255, 255, 255, 0.12);
}

.wp-mock-nav-items {
    display: flex;
    gap: 12px;
}

.wp-mock-nav-item {
    width: 40px;
    height: 8px;
    background: rgba(0, 0, 0, 0.08);
    border-radius: 3px;
}

.wp-theme-dark .wp-mock-nav-item {
    background: rgba(255, 255, 255, 0.08);
}

.wp-mock-content {
    padding: 0 16px;
}

.wp-mock-hero {
    width: 100%;
    height: 60px;
    background: rgba(0, 0, 0, 0.04);
    border-radius: 8px;
    margin-bottom: 16px;
}

.wp-theme-dark .wp-mock-hero {
    background: rgba(255, 255, 255, 0.04);
}

.wp-mock-line {
    height: 8px;
    background: rgba(0, 0, 0, 0.06);
    border-radius: 4px;
    margin-bottom: 8px;
}

.wp-theme-dark .wp-mock-line {
    background: rgba(255, 255, 255, 0.06);
}

.wp-mock-line-lg { width: 90%; }
.wp-mock-line-md { width: 70%; }
.wp-mock-line-sm { width: 50%; }

.wp-mock-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-top: 16px;
}

.wp-mock-card {
    height: 50px;
    background: rgba(0, 0, 0, 0.04);
    border-radius: 6px;
}

.wp-theme-dark .wp-mock-card {
    background: rgba(255, 255, 255, 0.04);
}

/* ===================================================================
   Launcher button
   =================================================================== */
.wp-launcher {
    position: absolute;
    bottom: 20px;
    width: 52px;
    height: 52px;
    border-radius: 50%;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s, box-shadow 0.2s;
    z-index: 10;
}

.wp-launcher:hover {
    transform: scale(1.08);
}

.wp-pos-right {
    right: 20px;
}

.wp-pos-left {
    left: 20px;
}

/* ===================================================================
   Panel
   =================================================================== */
.wp-panel {
    position: absolute;
    bottom: 20px;
    width: 320px;
    max-height: 480px;
    border-radius: 12px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    z-index: 10;
}

.wp-panel.wp-pos-right {
    right: 20px;
}

.wp-panel.wp-pos-left {
    left: 20px;
}

/* Header */
.wp-panel-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 16px;
    gap: 8px;
}

.wp-panel-header-text {
    flex: 1;
    min-width: 0;
}

.wp-panel-greeting {
    font-size: 14px;
    font-weight: 600;
    line-height: 1.4;
    display: block;
}

.wp-panel-close {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border: none;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 6px;
    cursor: pointer;
    flex-shrink: 0;
    transition: background-color 0.15s;
}

.wp-panel-close:hover {
    background: rgba(255, 255, 255, 0.25);
}

/* Body */
.wp-panel-body {
    flex: 1;
    overflow-y: auto;
    min-height: 0;
}

/* Tabs */
.wp-tabs {
    display: flex;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
    padding: 0 16px;
}

.wp-tab {
    flex: 1;
    padding: 10px 4px;
    background: none;
    border: none;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    color: inherit;
    opacity: 0.5;
    transition: opacity 0.15s, border-color 0.15s;
    font-family: inherit;
    text-align: center;
}

.wp-tab:hover {
    opacity: 0.8;
}

.wp-tab-active {
    opacity: 1;
}

/* KB tab */
.wp-kb-tab {
    padding: 12px 16px;
}

.wp-kb-search-wrap {
    position: relative;
    margin-bottom: 12px;
}

.wp-kb-search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    opacity: 0.4;
}

.wp-kb-search-input {
    width: 100%;
    padding: 8px 10px 8px 32px;
    border: 1px solid;
    border-radius: 6px;
    font-size: 12px;
    font-family: inherit;
    outline: none;
    box-sizing: border-box;
}

.wp-kb-results {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.wp-kb-article {
    padding: 10px 12px;
    border-radius: 6px;
    background: rgba(0, 0, 0, 0.03);
    cursor: pointer;
    transition: background-color 0.1s;
}

.wp-kb-article:hover {
    background: rgba(0, 0, 0, 0.06);
}

.wp-kb-article-title {
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 2px;
    line-height: 1.3;
}

.wp-kb-article-excerpt {
    font-size: 11px;
    opacity: 0.6;
    line-height: 1.4;
}

/* Form tab */
.wp-form-tab {
    padding: 12px 16px;
}

.wp-form-field {
    margin-bottom: 10px;
}

.wp-form-label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    margin-bottom: 4px;
}

.wp-form-required {
    color: #ef4444;
    margin-left: 1px;
}

.wp-form-input {
    display: block;
    width: 100%;
    padding: 7px 10px;
    border: 1px solid;
    border-radius: 6px;
    font-size: 12px;
    font-family: inherit;
    outline: none;
    box-sizing: border-box;
}

.wp-form-textarea {
    resize: none;
    min-height: 48px;
}

.wp-form-submit {
    display: block;
    width: 100%;
    padding: 10px;
    border: none;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 12px;
    transition: opacity 0.15s;
    font-family: inherit;
}

.wp-form-submit:hover {
    opacity: 0.9;
}

/* Footer */
.wp-panel-footer {
    padding: 8px 16px;
    text-align: center;
    border-top: 1px solid;
}

.wp-powered-by {
    font-size: 10px;
    opacity: 0.45;
}

.wp-powered-by strong {
    font-weight: 700;
}
</style>
