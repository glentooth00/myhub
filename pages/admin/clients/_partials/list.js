F1.deferred.push(function initClientsListPageView(app) {

  console.log('initClientsListPageView()');

  const Utils = F1.lib.Utils;  

  function calculateCurrencyColumnTotal(dataTable, columnIndex) {
    const total = dataTable.column(columnIndex, { search: 'applied' }).data().reduce((a, b) => {
      const amount = parseFloat(b.toString().replace(/[^(0-9)\-\.]/g, ''));
      return a + ((amount && typeof amount === 'number') ? amount : 0);
    }, 0);
    return total;
  }

  function updateCurrencyColumnTotals(dataTable, columnIndexes, footerElement) {
    let footerHTML = '<tr>';
    for (let i=0; i < aoColumns.length; i++) {
      let total = 0;
      if (columnIndexes.includes(i)) {
        total = calculateCurrencyColumnTotal(dataTable, i);
        footerHTML += '<th class="left nowrap">' + Utils.currency(total) + '</th>';
      } else {
        footerHTML += '<th></th>';
      }
    }
    footerHTML += '</tr>';
    footerElement.innerHTML = footerHTML;
  }

  function dataTableHasNonDefaultState(dataTable) {
    const state = dataTable.state.loaded();
    if (!state) return false;
    const isSorted = state.order.length > 0;
    const isSearched = state.search.search.length > 0;
    const isLengthChanged = state.length !== defaultPageLength;
    const accountantChanged = accountant !== defaultAccountant;
    const statusChanged = status !== defaultStatus;
    return isSorted || isSearched || isLengthChanged || accountantChanged || statusChanged;
  }


  /* table */
  const table = Utils.getEl('clients-table');
  table.onclick = function(event) {
    const targetTagName = event.target.tagName.toLowerCase();
    if ( ( targetTagName === 'i' && event.target.classList.contains('fa-file-pdf-o') ) ||
      targetTagName === 'a' ) return;
    const row = event.target.closest('tr');
    if (!row || !row.id || row.parentElement.tagName.toLowerCase() !== 'tbody') return;
    app.navigateTo({url:'admin/clients?view=details&id='+row.id, view:'details', title:'Client Details'});
  };


  // DataTable Currency Sorting
  jQuery.extend(jQuery.fn.dataTableExt.oSort, {
    'currency-pre': function(a) {
      const number = parseFloat(a.replace(/[^\d\-\.]/g, ''));
      return isNaN(number) ? -Infinity : number;
    },
    'currency-asc': function(a, b) { return a - b; },
    'currency-desc': function(a, b) { return b - a; }
  });  


  // DataTable Columns Configuration
  const aoColumns = [
    null,                    // Status
    null,                    // Client
    null,                    // Accountant
    null,                    // Tax Number
    { 'sType': 'currency' }, // SDA Mandate
    { 'sType': 'currency' }, // FIA Mandate
    { 'sType': 'currency' }, // FIA Approved
    { 'sType': 'currency' }, // FIA Available
    { 'sType': 'currency' }, // SDA Available
    { 'sType': 'date' },     // Created At
    { orderable: false }     // Statement PDF Link
  ];

  const defaultPageLength = 10;

  // Initialize DataTable with pagination options and saved pageLength value
  const dataTable = jQuery(table).DataTable({
    order: [],
    aoColumns,
    defaultPageLength,
    lengthMenu: [[10, 16, 20, 25, 30, 100, 250, 500, -1],
      [10, 16, 20, 25, 30, 100, 250, 500, 'All']],
    stateDuration: -1,
    stateSave: true,
  });

  const tableBody = table.querySelector('tbody');
  tableBody.classList.remove('hidden');  

  const customFiltersWrapper = Utils.newEl('div', 'custom-filters-wrapper');
  
  const url = new URL(window.location.href);

  const defaultAccountant = 'All';
  const accountant = url.searchParams.get('accountant') || table.dataset.accountant;
  const accountantFilter = Utils.newEl('label', 'accountant-filter');
  const accountantFilterControl = Utils.newEl('select', 'form-control');
  const accountantsTpl = Utils.getEl('accountants-tpl');
  accountantFilterControl.name = 'accountant_filter';
  accountantFilterControl.innerHTML = accountantsTpl.innerHTML;
  accountantFilterControl.value = accountant || defaultAccountant;
  accountantFilterControl.addEventListener('change', function() {
    const accountant = accountantFilterControl.value;
    url.searchParams.set('accountant', accountant);
    const replaceState = true; // Don't add a new state to the history
    const newState = Object.assign({}, app.currentPage.state(), { url: url.toString() });
    app.navigateTo(newState, 'fwd', null, replaceState);
  });

  const defaultStatus = 'Active';
  const status = url.searchParams.get('status') || table.dataset.status;
  const statusFilter = Utils.newEl('label', 'status-filter');
  const statusFilterControl = Utils.newEl('select', 'form-control');
  statusFilterControl.name = 'status_filter';
  statusFilterControl.innerHTML = `
    <option>All</option>
    <option>Active</option>
    <option>Inactive</option>
    <option>Closed</option>
    <option>Deleted</option>
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
    url.searchParams.set('accountant', defaultAccountant);
    const replaceState = true; // Don't add a new state to the history
    const newState = Object.assign({}, app.currentPage.state(), { url: url.toString() });
    app.navigateTo(newState, 'fwd', null, replaceState);
  });

  accountantFilter.appendChild(accountantFilterControl);
  customFiltersWrapper.appendChild(accountantFilter);
  statusFilter.appendChild(statusFilterControl);
  customFiltersWrapper.appendChild(statusFilter);  
  customFiltersWrapper.appendChild(resetButton);

  const searchControl = Utils.getEl('clients-table_filter');
  //insert custom filters after the search control
  searchControl.parentNode.insertBefore(customFiltersWrapper, searchControl);

  const tableFooter = Utils.newEl('tfoot');
  table.appendChild(tableFooter);

  // FIA and SDA columns indices
  const SDA_MANDATE_COLUMN = 4;
  const FIA_MANDATE_COLUMN = 5;
  const FIA_APPROVED_COLUMN = 6;
  const FIA_AVAIL_COLUMN = 7;
  const SDA_AVAIL_COLUMN = 8;

  const currencyColumns = [
    SDA_MANDATE_COLUMN,
    FIA_MANDATE_COLUMN,
    FIA_APPROVED_COLUMN,
    SDA_AVAIL_COLUMN,
    FIA_AVAIL_COLUMN,
  ];

  // Update the data table everytime we update the search term
  updateCurrencyColumnTotals( dataTable, currencyColumns, tableFooter );
  dataTable.on('search.dt', () => updateCurrencyColumnTotals( dataTable, currencyColumns, tableFooter ));

  // On data tabe sort click, show the reset button
  // dataTable.on('order.dt', () => resetButton.classList.remove('hidden'));

  // Attach the event handler to the 'order.dt' event
  dataTable.on('order.dt', function(event, settings) {
    console.log('order.dt', dataTable, event, settings);
    resetButton.classList.remove('hidden')
  });


  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');


  /* bottom nav */
  Utils.removeClassFrom(app.el.bottomBar, 'active');
  Utils.getEl('nav-item-clients').classList.add('active');  


  tippy('[data-tippy-content]', { allowHTML: true });

}); // END: initClientsListPageView()