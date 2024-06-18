/* global F1 */

F1.deferred.push(function initAdminToolsPushDataView(app) {

  console.log('initAdminToolsPushDataView()');

  const Popup = F1.lib.Popup;
  const Utils = F1.lib.Utils;
  const Ajax = F1.lib.Ajax;

  function log(...args) { if (F1.DEBUG > 1) console.log(...args); }
  function addClass(el, className) { el.classList.add(className); }
  function removeClass(el, className) { el.classList.remove(className); }


  app.push = function(table) {
   	log('app.push(), table =', table);

    const busyIcon = Utils.getEl('busy-icon-' + table);
 
    addClass(busyIcon, 'spin');

    const waitPopup = new Popup({
      title: `Pushing ${table} data to S2...`,
      content: 'Please wait...',
      position: 'center',
      theme: 'success',
      size: 'small',
      modal: true,
    });
    waitPopup.show();
 
    Ajax.post(location.href, { action: 'pushData', table })
    .then(function (resp) {
      log('push.ajax.success says Hi!');
      waitPopup.close({src: 'ajax.success'});
      removeClass(busyIcon, 'spin');
      if (!resp.success) return app.handleAjaxError(resp, 'push.error');
      app.toast({ message: resp.message });
    })
    .catch(function (error) {
      log('push.ajax.fail says Hi!', error);
      waitPopup.close({src: 'ajax.fail'});
      removeClass(busyIcon, 'spin');
      app.handleAjaxError(error, 'push.fail');
    });
 
  }


  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');


  /* bottom nav */
  Utils.removeClassFrom(app.el.bottomBar, 'active');
  addClass(Utils.getEl('nav-item-dash'), 'active');


  tippy('[data-tippy-content]', { allowHTML: true });

});	