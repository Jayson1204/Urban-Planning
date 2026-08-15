// AI Planning Assistant Module Bootstrap
window.loadCiventralScript('assets/js/ai-assistant/api.js');
window.loadCiventralScript('assets/js/ai-assistant/chat.js');
window.loadCiventralScript('assets/js/ai-assistant/events.js', () => {
    // Only execute if we are actually on the AI assistant page
    if (document.getElementById('aiChatMessages')) {
        const input = document.getElementById('aiChatInput');
        if (input) input.focus();
    }
});
