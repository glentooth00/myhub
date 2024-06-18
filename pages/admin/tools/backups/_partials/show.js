/* global F1 */

F1.deferred.push(function initAdminToolsBackupsView(app) {

  console.log('initAdminToolsBackupsView()');

  const Popup = F1.lib.Popup;
  const Utils = F1.lib.Utils;
  const Ajax = F1.lib.Ajax;

  function log(...args) { if (F1.DEBUG > 1) console.log(...args); }
  function addClass(el, className) { el.classList.add(className); }
  function removeClass(el, className) { el.classList.remove(className); }


  // Add stuff here...


  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');


  /* bottom nav */
  Utils.removeClassFrom(app.el.bottomBar, 'active');
  addClass(Utils.getEl('nav-item-dash'), 'active');


  tippy('[data-tippy-content]', { allowHTML: true });

});	