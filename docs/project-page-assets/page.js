const pageDocument = globalThis.document;
const filter = pageDocument.querySelector('.filter');
const input = pageDocument.querySelector('#project-search');
const items = [...pageDocument.querySelectorAll('#project-list li')];
const count = pageDocument.querySelector('#project-count');
if (filter && input && count) {
  filter.hidden = false;
  const update = () => {
    const query = input.value.trim().toLocaleLowerCase('en');
    let visible = 0;
    for (const item of items) {
      item.hidden = !item.textContent.toLocaleLowerCase('en').includes(query);
      if (!item.hidden) visible += 1;
    }
    count.textContent = visible === 0 ? 'No matching projects. Clear the search to show all projects.' : `${visible} ${visible === 1 ? 'project' : 'projects'} shown`;
  };
  input.addEventListener('input', update);
  update();
}
