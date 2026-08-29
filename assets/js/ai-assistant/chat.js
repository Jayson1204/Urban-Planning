// Lightweight formatter for Gemini's markdown-ish output: bold, bullet lines, line breaks.
// Uses the shared escapeHtml() from assets/js/app.js (loaded before any bridge script,
// including this one), not a local copy -- avoids two same-named global functions where
// whichever loads last silently wins for every caller in the app.
// Intentionally minimal (no markdown library) since the assistant's replies are short and
// mostly plain text with occasional **bold** or "- " bullet lines.
function formatAiText(text) {
  const escaped = escapeHtml(text);
  const withBold = escaped.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
  const lines = withBold.split('\n');

  let html = '';
  let inList = false;
  lines.forEach(line => {
    const bulletMatch = line.match(/^\s*[-*]\s+(.*)$/);
    if (bulletMatch) {
      if (!inList) { html += '<ul class="list-disc pl-4 space-y-0.5">'; inList = true; }
      html += `<li>${bulletMatch[1]}</li>`;
    } else {
      if (inList) { html += '</ul>'; inList = false; }
      html += line ? `<p>${line}</p>` : '<br>';
    }
  });
  if (inList) html += '</ul>';

  return html;
}

function appendChatMessage(role, text) {
  const container = document.getElementById('aiChatMessages');
  if (!container) return;

  const isUser = role === 'user';
  const wrapper = document.createElement('div');
  wrapper.className = `flex items-start gap-3 ${isUser ? 'flex-row-reverse' : ''}`;

  wrapper.innerHTML = `
    <div class="h-8 w-8 rounded-lg ${isUser ? 'bg-slate-700' : 'bg-brand-dark'} text-white flex items-center justify-center shrink-0">
      <i class="fa-solid ${isUser ? 'fa-user' : 'fa-robot'} text-xs"></i>
    </div>
    <div class="${isUser ? 'bg-brand-dark text-white rounded-tr-sm' : 'bg-slate-50 border border-slate-100 text-slate-600 rounded-tl-sm'} rounded-2xl px-4 py-3 max-w-2xl text-xs leading-relaxed space-y-1.5">
      ${formatAiText(text)}
    </div>
  `;

  container.appendChild(wrapper);
  container.scrollTop = container.scrollHeight;
}

function showTypingIndicator(show) {
  const indicator = document.getElementById('aiTypingIndicator');
  if (!indicator) return;
  indicator.classList.toggle('hidden', !show);
  indicator.classList.toggle('flex', show);
  if (show) {
    const container = document.getElementById('aiChatMessages');
    if (container) container.scrollTop = container.scrollHeight;
  }
}

function setAiChatInputEnabled(enabled) {
  const input = document.getElementById('aiChatInput');
  const btn = document.getElementById('aiChatSendBtn');
  if (input) input.disabled = !enabled;
  if (btn) btn.disabled = !enabled;
  if (enabled && input) input.focus();
}

function clearAiChat() {
  aiChatHistory = [];
  const container = document.getElementById('aiChatMessages');
  if (!container) return;
  container.innerHTML = `
    <div class="flex items-start gap-3">
      <div class="h-8 w-8 rounded-lg bg-brand-dark text-white flex items-center justify-center shrink-0">
        <i class="fa-solid fa-robot text-xs"></i>
      </div>
      <div class="bg-slate-50 border border-slate-100 rounded-2xl rounded-tl-sm px-4 py-3 max-w-2xl text-xs text-slate-600 leading-relaxed">
        Chat cleared. Ask me anything about residents, housing, urban projects, or field surveys.
      </div>
    </div>
  `;
}

function sendQuickPrompt(prompt) {
  sendAiMessage(prompt);
}
