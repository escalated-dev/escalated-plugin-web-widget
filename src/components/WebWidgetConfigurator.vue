<template>
    <div class="widget-config">
        <div class="widget-config-header">
            <h3>Web Widget</h3>
        </div>
        <div class="widget-config-body">
            <div class="widget-section">
                <h4>Embed Code</h4>
                <p class="widget-placeholder">
                    Generate an embeddable JavaScript snippet to add a support widget to your website. Includes contact
                    form submission and knowledge base article search.
                </p>
                <div class="widget-code-preview">
                    <code>&lt;script src="https://your-domain.com/widget.js"&gt;&lt;/script&gt;</code>
                </div>
            </div>
            <div class="widget-section">
                <h4>Customization</h4>
                <p class="widget-placeholder">Widget appearance and behavior customization options coming soon.</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const widgetColor = ref('#6366f1');
const widgetPosition = ref('bottom-right');
</script>

<style scoped>
.widget-config {
    background: var(--escalated-bg-secondary, #1e1e2e);
    border: 1px solid var(--escalated-border, #2e2e3e);
    border-radius: 8px;
    padding: 16px;
    color: var(--escalated-text, #e0e0e0);
}

.widget-config-header {
    margin-bottom: 12px;
}

.widget-config-header h3 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}

.widget-section {
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--escalated-border, #2e2e3e);
}

.widget-section:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.widget-section h4 {
    margin: 0 0 6px 0;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.widget-placeholder {
    font-size: 12px;
    color: var(--escalated-text-muted, #666);
    line-height: 1.5;
    margin: 0 0 8px 0;
}

.widget-code-preview {
    background: var(--escalated-bg-tertiary, #0d0d1a);
    border-radius: 4px;
    padding: 10px 12px;
    overflow-x: auto;
}

.widget-code-preview code {
    font-size: 12px;
    color: #a5b4fc;
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
}
</style>
