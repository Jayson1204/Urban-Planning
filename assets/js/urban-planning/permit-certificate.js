(async function () {
  const id = window.civentralPermitApplicationId;
  const loading = document.getElementById('paCertLoading');
  const notReady = document.getElementById('paCertNotReady');
  const certificate = document.getElementById('paCertificate');
  const notice = document.getElementById('paNotice');

  if (!id) {
    loading.classList.add('hidden');
    notReady.classList.remove('hidden');
    return;
  }

  try {
    const response = await fetch(`../../api/employee/permit-applications.php?id=${id}`);
    const result = await response.json();
    loading.classList.add('hidden');

    if (result.status !== 'success') {
      notReady.classList.remove('hidden');
      return;
    }

    const pa = result.data;

    if (pa.application_status === 'Permit Issued') {
      document.getElementById('certTitle').innerText = pa.application_type === 'Subdivision Plan' ? 'Subdivision Development Permit' : 'Building Permit';
      document.getElementById('certPermitNumber').innerText = pa.permit_number || '—';
      document.getElementById('certReference').innerText = pa.reference_number || '—';
      document.getElementById('certApplicant').innerText = pa.applicant_name || '—';
      document.getElementById('certProject').innerText = pa.project_name || '—';
      document.getElementById('certLocation').innerText = [pa.street_address, pa.barangay].filter(Boolean).join(', ') || '—';
      document.getElementById('certIssuedDate').innerText = pa.issued_date || '—';
      document.getElementById('certExpiryDate').innerText = pa.expiry_date || 'No expiry recorded';
      document.getElementById('certConditions').innerText = pa.conditions_of_approval && pa.conditions_of_approval.trim()
        ? pa.conditions_of_approval
        : 'No specific conditions recorded.';
      certificate.classList.remove('hidden');
    } else if (pa.application_status === 'Denied') {
      document.getElementById('noticeReference').innerText = pa.reference_number || '—';
      document.getElementById('noticeApplicant').innerText = pa.applicant_name || '—';
      document.getElementById('noticeProject').innerText = pa.project_name || '—';
      const lastDenial = (pa.reviews || []).filter(r => r.action === 'Denied').pop();
      document.getElementById('noticeFindings').innerText = lastDenial && lastDenial.remarks
        ? lastDenial.remarks
        : 'No specific findings were recorded.';
      notice.classList.remove('hidden');
    } else {
      notReady.classList.remove('hidden');
    }
  } catch (err) {
    console.error('Error loading permit document:', err);
    loading.classList.add('hidden');
    notReady.classList.remove('hidden');
  }
})();
