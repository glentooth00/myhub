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

table.dataTable th:first-child,
table.dataTable td:first-child {
  padding-left: 1em;
  white-space: nowrap;
  max-width: 3ch;
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
    <table id="referrers-table">
      <thead>
      <tr>
        <th>#</th>
        <th>Name</th>
        <th>ID Number</th>
        <th>Phone Number</th>
        <th>Email</th>
        <th>Created At</th>
      </tr>
      </thead>
      <tbody class="hidden">
<?php foreach( $referrers as $i => $referrer ): ?>
      <tr id="<?=$referrer->id?>">
        <td><?=($i+1).'.'?></td>
        <td><?=escape($referrer->name)?> (<?=escape($referrer->referrer_id)?>)</td>
        <td><?=escape($referrer->id_number)?></td>
        <td><?=escape($referrer->phone_number)?></td>
        <td><?=escape($referrer->email)?></td>
        <td><?=$referrer->created_at?></td>
      </tr>
<?php endforeach; ?>
      </tbody>
    </table>
    <button class="fab btn-round btn-primary" type="button"
        onclick="F1.app.navigateTo({url:'<?=$app->request->module?>/referrers?view=edit&id=new', view:'edit', title:'Referrer Form'})">
      <i class="fa fa-plus fa-lg"></i>
    </button>
  </div> <!-- .table-responsive -->
</div> <!-- .list-view -->
<script>
F1.deferred.push(function initReferrersListPageView(app) {

  console.log('initReferrersListPageView()');

  const Utils = F1.lib.Utils;

  /* table */
  const table = Utils.getEl('referrers-table');
  table.onclick = function(event) {
    console.log('table.onclick:', event);
    if (event.target.tagName.toLowerCase() === 'button') return;
    const row = event.target.closest('tr');
    if (!row || !row.id || row.parentElement.tagName.toLowerCase() !== 'tbody') return;
    app.navigateTo({url:'admin/referrers?view=details&id='+row.id, view:'details', title:'Referrer Details'});
  };

  // DataTable Columns Configuration
  let totalAmountSubmitted = 0;
  const aoColumns = [
    { orderable: false } ,    // #
    null,                     // name
    null,                     // id_number
    null,                     // phone_number
    null,                     // email
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

  let hasNonDefaultState = false;
  const state = dataTable.state.loaded();
  console.log('dataTable state:', state);

  if (state) {
    const isSorted = state.order.length > 0;
    const isSearched = state.search.search.length > 0;
    const isLengthChanged = state.length !== defaultPageLength;
    hasNonDefaultState = isSorted || isSearched || isLengthChanged;
  }

  const resetButtonClass = 'btn btn-sm btn-outline btn-clear-state' + ( hasNonDefaultState ? '' : ' hidden' );
  const resetButton = Utils.newEl('button', resetButtonClass);
  resetButton.innerHTML = '<span class="fa fa-eraser"></span>';
  resetButton.dataset.tippyContent = 'Reset table filters/sort';
  resetButton.addEventListener('click', function() {
    dataTable.state.clear();
    const replaceState = true; // Don't add a new state to the history
    const newState = Object.assign({}, app.currentPage.state(), { url: url.toString() });
    app.navigateTo(newState, 'fwd', null, replaceState);
  });

  customFiltersWrapper.appendChild(resetButton);

  const searchControl = Utils.getEl('referrers-table_filter');
  //insert custom filters after the search control
  searchControl.parentNode.insertBefore(customFiltersWrapper, searchControl);

  // On data tabe sort click, show the reset button
  dataTable.on('order.dt', () => resetButton.classList.remove('hidden'));

  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');


  /* bottom nav */
  Utils.removeClassFrom(app.el.bottomBar, 'active');


  tippy('[data-tippy-content]', { allowHTML: true });

}); // END: initReferrersListPageView
</script>
<!-- Compiled: 2024-06-17 16:03:31 -->