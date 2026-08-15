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

async function fetchSubjectHistory(subjectType, subjectId, siteLabel) {
  const params = new URLSearchParams({ action: 'history', subject_type: subjectType });
  if (subjectType === 'Site') {
    params.set('site_label', siteLabel);
  } else {
    params.set('subject_id', subjectId);
  }

  try {
    const response = await fetch(`../../api/employee/field-survey-assignments.php?${params.toString()}`);
    const result = await response.json();
    if (result.status === 'success') {
      renderHistoryTimeline(result.data || []);
    } else {
      showToast(result.message || 'Error loading survey history.');
    }
  } catch (err) {
    console.error('Error fetching survey history:', err);
    showToast('Network error while loading survey history.');
  }
}

// Pickers reuse existing module endpoints (no new endpoints needed)
async function searchResidentsForHistoryPicker(term) {
  try {
    const response = await fetch(`../../api/employee/residents.php?search=${encodeURIComponent(term)}&status=Active&per_page=8`);
    const result = await response.json();
    return result.status === 'success' ? (result.data || []) : [];
  } catch (err) {
    console.error('Error searching residents:', err);
    return [];
  }
}

async function searchHouseholdsForHistoryPicker(term) {
  try {
    const response = await fetch(`../../api/employee/households.php?search=${encodeURIComponent(term)}`);
    const result = await response.json();
    return result.status === 'success' ? (result.data || []) : [];
  } catch (err) {
    console.error('Error searching households:', err);
    return [];
  }
}
