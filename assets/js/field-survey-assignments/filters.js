let assignmentFilterDebounce = null;
function triggerAssignmentFilter() {
  clearTimeout(assignmentFilterDebounce);
  assignmentFilterDebounce = setTimeout(() => fetchAssignments(1), 350);
}

let assignmentResidentPickerDebounce = null;
function triggerAssignmentResidentPicker(term) {
  clearTimeout(assignmentResidentPickerDebounce);
  const box = document.getElementById('assignmentResidentResults');
  if (term.trim().length < 2) {
    box.classList.add('hidden');
    return;
  }
  assignmentResidentPickerDebounce = setTimeout(async () => {
    const residents = await searchResidentsForAssignmentPicker(term.trim());
    if (!residents.length) {
      box.innerHTML = '<div class="px-3 py-2.5 text-[11px] text-slate-400">No matching residents. Register them in Resident Management first.</div>';
      box.classList.remove('hidden');
      return;
    }
    box.innerHTML = residents.map(r => `
      <button type="button" onclick='selectAssignmentResident(${JSON.stringify(r).replace(/'/g, "&apos;")})' class="w-full text-left px-3 py-2.5 text-[11px] hover:bg-slate-50 border-b border-slate-100 last:border-0 cursor-pointer">
        <span class="font-bold text-slate-700">${residentFullName(r)}</span>
        ${r.barangay ? `<span class="text-slate-400"> — ${r.barangay}</span>` : ''}
      </button>
    `).join('');
    box.classList.remove('hidden');
  }, 300);
}

let assignmentHouseholdPickerDebounce = null;
function triggerAssignmentHouseholdPicker(term) {
  clearTimeout(assignmentHouseholdPickerDebounce);
  const box = document.getElementById('assignmentHouseholdResults');
  if (term.trim().length < 1) {
    box.classList.add('hidden');
    return;
  }
  assignmentHouseholdPickerDebounce = setTimeout(async () => {
    const households = await searchHouseholdsForAssignmentPicker(term.trim());
    if (!households.length) {
      box.innerHTML = '<div class="px-3 py-2.5 text-[11px] text-slate-400">No matching households.</div>';
      box.classList.remove('hidden');
      return;
    }
    box.innerHTML = households.map(h => `
      <button type="button" onclick='selectAssignmentHousehold(${JSON.stringify(h).replace(/'/g, "&apos;")})' class="w-full text-left px-3 py-2.5 text-[11px] hover:bg-slate-50 border-b border-slate-100 last:border-0 cursor-pointer">
        <span class="font-bold text-slate-700 font-mono">${h.household_number || 'HH'}</span>
        ${h.barangay ? `<span class="text-slate-400"> — ${h.barangay}</span>` : ''}
      </button>
    `).join('');
    box.classList.remove('hidden');
  }, 300);
}
