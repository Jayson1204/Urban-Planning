// DOM Elements
const deptsTbody = document.getElementById('deptsTableBody');
const searchInput = document.getElementById('deptSearchInput');

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

// Export CSV
function exportDeptsCsv() {
  const query = searchInput.value.toLowerCase().trim();

  let csvContent = "data:text/csv;charset=utf-8,";
  csvContent += "Dept Code,Department Name,Department Administrator,Status,Created At\r\n";

  const filtered = departmentsData.filter(dept => {
    const adminObj = dept.users || null;
    const adminName = adminObj ? `${adminObj.first_name} ${adminObj.last_name}`.toLowerCase() : '';
    return dept.department_name.toLowerCase().includes(query) ||
           dept.department_code.toLowerCase().includes(query) ||
           adminName.includes(query);
  });

  filtered.forEach(dept => {
    const adminObj = dept.users || null;
    const adminName = adminObj ? `${adminObj.first_name} ${adminObj.last_name}` : 'Unassigned';
    const createdAt = dept.created_at ? dept.created_at.replace('T', ' ').substring(0, 19) : '';
    csvContent += `"${dept.department_code}","${dept.department_name}","${adminName}","${dept.status}","${createdAt}"\r\n`;
  });

  const encodedUri = encodeURI(csvContent);
  const link = document.createElement("a");
  link.setAttribute("href", encodedUri);
  link.setAttribute("download", `CIVENTRAL_Department_Directory_${new Date().toISOString().split('T')[0]}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  showToast(`Successfully exported ${filtered.length} department records.`);
}
