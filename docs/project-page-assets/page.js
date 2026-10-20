const { document } = globalThis;
const filter = document.querySelector('.filter');
const input = document.querySelector('#project-search');
const items = [...document.querySelectorAll('#project-list li')];
const count = document.querySelector('#project-count');
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
