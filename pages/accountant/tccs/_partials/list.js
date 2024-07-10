F1.deferred.push(function initTccsListPageView(app) {

  console.log('initTccsListPageView()');

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


  /* table */
  const table = Utils.getEl('tccs-table');
  table.onclick = function(event) {
    if (event.target.tagName.toLowerCase() === 'button') return;
    const row = event.target.closest('tr');
    if (!row || !row.id || row.parentElement.tagName.toLowerCase() !== 'tbody') return;
    app.navigateTo({url:'accountant/tccs?view=details&id='+row.id, view:'details', title:'TCC Details'});
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
    null,                     // Status
    null,                     // PIN No
    { 'sType': 'currency' },  // PIN Value
    { 'sType': 'date' },      // Applied
    null,                     // Client
    null,                     // Case No
    { orderable: false },     // PDF    
    { 'sType': 'date' },      // Issued
    { 'sType': 'currency' },  // Rollover
    { 'sType': 'currency' },  // Used
    { 'sType': 'currency' },  // Available
    { 'sType': 'date' },      // Created
  ];  

  // new DataTable('#example', {
  //     language: {
  //         decimal: ',',
  //         thousands: '.'
  //     },
  //     deferRender: true
  // });


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

  const defaultCategory = 'All';
  const category = url.searchParams.get('category') || table.dataset.category;
  const categoryFilter = Utils.newEl('label', 'category-filter');
  const categoryFilterControl = Utils.newEl('select', 'form-control');
  categoryFilterControl.name = 'category_filter';
  categoryFilterControl.innerHTML = `
    <option>All</option>
    <option>Pending</option>
    <option>Awaiting Docs</option>
    <option>Approved</option>
    <option>Declined</option>
    <option>Updated</option>
    <option>Expired</option>
    <option>Deleted</option>
  `;
  categoryFilterControl.value = category || defaultCategory;
  categoryFilterControl.addEventListener('change', function() {
    const category = categoryFilterControl.value;
    url.searchParams.set('category', category);
    const replaceState = true; // Don't add a new state to the history
    const newState = Object.assign({}, app.currentPage.state(), { url: url.toString() });
    app.navigateTo(newState, 'fwd', null, replaceState);
  });

  const defaultDays = 'this-week';
  const days = url.searchParams.get('days') || table.dataset.days;
  const daysFilter = Utils.newEl('label', 'days-filter');
  const daysFilterControl = Utils.newEl('select', 'form-control');
  daysFilterControl.name = 'days_filter';
  daysFilterControl.innerHTML = `
    <option value="this-week">this Week</option>
    <option value="last-week">last Week</option>
    <option value="this-month">this Month</option>
    <option value="last-month">last Month</option>
    <option value="this-year">this Year</option>
    <option value="last-year">last Year</option>
    <option value="30">in the last 30 days</option>
    <option value="60">in the last 60 days</option>
    <option value="90">in the last 90 days</option>
    <option value="365">in the last Year</option>
    <option value="730">in the last 2 Years</option>
  `;
  daysFilterControl.value = days || defaultDays;
  daysFilterControl.addEventListener('change', function() {
    const days = daysFilterControl.value;
    url.searchParams.set('days', days);
    const replaceState = true; // Don't add a new state to the history
    const newState = Object.assign({}, app.currentPage.state(), { url: url.toString() });
    app.navigateTo(newState, 'fwd', null, replaceState);
  });

  let hasNonDefaultState = false;
  const state = dataTable.state.loaded();
  console.log('dataTable state:', state);

  if (state) {
    const isSorted = state.order.length > 0;
    const isSearched = state.search.search.length > 0;
    const isLengthChanged = state.length !== defaultPageLength;
    const categoryChanged = category !== defaultCategory;
    const daysChanged = days !== defaultDays;
    hasNonDefaultState = isSorted || isSearched || isLengthChanged || categoryChanged || daysChanged;
  }  

  const resetButtonClass = 'btn btn-sm btn-outline btn-clear-state' + ( hasNonDefaultState ? '' : ' hidden' );
  const resetButton = Utils.newEl('button', resetButtonClass);
  resetButton.dataset.tippyContent = 'Reset table filters/sort';
  resetButton.innerHTML = '<span class="fa fa-eraser"></span>';
  resetButton.addEventListener('click', function() {
    dataTable.state.clear();
    url.searchParams.set('category', defaultCategory);
    url.searchParams.set('days', defaultDays);
    const replaceState = true; // Don't add a new state to the history
    const newState = Object.assign({}, app.currentPage.state(), { url: url.toString() });
    app.navigateTo(newState, 'fwd', null, replaceState);
  });

  daysFilter.appendChild(daysFilterControl);
  categoryFilter.appendChild(categoryFilterControl);
  customFiltersWrapper.appendChild(categoryFilter);
  customFiltersWrapper.appendChild(daysFilter);
  customFiltersWrapper.appendChild(resetButton);

  const searchControl = Utils.getEl('tccs-table_filter');
  //insert custom filters after the search control
  searchControl.parentNode.insertBefore(customFiltersWrapper, searchControl);

  const tableFooter = Utils.newEl('tfoot');
  table.appendChild(tableFooter);

  const AMOUNT_COLUMN = 2;
  const AMOUNT_RO_COLUMN = 8;
  const AMOUNT_USED_COLUMN = 9;
  const AMOUNT_AVAIL_COLUMN = 10;

  const currencyColumns = [
    AMOUNT_COLUMN,
    AMOUNT_RO_COLUMN,
    AMOUNT_USED_COLUMN,
    AMOUNT_AVAIL_COLUMN,
  ];

  // Update the data table everytime we update the search term
  updateCurrencyColumnTotals( dataTable, currencyColumns, tableFooter );
  dataTable.on('search.dt', () => updateCurrencyColumnTotals( dataTable, currencyColumns, tableFooter ));

  // On data tabe sort click, show the reset button
  dataTable.on('order.dt', () => resetButton.classList.remove('hidden'));

  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');  

  
  /* bottom nav */
  Utils.removeClassFrom(app.el.bottomBar, 'active');
  Utils.getEl('nav-item-tccs').classList.add('active');  


  tippy('[data-tippy-content]', { allowHTML: true });  

}); // END: initTccsListPageView()