<template>
    <div :class="['ww-config', { 'ww-dark': isDark }]">
        <!-- Header -->
        <div class="ww-config-header">
            <div>
                <h2 class="ww-config-title">Web Widget Configuration</h2>
                <p class="ww-config-subtitle">
                    Configure and embed a support widget on your website for contact form submissions and knowledge base search.
                </p>
            </div>
            <button
                class="ww-btn ww-btn-primary"
                :disabled="saving"
                @click="handleSave"
            >
                <svg v-if="saving" class="ww-spin ww-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2v4m0 12v4m-7.07-3.93l2.83-2.83m8.48-8.48l2.83-2.83M2 12h4m12 0h4M4.93 4.93l2.83 2.83m8.48 8.48l2.83 2.83" />
                </svg>
                <span>{{ saving ? 'Saving...' : 'Save Settings' }}</span>
            </button>
        </div>

        <!-- Success banner -->
        <div v-if="saveSuccess" class="ww-banner ww-banner-success">
            <svg class="ww-icon" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
            </svg>
            <span>Settings saved successfully.</span>
        </div>

        <div class="ww-config-layout">
            <!-- Left column: settings -->
            <div class="ww-config-panels">

                <!-- ============================================================ -->
                <!-- Appearance Section -->
                <!-- ============================================================ -->
                <section class="ww-section">
                    <button class="ww-section-toggle" @click="toggleSection('appearance')">
                        <svg class="ww-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M3.5 2A1.5 1.5 0 002 3.5V5c0 .276.224.5.5.5h15a.5.5 0 00.5-.5V3.5A1.5 1.5 0 0016.5 2h-13zM2 7.5v9A1.5 1.5 0 003.5 18h13a1.5 1.5 0 001.5-1.5v-9H2z" clip-rule="evenodd" />
                        </svg>
                        <span class="ww-section-title">Appearance</span>
                        <svg :class="['ww-chevron', { 'ww-chevron-open': openSections.appearance }]" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 011.06 0L10 11.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 9.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div v-show="openSections.appearance" class="ww-section-body">
                        <!-- Color pickers -->
                        <div class="ww-color-grid">
                            <div v-for="colorKey in colorKeys" :key="colorKey.key" class="ww-color-field">
                                <label class="ww-label">{{ colorKey.label }}</label>
                                <div class="ww-color-input-wrap">
                                    <input
                                        type="color"
                                        :value="config.colors[colorKey.key]"
                                        @input="config.colors[colorKey.key] = $event.target.value"
                                        class="ww-color-picker"
                                    />
                                    <input
                                        type="text"
                                        :value="config.colors[colorKey.key]"
                                        @input="config.colors[colorKey.key] = $event.target.value"
                                        class="ww-input ww-color-text"
                                        maxlength="7"
                                        placeholder="#000000"
                                    />
                                    <div
                                        class="ww-color-preview"
                                        :style="{ backgroundColor: config.colors[colorKey.key] }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Position selector -->
                        <div class="ww-field">
                            <label class="ww-label">Widget Position</label>
                            <div class="ww-radio-group">
                                <label class="ww-radio-option" :class="{ 'ww-radio-selected': config.position === 'bottom-right' }">
                                    <input
                                        type="radio"
                                        value="bottom-right"
                                        v-model="config.position"
                                        class="ww-radio-input"
                                    />
                                    <svg class="ww-icon" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 5a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 9.75zm7 5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Bottom Right</span>
                                </label>
                                <label class="ww-radio-option" :class="{ 'ww-radio-selected': config.position === 'bottom-left' }">
                                    <input
                                        type="radio"
                                        value="bottom-left"
                                        v-model="config.position"
                                        class="ww-radio-input"
                                    />
                                    <svg class="ww-icon" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zm0 5a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 9.75zm0 5a.75.75 0 01.75-.75h7.5a.75.75 0 010 1.5h-7.5A.75.75 0 012 14.75z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Bottom Left</span>
                                </label>
                            </div>
                        </div>

                        <!-- Greeting message -->
                        <div class="ww-field">
                            <label class="ww-label">Greeting Message</label>
                            <textarea
                                v-model="config.greeting"
                                class="ww-input ww-textarea"
                                rows="2"
                                placeholder="Hi there! How can we help you?"
                            ></textarea>
                        </div>
                    </div>
                </section>

                <!-- ============================================================ -->
                <!-- Form Fields Section -->
                <!-- ============================================================ -->
                <section class="ww-section">
                    <button class="ww-section-toggle" @click="toggleSection('fields')">
                        <svg class="ww-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M5.433 13.917l1.262-3.155A4 4 0 017.58 9.42l6.92-6.918a2.121 2.121 0 013 3l-6.92 6.918c-.383.383-.84.685-1.343.886l-3.154 1.262a.5.5 0 01-.65-.65z" />
                            <path d="M3.5 5.75c0-.69.56-1.25 1.25-1.25H10A.75.75 0 0010 3H4.75A2.75 2.75 0 002 5.75v9.5A2.75 2.75 0 004.75 18h9.5A2.75 2.75 0 0017 15.25V10a.75.75 0 00-1.5 0v5.25c0 .69-.56 1.25-1.25 1.25h-9.5c-.69 0-1.25-.56-1.25-1.25v-9.5z" />
                        </svg>
                        <span class="ww-section-title">Form Fields</span>
                        <svg :class="['ww-chevron', { 'ww-chevron-open': openSections.fields }]" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 011.06 0L10 11.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 9.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div v-show="openSections.fields" class="ww-section-body">
                        <p class="ww-hint">Define the fields displayed in the widget contact form. Drag to reorder.</p>

                        <div class="ww-field-list">
                            <div
                                v-for="(field, index) in config.form_fields"
                                :key="field.name + '-' + index"
                                class="ww-field-item"
                                draggable="true"
                                @dragstart="onDragStart(index, $event)"
                                @dragover.prevent="onDragOver(index, $event)"
                                @drop="onDrop(index)"
                                @dragend="dragIndex = null"
                                :class="{ 'ww-field-dragging': dragIndex === index, 'ww-field-dragover': dragOverIndex === index && dragIndex !== index }"
                            >
                                <div class="ww-field-drag-handle" title="Drag to reorder">
                                    <svg class="ww-icon" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M7 2a2 2 0 10 4 0 2 2 0 000-4zm6 0a2 2 0 100 4 2 2 0 000-4zM7 8a2 2 0 100 4 2 2 0 000-4zm6 0a2 2 0 100 4 2 2 0 000-4zM7 14a2 2 0 100 4 2 2 0 000-4zm6 0a2 2 0 100 4 2 2 0 000-4z" />
                                    </svg>
                                </div>

                                <div class="ww-field-item-body">
                                    <div class="ww-field-item-row">
                                        <input
                                            v-model="field.label"
                                            class="ww-input ww-input-sm"
                                            placeholder="Label"
                                        />
                                        <input
                                            v-model="field.name"
                                            class="ww-input ww-input-sm ww-input-mono"
                                            placeholder="field_name"
                                            :disabled="isDefaultField(field.name)"
                                        />
                                        <select v-model="field.type" class="ww-input ww-input-sm ww-select">
                                            <option value="text">Text</option>
                                            <option value="email">Email</option>
                                            <option value="textarea">Textarea</option>
                                            <option value="select">Select</option>
                                        </select>
                                    </div>
                                    <div class="ww-field-item-meta">
                                        <label class="ww-checkbox-label">
                                            <input type="checkbox" v-model="field.required" class="ww-checkbox" />
                                            <span>Required</span>
                                        </label>
                                        <button
                                            v-if="!isDefaultField(field.name)"
                                            class="ww-btn-icon ww-btn-danger-icon"
                                            @click="removeField(index)"
                                            title="Remove field"
                                        >
                                            <svg class="ww-icon" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 01.7.8l-.29 5.7a.75.75 0 01-1.497-.076l.29-5.7a.75.75 0 01.797-.724zm3.64.8a.75.75 0 10-1.497-.076l-.29 5.7a.75.75 0 001.497.076l.29-5.7z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button class="ww-btn ww-btn-secondary ww-btn-sm" @click="addField">
                            <svg class="ww-icon" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                            </svg>
                            <span>Add Custom Field</span>
                        </button>
                    </div>
                </section>

                <!-- ============================================================ -->
                <!-- Security Section -->
                <!-- ============================================================ -->
                <section class="ww-section">
                    <button class="ww-section-toggle" @click="toggleSection('security')">
                        <svg class="ww-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 1a4.5 4.5 0 00-4.5 4.5V9H5a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2h-.5V5.5A4.5 4.5 0 0010 1zm3 8V5.5a3 3 0 10-6 0V9h6z" clip-rule="evenodd" />
                        </svg>
                        <span class="ww-section-title">Security</span>
                        <svg :class="['ww-chevron', { 'ww-chevron-open': openSections.security }]" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 011.06 0L10 11.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 9.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div v-show="openSections.security" class="ww-section-body">
                        <!-- API Key -->
                        <div class="ww-field">
                            <label class="ww-label">API Key</label>
                            <div class="ww-api-key-row">
                                <input
                                    type="text"
                                    :value="config.api_key"
                                    readonly
                                    class="ww-input ww-input-mono ww-input-readonly"
                                    @focus="$event.target.select()"
                                />
                                <button class="ww-btn ww-btn-secondary ww-btn-sm" @click="copyApiKey" title="Copy API key">
                                    <svg class="ww-icon" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M7 3.5A1.5 1.5 0 018.5 2h3.879a1.5 1.5 0 011.06.44l3.122 3.12A1.5 1.5 0 0117 6.622V12.5a1.5 1.5 0 01-1.5 1.5h-1v-3.379a3 3 0 00-.879-2.121L10.5 5.379A3 3 0 008.379 4.5H7v-1z" />
                                        <path d="M4.5 6A1.5 1.5 0 003 7.5v9A1.5 1.5 0 004.5 18h7a1.5 1.5 0 001.5-1.5v-5.879a1.5 1.5 0 00-.44-1.06L9.44 6.439A1.5 1.5 0 008.378 6H4.5z" />
                                    </svg>
                                    <span>{{ apiKeyCopied ? 'Copied!' : 'Copy' }}</span>
                                </button>
                                <button class="ww-btn ww-btn-warning ww-btn-sm" @click="handleRegenerateApiKey" title="Regenerate API key">
                                    <svg class="ww-icon" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M15.312 11.424a5.5 5.5 0 01-9.201 2.466l-.312-.311h2.433a.75.75 0 000-1.5H4.598a.75.75 0 00-.75.75v3.634a.75.75 0 001.5 0v-2.033l.312.311a7 7 0 0011.712-3.138.75.75 0 00-1.06-.179zm-1.624-8.848a7 7 0 00-11.712 3.138.75.75 0 001.06.179 5.5 5.5 0 019.2-2.466l.312.311H10.114a.75.75 0 000 1.5h3.634a.75.75 0 00.75-.75V.854a.75.75 0 00-1.5 0v2.033l-.31-.311z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Regenerate</span>
                                </button>
                            </div>
                            <p class="ww-hint">This key authenticates widget requests. Regenerating will invalidate the current embed code.</p>
                        </div>

                        <!-- Allowed domains -->
                        <div class="ww-field">
                            <label class="ww-label">Allowed Domains</label>
                            <p class="ww-hint">Restrict which domains can embed the widget. Leave empty to allow all domains.</p>
                            <div class="ww-domain-list">
                                <div v-for="(domain, index) in config.allowed_domains" :key="index" class="ww-domain-item">
                                    <input
                                        :value="domain"
                                        @input="config.allowed_domains[index] = $event.target.value"
                                        class="ww-input ww-input-sm"
                                        placeholder="example.com"
                                    />
                                    <button class="ww-btn-icon ww-btn-danger-icon" @click="removeDomain(index)" title="Remove domain">
                                        <svg class="ww-icon" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <button class="ww-btn ww-btn-secondary ww-btn-sm" @click="addDomain">
                                <svg class="ww-icon" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                                </svg>
                                <span>Add Domain</span>
                            </button>
                        </div>

                        <!-- Rate limit -->
                        <div class="ww-field">
                            <label class="ww-label">
                                Rate Limit
                                <span class="ww-label-badge">{{ config.rate_limit_per_hour }} / hour</span>
                            </label>
                            <input
                                type="range"
                                v-model.number="config.rate_limit_per_hour"
                                min="10"
                                max="200"
                                step="10"
                                class="ww-range"
                            />
                            <div class="ww-range-labels">
                                <span>10</span>
                                <span>200</span>
                            </div>
                            <p class="ww-hint">Maximum form submissions per IP address per hour.</p>
                        </div>
                    </div>
                </section>

                <!-- ============================================================ -->
                <!-- KB Integration Section -->
                <!-- ============================================================ -->
                <section class="ww-section">
                    <button class="ww-section-toggle" @click="toggleSection('kb')">
                        <svg class="ww-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M10.75 16.82A7.462 7.462 0 0115 15.5c.71 0 1.396.098 2.046.282A.75.75 0 0018 15.06V4.94a.75.75 0 00-.546-.721A9.006 9.006 0 0015 3.75a8.963 8.963 0 00-4.25 1.065V16.82zM9.25 4.815A8.963 8.963 0 005 3.75c-.85 0-1.673.118-2.454.34A.75.75 0 002 4.838v10.223a.75.75 0 00.954.721A7.506 7.506 0 015 15.5a7.462 7.462 0 014.25 1.32V4.815z" />
                        </svg>
                        <span class="ww-section-title">Knowledge Base Integration</span>
                        <svg :class="['ww-chevron', { 'ww-chevron-open': openSections.kb }]" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 011.06 0L10 11.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 9.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div v-show="openSections.kb" class="ww-section-body">
                        <!-- KB toggle -->
                        <div class="ww-field">
                            <label class="ww-toggle-label">
                                <button
                                    :class="['ww-toggle', { 'ww-toggle-on': config.kb_enabled }]"
                                    @click="config.kb_enabled = !config.kb_enabled"
                                    role="switch"
                                    :aria-checked="config.kb_enabled"
                                >
                                    <span class="ww-toggle-knob"></span>
                                </button>
                                <span>Enable KB search in widget</span>
                            </label>
                            <p class="ww-hint">Allow visitors to search knowledge base articles directly from the widget.</p>
                        </div>

                        <!-- Category filter -->
                        <div v-if="config.kb_enabled" class="ww-field">
                            <label class="ww-label">Searchable Categories</label>
                            <p class="ww-hint">Select which KB categories are searchable from the widget. Leave all unchecked to include all categories.</p>
                            <div class="ww-kb-categories">
                                <label
                                    v-for="cat in availableKbCategories"
                                    :key="cat.id"
                                    class="ww-checkbox-label ww-kb-cat-item"
                                >
                                    <input
                                        type="checkbox"
                                        :value="cat.id"
                                        :checked="config.kb_categories.includes(cat.id)"
                                        @change="toggleKbCategory(cat.id)"
                                        class="ww-checkbox"
                                    />
                                    <span>{{ cat.name }}</span>
                                    <span v-if="cat.article_count" class="ww-badge">{{ cat.article_count }}</span>
                                </label>
                                <p v-if="availableKbCategories.length === 0" class="ww-empty-text">
                                    No KB categories available. Categories will appear here once created.
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ============================================================ -->
                <!-- Embed Code Section -->
                <!-- ============================================================ -->
                <section class="ww-section">
                    <button class="ww-section-toggle" @click="toggleSection('embed')">
                        <svg class="ww-icon" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M6.28 5.22a.75.75 0 010 1.06L2.56 10l3.72 3.72a.75.75 0 01-1.06 1.06L.97 10.53a.75.75 0 010-1.06l4.25-4.25a.75.75 0 011.06 0zm7.44 0a.75.75 0 011.06 0l4.25 4.25a.75.75 0 010 1.06l-4.25 4.25a.75.75 0 01-1.06-1.06L17.44 10l-3.72-3.72a.75.75 0 010-1.06zM11.377 2.011a.75.75 0 01.612.867l-2.5 14.5a.75.75 0 01-1.478-.255l2.5-14.5a.75.75 0 01.866-.612z" clip-rule="evenodd" />
                        </svg>
                        <span class="ww-section-title">Embed Code</span>
                        <svg :class="['ww-chevron', { 'ww-chevron-open': openSections.embed }]" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 011.06 0L10 11.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 9.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                        </svg>
                    </button>

                    <div v-show="openSections.embed" class="ww-section-body">
                        <p class="ww-hint">
                            Copy the snippet below and paste it just before the closing <code>&lt;/body&gt;</code> tag
                            on every page where you want the widget to appear.
                        </p>
                        <div class="ww-code-block">
                            <div class="ww-code-header">
                                <span class="ww-code-lang">HTML</span>
                                <button class="ww-btn ww-btn-secondary ww-btn-xs" @click="copyEmbedCode">
                                    <svg class="ww-icon" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M7 3.5A1.5 1.5 0 018.5 2h3.879a1.5 1.5 0 011.06.44l3.122 3.12A1.5 1.5 0 0117 6.622V12.5a1.5 1.5 0 01-1.5 1.5h-1v-3.379a3 3 0 00-.879-2.121L10.5 5.379A3 3 0 008.379 4.5H7v-1z" />
                                        <path d="M4.5 6A1.5 1.5 0 003 7.5v9A1.5 1.5 0 004.5 18h7a1.5 1.5 0 001.5-1.5v-5.879a1.5 1.5 0 00-.44-1.06L9.44 6.439A1.5 1.5 0 008.378 6H4.5z" />
                                    </svg>
                                    <span>{{ embedCopied ? 'Copied!' : 'Copy' }}</span>
                                </button>
                            </div>
                            <pre class="ww-code-body"><code>{{ embedCode }}</code></pre>
                        </div>
                        <p class="ww-hint" style="margin-top: 8px;">
                            The widget will automatically load and display on your website. Ensure the API key above is valid
                            and that your domain is in the allowed list (or the list is empty to allow all).
                        </p>
                    </div>
                </section>
            </div>

            <!-- Right column: live preview -->
            <div class="ww-config-preview">
                <div class="ww-preview-header">
                    <h3 class="ww-preview-title">Live Preview</h3>
                    <div class="ww-preview-controls">
                        <button
                            :class="['ww-btn-icon', { 'ww-btn-icon-active': previewTheme === 'light' }]"
                            @click="previewTheme = 'light'"
                            title="Light background"
                        >
                            <svg class="ww-icon" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M10 2a.75.75 0 01.75.75v1.5a.75.75 0 01-1.5 0v-1.5A.75.75 0 0110 2zm0 13a.75.75 0 01.75.75v1.5a.75.75 0 01-1.5 0v-1.5A.75.75 0 0110 15zm-8-5a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5A.75.75 0 012 10zm13 0a.75.75 0 01.75-.75h1.5a.75.75 0 010 1.5h-1.5A.75.75 0 0115 10zM4.343 4.343a.75.75 0 011.06 0l1.061 1.06a.75.75 0 01-1.06 1.061l-1.061-1.06a.75.75 0 010-1.061zm9.193 9.193a.75.75 0 011.06 0l1.061 1.061a.75.75 0 01-1.06 1.06l-1.061-1.06a.75.75 0 010-1.06zM4.343 15.657a.75.75 0 010-1.06l1.06-1.061a.75.75 0 011.061 1.06l-1.06 1.061a.75.75 0 01-1.061 0zm9.193-9.193a.75.75 0 010-1.06l1.061-1.061a.75.75 0 111.06 1.06l-1.06 1.061a.75.75 0 01-1.061 0zM7 10a3 3 0 116 0 3 3 0 01-6 0z" />
                            </svg>
                        </button>
                        <button
                            :class="['ww-btn-icon', { 'ww-btn-icon-active': previewTheme === 'dark' }]"
                            @click="previewTheme = 'dark'"
                            title="Dark background"
                        >
                            <svg class="ww-icon" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M7.455 2.004a.75.75 0 01.26.77 7 7 0 009.958 7.967.75.75 0 011.067.853A8.5 8.5 0 1110.239 1.49a.75.75 0 01-.784.514z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
                <WidgetPreview
                    :config="config"
                    :theme="previewTheme"
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, computed, inject, onMounted, watch } from 'vue';
import WidgetPreview from './WidgetPreview.vue';

const isDark = inject('esc-dark', false);
const webWidget = inject('webWidget', null);

// ---------------------------------------------------------------------------
// State
// ---------------------------------------------------------------------------

const saving = ref(false);
const saveSuccess = ref(false);
const apiKeyCopied = ref(false);
const embedCopied = ref(false);
const previewTheme = ref('light');

const dragIndex = ref(null);
const dragOverIndex = ref(null);

const openSections = reactive({
    appearance: true,
    fields: true,
    security: true,
    kb: false,
    embed: true,
});

const config = reactive({
    api_key: '',
    colors: {
        primary: '#6366f1',
        background: '#ffffff',
        text: '#1f2937',
        launcher: '#6366f1',
    },
    position: 'bottom-right',
    greeting: 'Hi there! How can we help you?',
    form_fields: [
        { name: 'name', label: 'Name', type: 'text', required: true },
        { name: 'email', label: 'Email', type: 'email', required: true },
        { name: 'subject', label: 'Subject', type: 'text', required: false },
        { name: 'message', label: 'Message', type: 'textarea', required: true },
    ],
    allowed_domains: [],
    kb_enabled: true,
    kb_categories: [],
    rate_limit_per_hour: 60,
});

const availableKbCategories = ref([
    { id: 'cat_general', name: 'General', article_count: 12 },
    { id: 'cat_billing', name: 'Billing & Payments', article_count: 8 },
    { id: 'cat_technical', name: 'Technical Support', article_count: 15 },
    { id: 'cat_account', name: 'Account Management', article_count: 6 },
    { id: 'cat_getting_started', name: 'Getting Started', article_count: 10 },
]);

const colorKeys = [
    { key: 'primary', label: 'Primary Color' },
    { key: 'background', label: 'Background Color' },
    { key: 'text', label: 'Text Color' },
    { key: 'launcher', label: 'Launcher Button Color' },
];

const DEFAULT_FIELDS = ['name', 'email', 'subject', 'message'];

// ---------------------------------------------------------------------------
// Computed
// ---------------------------------------------------------------------------

const embedCode = computed(() => {
    const apiKey = config.api_key || 'YOUR_API_KEY';
    const lines = [
        '<script>',
        '  (function(w,d,s,o){',
        '    var f=d.getElementsByTagName(s)[0],j=d.createElement(s);',
        '    j.async=true;j.src=o.url;j.dataset.apiKey=o.apiKey;',
        '    w.EscalatedWidgetConfig=o;f.parentNode.insertBefore(j,f);',
        '  })(window,document,"script",{',
        '    url: "https://your-domain.com/plugins/web-widget/widget.js",',
        `    apiKey: "${apiKey}"`,
        '  });',
        '<\/script>',
    ];
    return lines.join('\n');
});

// ---------------------------------------------------------------------------
// Section toggle
// ---------------------------------------------------------------------------

function toggleSection(key) {
    openSections[key] = !openSections[key];
}

// ---------------------------------------------------------------------------
// Field management
// ---------------------------------------------------------------------------

function isDefaultField(name) {
    return DEFAULT_FIELDS.includes(name);
}

function addField() {
    const timestamp = Date.now();
    config.form_fields.push({
        name: `custom_${timestamp}`,
        label: 'New Field',
        type: 'text',
        required: false,
    });
}

function removeField(index) {
    config.form_fields.splice(index, 1);
}

// ---------------------------------------------------------------------------
// Drag and drop reorder
// ---------------------------------------------------------------------------

function onDragStart(index, event) {
    dragIndex.value = index;
    event.dataTransfer.effectAllowed = 'move';
    event.dataTransfer.setData('text/plain', String(index));
}

function onDragOver(index, event) {
    dragOverIndex.value = index;
    event.dataTransfer.dropEffect = 'move';
}

function onDrop(toIndex) {
    const fromIndex = dragIndex.value;
    if (fromIndex === null || fromIndex === toIndex) {
        dragIndex.value = null;
        dragOverIndex.value = null;
        return;
    }

    const fields = [...config.form_fields];
    const [moved] = fields.splice(fromIndex, 1);
    fields.splice(toIndex, 0, moved);
    config.form_fields = fields;

    dragIndex.value = null;
    dragOverIndex.value = null;
}

// ---------------------------------------------------------------------------
// Domain management
// ---------------------------------------------------------------------------

function addDomain() {
    config.allowed_domains.push('');
}

function removeDomain(index) {
    config.allowed_domains.splice(index, 1);
}

// ---------------------------------------------------------------------------
// KB category toggle
// ---------------------------------------------------------------------------

function toggleKbCategory(catId) {
    const idx = config.kb_categories.indexOf(catId);
    if (idx >= 0) {
        config.kb_categories.splice(idx, 1);
    } else {
        config.kb_categories.push(catId);
    }
}

// ---------------------------------------------------------------------------
// API key management
// ---------------------------------------------------------------------------

async function copyApiKey() {
    try {
        await navigator.clipboard.writeText(config.api_key);
        apiKeyCopied.value = true;
        setTimeout(() => { apiKeyCopied.value = false; }, 2000);
    } catch {
        // Fallback
        selectAndCopy(config.api_key);
    }
}

async function handleRegenerateApiKey() {
    if (!confirm('Regenerating the API key will invalidate the current embed code on all websites. Continue?')) {
        return;
    }

    try {
        // Generate locally for now (in production this calls the backend)
        const chars = 'abcdef0123456789';
        let key = 'esc_wk_';
        for (let i = 0; i < 32; i++) {
            key += chars[Math.floor(Math.random() * chars.length)];
        }
        config.api_key = key;
    } catch (err) {
        console.error('[web-widget] Failed to regenerate API key:', err);
    }
}

// ---------------------------------------------------------------------------
// Embed code copy
// ---------------------------------------------------------------------------

async function copyEmbedCode() {
    try {
        await navigator.clipboard.writeText(embedCode.value);
        embedCopied.value = true;
        setTimeout(() => { embedCopied.value = false; }, 2000);
    } catch {
        selectAndCopy(embedCode.value);
    }
}

function selectAndCopy(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    try { document.execCommand('copy'); } catch {}
    document.body.removeChild(textarea);
}

// ---------------------------------------------------------------------------
// Save
// ---------------------------------------------------------------------------

async function handleSave() {
    saving.value = true;
    saveSuccess.value = false;

    try {
        if (webWidget) {
            await webWidget.saveSettings({ ...config });
        } else {
            throw new Error('Web Widget service not available.');
        }

        saveSuccess.value = true;
        setTimeout(() => { saveSuccess.value = false; }, 3000);
    } catch (err) {
        console.error('[web-widget] Failed to save settings:', err);
    } finally {
        saving.value = false;
    }
}

// ---------------------------------------------------------------------------
// Lifecycle
// ---------------------------------------------------------------------------

onMounted(async () => {
    if (webWidget) {
        await webWidget.fetchSettings();
        const s = webWidget.state.settings;
        Object.assign(config, s);
        if (s.colors) Object.assign(config.colors, s.colors);
    }

    if (!config.api_key && webWidget) {
        const key = await webWidget.regenerateApiKey();
        if (key) config.api_key = key;
    }
});
</script>

<style scoped>
/* ===================================================================
   Base / Theme Variables
   =================================================================== */
.ww-config {
    --ww-bg: #ffffff;
    --ww-bg-secondary: #f9fafb;
    --ww-bg-tertiary: #f3f4f6;
    --ww-border: #e5e7eb;
    --ww-text: #111827;
    --ww-text-secondary: #6b7280;
    --ww-text-muted: #9ca3af;
    --ww-primary: #6366f1;
    --ww-primary-hover: #4f46e5;
    --ww-primary-light: #eef2ff;
    --ww-danger: #ef4444;
    --ww-danger-hover: #dc2626;
    --ww-warning: #f59e0b;
    --ww-success: #22c55e;
    --ww-radius: 8px;
    --ww-radius-sm: 6px;

    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: var(--ww-text);
    font-size: 14px;
    line-height: 1.5;
}

.ww-dark {
    --ww-bg: #1a1a2e;
    --ww-bg-secondary: #16162a;
    --ww-bg-tertiary: #0f0f1e;
    --ww-border: #2d2d4a;
    --ww-text: #e2e8f0;
    --ww-text-secondary: #94a3b8;
    --ww-text-muted: #64748b;
    --ww-primary: #818cf8;
    --ww-primary-hover: #6366f1;
    --ww-primary-light: #1e1b4b;
    --ww-danger: #f87171;
    --ww-danger-hover: #ef4444;
}

/* ===================================================================
   Layout
   =================================================================== */
.ww-config-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 24px;
    gap: 16px;
    flex-wrap: wrap;
}

.ww-config-title {
    font-size: 20px;
    font-weight: 700;
    margin: 0 0 4px 0;
    color: var(--ww-text);
}

.ww-config-subtitle {
    font-size: 13px;
    color: var(--ww-text-secondary);
    margin: 0;
}

.ww-config-layout {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 24px;
    align-items: start;
}

@media (max-width: 1100px) {
    .ww-config-layout {
        grid-template-columns: 1fr;
    }
}

.ww-config-panels {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

/* ===================================================================
   Banner
   =================================================================== */
.ww-banner {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border-radius: var(--ww-radius);
    font-size: 13px;
    margin-bottom: 16px;
}

.ww-banner-success {
    background: #f0fdf4;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.ww-dark .ww-banner-success {
    background: #052e16;
    color: #86efac;
    border-color: #14532d;
}

/* ===================================================================
   Section accordion
   =================================================================== */
.ww-section {
    background: var(--ww-bg);
    border: 1px solid var(--ww-border);
    border-radius: var(--ww-radius);
    overflow: hidden;
}

.ww-section-toggle {
    display: flex;
    align-items: center;
    gap: 10px;
    width: 100%;
    padding: 14px 16px;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--ww-text);
    font-size: 14px;
    font-weight: 600;
    text-align: left;
}

.ww-section-toggle:hover {
    background: var(--ww-bg-secondary);
}

.ww-section-title {
    flex: 1;
}

.ww-chevron {
    width: 16px;
    height: 16px;
    transition: transform 0.2s ease;
    color: var(--ww-text-muted);
}

.ww-chevron-open {
    transform: rotate(180deg);
}

.ww-section-body {
    padding: 0 16px 16px 16px;
}

/* ===================================================================
   Form elements
   =================================================================== */
.ww-field {
    margin-bottom: 16px;
}

.ww-field:last-child {
    margin-bottom: 0;
}

.ww-label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
    color: var(--ww-text);
    margin-bottom: 6px;
}

.ww-label-badge {
    font-size: 11px;
    font-weight: 500;
    background: var(--ww-primary-light);
    color: var(--ww-primary);
    padding: 2px 8px;
    border-radius: 10px;
}

.ww-hint {
    font-size: 12px;
    color: var(--ww-text-muted);
    margin: 4px 0 12px 0;
    line-height: 1.5;
}

.ww-hint code {
    background: var(--ww-bg-tertiary);
    padding: 1px 5px;
    border-radius: 3px;
    font-size: 11px;
    font-family: 'JetBrains Mono', 'Fira Code', ui-monospace, monospace;
}

.ww-input {
    display: block;
    width: 100%;
    padding: 8px 12px;
    background: var(--ww-bg-secondary);
    border: 1px solid var(--ww-border);
    border-radius: var(--ww-radius-sm);
    color: var(--ww-text);
    font-size: 13px;
    font-family: inherit;
    outline: none;
    transition: border-color 0.15s;
}

.ww-input:focus {
    border-color: var(--ww-primary);
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.15);
}

.ww-input-sm {
    padding: 6px 10px;
    font-size: 12px;
}

.ww-input-mono {
    font-family: 'JetBrains Mono', 'Fira Code', ui-monospace, monospace;
    font-size: 12px;
}

.ww-input-readonly {
    cursor: default;
    opacity: 0.85;
}

.ww-textarea {
    resize: vertical;
    min-height: 48px;
}

.ww-select {
    appearance: auto;
}

.ww-checkbox {
    accent-color: var(--ww-primary);
    width: 14px;
    height: 14px;
}

.ww-checkbox-label {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--ww-text);
    cursor: pointer;
}

/* ===================================================================
   Color inputs
   =================================================================== */
.ww-color-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 16px;
}

@media (max-width: 640px) {
    .ww-color-grid {
        grid-template-columns: 1fr;
    }
}

.ww-color-field {
    display: flex;
    flex-direction: column;
}

.ww-color-input-wrap {
    display: flex;
    align-items: center;
    gap: 8px;
}

.ww-color-picker {
    width: 36px;
    height: 36px;
    border: 1px solid var(--ww-border);
    border-radius: var(--ww-radius-sm);
    padding: 2px;
    cursor: pointer;
    background: var(--ww-bg-secondary);
}

.ww-color-text {
    flex: 1;
    min-width: 0;
}

.ww-color-preview {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 2px solid var(--ww-border);
    flex-shrink: 0;
}

/* ===================================================================
   Radio buttons
   =================================================================== */
.ww-radio-group {
    display: flex;
    gap: 8px;
}

.ww-radio-option {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 16px;
    border: 1px solid var(--ww-border);
    border-radius: var(--ww-radius-sm);
    cursor: pointer;
    font-size: 13px;
    transition: border-color 0.15s, background-color 0.15s;
    flex: 1;
}

.ww-radio-option:hover {
    background: var(--ww-bg-secondary);
}

.ww-radio-selected {
    border-color: var(--ww-primary);
    background: var(--ww-primary-light);
}

.ww-radio-input {
    display: none;
}

/* ===================================================================
   Toggle switch
   =================================================================== */
.ww-toggle-label {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 500;
    color: var(--ww-text);
    cursor: pointer;
}

.ww-toggle {
    position: relative;
    width: 40px;
    height: 22px;
    background: var(--ww-bg-tertiary);
    border: 1px solid var(--ww-border);
    border-radius: 11px;
    cursor: pointer;
    transition: background-color 0.2s;
    flex-shrink: 0;
    padding: 0;
}

.ww-toggle-on {
    background: var(--ww-primary);
    border-color: var(--ww-primary);
}

.ww-toggle-knob {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 16px;
    height: 16px;
    background: #fff;
    border-radius: 50%;
    transition: transform 0.2s;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
}

.ww-toggle-on .ww-toggle-knob {
    transform: translateX(18px);
}

/* ===================================================================
   Range slider
   =================================================================== */
.ww-range {
    width: 100%;
    accent-color: var(--ww-primary);
    margin: 4px 0;
}

.ww-range-labels {
    display: flex;
    justify-content: space-between;
    font-size: 11px;
    color: var(--ww-text-muted);
}

/* ===================================================================
   Form fields list (drag and drop)
   =================================================================== */
.ww-field-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 12px;
}

.ww-field-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    padding: 10px 12px;
    background: var(--ww-bg-secondary);
    border: 1px solid var(--ww-border);
    border-radius: var(--ww-radius-sm);
    cursor: grab;
    transition: box-shadow 0.15s, border-color 0.15s;
}

.ww-field-item:active {
    cursor: grabbing;
}

.ww-field-dragging {
    opacity: 0.5;
}

.ww-field-dragover {
    border-color: var(--ww-primary);
    box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
}

.ww-field-drag-handle {
    padding: 2px;
    color: var(--ww-text-muted);
    cursor: grab;
    flex-shrink: 0;
    margin-top: 2px;
}

.ww-field-item-body {
    flex: 1;
    min-width: 0;
}

.ww-field-item-row {
    display: flex;
    gap: 8px;
    margin-bottom: 6px;
}

.ww-field-item-row .ww-input {
    flex: 1;
    min-width: 0;
}

.ww-field-item-row .ww-select {
    width: 120px;
    flex: none;
}

.ww-field-item-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

/* ===================================================================
   Domain list
   =================================================================== */
.ww-domain-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 10px;
}

.ww-domain-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.ww-domain-item .ww-input {
    flex: 1;
}

/* ===================================================================
   API key row
   =================================================================== */
.ww-api-key-row {
    display: flex;
    gap: 8px;
    align-items: center;
}

.ww-api-key-row .ww-input {
    flex: 1;
    min-width: 0;
}

/* ===================================================================
   KB categories
   =================================================================== */
.ww-kb-categories {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 8px 0;
}

.ww-kb-cat-item {
    padding: 6px 10px;
    border-radius: var(--ww-radius-sm);
    transition: background-color 0.1s;
}

.ww-kb-cat-item:hover {
    background: var(--ww-bg-secondary);
}

.ww-badge {
    font-size: 10px;
    background: var(--ww-bg-tertiary);
    color: var(--ww-text-secondary);
    padding: 1px 6px;
    border-radius: 8px;
    margin-left: auto;
}

.ww-empty-text {
    font-size: 12px;
    color: var(--ww-text-muted);
    font-style: italic;
    padding: 8px 0;
    margin: 0;
}

/* ===================================================================
   Code block
   =================================================================== */
.ww-code-block {
    border: 1px solid var(--ww-border);
    border-radius: var(--ww-radius);
    overflow: hidden;
}

.ww-code-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    background: var(--ww-bg-secondary);
    border-bottom: 1px solid var(--ww-border);
}

.ww-code-lang {
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--ww-text-muted);
}

.ww-code-body {
    padding: 14px 16px;
    background: var(--ww-bg-tertiary);
    margin: 0;
    overflow-x: auto;
}

.ww-code-body code {
    font-family: 'JetBrains Mono', 'Fira Code', ui-monospace, monospace;
    font-size: 12px;
    line-height: 1.6;
    color: var(--ww-text);
    white-space: pre;
}

/* ===================================================================
   Preview panel
   =================================================================== */
.ww-config-preview {
    position: sticky;
    top: 16px;
}

.ww-preview-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.ww-preview-title {
    font-size: 14px;
    font-weight: 600;
    margin: 0;
    color: var(--ww-text);
}

.ww-preview-controls {
    display: flex;
    gap: 4px;
}

/* ===================================================================
   Buttons
   =================================================================== */
.ww-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border: none;
    border-radius: var(--ww-radius-sm);
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.15s, opacity 0.15s;
    white-space: nowrap;
    font-family: inherit;
}

.ww-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.ww-btn-primary {
    background: var(--ww-primary);
    color: #fff;
}

.ww-btn-primary:hover:not(:disabled) {
    background: var(--ww-primary-hover);
}

.ww-btn-secondary {
    background: var(--ww-bg-secondary);
    color: var(--ww-text);
    border: 1px solid var(--ww-border);
}

.ww-btn-secondary:hover:not(:disabled) {
    background: var(--ww-bg-tertiary);
}

.ww-btn-warning {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}

.ww-dark .ww-btn-warning {
    background: #422006;
    color: #fbbf24;
    border-color: #713f12;
}

.ww-btn-warning:hover:not(:disabled) {
    background: #fde68a;
}

.ww-dark .ww-btn-warning:hover:not(:disabled) {
    background: #713f12;
}

.ww-btn-sm {
    padding: 5px 10px;
    font-size: 12px;
}

.ww-btn-xs {
    padding: 3px 8px;
    font-size: 11px;
}

.ww-btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    padding: 0;
    background: none;
    border: 1px solid transparent;
    border-radius: var(--ww-radius-sm);
    cursor: pointer;
    color: var(--ww-text-secondary);
    transition: background-color 0.15s, color 0.15s;
}

.ww-btn-icon:hover {
    background: var(--ww-bg-secondary);
    color: var(--ww-text);
}

.ww-btn-icon-active {
    background: var(--ww-primary-light);
    color: var(--ww-primary);
    border-color: var(--ww-primary);
}

.ww-btn-danger-icon {
    color: var(--ww-text-muted);
}

.ww-btn-danger-icon:hover {
    background: #fef2f2;
    color: var(--ww-danger);
}

.ww-dark .ww-btn-danger-icon:hover {
    background: #450a0a;
}

/* ===================================================================
   Icons & Utilities
   =================================================================== */
.ww-icon {
    width: 16px;
    height: 16px;
    flex-shrink: 0;
}

.ww-spin {
    animation: ww-spin-anim 1s linear infinite;
}

@keyframes ww-spin-anim {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
