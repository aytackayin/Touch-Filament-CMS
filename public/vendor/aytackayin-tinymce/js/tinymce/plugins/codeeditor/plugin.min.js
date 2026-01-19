/**
 * @copyright ©Melqui Brito. All rights reserved.
 * @author Melqui Brito
 * @version 1.0.0 (2020-03-24)
 * @description Tinymce custom advanced plugin for source code editing.
 * Modified to match Filament/Tailwind UI aesthetics.
 */


var aceEditor, tryToBuildAceTimer;

function displayToxEditorModal(display = true) {
    let el = document.getElementById('tox-codeeditor-wrap');
    if (display) {
        el.style.display = "flex";
        el.focus();
        document.body.classList.add('tox-codeeditor__disable-scroll');
    } else {
        el.style.display = "none";
        document.body.classList.remove('tox-codeeditor__disable-scroll');
        tinymce.activeEditor.focus();
    }
}

function saveContent() {
    let e = tinymce.activeEditor;
    e.focus();
    e.undoManager.transact(function () {
        e.setContent(aceEditor.getValue())
    });
    e.selection.setCursorLocation();
    e.nodeChanged();
    displayToxEditorModal(false);
}

function applyTheme(ref) {
    aceEditor.setTheme(ref.options[ref.selectedIndex].value);
}

! function () {
    "use strict";

    let themesPack = function () {
        let customPack = tinymce.activeEditor.getParam('codeeditor_themes_pack');
        if (typeof customPack === "string") {
            return customPack.trim().replace(/(\s+)/g, "?").split("?");
        }
        if (Array.isArray(customPack)) {
            return customPack
        }
        return ['twilight', 'merbivore', 'dawn', 'kuroir']
    }();

    let fontSize = function () {
        let customFontSize = tinymce.activeEditor.getParam('codeeditor_font_size');
        if (typeof customFontSize === "number") {
            return parseInt(customFontSize);
        }
        return 16 // Improved default readability
    }();

    let wrapMode = function () {
        let wrapContent = tinymce.activeEditor.getParam('codeeditor_wrap_mode');
        if (typeof wrapContent === "boolean") {
            return wrapContent
        }
        return true
    }();

    const getOptions = function () {
        let options = '';
        for (let theme of themesPack) {
            options = options + `<option value="ace/theme/${theme}">${theme[0].toUpperCase() + theme.slice(1)}</option>`
        }
        return options
    }
    /** Set Label translates */
    const windowLabel = function () {
        return tinymce.activeEditor.translate("Source code")
    }
    const cancelButtonLabel = function () {
        return tinymce.activeEditor.translate("cancel")
    }
    const saveButtonLabel = function () {
        return tinymce.activeEditor.translate("save")
    }

    /* CSS mimicking Filament V3/V4 Tailwind UI */
    let styleInnerHTML = `
#tox-codeeditor-wrap {
    position: fixed;
    display: none;
    align-items: center;
    justify-content: center;
    top: 0;
    left: 0;
    bottom: 0;
    right: 0;
    z-index: 10000;
    background-color: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
    font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    -webkit-tap-highlight-color: rgba(0,0,0,0);
}

#tox-codeeditor-modal {
    position: relative;
    display: flex;
    flex-direction: column;
    height: 90vh;
    width: 95vw;
    max-width: 1200px;
    background-color: #ffffff;
    border-radius: 0.75rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    outline: 0 none;
    overflow: hidden;
    color: #111827;
}

:is(.dark) #tox-codeeditor-modal {
    background-color: #18181b;
    border: 1px solid #27272a;
    color: #ffffff;
}

#tox-codeeditor-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    background-color: #ffffff;
    border-bottom: 1px solid #e5e7eb;
}

:is(.dark) #tox-codeeditor-header {
    background-color: #18181b;
    border-color: #27272a;
}

#tox-codeeditor-modal-title {
    font-size: 1.125rem;
    font-weight: 600;
    line-height: 1.75rem;
    margin: 0;
}

#tox-codeeditor-editor-container {
    position: relative;
    flex: 1;
    width: 100%;
    overflow: hidden;
    background-color: #f9fafb;
}

:is(.dark) #tox-codeeditor-editor-container {
    background-color: #000000;
}

#tox-codeeditor-editor {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
}

#tox-codeeditor-close-button {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0.25rem;
    background-color: transparent;
    border: none;
    color: #6b7280;
    border-radius: 9999px;
    cursor: pointer;
    transition: all 0.2s ease;
}

#tox-codeeditor-close-button:hover {
    color: #111827;
    background-color: #f3f4f6;
}

:is(.dark) #tox-codeeditor-close-button {
    color: #9ca3af;
}

:is(.dark) #tox-codeeditor-close-button:hover {
    color: #ffffff;
    background-color: #27272a;
}

#tox-codeeditor-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    background-color: #ffffff;
    border-top: 1px solid #e5e7eb;
}

:is(.dark) #tox-codeeditor-footer {
    background-color: #18181b;
    border-color: #27272a;
}

#tox-codeeditor-footer-controls {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

#tox-codeeditor-theme-label {
    font-size: 0.875rem;
    color: #374151;
    font-weight: 500;
}

:is(.dark) #tox-codeeditor-theme-label {
    color: #d1d5db;
}

#tox-codeeditor-theme-picker {
    appearance: none;
    background-color: #fff;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    padding: 0.375rem 2rem 0.375rem 0.75rem;
    font-size: 0.875rem;
    line-height: 1.5rem;
    color: #111827;
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
}

:is(.dark) #tox-codeeditor-theme-picker {
    background-color: #27272a;
    border-color: #3f3f46;
    color: #ffffff;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%239ca3af' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
}

#tox-codeeditor-footer-buttons {
    display: flex;
    gap: 0.75rem;
}

.tox-codeeditor-btn {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
    border: 1px solid transparent;
}

.tox-codeeditor-secondary-button {
    background-color: #ffffff;
    border-color: #d1d5db;
    color: #374151;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.tox-codeeditor-secondary-button:hover {
    background-color: #f9fafb;
    border-color: #9ca3af;
}

:is(.dark) .tox-codeeditor-secondary-button {
    background-color: #27272a;
    border-color: #3f3f46;
    color: #ffffff;
}

:is(.dark) .tox-codeeditor-secondary-button:hover {
    background-color: #3f3f46;
    border-color: #52525b;
}

.tox-codeeditor-primary-button {
    background-color: #4f46e5; /* Indigo 600 */
    color: #ffffff;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

.tox-codeeditor-primary-button:hover {
    background-color: #4338ca; /* Indigo 700 */
}

.tox-codeeditor__disable-scroll {
    overflow: hidden;
}

#tox-codeeditor-modal, #tox-codeeditor-header, #tox-codeeditor-editor-container, #tox-codeeditor-footer {
    box-sizing: border-box;
}
    `;
    let aceStyle = document.createElement('style');
    aceStyle.media = "screen";
    aceStyle.type = "text/css";
    aceStyle.innerHTML = styleInnerHTML;
    document.head.appendChild(aceStyle);

    let codeeditor = document.createElement('div');
    codeeditor.id = "tox-codeeditor-wrap";
    codeeditor.tabIndex = "-1";
    codeeditor.innerHTML = `
<div id="tox-codeeditor-modal" tabindex="-1">
    <div id="tox-codeeditor-header" role="presentation">
        <h3 id="tox-codeeditor-modal-title">${windowLabel()}</h3>
        <button type="button" onclick="displayToxEditorModal(false)" tabindex="-1" id="tox-codeeditor-close-button" title="Close">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>
    <div id="tox-codeeditor-editor-container">
        <div id="tox-codeeditor-editor"></div>
    </div>
    <div id="tox-codeeditor-footer">
        <div id="tox-codeeditor-footer-controls">
            <label for="tox-codeeditor-theme-picker" id="tox-codeeditor-theme-label">Theme:</label>
            <select id="tox-codeeditor-theme-picker" onchange="applyTheme(this)">
                ${getOptions()}
            </select>
        </div>
        <div role="presentation" id="tox-codeeditor-footer-buttons">
            <button title="Cancel" type="button" tabindex="-1" onclick="displayToxEditorModal(false)" class="tox-codeeditor-btn tox-codeeditor-secondary-button">${cancelButtonLabel()}</button>
            <button title="Save" type="button" tabindex="-1" onclick="saveContent()" class="tox-codeeditor-btn tox-codeeditor-primary-button">${saveButtonLabel()}</button>
        </div>
    </div>
</div>
    `;
    document.body.appendChild(codeeditor);

    let aceScript = document.createElement('script');
    aceScript.src = "https://cdnjs.cloudflare.com/ajax/libs/ace/1.4.7/ace.js";
    aceScript.type = "text/javascript";
    aceScript.charset = "utf-8";
    document.body.appendChild(aceScript);

    let beautify = document.createElement('script');
    beautify.src = "https://cdnjs.cloudflare.com/ajax/libs/js-beautify/1.10.3/beautify.min.js";
    beautify.type = "text/javascript";
    document.body.appendChild(beautify);

    let beautifyCss = document.createElement('script');
    beautifyCss.src = "https://cdnjs.cloudflare.com/ajax/libs/js-beautify/1.10.3/beautify-css.min.js";
    beautifyCss.type = "text/javascript";
    document.body.appendChild(beautifyCss);

    let beautifyHtml = document.createElement('script');
    beautifyHtml.src = "https://cdnjs.cloudflare.com/ajax/libs/js-beautify/1.10.3/beautify-html.min.js";
    beautifyHtml.type = "text/javascript";
    document.body.appendChild(beautifyHtml);

    tryToBuildAceTimer = setInterval(() => {
        try {
            aceEditor = ace.edit("tox-codeeditor-editor");
            aceEditor.setTheme("ace/theme/twilight");
            aceEditor.setFontSize(fontSize);
            clearInterval(tryToBuildAceTimer);
        } catch (e) { }
    }, 500);

    tinymce.PluginManager.add('codeeditor', function (e) {
        e.ui.registry.addButton('codeeditor', {
            icon: 'sourcecode',
            tooltip: 'Code Editor',
            onAction: function () {
                displayToxEditorModal();
                let content = html_beautify(e.dom.decode(e.getContent({ source_view: !0 })));
                let session = ace.createEditSession(content, "ace/mode/html");
                session.setUseWrapMode(wrapMode);
                aceEditor.setSession(session);
            }
        });
        return {
            getMetadata: function () {
                return {
                    name: "CodeEditor",
                    url: "https://github.com/melquibrito/Source-code-editor-tinymce-plugin"
                };
            }
        }
    });
}();
