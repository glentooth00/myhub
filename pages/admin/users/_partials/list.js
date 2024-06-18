F1.deferred.push(function initUsersListPageView(app) {

  console.log('initUsersListPageView()');

  const Utils = F1.lib.Utils;

  function dataTableHasNonDefaultState(dataTable) {
    const state = dataTable.state.loaded();
    if (!state) return false;
    const isSorted = state.order.length > 0;
    const isSearched = state.search.search.length > 0;
    const isLengthChanged = state.length !== defaultPageLength;
    const statusChanged = status !== defaultStatus;
    return isSorted || isSearched || isLengthChanged || statusChanged;
  }


  /* table */
  const table = Utils.getEl('users-table');
  table.onclick = function(event) {
    console.log('table.onclick:', event);
    if (event.target.tagName.toLowerCase() === 'button') return;
    const row = event.target.closest('tr');
    if (!row || !row.id || row.parentElement.tagName.toLowerCase() !== 'tbody') return;
    app.navigateTo({url:'admin/users?view=profile&id='+row.id, view:'profile', title:'User Profile'});
  };

  // DataTable Columns Configuration
  let totalAmountSubmitted = 0;
  const aoColumns = [
    null,                     // ID
    null,                     // Status
    null,                     // First Name
    null,                     // Last Name
    null,                     // Role
    null,                     // Username
    null,                     // Email
    null,                     // Failed Logins
    { 'sType': 'date' },      // Last Login At
    { 'sType': 'date' },      // Created At
  ];

  const defaultPageLength = 10;

  // Initialize DataTable with pagination options and saved pageLength value
  const dataTable = jQuery(table).DataTable({
    order: [],
    aoColumns,
    defaultPageLength,
    lengthMenu: [[10, 16, 25, 50, 100, 250, 500, -1], 
      [10, 16, 25, 50, 100, 250, 500, 'All']],
    stateDuration: -1,
    stateSave: true,
  });

  const tableBody = table.querySelector('tbody');
  tableBody.classList.remove('hidden');

  const customFiltersWrapper = Utils.newEl('div', 'custom-filters-wrapper');

  const url = new URL(window.location.href);

  const state = dataTable.state.loaded();
  console.log('dataTable state:', state);

  const defaultStatus = 'active';
  const status = url.searchParams.get('status') || table.dataset.status;
  const statusFilter = Utils.newEl('label', 'status-filter');
  const statusFilterControl = Utils.newEl('select', 'form-control');
  statusFilterControl.name = 'status_filter';
  statusFilterControl.innerHTML = `
    <option value="all">All</option>
    <option value="active">Active</option>
    <option value="inactive">Inactive</option>
  `;
  statusFilterControl.value = status || defaultStatus;
  statusFilterControl.addEventListener('change', function() {
    const status = statusFilterControl.value;
    url.searchParams.set('status', status);
    const replaceState = true; // Don't add a new state to the history
    const newState = Object.assign({}, app.currentPage.state(), { url: url.toString() });
    app.navigateTo(newState, 'fwd', null, replaceState);
  });  

  let hasNonDefaultState = dataTableHasNonDefaultState(dataTable);

  const resetButtonClass = 'btn btn-sm btn-outline btn-clear-state' + ( hasNonDefaultState ? '' : ' hidden' );
  const resetButton = Utils.newEl('button', resetButtonClass);
  resetButton.dataset.tippyContent = 'Reset table filters/sort';
  resetButton.innerHTML = '<span class="fa fa-eraser"></span>';
  resetButton.addEventListener('click', function() {
    dataTable.state.clear();
    url.searchParams.set('status', defaultStatus);
    const replaceState = true; // Don't add a new state to the history
    const newState = Object.assign({}, app.currentPage.state(), { url: url.toString() });
    app.navigateTo(newState, 'fwd', null, replaceState);
  });  

  if (state) {
    const isSorted = state.order.length > 0;
    const isSearched = state.search.search.length > 0;
    const isLengthChanged = state.length !== defaultPageLength;
    hasNonDefaultState = isSorted || isSearched || isLengthChanged;
  }

  statusFilter.appendChild(statusFilterControl);
  customFiltersWrapper.appendChild(statusFilter);
  customFiltersWrapper.appendChild(resetButton);

  const searchControl = Utils.getEl('users-table_filter');
  //insert custom filters after the search control
  searchControl.parentNode.insertBefore(customFiltersWrapper, searchControl);

  // On data tabe sort click, show the reset button
  dataTable.on('order.dt', () => resetButton.classList.remove('hidden'));

  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');  

  
  /* bottom nav */
  Utils.removeClassFrom(app.el.bottomBar, 'active');


  tippy('[data-tippy-content]', { allowHTML: true });  

}); // END: initUsersListPageView()