<style>
/* Page Specific CSS */

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

th, td {
  text-align: left;
  white-space: nowrap;
}

td {
  border-bottom: 1px solid #ddd;
  white-space: nowrap;
}

td .fa {
  width: 1em;
  color: var(--primary-color);
  opacity: 0.67;
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

.status-filter select {
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
    <table id="clients-table" data-status="<?=$status?>">
      <thead>
        <tr>
          <th>Status</th>
          <th>Client</th>
          <th>Tax Number</th>
          <th>SDA Mandate</th>
          <th>FIA Mandate</th>
          <th>FIA Approved</th>
          <th>FIA Available</th>
          <th>SDA Available</th>
          <th>Created At</th>
          <th data-tippy-content="Actions"><i class="fa fa-ellipsis-v"></i></th>
        </tr>
      </thead>
      <tbody class="hidden">
<?php foreach( $clients as $client ): ?>
<?php 
  $fia_avail = min($client->fia_mandate, $client->fia_approved) - $client->fia_used;
  $sda_avail = $client->sda_mandate - $client->sda_used;
?>
        <tr id="<?=$client->id?>">
          <td><?=$client->status?></td>
          <td class="name"><i class="fa fa-user-circle-o" title="Client"></i>&nbsp;<?=escape(fullName($client))?> 
            <small class="mute"><i>(<?=escape(clientComboID($client))?>)</i><?=$client->spouse_id?' (SP)':''?></small></td>
          <td><?=escape($client->tax_number)?></td>
          <td><?=currency($client->sda_mandate)?></td>
          <td><?=currency($client->fia_mandate)?></td>
          <td><?=currency($client->fia_approved)?></td>
          <td><?=currency($fia_avail)?></td>
          <td><?=currency($sda_avail)?></td>
          <td><?=substr($client->created_at, 0, 10)?></td>
          <td>
            <a href="<?=generatePDFLink($client->client_id)?>" target="_blank" 
              title="Download Statement PDF"><i class="fa fa-file-pdf-o"></i></a>
          </td>
        </tr>
<?php endforeach; ?>
      </tbody>
    </table>
  </div> <!-- .table-responsive -->
</div> <!-- .list-view -->
<script>
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
    const statusChanged = status !== defaultStatus;
    return isSorted || isSearched || isLengthChanged || statusChanged;
  }


  /* table */
  const table = Utils.getEl('clients-table');
  table.onclick = function(event) {
    const targetTagName = event.target.tagName.toLowerCase();
    if ( ( targetTagName === 'i' && event.target.classList.contains('fa-file-pdf-o') ) ||
      targetTagName === 'a' ) return;
    const row = event.target.closest('tr');
    if (!row || !row.id || row.parentElement.tagName.toLowerCase() !== 'tbody') return;
    app.navigateTo({url:'accountant/clients?view=details&id='+row.id, view:'details', title:'Client Details'});
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
    const replaceState = true; // Don't add a new state to the history
    const newState = Object.assign({}, app.currentPage.state(), { url: url.toString() });
    app.navigateTo(newState, 'fwd', null, replaceState);
  });

  statusFilter.appendChild(statusFilterControl);
  customFiltersWrapper.appendChild(statusFilter);
  customFiltersWrapper.appendChild(resetButton);

  const searchControl = Utils.getEl('clients-table_filter');
  //insert custom filters after the search control
  searchControl.parentNode.insertBefore(customFiltersWrapper, searchControl);

  const tableFooter = Utils.newEl('tfoot');
  table.appendChild(tableFooter);

  // FIA and SDA columns indices
  const SDA_MANDATE_COLUMN = 3;
  const FIA_MANDATE_COLUMN = 4;
  const FIA_APPROVED_COLUMN = 5;
  const FIA_AVAIL_COLUMN = 6;
  const SDA_AVAIL_COLUMN = 7;

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
</script>
<!-- Compiled: 2024-06-17 16:04:04 -->