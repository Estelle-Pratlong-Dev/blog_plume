/* =============================================================
   Filtres par rubrique et tri accessible du tableau d’administration
   ============================================================= */
(() => {
  const table = document.querySelector('#admin-texts-table');
  const filters = document.querySelector('.admin-category-filters');
  const status = document.querySelector('.admin-table-status');
  if (!table || !status) return;

  const body = table.tBodies[0];
  const rows = Array.from(body.rows);
  const buttons = Array.from(filters?.querySelectorAll('button') ?? []);
  const headers = Array.from(table.querySelectorAll('th[data-sort]'));
  const collator = new Intl.Collator('fr', { numeric: true, sensitivity: 'base' });
  let category = '';
  let column = null;
  let ascending = true;

  const update = () => {
    let visible = 0;
    rows.forEach((row) => {
      row.hidden = category !== '' && row.dataset.category !== category;
      if (!row.hidden) visible += 1;
    });
    status.textContent = visible === 0 ? 'Aucun texte dans cette rubrique.'
      : `${visible} texte${visible > 1 ? 's' : ''} affiché${visible > 1 ? 's' : ''}.`;
  };

  buttons.forEach((button) => {
    const count = rows.filter((row) => button.dataset.category === ''
      || row.dataset.category === button.dataset.category).length;
    button.querySelector('[data-filter-count]').textContent = `(${count})`;
    button.addEventListener('click', () => {
      category = button.dataset.category;
      buttons.forEach((item) => item.setAttribute('aria-pressed', String(item === button)));
      update();
    });
  });

  headers.forEach((header) => {
    const label = header.textContent.trim();
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'admin-sort-button';
    button.textContent = label;
    const indicator = document.createElement('span');
    indicator.setAttribute('aria-hidden', 'true');
    indicator.textContent = ' ↕';
    button.append(indicator);
    button.setAttribute('aria-label', `Trier par ${label}, ordre croissant`);
    header.replaceChildren(button);
    button.addEventListener('click', () => {
      const selected = Number(header.dataset.sort);
      ascending = column === selected ? !ascending : true;
      column = selected;
      rows.sort((a, b) => collator.compare(a.cells[column].dataset.sortValue ?? a.cells[column].textContent.trim(),
        b.cells[column].dataset.sortValue ?? b.cells[column].textContent.trim()) * (ascending ? 1 : -1));
      body.append(...rows);
      headers.forEach((item) => {
        const active = item === header;
        if (active) item.setAttribute('aria-sort', ascending ? 'ascending' : 'descending');
        else item.removeAttribute('aria-sort');
        item.querySelector('span').textContent = active ? (ascending ? ' ↑' : ' ↓') : ' ↕';
        item.querySelector('button').setAttribute('aria-label',
          `Trier par ${item.querySelector('button').firstChild.textContent.trim()}, ordre ${active && ascending ? 'décroissant' : 'croissant'}`);
      });
      update();
    });
  });

  if (filters) {
    filters.hidden = false;
    status.hidden = false;
  }
  // Ouvrir la liste du plus ancien au plus récent, toutes rubriques confondues.
  headers.find((header) => header.dataset.sort === '4')?.querySelector('button').click();
  update();
})();
