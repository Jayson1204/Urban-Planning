// Global State
let beneficiariesData = [];
let beneficiariesPagination = { page: 1, per_page: 10, total: 0, total_pages: 1 };
let selectedResidentId = null;
let selectedBenUnitId = null;

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

async function fetchBeneficiaries(page = 1) {
  const search = document.getElementById('benSearchInput').value.trim();
  const status = document.getElementById('benStatusFilter').value;
  const category = document.getElementById('benCategoryFilter').value;
  const recordStatus = document.getElementById('benRecordStatusFilter').value;
  const sort = document.getElementById('benSortFilter').value;

  const params = new URLSearchParams({ page, per_page: beneficiariesPagination.per_page });
  if (search) params.set('search', search);
  if (status) params.set('beneficiary_status', status);
  if (category) params.set('category', category);
  if (recordStatus) params.set('status', recordStatus);
  if (sort) params.set('sort', sort);

  try {
    const response = await fetch(`../../api/employee/housing-beneficiaries.php?${params.toString()}`);
    const result = await response.json();

    if (result.status === 'success') {
      beneficiariesData = result.data || [];
      beneficiariesPagination = {
        page: result.page,
        per_page: result.per_page,
        total: result.total,
        total_pages: result.total_pages,
      };
      renderBeneficiaries();
      renderBenPagination();
    } else {
      showToast(result.message || 'Error loading beneficiaries.');
    }
  } catch (err) {
    console.error('Error fetching beneficiaries:', err);
    showToast('Network error while loading beneficiaries.');
  }
}

async function fetchBeneficiaryStats() {
  try {
    const response = await fetch('../../api/employee/housing-beneficiaries.php?action=stats');
    const result = await response.json();
    if (result.status === 'success') {
      const s = result.data;
      document.getElementById('statTotalBen').innerText = s.total || 0;
      document.getElementById('statApplicantsBen').innerText = s.applicants || 0;
      document.getElementById('statAwardedBen').innerText = s.awarded || 0;
      document.getElementById('statDisqualifiedBen').innerText = s.disqualified || 0;
    }
  } catch (err) {
    console.error('Error fetching beneficiary stats:', err);
  }
}

let isSavingBeneficiary = false;

async function handleSaveBeneficiary(e) {
  e.preventDefault();

  if (isSavingBeneficiary) return;
  isSavingBeneficiary = true;
  const saveBtn = document.getElementById('beneficiarySaveBtn');
  const originalBtnText = saveBtn ? saveBtn.innerText : '';
  if (saveBtn) {
    saveBtn.disabled = true;
    saveBtn.innerText = 'Saving...';
  }

  const idRef = document.getElementById('beneficiaryIdRef').value;
  const householdId = document.getElementById('benHouseholdId').value;
  const payload = {
    resident_id: selectedResidentId ? parseInt(selectedResidentId) : null,
    household_id: householdId ? parseInt(householdId) : null,
    unit_id: selectedBenUnitId ? parseInt(selectedBenUnitId) : null,
    beneficiary_status: document.getElementById('benStatus').value,
    category: document.getElementById('benCategory').value,
    application_date: document.getElementById('benApplicationDate').value || null,
    award_date: document.getElementById('benAwardDate').value || null,
    monthly_income: document.getElementById('benMonthlyIncome').value || null,
    family_size: document.getElementById('benFamilySize').value || null,
    amortization_status: document.getElementById('benAmortizationStatus').value,
    remarks: document.getElementById('benRemarks').value.trim(),
  };

  const isEdit = idRef !== '';
  if (isEdit) payload.beneficiary_id = parseInt(idRef);

  try {
    const response = await fetch('../../api/employee/housing-beneficiaries.php', {
      method: isEdit ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Beneficiary saved successfully.');
      closeModal('beneficiaryModal');
      await fetchBeneficiaries(beneficiariesPagination.page);
      await fetchBeneficiaryStats();
    } else {
      showToast(result.message || 'Failed to save beneficiary.');
    }
  } catch (err) {
    console.error('Error saving beneficiary:', err);
    showToast('Failed to save beneficiary.');
  } finally {
    isSavingBeneficiary = false;
    if (saveBtn) {
      saveBtn.disabled = false;
      saveBtn.innerText = originalBtnText;
    }
  }
}

async function handleToggleBeneficiaryStatus(beneficiaryId, newStatus) {
  try {
    const response = await fetch(`../../api/employee/housing-beneficiaries.php?id=${beneficiaryId}&status=${newStatus}`, {
      method: 'DELETE'
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message);
      await fetchBeneficiaries(beneficiariesPagination.page);
      await fetchBeneficiaryStats();
    } else {
      showToast(result.message || 'Failed to update beneficiary status.');
    }
  } catch (err) {
    console.error('Error updating beneficiary status:', err);
    showToast('Failed to update beneficiary status.');
  }
}

async function fetchBeneficiaryDetail(beneficiaryId) {
  try {
    const response = await fetch(`../../api/employee/housing-beneficiaries.php?id=${beneficiaryId}`);
    const result = await response.json();
    return result.status === 'success' ? result.data : null;
  } catch (err) {
    console.error('Error fetching beneficiary detail:', err);
    return null;
  }
}

// Pickers reuse existing module endpoints (no new endpoints needed)
async function searchResidentsForPicker(term) {
  try {
    const response = await fetch(`../../api/employee/residents.php?search=${encodeURIComponent(term)}&status=Active&per_page=8`);
    const result = await response.json();
    return result.status === 'success' ? (result.data || []) : [];
  } catch (err) {
    console.error('Error searching residents:', err);
    return [];
  }
}

async function searchUnitsForPicker(term) {
  try {
    const response = await fetch(`../../api/employee/housing-units.php?search=${encodeURIComponent(term)}&status=Active&per_page=8`);
    const result = await response.json();
    return result.status === 'success' ? (result.data || []) : [];
  } catch (err) {
    console.error('Error searching units:', err);
    return [];
  }
}
