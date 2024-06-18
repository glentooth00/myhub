F1.deferred.push(function initClientTCCsPageView(app) {

  console.log('initClientTCCsPageView()');

  const Utils = F1.lib.Utils;
  const CsvExporter = F1.lib.CsvExporter;


  app.exportToCsv = function(tableName) {
   const tableElement = document.querySelector(`.related-${tableName} > table`);
   const tableExporter = new CsvExporter('TableElement', tableElement);
   const timestamp = new Date().toISOString().slice(0, -5).replace(/[-:]/g, '');
   const data = tableExporter.dataParser.getData();
   const headers = data[0];
   const rows = data.slice(1, data.length - 1); // Drop header and summary rows
   console.log('app.exportToCsv()', {tableName, data, headers, rows});
   tableExporter.downloadCSV(`${tableName}_export_${timestamp}.csv`, [headers, ...rows]);
  }


  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');


  tippy('[data-tippy-content]', { allowHTML: true });

});