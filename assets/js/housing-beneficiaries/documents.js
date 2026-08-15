// Beneficiary application documents: rendered inside the View Beneficiary modal.
// Citizens submit these via the future mobile app; staff review/approve/reject here,
// and can upload on an applicant's behalf until that mobile client exists.

let currentBenDocBeneficiaryId = null;

function benDocStatusBadge(status) {
  const map = {
    Pending: 'bg-amber-50 text-amber-700 border-amber-150',
    Verified: 'bg-emerald-50 text-emerald-700 border-emerald-150',
    Rejected: 'bg-rose-50 text-rose-700 border-rose-150',
  };
  return map[status] || 'bg-slate-50 text-slate-500 border-slate-200';
}

function renderBenDocuments(beneficiary) {
  const container = document.getElementById('viewBenDocuments');
  if (!container) return;

  const docs = beneficiary.documents || [];
  document.getElementById('uploadBenBeneficiaryId').value = beneficiary.beneficiary_id;
  document.getElementById('benDocumentUploadForm').classList.add('hidden');
  currentBenDocBeneficiaryId = beneficiary.beneficiary_id;

  if (!docs.length) {
    container.innerHTML = '<div class="px-4 py-3 text-slate-400">No documents submitted yet.</div>';
    return;
  }

  const canReview = window.benCanReviewDocuments === true;

  container.innerHTML = docs.map(d => `
    <div class="px-4 py-3 flex items-start justify-between gap-3">
      <div class="min-w-0 space-y-1">
        <div class="flex items-center gap-2 flex-wrap">
          <span class="font-semibold text-slate-700">${d.document_type}</span>
          <span class="text-[9px] font-extrabold px-2 py-0.5 rounded-full border ${benDocStatusBadge(d.review_status)}">${d.review_status}</span>
          <span class="text-[9px] font-bold text-slate-400 uppercase">${d.submitted_by === 'Citizen' ? 'Submitted by applicant' : 'Uploaded by staff'}</span>
        </div>
        <a href="../../${d.file_path}" target="_blank" rel="noopener" class="text-[11px] text-brand-dark hover:underline break-all">${d.file_name}</a>
        ${d.review_status === 'Rejected' && d.review_notes ? `<p class="text-[10px] text-rose-600 leading-relaxed">Reason: ${d.review_notes}</p>` : ''}
        ${d.reviewed_by_name ? `<p class="text-[9px] text-slate-400">Reviewed by ${d.reviewed_by_name}${d.reviewed_at ? ' on ' + d.reviewed_at.substring(0, 10) : ''}</p>` : ''}
      </div>
      <div class="flex items-center gap-1.5 shrink-0">
        ${canReview && d.review_status === 'Pending' ? `
          <button onclick="handleApproveBenDocument(${d.document_id})" class="text-emerald-600 hover:bg-emerald-50 p-1.5 rounded-lg border border-transparent hover:border-emerald-150 transition cursor-pointer" title="Approve document">
            <i class="fa-solid fa-check text-xs"></i>
          </button>
          <button onclick="openRejectBenDocumentModal(${d.document_id})" class="text-rose-600 hover:bg-rose-50 p-1.5 rounded-lg border border-transparent hover:border-rose-150 transition cursor-pointer" title="Reject document">
            <i class="fa-solid fa-xmark text-xs"></i>
          </button>
        ` : ''}
        <button onclick="handleDeleteBenDocument(${d.document_id})" class="text-slate-400 hover:text-red-500 cursor-pointer p-1.5" title="Delete document">
          <i class="fa-solid fa-trash-can text-xs"></i>
        </button>
      </div>
    </div>
  `).join('');
}

function openBenDocumentUpload() {
  document.getElementById('benDocumentUploadForm').classList.remove('hidden');
}

async function handleUploadBenDocument(e) {
  e.preventDefault();

  const beneficiaryId = document.getElementById('uploadBenBeneficiaryId').value;
  const documentType = document.getElementById('benDocumentType').value;
  const fileInput = document.getElementById('benDocumentFile');

  if (!fileInput.files.length) return;

  const formData = new FormData();
  formData.append('beneficiary_id', beneficiaryId);
  formData.append('document_type', documentType);
  formData.append('file', fileInput.files[0]);

  try {
    const response = await fetch('../../api/employee/beneficiary-documents.php', {
      method: 'POST',
      body: formData
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Document uploaded.');
      document.getElementById('benDocumentUploadForm').reset();
      document.getElementById('benDocumentUploadForm').classList.add('hidden');
      await openViewBeneficiaryModal(beneficiaryId);
    } else {
      showToast(result.message || 'Failed to upload document.');
    }
  } catch (err) {
    console.error('Error uploading beneficiary document:', err);
    showToast('Failed to upload document.');
  }
}

async function handleApproveBenDocument(documentId) {
  try {
    const response = await fetch('../../api/employee/beneficiary-documents.php', {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ document_id: documentId, review_status: 'Verified' })
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Document approved.');
      await openViewBeneficiaryModal(currentBenDocBeneficiaryId);
    } else {
      showToast(result.message || 'Failed to approve document.');
    }
  } catch (err) {
    console.error('Error approving beneficiary document:', err);
    showToast('Failed to approve document.');
  }
}

function openRejectBenDocumentModal(documentId) {
  document.getElementById('rejectBenDocumentId').value = documentId;
  document.getElementById('rejectBenDocumentReason').value = '';
  openModal('rejectBenDocumentModal');
}

async function handleConfirmRejectBenDocument(e) {
  e.preventDefault();
  const documentId = document.getElementById('rejectBenDocumentId').value;
  const reason = document.getElementById('rejectBenDocumentReason').value.trim();

  try {
    const response = await fetch('../../api/employee/beneficiary-documents.php', {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ document_id: documentId, review_status: 'Rejected', review_notes: reason })
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Document rejected.');
      closeModal('rejectBenDocumentModal');
      await openViewBeneficiaryModal(currentBenDocBeneficiaryId);
    } else {
      showToast(result.message || 'Failed to reject document.');
    }
  } catch (err) {
    console.error('Error rejecting beneficiary document:', err);
    showToast('Failed to reject document.');
  }
}

async function handleDeleteBenDocument(documentId) {
  try {
    const response = await fetch(`../../api/employee/beneficiary-documents.php?id=${documentId}`, {
      method: 'DELETE'
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Document deleted.');
      await openViewBeneficiaryModal(currentBenDocBeneficiaryId);
    } else {
      showToast(result.message || 'Failed to delete document.');
    }
  } catch (err) {
    console.error('Error deleting beneficiary document:', err);
    showToast('Failed to delete document.');
  }
}
