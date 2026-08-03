let residentFilterDebounce = null;
function triggerResidentFilter() {
  clearTimeout(residentFilterDebounce);
  residentFilterDebounce = setTimeout(() => fetchResidents(1), 350);
}

let householdSearchDebounce = null;
function triggerHouseholdSearch(term) {
  clearTimeout(householdSearchDebounce);
  const resultsBox = document.getElementById('householdSearchResults');

  if (term.trim().length < 2) {
    resultsBox.classList.add('hidden');
    return;
  }

  householdSearchDebounce = setTimeout(async () => {
    const households = await searchHouseholds(term.trim());
    if (!households.length) {
      resultsBox.innerHTML = '<div class="px-3 py-2.5 text-[11px] text-slate-400">No matching households. Create one via the household form on the Households page.</div>';
      resultsBox.classList.remove('hidden');
      return;
    }
    resultsBox.innerHTML = households.map(h => `
      <button type="button" onclick='selectHousehold(${JSON.stringify(h).replace(/'/g, "&apos;")})' class="w-full text-left px-3 py-2.5 text-[11px] hover:bg-slate-50 border-b border-slate-100 last:border-0 cursor-pointer">
        <span class="font-bold text-slate-700">${h.barangay}</span> — ${h.street_address}
        ${h.household_number ? `<span class="text-slate-400 font-mono"> (HH ${h.household_number})</span>` : ''}
      </button>
    `).join('');
    resultsBox.classList.remove('hidden');
  }, 300);
}
