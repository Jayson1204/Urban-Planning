window.civAudit = window.civAudit || {};
window.civAudit.dataChanges = window.civAudit.dataChanges || {};

window.civAudit.dataChanges.modal = {
  openMutationModal(row) {
    if (!row) return;
    
    const id = row.getAttribute('data-id');
    const actor = row.getAttribute('data-actor');
    const time = row.getAttribute('data-time');
    const mod = row.getAttribute('data-module');
    const record = row.getAttribute('data-record');
    const field = row.getAttribute('data-field');
    const oldVal = row.getAttribute('data-old');
    const newVal = row.getAttribute('data-new');
    const reason = row.getAttribute('data-reason');
    const oldJson = row.getAttribute('data-old-json');
    const newJson = row.getAttribute('data-new-json');

    const elId = document.getElementById('modalMutId');
    const elActor = document.getElementById('modalActor');
    const elTime = document.getElementById('modalTime');
    const elMod = document.getElementById('modalModule');
    const elRec = document.getElementById('modalRecord');
    const elFldOld = document.getElementById('modalFieldOldLabel');
    const elFldNew = document.getElementById('modalFieldNewLabel');
    const elOldV = document.getElementById('modalOldValue');
    const elNewV = document.getElementById('modalNewValue');
    const elReason = document.getElementById('modalReason');
    const elOldJ = document.getElementById('modalOldJson');
    const elNewJ = document.getElementById('modalNewJson');

    if (elId) elId.innerText = id;
    if (elActor) elActor.innerText = actor;
    if (elTime) elTime.innerText = time;
    if (elMod) elMod.innerText = mod;
    if (elRec) elRec.innerText = record;

    if (elFldOld) elFldOld.innerText = `${field}:`;
    if (elFldNew) elFldNew.innerText = `${field}:`;

    if (elOldV) elOldV.innerText = oldVal;
    if (elNewV) {
      elNewV.innerText = newVal;
      if (newVal !== 'Success') {
        elNewV.className = 'text-xs font-bold text-rose-800 bg-rose-50 border border-rose-100 px-2 py-0.5 rounded';
      } else {
        elNewV.className = 'text-xs font-bold text-emerald-800 bg-emerald-50 border border-emerald-100 px-2 py-0.5 rounded';
      }
    }
    
    if (elReason) elReason.innerText = reason;
    if (elOldJ) elOldJ.textContent = oldJson;
    if (elNewJ) elNewJ.textContent = newJson;

    const modal = document.getElementById('mutationDetailsModal');
    const card = document.getElementById('modalCard');
    if (modal && card) {
      modal.classList.remove('hidden');
      setTimeout(() => {
        card.classList.remove('scale-95', 'opacity-0');
        card.classList.add('scale-100', 'opacity-100');
      }, 10);
    }
  },

  closeMutationModal() {
    const modal = document.getElementById('mutationDetailsModal');
    const card = document.getElementById('modalCard');
    if (modal && card) {
      card.classList.remove('scale-100', 'opacity-100');
      card.classList.add('scale-95', 'opacity-0');
      setTimeout(() => {
        modal.classList.add('hidden');
      }, 150);
    }
  }
};
