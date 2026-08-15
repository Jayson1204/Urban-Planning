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

    const matches = Array.from(barangayIndex.values())
      .filter(b => b.name.toLowerCase().includes(term))
      .slice(0, 8);

    if (!matches.length) {
      resultsBox.innerHTML = '<div class="px-3 py-2 text-xs text-slate-400">No matching barangay.</div>';
      resultsBox.classList.remove('hidden');
      return;
    }

    resultsBox.innerHTML = matches.map(b => `
      <button type="button" data-psgc="${b.psgc_code}" class="map-search-result w-full text-left px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-brand-light transition">
        ${b.name}
      </button>
    `).join('');
    resultsBox.classList.remove('hidden');

    resultsBox.querySelectorAll('.map-search-result').forEach(btn => {
      btn.addEventListener('click', () => {
        searchInput.value = btn.textContent.trim();
        resultsBox.classList.add('hidden');
        zoomToBarangay(btn.dataset.psgc);
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

  document.getElementById('layerHousingProjects').addEventListener('change', (e) => {
    if (e.target.checked) {
      barangayMap.addLayer(housingMarkersLayer);
    } else {
      barangayMap.removeLayer(housingMarkersLayer);
    }
  });
}

async function initBarangayMappingModule() {
  initBarangayMap();
  initMappingEvents();
  await loadBarangayIndex();
  await loadBoundaryLayer();
  await loadHousingMarkers();
}
