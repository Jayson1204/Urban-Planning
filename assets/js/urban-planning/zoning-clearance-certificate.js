(async function () {
  const id = window.civentralZoningClearanceId;
  const loading = document.getElementById('zcCertLoading');
  const notReady = document.getElementById('zcCertNotReady');
  const certificate = document.getElementById('zcCertificate');
  const notice = document.getElementById('zcNotice');

  if (!id) {
    loading.classList.add('hidden');
    notReady.classList.remove('hidden');
    return;
  }

  try {
    const response = await fetch(`../../api/employee/zoning-clearances.php?id=${id}`);
    const result = await response.json();
    loading.classList.add('hidden');

    if (result.status !== 'success') {
      notReady.classList.remove('hidden');
      return;
    }

    const zc = result.data;

    if (zc.clearance_status === 'Approved') {
      document.getElementById('certReference').innerText = zc.reference_number || '—';
      document.getElementById('certApplicant').innerText = zc.applicant_name || '—';
      document.getElementById('certZone').innerText = zc.zone_classification || '—';
      document.getElementById('certUse').innerText = zc.use_category || '—';
      document.getElementById('certLocation').innerText = [zc.street_address, zc.barangay].filter(Boolean).join(', ') || '—';
      document.getElementById('certApprovedDate').innerText = zc.approved_date || '—';
      document.getElementById('certVerificationCode').innerText = zc.verification_code || '—';
      certificate.classList.remove('hidden');
    } else if (zc.clearance_status === 'Denied') {
      document.getElementById('noticeReference').innerText = zc.reference_number || '—';
      document.getElementById('noticeApplicant').innerText = zc.applicant_name || '—';
      document.getElementById('noticeZone').innerText = zc.zone_classification || '—';
      document.getElementById('noticeUse').innerText = zc.use_category || '—';
      document.getElementById('noticeFindings').innerText = zc.conformity_notes && zc.conformity_notes.trim()
        ? zc.conformity_notes
        : 'No specific findings were recorded.';
      notice.classList.remove('hidden');
    } else {
      notReady.classList.remove('hidden');
    }
  } catch (err) {
    console.error('Error loading zoning clearance certificate:', err);
    loading.classList.add('hidden');
    notReady.classList.remove('hidden');
  }
})();
