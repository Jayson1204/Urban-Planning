// Global State
let aiChatHistory = [];
let aiChatBusy = false;

// Toast Popup
function showToast(message) {
  const toast = document.getElementById('toast');
  const toastMsg = document.getElementById('toastMsg');
  if (!toast || !toastMsg) return;

  toastMsg.innerText = message;
  toast.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-4');
  toast.classList.add('opacity-100', 'translate-y-0');

  setTimeout(() => {
    toast.classList.remove('opacity-100', 'translate-y-0');
    toast.classList.add('opacity-0', 'pointer-events-none', 'translate-y-4');
  }, 3200);
}

async function sendAiMessage(message) {
  if (aiChatBusy || !message.trim()) return;
  aiChatBusy = true;

  appendChatMessage('user', message);
  aiChatHistory.push({ role: 'user', text: message });
  // Keep client-side history in step with the server's cap so the browser isn't
  // uploading an ever-growing payload every turn in a long session.
  if (aiChatHistory.length > 12) {
    aiChatHistory = aiChatHistory.slice(-12);
  }
  setAiChatInputEnabled(false);
  showTypingIndicator(true);

  try {
    const response = await fetch('../../api/employee/ai-assistant.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message, history: aiChatHistory.slice(0, -1) })
    });
    const result = await response.json();

    if (result.status === 'success') {
      appendChatMessage('model', result.response);
      aiChatHistory.push({ role: 'model', text: result.response });
    } else {
      appendChatMessage('model', `Sorry, I ran into an error: ${result.message || 'unknown error'}`);
      showToast(result.message || 'Failed to get a response from the assistant.');
    }
  } catch (err) {
    console.error('Error contacting AI assistant:', err);
    appendChatMessage('model', 'Sorry, I could not reach the assistant service. Please try again.');
    showToast('Network error while contacting the assistant.');
  } finally {
    showTypingIndicator(false);
    setAiChatInputEnabled(true);
    aiChatBusy = false;
  }
}
