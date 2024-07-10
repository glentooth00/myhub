F1.deferred.push(function initTemplatesListPageView(app) {

    console.log('initTemplatesListPageView()');
  
    const Utils = F1.lib.Utils;
  
    /* table */
    const table = Utils.getEl('template-table');
    table.onclick = function(event) {
      console.log('table.onclick:', event);
      if (event.target.tagName.toLowerCase() === 'button') return;
      const row = event.target.closest('tr');
      if (!row || !row.id || row.parentElement.tagName.toLowerCase() !== 'tbody') return;
      app.navigateTo({url:'admin/referrers?view=template-details&id='+row.id, view:'template-details', title:'Template Details'});
    };
  
    // DataTable Columns Configuration
    let totalAmountSubmitted = 0;
    const aoColumns = [
      { orderable: false } ,    // ID
      null,                     // name
      null,                     // Model Type ID
      //{ 'sType': 'date' },      // Created At
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
  
    const searchControl = Utils.getEl('template-table_filter');
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