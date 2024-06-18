<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <base href="<?=$app->baseUri?>">
  <title><?=$app->view->get('title')?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="vendors/FAwesome/font-awesome.min.css">
  <link rel="stylesheet" href="vendors/JQDTables/jquery.dataTables.css">
  <link rel="stylesheet" href="vendors/GridStack/gridstack.css">
  <link rel="stylesheet" href="vendors/F1/css/sidebar.css?<?=$app->ver?>">
  <link rel="stylesheet" href="vendors/F1/css/select.css?<?=$app->ver?>">
  <link rel="stylesheet" href="vendors/F1/css/upload.css?<?=$app->ver?>">
  <link rel="stylesheet" href="vendors/F1/css/popup.css?<?=$app->ver?>">
  <link rel="stylesheet" href="assets/css/main.css?<?=$app->ver?>">
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <script>window.F1 = window.F1 || { DEBUG: '<?=__DEBUG__?>', lib: {}, deferred: [], pageRef: '<?=page_link()?>' }</script>
</head>
<body>
<nav id="top-bar" class="flex-row flex-gap space-between">
  <header class="px1 flex-row flex-gap align-center">
    <span id="nav-buttons" class="flex-row flex-gap align-center">
      <button class="icon btn-round hidden" id="back-arrow" type="button"
        data-tippy-content="Go Back" onclick="F1.app.onExit(event, history.back.bind(history))">
        <i class="fa fa-arrow-left fa-lg"></i>
      </button>
      <button class="icon btn-round" id="show-sidebar" type="button"
        data-tippy-content="Show Sidebar Menu" onclick="F1.app.showSidebar('left')">
        <i class="fa fa-bars fa-lg"></i>
      </button>
    </span>
    <h1 class="page-title" id="page-title"><?=$app->view->get('title')?></h1>
  </header>
  <section id="app-toolbar" class="px1 flex-row flex-gap align-center">
    <button class="icon btn-round" id="show-notifications" type="button"
      data-tippy-content="Show Notifications" onclick="F1.app.showNotifications()">
      <i class="fa fa-bell-o fa-lg"></i>
    </button>
  </section>
</nav>
<nav id="sidebar-left" class="sidebar off-screen-left" onclick="F1.app.maybeCloseSidebar(event, 'left')">
  <div class="sidebar-content">
    <header class="flex-row align-center">
      <img src="assets/img/brand/logo.png" alt="Logo">
      <h2><?=$app->name?></h2>
      <small><?=$app->ver . ' (' . (__ENV_PROD__ ? 'prod' : 'dev' ) . ')'?></small>
    </header>
    <section class="menu main-menu">
      <button type="button" onclick="F1.app.navigateTo({url:'admin/dashboard', view:'show', title:'Dashboard'})">
        <i class="fa fa-dashboard fa-lg"></i><span>Dashboard</span>
      </button>
      <button type="button" onclick="F1.app.navigateTo({url:'admin/settings?back=dashboard', view:'show', title:'Settings'})">
        <i class="fa fa-wrench fa-lg"></i><span>Settings</span>
      </button>
      <button type="button" onclick="F1.app.navigateTo({url:'admin/referrers?back=dashboard', view:'list', title:'Referrers'})">
        <i class="fa fa-link fa-lg"></i><span>Referrers</span>
      </button>      
      <button type="button" onclick="F1.app.navigateTo({url:'admin/clients?back=dashboard', view:'list', title:'Clients'})">
        <i class="fa fa-users fa-lg"></i><span>Clients</span>
      </button>      
      <button type="button" onclick="F1.app.navigateTo({url:'admin/trades?back=dashboard', view:'list', title:'Trades'})">
        <i class="fa fa-money fa-lg"></i><span>Trades</span>
      </button> 
      <button type="button" onclick="F1.app.navigateTo({url:'admin/tccs?back=dashboard', view:'list', title:'FIA TAX Clearances'})">
        <i class="fa fa-star-o fa-lg"></i><span>TCC's</span>
      </button>
      <button type="button" onclick="F1.app.navigateTo({url:'admin/users?back=dashboard', view:'list', title:'Users'})">
        <i class="fa fa-user fa-lg"></i><span>Users</span>
      </button>
      <a class="button flex-row align-center" href="#help">
        <i class="fa fa-question-circle-o fa-lg"></i><span>Help</span>
      </a>
    </section>
    <section class="menu user-menu">
      <button type="button" onclick="F1.app.navigateTo({url:'admin/profile?back=dashboard', view:'details', title:'My Profile'})">
        <i class="fa fa-user-circle-o fa-lg"></i><span><?=$app->user->username?></span>
      </button>
      <small class="user-role">Role: <span><?=$app->user->role?></span></small>
    </section>
    <footer class="flex-row space-between">
      <a class="button btn-outline logout" href="user/logout">Logout</a>
      <button class="btn-default btn-outline" onclick="F1.app.closeSidebar('left')">Close</button>
    </footer>
  </div>
</nav>
<main id="main">
  <div class="page" data-view="list" data-title="FIA TAX Clearances">   
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
      text-align: left;
      white-space: nowrap;
    }
    
    td {
      border-bottom: 1px solid #ddd;
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
        <table id="tccs-table" data-days="<?=$days?>" data-category="<?=$category?>" data-accountant="<?=$accountantId?>">
          <thead>
            <tr>
              <th>Status</th>
              <th>PIN No</th>
              <th>PIN Value</th>
              <th>Applied</th>
              <th>Client</th>
              <th>Accountant</th>
              <th>Case No</th>
              <th></th>
              <th>Issued</th>
              <th>Rollover</th>
              <th>Used</th>
              <th>Available</th>
              <th>Created</th>          
            </tr>
          </thead>
          <tbody class="hidden">
<?php foreach( $tccs as $tcc ): ?>
            <tr id="<?=$tcc->id?>">
              <td class="<?=$tcc->deleted_at ? 'status deleted' : 'status'?>"><?=$tcc->status?></td>
              <td><?=escape($tcc->tcc_pin)?></td>
              <td><?=currency($tcc->amount_cleared)?></td>
              <td><?=$tcc->application_date?></td>
              <td class="name"><i class="fa fa-user-circle-o" title="Client"></i>&nbsp;<?=escape($tcc->client_name)?> 
                <small class="mute"><i>(<?=$tcc->client_id?>)</i></small></td>
              <td class="accountant"><i class="fa fa-calculator" title="Accountant"></i>&nbsp;<?=escape($tcc->client_accountant)?></td>
              <td><?=escape($tcc->tax_case_no)?></td>
              <td><?=get_cert_link($tcc)?></td>
              <td><?=$tcc->date?></td>
              <td><?=currency($tcc->rollover)?></td>
              <td><?=currency($tcc->amount_used)?></td>
              <td><?=currency($tcc->amount_available)?></td>
              <td><?=substr($tcc->created_at, 0, 10)?></td>
            </tr>
<?php endforeach; ?>
          </tbody>
        </table>
        <button class="fab btn-round btn-primary" type="button"
          onclick="F1.app.navigateTo({url:'<?=$app->request->module?>/tccs?view=edit&id=new', view:'edit', title:'TCC Form'})">
          <i class="fa fa-plus fa-lg"></i>
        </button>    
      </div> <!-- .table-responsive -->
      <template id="accountants-tpl">
        <option>All</option>
<?php foreach( $accountants as $ac ): ?>
        <option value="<?=$ac->id?>"><?=escape($ac->name)?></option>
<?php endforeach; ?>
      </template>
    </div> <!-- .list-view -->
    <script>
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
        app.navigateTo({url:'admin/tccs?view=details&id='+row.id, view:'details', title:'TCC Details'});
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
        null,                     // Status
        null,                     // PIN No
        { 'sType': 'currency' },  // PIN Value
        { 'sType': 'date' },      // Applied
        null,                     // Accountant
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
        const accountantChanged = accountant !== defaultAccountant;
        const categoryChanged = category !== defaultCategory;
        const daysChanged = days !== defaultDays;
        hasNonDefaultState = isSorted || isSearched || isLengthChanged || 
          categoryChanged || accountantChanged || daysChanged;
      }  
    
      const resetButtonClass = 'btn btn-sm btn-outline btn-clear-state' + ( hasNonDefaultState ? '' : ' hidden' );
      const resetButton = Utils.newEl('button', resetButtonClass);
      resetButton.dataset.tippyContent = 'Reset table filters/sort';
      resetButton.innerHTML = '<span class="fa fa-eraser"></span>';
      resetButton.addEventListener('click', function() {
        dataTable.state.clear();
        url.searchParams.set('accountant', defaultAccountant);
        url.searchParams.set('category', defaultCategory);
        url.searchParams.set('days', defaultDays);
        const replaceState = true; // Don't add a new state to the history
        const newState = Object.assign({}, app.currentPage.state(), { url: url.toString() });
        app.navigateTo(newState, 'fwd', null, replaceState);
      });
    
      daysFilter.appendChild(daysFilterControl);
      accountantFilter.appendChild(accountantFilterControl);
      categoryFilter.appendChild(categoryFilterControl);
      customFiltersWrapper.appendChild(accountantFilter);
      customFiltersWrapper.appendChild(categoryFilter);
      customFiltersWrapper.appendChild(daysFilter);
      customFiltersWrapper.appendChild(resetButton);
    
      const searchControl = Utils.getEl('tccs-table_filter');
      //insert custom filters after the search control
      searchControl.parentNode.insertBefore(customFiltersWrapper, searchControl);
    
      const tableFooter = Utils.newEl('tfoot');
      table.appendChild(tableFooter);
    
      const AMOUNT_COLUMN = 2;
      const AMOUNT_RO_COLUMN = 9;
      const AMOUNT_USED_COLUMN = 10;
      const AMOUNT_AVAIL_COLUMN = 11;
    
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
    </script>
  </div>
</main>
<aside id="sidebar-right" class="sidebar off-screen-right">
  <div class="sidebar-content">
    Filter options etc.
  </div>
</aside>
<nav id="bottom-bar" class="flex-row flex-gap space-around">
  <a href="admin/dashboard" id="nav-item-dash" class="nav-item"
    onclick="F1.app.navigateTo({event, view:'show', title:'Dashboard'})">
    <i class="fa fa-dashboard fa-lg"></i>
    <span>Dashboard</span>
    <hr>
  </a>
  <a href="admin/clients?back=dashboard" id="nav-item-clients" class="nav-item"
    onclick="F1.app.navigateTo({event, view:'list', title:'Clients'})">
    <i class="fa fa-users fa-lg"></i>
    <span>Clients</span>
    <hr>
  </a>
  <a href="admin/trades?back=dashboard" id="nav-item-trades" class="nav-item"
    onclick="F1.app.navigateTo({event, view:'list', title:'Trades'})">
    <i class="fa fa-money fa-lg"></i>
    <span>Trades</span>
    <hr>
  </a>
  <a href="admin/tccs?back=dashboard" id="nav-item-tccs" class="nav-item"
    onclick="F1.app.navigateTo({event, view:'list', title:'FIA TAX Clearances'})">
    <i class="fa fa-star-o fa-lg"></i>
    <span>TCC's</span>
    <hr>
  </a>
</nav>
<script src="vendors/JQuery/jquery-3.6.0.js"></script>
<script src="vendors/JQDTables/jquery.dataTables.js"></script>
<script src="vendors/GridStack/gridstack-all.js"></script>
<script src="vendors/Tippy/popper_core.min.js"></script>
<script src="vendors/Tippy/tippy.min.js"></script>
<script src="vendors/F1/js/formfield.js?<?=$app->ver?>"></script>
<script src="vendors/F1/js/formfields.js?<?=$app->ver?>"></script>
<script src="vendors/F1/js/upload.js?<?=$app->ver?>"></script>
<script src="vendors/F1/js/select.js?<?=$app->ver?>"></script>
<script src="vendors/F1/js/popup.js?<?=$app->ver?>"></script>
<script src="vendors/F1/js/utils.js?<?=$app->ver?>"></script>
<script src="vendors/F1/js/form.js?<?=$app->ver?>"></script>
<script src="vendors/F1/js/ajax.js?<?=$app->ver?>"></script>
<script src="assets/js/main.js?<?=$app->ver?>"></script>
</body>
</html>
<!-- Compiled: 2024-06-17 16:34:17 -->