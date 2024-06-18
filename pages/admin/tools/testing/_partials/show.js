/* global F1 */

F1.deferred.push(function initAdminToolsTestingView(app) {

  console.log('initAdminToolsTestingView()');

  const Popup = F1.lib.Popup;
  const Utils = F1.lib.Utils;
  const Ajax = F1.lib.Ajax;

  const busyIcon = Utils.getEl('busy-icon');


  function log(...args) { if (F1.DEBUG > 1) console.log(...args); }
  function addClass(el, className) { el.classList.add(className); }
  function removeClass(el, className) { el.classList.remove(className); }


  app.startTesting = function() {
   	log('app.fetchClients()');
 
    addClass(busyIcon, 'spin');

    const waitPopup = new Popup({
      title: 'Testing MyHub...',
      content: 'Please wait...',
      position: 'center',
      theme: 'success',
      size: 'small',
      modal: true,
    });
    waitPopup.show();
 
    const jobId = Utils.generateUid();
 
    Ajax.post(location.href, { action: 'test', jobId })
    .then(function (resp) {
      log('testing.ajax.success says Hi!');
      waitPopup.close({src: 'ajax.success'});
      removeClass(busyIcon, 'spin');
      if (!resp.success) return app.handleAjaxError(resp, 'testing.error');
      app.toast({ message: resp.message });
    })
    .catch(function (error) {
      log('testing.ajax.fail says Hi!', error);
      waitPopup.close({src: 'ajax.fail'});
      removeClass(busyIcon, 'spin');
      app.handleAjaxError(error, 'testing.fail');
    });
 
  }


  /* top nav */
  Utils.removeFrom(app.el.toolbar, '.tool');


  /* bottom nav */
  Utils.removeClassFrom(app.el.bottomBar, 'active');
  addClass(Utils.getEl('nav-item-dash'), 'active');


  tippy('[data-tippy-content]', { allowHTML: true });

});	