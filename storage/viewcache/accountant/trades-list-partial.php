<style>
/* list view */
.list-view {
  width: 100%;
  height: 100%;
  padding: 1rem;
  overflow: hidden;
}

.list-view header {
  padding: 0.5rem;
  height: 2.5rem;
  margin-bottom: 0.5rem;
  top: 0;
}

.list-view .name {
  display: flex;
  align-items: center;
  gap: 0.34rem;
}

.list-view .left {
  text-align: left;
}


/* table */
.table-responsive {
  width: 100%;
  height: 100%;
  border: 1px solid #ddd;
  overflow: auto;
}

table {
  border-collapse: collapse;
  width: 100%;
}

thead {
  background-color: #222;
  position: sticky;
  color: #fff;
  top: 0;
}

tr .fa {
  width: 1em;
  color: var(--primary-color);
  opacity: 0.67;
}

th, td {
  padding: 8px;
  text-align: left;
  white-space: nowrap;
}

td {
  border-bottom: 1px solid #ddd;
}

table.dataTable tbody tr:hover {
  background-color: #eee;
  cursor: pointer;
}

table.dataTable tfoot th {
  background-color: #eee;
  font-size: 0.85em;
}


/* filter */

.dataTables_filter {
  white-space: nowrap;
  display: inline-block;
}

.custom-filters-wrapper {
  user-select: none;
  font-size: 13px;
  float: right;  
}

.accountant-filter select,
.category-filter select,
.days-filter select {
  background-color: transparent;
  border: 1px solid #aaa;
  border-radius: 3px;
  margin: 5px;
  padding: 5px;
}

.btn-clear-state {
  border-color: var(--primary-color);
  color: var(--primary-color);
  margin: 5px;
}

.btn-clear-state.hidden {
  display: none;
}

.deleted {
  text-decoration: line-through;
}

@media screen and (max-width: 640px)
{
  .custom-filters-wrapper {
    text-align: center;
    float: none;
  }
}


</style>
<div class="list-view">
  <div class="table-responsive">
    <table id="trades-table" data-days="<?=$days?>">
      <thead>
        <tr>
          <th>Trade ID</th>
          <th>OTC</th>
          <th>Trade Date</th>
          <th>Client ID</th>
          <th>Type</th>
          <th>ZAR Sent</th>
          <th>TUSD Bought</th>
          <th>Rate (R)</th>
          <th>Covered (R)</th>
          <th>Allocations</th>   
        </tr>
      </thead>
      <tbody class="hidden">
<?php foreach( $trades as $trade ): ?>
        <tr id="<?=$trade->id?>">
          <td><?=escape($trade->trade_id)?></td>
          <td><?=escape($trade->otc)?></td>
          <td><?=$trade->date?></td>
          <td class="name"><i class="fa fa-user-circle-o" title="Client"></i> <?=escape($trade->client_id)?></td>
          <td><?=escape($trade->sda_fia)?></td>
          <td><?=currency($trade->zar_sent)?></td>
          <td><?=currency($trade->usd_bought, '$')?></td>
          <td><?=currency($trade->forex_rate, '', ' ', 3)?></td>
          <td><?=currency($trade->amount_covered)?></td>
          <td><?=escape($trade->allocated_pins)?></td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div> <!-- .table-responsive -->
</div> <!-- .list-view -->
<script>
F1.deferred.push(function initTradesListPageView(app) {

  console.log('initTradesListPageView()');

  const Utils = F1.lib.Utils;

  function calculateCurrencyColumnTotal(dataTable, columnIndex) {
    const total = dataTable.column(columnIndex, { search: 'applied' }).data().reduce((a, b) => {
      const amount = parseFloat(b.toString().replace(/[^(0-9)\-\.]/g, ''));
      return a + ((amount && typeof amount === 'number') ? amount : 0);
    }, 0);
    return total;
  }

  function updateCurrencyColumnTotals(dataTable, columnsInfo, footerElement) {
    let footerHTML = '<tr>';
    for (let i=0; i < aoColumns.length; i++) {
      let total = 0;
      const columnIndexes = columnsInfo.map( c => c.index );
      const columnSymbols = columnsInfo.map( c => c.symbol );
      // console.log('columnIndexes:', columnIndexes);
      // console.log('columnSymbols:', columnSymbols);
      const index = columnIndexes.indexOf(i);
      if (index > -1) {
        const symbol = columnSymbols[index];
        // console.log('symbol:', symbol);
        total = calculateCurrencyColumnTotal(dataTable, i);
        footerHTML += '<th class="left nowrap">' + Utils.currency(total, symbol) + '</th>';
      } else {
        footerHTML += '<th></th>';
      }
    }
    footerHTML += '</tr>';
    footerElement.innerHTML = footerHTML;
  }


  /* table */
  const table = Utils.getEl('trades-table');
  table.onclick = function(event) {
    if (event.target.tagName.toLowerCase() === 'button') return;
    const row = event.target.closest('tr');
    if (!row || !row.id || row.parentElement.tagName.toLowerCase() !== 'tbody') return;
    app.navigateTo({url:'accountant/trades?view=details&id='+row.id, view:'details', title:'Trade Details'});
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
  let totalAmountSubmitted = 0;
  const aoColumns = [
    null,                     // ID
    null,                     // OTC
    { 'sType': 'date' },      // Date
    null,                     // Client
    null,                     // Type
    { 'sType': 'currency' },  // ZAR Sent
    { 'sType': 'currency' },  // TUSD Bought
    { 'sType': 'currency' },  // Forex Rate
    { 'sType': 'currency' },  // Covered R
    null,                     // Allocations
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
    const daysChanged = days !== defaultDays;
    hasNonDefaultState = isSorted || isSearched || isLengthChanged || daysChanged;
  }  

  const resetButtonClass = 'btn btn-sm btn-outline btn-clear-state' + ( hasNonDefaultState ? '' : ' hidden' );
  const resetButton = Utils.newEl('button', resetButtonClass);
  resetButton.dataset.tippyContent = 'Reset table filters/sort';
  resetButton.innerHTML = '<span class="fa fa-eraser"></span>';
  resetButton.addEventListener('click', function() {
    dataTable.state.clear();
    url.searchParams.set('days', defaultDays);
    const replaceState = true; // Don't add a new state to the history
    const newState = Object.assign({}, app.currentPage.state(), { url: url.toString() });
    app.navigateTo(newState, 'fwd', null, replaceState);
  });

  daysFilter.appendChild(daysFilterControl);
  customFiltersWrapper.appendChild(daysFilter);
  customFiltersWrapper.appendChild(resetButton);

  const searchControl = Utils.getEl('trades-table_filter');
  //insert custom filters after the search control
  searchControl.parentNode.insertBefore(customFiltersWrapper, searchControl);

  const tableFooter = Utils.newEl('tfoot');
  table.appendChild(tableFooter);

  const ZAR_SENT_COLUMN = 5;
  const TUSD_BOUGHT_COLUMN = 6;
  const COVERED_COLUMN = 8;

  const currencyColumns = [
    { index: ZAR_SENT_COLUMN, symbol: 'R' },
    { index: TUSD_BOUGHT_COLUMN, symbol: '$' },
    { index: COVERED_COLUMN, symbol: 'R' }
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
  // Utils.getEl('nav-item-trades').classList.add('active');


  tippy('[data-tippy-content]', { allowHTML: true });  

}); // END: initTradesListPageView()
</script>
<!-- Compiled: 2024-06-17 16:10:11 -->