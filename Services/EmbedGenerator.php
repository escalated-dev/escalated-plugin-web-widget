<?php

namespace Escalated\Plugins\WebWidget\Services;

use Escalated\Plugins\WebWidget\Support\Config;

/**
 * Generates the embeddable JavaScript widget loader and the HTML snippet
 * customers paste into their websites.
 *
 * The loader creates a Shadow DOM container, renders a contact form
 * (with optional KB article search), handles form submission via a
 * direct API call, and applies server-configured styles.
 */
class EmbedGenerator
{
    protected Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    // -----------------------------------------------------------------
    // Public API
    // -----------------------------------------------------------------

    /**
     * Generate the HTML embed snippet a customer pastes into their site.
     *
     * @param  string|null $baseUrl Override the platform base URL.
     * @return string               The <script> snippet.
     */
    public function generateSnippet(?string $baseUrl = null): string
    {
        $settings = $this->config->get();
        $apiKey   = $settings['api_key'] ?? '';

        if ($baseUrl === null) {
            $baseUrl = function_exists('escalated_url')
                ? rtrim(escalated_url(''), '/')
                : 'https://your-domain.com';
        }

        $widgetUrl = $baseUrl . '/plugins/web-widget/widget.js';

        return '<script>' . "\n"
            . '  (function(w,d,s,o){' . "\n"
            . '    var f=d.getElementsByTagName(s)[0],j=d.createElement(s);' . "\n"
            . '    j.async=true;j.src=o.url;j.dataset.apiKey=o.apiKey;' . "\n"
            . '    w.EscalatedWidgetConfig=o;f.parentNode.insertBefore(j,f);' . "\n"
            . '  })(window,document,"script",{' . "\n"
            . '    url: "' . $widgetUrl . '",' . "\n"
            . '    apiKey: "' . $apiKey . '"' . "\n"
            . '  });' . "\n"
            . '</script>';
    }

    /**
     * Generate the full self-executing JavaScript widget that runs on
     * the customer's website. This is served as application/javascript
     * from the embed endpoint.
     *
     * @param  array $settings Resolved plugin settings.
     * @return string          Complete JavaScript source.
     */
    public function generateScript(array $settings): string
    {
        $clientConfig = json_encode([
            'colors'     => $settings['colors'] ?? $this->config->defaults()['colors'],
            'position'   => $settings['position'] ?? 'bottom-right',
            'greeting'   => $settings['greeting'] ?? 'Hi there! How can we help you?',
            'fields'     => $settings['form_fields'] ?? $this->config->defaults()['form_fields'],
            'kb_enabled' => !empty($settings['kb_enabled']),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $version = Config::VERSION;

        return <<<JAVASCRIPT
/* Escalated Web Widget v{$version} */
(function(){
  'use strict';

  if(window.__escalatedWidgetLoaded) return;
  window.__escalatedWidgetLoaded = true;

  var scriptTag = document.currentScript || (function(){
    var scripts = document.getElementsByTagName('script');
    return scripts[scripts.length - 1];
  })();
  var apiKey = (scriptTag && scriptTag.dataset && scriptTag.dataset.apiKey)
    || (window.EscalatedWidgetConfig && window.EscalatedWidgetConfig.apiKey)
    || '';
  var baseUrl = (scriptTag && scriptTag.src)
    ? scriptTag.src.replace(/\/plugins\/web-widget\/widget\.js.*/, '')
    : '';

  var cfg = {$clientConfig};

  /* ------------------------------------------------------------------ */
  /* Colour helpers                                                      */
  /* ------------------------------------------------------------------ */
  function hexToRgb(hex){
    hex = hex.replace('#','');
    if(hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
    var n = parseInt(hex,16);
    return [(n>>16)&255,(n>>8)&255,n&255];
  }
  function luminance(hex){
    var c = hexToRgb(hex);
    return (0.299*c[0] + 0.587*c[1] + 0.114*c[2]) / 255;
  }
  function contrastText(hex){ return luminance(hex) > 0.6 ? '#1f2937' : '#ffffff'; }

  /* ------------------------------------------------------------------ */
  /* Build Shadow DOM host                                               */
  /* ------------------------------------------------------------------ */
  var host = document.createElement('div');
  host.id = 'escalated-widget-host';
  host.style.cssText = 'all:initial;position:fixed;bottom:0;z-index:2147483647;'
    + (cfg.position === 'bottom-left' ? 'left:0;' : 'right:0;');
  document.body.appendChild(host);

  var shadow = host.attachShadow({mode:'open'});

  /* ------------------------------------------------------------------ */
  /* Styles (scoped inside Shadow DOM)                                   */
  /* ------------------------------------------------------------------ */
  var primaryColor  = cfg.colors.primary   || '#6366f1';
  var bgColor       = cfg.colors.background|| '#ffffff';
  var textColor     = cfg.colors.text      || '#1f2937';
  var launcherColor = cfg.colors.launcher  || primaryColor;
  var launcherText  = contrastText(launcherColor);
  var primaryText   = contrastText(primaryColor);
  var positionSide  = cfg.position === 'bottom-left' ? 'left' : 'right';

  var style = document.createElement('style');
  style.textContent = [
    ':host{all:initial;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;font-size:14px;line-height:1.5;color:' + textColor + ';}',
    '*,*::before,*::after{box-sizing:border-box;}',
    '.esc-launcher{position:fixed;bottom:20px;' + positionSide + ':20px;width:56px;height:56px;border-radius:50%;background:' + launcherColor + ';color:' + launcherText + ';border:none;cursor:pointer;box-shadow:0 4px 12px rgba(0,0,0,.15);display:flex;align-items:center;justify-content:center;transition:transform .2s,box-shadow .2s;z-index:2147483647;}',
    '.esc-launcher:hover{transform:scale(1.08);box-shadow:0 6px 20px rgba(0,0,0,.2);}',
    '.esc-launcher svg{width:24px;height:24px;fill:currentColor;}',
    '.esc-panel{position:fixed;bottom:88px;' + positionSide + ':20px;width:380px;max-width:calc(100vw - 40px);max-height:calc(100vh - 120px);background:' + bgColor + ';border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.12);display:none;flex-direction:column;overflow:hidden;z-index:2147483647;}',
    '.esc-panel.open{display:flex;}',
    '.esc-header{background:' + primaryColor + ';color:' + primaryText + ';padding:20px;font-size:16px;font-weight:600;}',
    '.esc-header-greeting{margin:0;font-size:15px;font-weight:400;opacity:.9;margin-top:4px;}',
    '.esc-tabs{display:flex;border-bottom:1px solid #e5e7eb;}',
    '.esc-tab{flex:1;padding:10px;text-align:center;cursor:pointer;font-size:13px;font-weight:500;color:' + textColor + ';background:transparent;border:none;border-bottom:2px solid transparent;transition:border-color .15s,color .15s;}',
    '.esc-tab.active{border-bottom-color:' + primaryColor + ';color:' + primaryColor + ';}',
    '.esc-body{flex:1;overflow-y:auto;padding:16px;}',
    '.esc-field{margin-bottom:14px;}',
    '.esc-field label{display:block;font-size:13px;font-weight:500;margin-bottom:4px;color:' + textColor + ';}',
    '.esc-field label .req{color:#ef4444;margin-left:2px;}',
    '.esc-field input,.esc-field textarea{width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;font-family:inherit;color:' + textColor + ';background:' + bgColor + ';transition:border-color .15s;outline:none;}',
    '.esc-field input:focus,.esc-field textarea:focus{border-color:' + primaryColor + ';}',
    '.esc-field textarea{min-height:80px;resize:vertical;}',
    '.esc-field .esc-error{color:#ef4444;font-size:12px;margin-top:2px;}',
    '.esc-submit{width:100%;padding:10px;border:none;border-radius:6px;background:' + primaryColor + ';color:' + primaryText + ';font-size:14px;font-weight:600;cursor:pointer;transition:opacity .15s;}',
    '.esc-submit:hover{opacity:.9;}',
    '.esc-submit:disabled{opacity:.5;cursor:not-allowed;}',
    '.esc-alert{padding:12px;border-radius:6px;margin-bottom:14px;font-size:13px;}',
    '.esc-alert-success{background:#ecfdf5;color:#065f46;border:1px solid #a7f3d0;}',
    '.esc-alert-error{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;}',
    '.esc-search-input{width:100%;padding:8px 12px;border:1px solid #d1d5db;border-radius:6px;font-size:14px;font-family:inherit;margin-bottom:12px;outline:none;color:' + textColor + ';background:' + bgColor + ';}',
    '.esc-search-input:focus{border-color:' + primaryColor + ';}',
    '.esc-article{display:block;padding:10px 12px;margin-bottom:8px;border:1px solid #e5e7eb;border-radius:6px;text-decoration:none;color:' + textColor + ';transition:border-color .15s;}',
    '.esc-article:hover{border-color:' + primaryColor + ';}',
    '.esc-article-title{font-weight:600;font-size:14px;margin-bottom:2px;}',
    '.esc-article-excerpt{font-size:12px;color:#6b7280;line-height:1.4;}',
    '.esc-empty{text-align:center;color:#9ca3af;padding:24px 0;font-size:13px;}',
    '.esc-powered{text-align:center;padding:8px;font-size:11px;color:#9ca3af;border-top:1px solid #e5e7eb;}',
    '.esc-powered a{color:#6b7280;text-decoration:none;}',
    '@media(max-width:480px){.esc-panel{width:100%;max-width:100%;bottom:0;' + positionSide + ':0;border-radius:12px 12px 0 0;max-height:85vh;}}'
  ].join('\\n');
  shadow.appendChild(style);

  /* ------------------------------------------------------------------ */
  /* Launcher button                                                     */
  /* ------------------------------------------------------------------ */
  var launcher = document.createElement('button');
  launcher.className = 'esc-launcher';
  launcher.setAttribute('aria-label','Open support widget');
  launcher.innerHTML = '<svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.2L4 17.2V4h16v12z"/><path d="M7 9h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2z"/></svg>';
  shadow.appendChild(launcher);

  /* ------------------------------------------------------------------ */
  /* Panel                                                               */
  /* ------------------------------------------------------------------ */
  var panel = document.createElement('div');
  panel.className = 'esc-panel';

  var headerHtml = '<div class="esc-header">'
    + '<div style="font-size:16px;font-weight:700;">Support</div>'
    + '<p class="esc-header-greeting">' + escapeHtml(cfg.greeting) + '</p>'
    + '</div>';

  var tabsHtml = '';
  if(cfg.kb_enabled){
    tabsHtml = '<div class="esc-tabs">'
      + '<button class="esc-tab active" data-tab="contact">Contact Us</button>'
      + '<button class="esc-tab" data-tab="kb">Search Articles</button>'
      + '</div>';
  }

  var fieldsHtml = '';
  (cfg.fields || []).forEach(function(f){
    var req = f.required ? '<span class="req">*</span>' : '';
    if(f.type === 'textarea'){
      fieldsHtml += '<div class="esc-field"><label>' + escapeHtml(f.label) + req + '</label>'
        + '<textarea name="' + escapeHtml(f.name) + '"' + (f.required?' required':'') + '></textarea></div>';
    } else {
      fieldsHtml += '<div class="esc-field"><label>' + escapeHtml(f.label) + req + '</label>'
        + '<input type="' + escapeHtml(f.type||'text') + '" name="' + escapeHtml(f.name) + '"'
        + (f.required?' required':'') + '/></div>';
    }
  });

  var contactHtml = '<div class="esc-body esc-view" data-view="contact">'
    + '<div class="esc-alert-container"></div>'
    + '<form class="esc-form">' + fieldsHtml
    + '<button type="submit" class="esc-submit">Send Message</button>'
    + '</form></div>';

  var kbHtml = cfg.kb_enabled
    ? '<div class="esc-body esc-view" data-view="kb" style="display:none;">'
      + '<input type="text" class="esc-search-input" placeholder="Search for help articles..." />'
      + '<div class="esc-kb-results"><div class="esc-empty">Type a query to search articles.</div></div>'
      + '</div>'
    : '';

  var poweredHtml = '<div class="esc-powered">Powered by <a href="https://escalated.dev" target="_blank" rel="noopener">Escalated</a></div>';

  panel.innerHTML = headerHtml + tabsHtml + contactHtml + kbHtml + poweredHtml;
  shadow.appendChild(panel);

  /* ------------------------------------------------------------------ */
  /* Interactions                                                        */
  /* ------------------------------------------------------------------ */
  var isOpen = false;

  launcher.addEventListener('click', function(){
    isOpen = !isOpen;
    panel.classList.toggle('open', isOpen);
    launcher.innerHTML = isOpen
      ? '<svg viewBox="0 0 24 24"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>'
      : '<svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H5.2L4 17.2V4h16v12z"/><path d="M7 9h2v2H7zm4 0h2v2h-2zm4 0h2v2h-2z"/></svg>';
  });

  /* Tab switching */
  var tabs = panel.querySelectorAll('.esc-tab');
  var views = panel.querySelectorAll('.esc-view');

  tabs.forEach(function(tab){
    tab.addEventListener('click', function(){
      var target = tab.getAttribute('data-tab');
      tabs.forEach(function(t){ t.classList.remove('active'); });
      tab.classList.add('active');
      views.forEach(function(v){
        v.style.display = v.getAttribute('data-view') === target ? '' : 'none';
      });
    });
  });

  /* Form submission */
  var form = panel.querySelector('.esc-form');
  var alertContainer = panel.querySelector('.esc-alert-container');
  var submitBtn = panel.querySelector('.esc-submit');

  if(form){
    form.addEventListener('submit', function(e){
      e.preventDefault();
      alertContainer.innerHTML = '';

      var formData = {};
      var valid = true;

      panel.querySelectorAll('.esc-field .esc-error').forEach(function(el){ el.remove(); });

      (cfg.fields || []).forEach(function(f){
        var el = form.querySelector('[name="' + f.name + '"]');
        var val = el ? el.value.trim() : '';
        formData[f.name] = val;

        if(f.required && !val){
          valid = false;
          showFieldError(el, f.label + ' is required.');
        }
        if(f.type === 'email' && val && !isValidEmail(val)){
          valid = false;
          showFieldError(el, 'Please enter a valid email address.');
        }
      });

      if(!valid) return;

      submitBtn.disabled = true;
      submitBtn.textContent = 'Sending...';

      postJson(baseUrl + '/api/plugins/web-widget/submit', {
        api_key: apiKey,
        form_data: formData
      }, function(res){
        submitBtn.disabled = false;
        submitBtn.textContent = 'Send Message';

        if(res && res.success){
          form.reset();
          showAlert('success', 'Your message has been sent! We will get back to you soon.');
        } else {
          var msg = 'Something went wrong. Please try again.';
          if(res && res.error === 'rate_limit_exceeded') msg = 'Too many submissions. Please wait before trying again.';
          if(res && res.error === 'validation_failed') msg = 'Please correct the highlighted fields.';
          showAlert('error', msg);
        }
      });
    });
  }

  /* KB search */
  var searchInput = panel.querySelector('.esc-search-input');
  var kbResults   = panel.querySelector('.esc-kb-results');
  var searchTimer = null;

  if(searchInput && kbResults){
    searchInput.addEventListener('input', function(){
      var q = searchInput.value.trim();
      clearTimeout(searchTimer);

      if(q.length < 2){
        kbResults.innerHTML = '<div class="esc-empty">Type a query to search articles.</div>';
        return;
      }

      searchTimer = setTimeout(function(){
        kbResults.innerHTML = '<div class="esc-empty">Searching...</div>';

        postJson(baseUrl + '/api/plugins/web-widget/kb-search', {
          api_key: apiKey,
          query: q
        }, function(res){
          if(!res || !res.success || !res.articles || res.articles.length === 0){
            kbResults.innerHTML = '<div class="esc-empty">No articles found.</div>';
            return;
          }

          var html = '';
          res.articles.forEach(function(a){
            var href = a.url ? ' href="' + escapeHtml(a.url) + '" target="_blank" rel="noopener"' : '';
            html += '<a class="esc-article"' + href + '>'
              + '<div class="esc-article-title">' + escapeHtml(a.title || 'Untitled') + '</div>'
              + '<div class="esc-article-excerpt">' + escapeHtml(a.excerpt || '') + '</div>'
              + '</a>';
          });
          kbResults.innerHTML = html;
        });
      }, 350);
    });
  }

  /* ------------------------------------------------------------------ */
  /* Helpers                                                             */
  /* ------------------------------------------------------------------ */
  function escapeHtml(str){
    var d = document.createElement('div');
    d.appendChild(document.createTextNode(str || ''));
    return d.innerHTML;
  }

  function isValidEmail(email){
    return /^[^\\s@]+@[^\\s@]+\\.[^\\s@]+$/.test(email);
  }

  function showFieldError(el, msg){
    if(!el) return;
    var err = document.createElement('div');
    err.className = 'esc-error';
    err.textContent = msg;
    el.parentNode.appendChild(err);
  }

  function showAlert(type, msg){
    alertContainer.innerHTML = '<div class="esc-alert esc-alert-' + type + '">' + escapeHtml(msg) + '</div>';
  }

  function postJson(url, data, cb){
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-Type','application/json');
    xhr.withCredentials = false;
    xhr.onreadystatechange = function(){
      if(xhr.readyState === 4){
        var res = null;
        try{ res = JSON.parse(xhr.responseText); }catch(e){}
        cb(res);
      }
    };
    xhr.onerror = function(){ cb(null); };
    xhr.send(JSON.stringify(data));
  }

})();
JAVASCRIPT;
    }

    // -----------------------------------------------------------------
    // Serve endpoint
    // -----------------------------------------------------------------

    /**
     * Validate the request and return the embed script response.
     *
     * @param  string $apiKey The API key from the request query string.
     * @param  string $origin The Origin header.
     * @return array          ['content_type' => ..., 'body' => ...] or ['error' => ..., 'status' => ...]
     */
    public function serve(string $apiKey, string $origin = ''): array
    {
        if (!$this->config->validateApiKey($apiKey)) {
            return ['error' => 'invalid_api_key', 'status' => 403];
        }

        if ($origin !== '' && !$this->config->validateDomain($origin)) {
            return ['error' => 'domain_not_allowed', 'status' => 403];
        }

        $settings = $this->config->get();

        return [
            'content_type' => 'application/javascript; charset=utf-8',
            'body'         => $this->generateScript($settings),
        ];
    }
}
