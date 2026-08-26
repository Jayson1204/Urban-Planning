<?php
// Public page, intentionally not going through includes/header.php / src/bootstrap.php --
// this is reached by a citizen with no staff session and no citizen session yet, from an
// emailed link, so it must not trigger SessionTimeout or any staff-auth redirect.
$token = trim($_GET['token'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Set your password - Civentral</title>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <style type="text/tailwindcss">
    @theme {
      --color-brand-light: #EEF5FF;
      --color-brand-border: #B4D4FF;
      --color-brand-medium: #86B6F6;
      --color-brand-dark: #176B87;
    }
  </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
  <div class="bg-white border border-slate-200/80 rounded-2xl shadow-xs max-w-sm w-full p-8 space-y-5">
    <div class="space-y-1 text-center">
      <h1 class="text-lg font-black text-slate-900">Set your password</h1>
      <p class="text-xs text-slate-500 leading-relaxed">Choose a password for your Civentral citizen mobile app account.</p>
    </div>

    <?php if (!$token): ?>
      <p class="text-xs text-rose-600 text-center">This link is missing a token. Please use the link from your email.</p>
    <?php else: ?>
      <form id="setPasswordForm" class="space-y-3">
        <input type="hidden" id="token" value="<?php echo htmlspecialchars($token); ?>">
        <div class="space-y-1.5">
          <label class="text-[10px] font-black text-slate-400 uppercase tracking-wider block">New password</label>
          <input type="password" id="password" required minlength="8" class="border border-slate-200 rounded-xl px-3 py-2.5 text-sm w-full focus:outline-none focus:border-brand-medium transition">
        </div>
        <div id="setPasswordMessage" class="text-xs hidden"></div>
        <button type="submit" class="w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-2.5 rounded-xl text-xs cursor-pointer transition">Set password</button>
      </form>
    <?php endif; ?>
  </div>

  <script>
    const form = document.getElementById('setPasswordForm');
    if (form) {
      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const messageEl = document.getElementById('setPasswordMessage');
        messageEl.classList.add('hidden');
        try {
          const response = await fetch('../../api/citizen-app/set-password.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              token: document.getElementById('token').value,
              password: document.getElementById('password').value,
            }),
          });
          const result = await response.json();
          messageEl.textContent = result.message || '';
          messageEl.className = 'text-xs ' + (result.status === 'success' ? 'text-emerald-600' : 'text-rose-600');
          messageEl.classList.remove('hidden');
          if (result.status === 'success') {
            form.querySelector('button').disabled = true;
          }
        } catch (err) {
          messageEl.textContent = 'Network error. Please try again.';
          messageEl.className = 'text-xs text-rose-600';
          messageEl.classList.remove('hidden');
        }
      });
    }
  </script>
</body>
</html>
