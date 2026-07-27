// Shared Toast Notification for Roles & Permissions

let rolesToastTimer = null;

function showToast(message, isError = false) {
  const toast = document.getElementById('toast');
  const toastMsg = document.getElementById('toastMsg');
  const iconBox = document.getElementById('toastIconBox');
  const iconSymbol = document.getElementById('toastIconSymbol');
  
  if (!toast || !toastMsg) return;

  const msgLower = message.toLowerCase();
  if (!isError && (msgLower.includes('error') || msgLower.includes('failed') || msgLower.includes('invalid') || msgLower.includes('network') || msgLower.includes('require'))) {
    isError = true;
  }

  toastMsg.innerText = message;
  
  if (isError) {
      toast.classList.replace('bg-emerald-500', 'bg-rose-500');
  } else {
      toast.classList.replace('bg-rose-500', 'bg-emerald-500');
  }

  if (iconBox && iconSymbol) {
    if (isError) {
      iconBox.className = "h-5 w-5 rounded-full bg-rose-500 flex items-center justify-center text-white text-[10px]";
      iconSymbol.className = "fa-solid fa-xmark";
    } else {
      iconBox.className = "h-5 w-5 rounded-full bg-emerald-500 flex items-center justify-center text-white text-[10px]";
      iconSymbol.className = "fa-solid fa-check";
    }
  }

  toast.classList.remove('opacity-0', 'pointer-events-none', 'translate-y-4');
  toast.classList.add('opacity-100', 'translate-y-0');

  if (rolesToastTimer) clearTimeout(rolesToastTimer);
  
  rolesToastTimer = setTimeout(() => {
    toast.classList.remove('opacity-100', 'translate-y-0');
    toast.classList.add('opacity-0', 'pointer-events-none', 'translate-y-4');
  }, 3200);
}
