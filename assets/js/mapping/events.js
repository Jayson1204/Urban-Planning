function initMappingEvents() {
  const searchInput = document.getElementById('mapBarangaySearch');
  const resultsBox = document.getElementById('mapSearchResults');

  searchInput.addEventListener('input', () => {
    const term = searchInput.value.trim().toLowerCase();
    resultsBox.innerHTML = '';

    if (!term) {
      resultsBox.classList.add('hidden');
      return;
    }

    const barangayMatches = Array.from(barangayIndex.values())
      .filter(b => b.name.toLowerCase().includes(term))
      .slice(0, 5)
      .map(b => ({ kind: 'barangay', key: b.psgc_code, label: b.name, sub: 'Barangay' }));

    const subdivisionMatches = Array.from(subdivisionIndex.values())
      .filter(s => s.name.toLowerCase().includes(term))
      .slice(0, 5)
      .map(s => ({ kind: 'subdivision', key: s.subdivision_id, label: s.name, sub: 'Subdivision' }));

    const housingProjectMatches = Array.from(housingProjectIndex.values())
      .filter(p => p.name.toLowerCase().includes(term))
      .slice(0, 5)
      .map(p => ({ kind: 'housing-project', key: p.housing_project_id, label: p.name, sub: 'Housing Project' }));

    const matches = [...barangayMatches, ...subdivisionMatches, ...housingProjectMatches].slice(0, 10);

    if (!matches.length) {
      resultsBox.innerHTML = '<div class="px-3 py-2 text-xs text-slate-400">No matching location.</div>';
      resultsBox.classList.remove('hidden');
      return;
    }

    resultsBox.innerHTML = matches.map(m => `
      <button type="button" data-kind="${m.kind}" data-key="${m.key}" class="map-search-result w-full text-left px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-brand-light transition flex items-center justify-between gap-2">
        <span>${m.label}</span>
        <span class="text-[9px] font-black uppercase text-slate-400">${m.sub}</span>
      </button>
    `).join('');
    resultsBox.classList.remove('hidden');

    resultsBox.querySelectorAll('.map-search-result').forEach(btn => {
      btn.addEventListener('click', () => {
        searchInput.value = btn.querySelector('span').textContent.trim();
        resultsBox.classList.add('hidden');
        if (btn.dataset.kind === 'barangay') zoomToBarangay(btn.dataset.key);
        else if (btn.dataset.kind === 'subdivision') zoomToSubdivision(btn.dataset.key);
        else if (btn.dataset.kind === 'housing-project') zoomToHousingProject(btn.dataset.key);
      });
    });
  });

  document.addEventListener('click', (e) => {
    if (!resultsBox.contains(e.target) && e.target !== searchInput) {
      resultsBox.classList.add('hidden');
    }
  });

  document.getElementById('layerBoundaries').addEventListener('change', (e) => {
    if (e.target.checked) {
      barangayMap.addLayer(boundariesLayer);
    } else {
      barangayMap.removeLayer(boundariesLayer);
    }
  });

  document.getElementById('layerHousingUnits').addEventListener('change', (e) => {
    if (e.target.checked) {
      barangayMap.addLayer(housingMarkersLayer);
    } else {
      barangayMap.removeLayer(housingMarkersLayer);
    }
  });

  document.getElementById('layerSubdivisions').addEventListener('change', updateResidentialVisibility);
  document.getElementById('layerHousingProjects').addEventListener('change', updateResidentialVisibility);
  document.getElementById('layerBuildings').addEventListener('change', scheduleBuildingFetch);
}

async function initBarangayMappingModule() {
  try {
    initBarangayMap();
    initMappingEvents();
    await loadBarangayIndex();
    await loadBoundaryLayer();
    await loadHousingMarkers();
    await loadSubdivisionsLayer();
    await loadHousingProjectsLayer();
    updateResidentialVisibility();
    document.getElementById('statBuildingCount').innerText = await fetchBuildingStats();
  } catch (error) {
    console.error('Barangay map failed to initialize:', error);
    const container = document.getElementById('barangayMap');
    const skeleton = container.querySelector('.skeleton-loader');
    if (skeleton) {
      skeleton.innerText = `Map failed to load: ${error.message}`;
      skeleton.classList.add('text-red-500');
    }
  }
}
