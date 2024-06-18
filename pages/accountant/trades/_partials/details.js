F1.deferred.push(function initTradesDetailsPageView(app) {

  console.log('initTradesDetailsPageView()');

  const Utils = F1.lib.Utils;


  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');

  tippy('[data-tippy-content]', { allowHTML: true });

});