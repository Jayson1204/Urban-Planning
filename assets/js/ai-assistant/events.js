function handleSendAiMessage(e) {
  e.preventDefault();
  const input = document.getElementById('aiChatInput');
  if (!input) return;
  const message = input.value.trim();
  if (!message) return;
  input.value = '';
  sendAiMessage(message);
}
