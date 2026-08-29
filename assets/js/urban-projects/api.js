// Global State
let projectsData = [];
let projectsPagination = { page: 1, per_page: 10, total: 0, total_pages: 1 };
let selectedProjectPlanId = null;

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

async function fetchProjects(page = 1) {
  const search = document.getElementById('projectSearchInput').value.trim();
  const type = document.getElementById('projectTypeFilter').value;
  const projectStatus = document.getElementById('projectStatusFilter').value;
  const rowStatus = document.getElementById('projectRowStatusFilter').value;

  const params = new URLSearchParams({ page, per_page: projectsPagination.per_page });
  if (search) params.set('search', search);
  if (type) params.set('project_type', type);
  if (projectStatus) params.set('project_status', projectStatus);
  if (rowStatus) params.set('status', rowStatus);

  try {
    const response = await fetch(`../../api/employee/urban-projects.php?${params.toString()}`);
    const result = await response.json();

    if (result.status === 'success') {
      projectsData = result.data || [];
      projectsPagination = {
        page: result.page,
        per_page: result.per_page,
        total: result.total,
        total_pages: result.total_pages,
      };
      renderProjects();
      renderProjectsPagination();
    } else {
      showToast(result.message || 'Error loading urban projects.');
    }
  } catch (err) {
    console.error('Error fetching urban projects:', err);
    showToast('Network error while loading urban projects.');
  }
}

async function fetchProjectStats() {
  try {
    const response = await fetch('../../api/employee/urban-projects.php?action=stats');
    const result = await response.json();
    if (result.status === 'success') {
      const s = result.data;
      document.getElementById('statTotalProjects').innerText = s.total || 0;
      document.getElementById('statPlannedProjects').innerText = s.planned || 0;
      document.getElementById('statOngoingProjects').innerText = s.ongoing || 0;
      document.getElementById('statCompletedProjects').innerText = s.completed || 0;
    }
  } catch (err) {
    console.error('Error fetching project stats:', err);
  }
}

let isSavingProject = false;

async function handleSaveProject(e) {
  e.preventDefault();

  if (isSavingProject) return;
  isSavingProject = true;
  const saveBtn = document.getElementById('projectSaveBtn');
  const originalBtnText = saveBtn ? saveBtn.innerText : '';
  if (saveBtn) {
    saveBtn.disabled = true;
    saveBtn.innerText = 'Saving...';
  }

  const idRef = document.getElementById('projectIdRef').value;
  const payload = {
    plan_id: selectedProjectPlanId ? parseInt(selectedProjectPlanId) : null,
    project_code: document.getElementById('projectCode').value.trim(),
    project_title: document.getElementById('projectTitle').value.trim(),
    project_type: document.getElementById('projectType').value,
    project_status: document.getElementById('projectStatus').value,
    barangay: document.getElementById('projectBarangay').value.trim(),
    coverage_area: document.getElementById('projectCoverageArea').value.trim(),
    contractor: document.getElementById('projectContractor').value.trim(),
    budget: document.getElementById('projectBudget').value || null,
    start_date: document.getElementById('projectStartDate').value || null,
    target_completion_date: document.getElementById('projectTargetDate').value || null,
    actual_completion_date: document.getElementById('projectActualDate').value || null,
    description: document.getElementById('projectDescription').value.trim(),
  };

  const isEdit = idRef !== '';
  if (isEdit) payload.project_id = parseInt(idRef);

  try {
    const response = await fetch('../../api/employee/urban-projects.php', {
      method: isEdit ? 'PUT' : 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message || 'Urban project saved successfully.');
      closeModal('projectModal');
      await fetchProjects(projectsPagination.page);
      await fetchProjectStats();
    } else {
      showToast(result.message || 'Failed to save urban project.');
    }
  } catch (err) {
    console.error('Error saving urban project:', err);
    showToast('Failed to save urban project.');
  } finally {
    isSavingProject = false;
    if (saveBtn) {
      saveBtn.disabled = false;
      saveBtn.innerText = originalBtnText;
    }
  }
}

async function handleToggleProjectStatus(projectId, newStatus) {
  try {
    const response = await fetch(`../../api/employee/urban-projects.php?id=${projectId}&status=${newStatus}`, {
      method: 'DELETE'
    });
    const result = await response.json();
    if (result.status === 'success') {
      showToast(result.message);
      await fetchProjects(projectsPagination.page);
      await fetchProjectStats();
    } else {
      showToast(result.message || 'Failed to update project status.');
    }
  } catch (err) {
    console.error('Error updating project status:', err);
    showToast('Failed to update project status.');
  }
}

async function fetchProjectDetail(projectId) {
  try {
    const response = await fetch(`../../api/employee/urban-projects.php?id=${projectId}`);
    const result = await response.json();
    return result.status === 'success' ? result.data : null;
  } catch (err) {
    console.error('Error fetching project detail:', err);
    return null;
  }
}

// Picker reuses the existing development-plans endpoint (no new endpoint needed)
async function searchPlansForPicker(term) {
  try {
    const response = await fetch(`../../api/employee/development-plans.php?search=${encodeURIComponent(term)}&status=Active&per_page=8`);
    const result = await response.json();
    return result.status === 'success' ? (result.data || []) : [];
  } catch (err) {
    console.error('Error searching development plans:', err);
    return [];
  }
}
