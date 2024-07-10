/* global F1 */

F1.deferred.push(function initReferrerTypeView(app) {

    console.log('initReferrerTypeView()');
    
    const Ajax = F1.lib.Ajax;
    const Popup = F1.lib.Popup;
    const Utils = F1.lib.Utils;
    
    
    app.el.form = Utils.getEl('ReferrerTypeForm');
    
    
    app.showReferrerTypeForm = function(data = null) {
      app.el.form.reset();
      if (!data) {
        console.log('showReferrerTypeForm(), new referrer-type');
        app.el.form.querySelector('h4').textContent = 'New Referrer-type';
        app.el.form.elements[0].value = 'new'; // data.id
      } else {
        console.log('showReferrerTypeForm(), edit referrer-type:', data);
        app.el.form.querySelector('h4').textContent = 'Edit referrer-type';
        app.el.form.elements[0].value = data.id;
        app.el.form.elements[1].value = data.name;
        app.el.form.elements[2].value = data.description;
        // app.el.form.elements[2].value = data.type_id;
      }
      app.modal = new Popup({
        modal: true,
        backdrop: 'dim',
        animation: 'fade',
        content: app.el.form,
        anchor: app.currentPage.el,
        position: 'center',
        // size: 'large',
      });
      app.modal.show();
    };
    
    
    app.editReferrerType = function(id) {
      const url = new URL(window.location.href);
      url.searchParams.set('id', id);
      Ajax.fetch(url.toString())
      .then(resp => {
        console.log(resp);
        app.showReferrerTypeForm(resp.data);
      })
      .catch((err) => app.handleAjaxError(err, 'edit'));  
    };
    
    
    app.deletereferrerType = function(id,name) {
      if (!confirm('Are you sure you want to delete: ' + name)) return;
      const url = new URL(window.location.href);
      url.searchParams.set('id', id);
      app.showBusy();
      Ajax.post(url.toString(), { id, action: 'deletereferrerType' })
      .then(resp => {
        console.log(resp);
        if (!resp.success) return app.handleAjaxError(resp, 'delete');
        app.removeBusy();
        console.log('delete.success:', resp);
        app.redirect(resp.goto);
      })
      .catch((err) => app.handleAjaxError(err, 'delete'));
    };
    
    
    app.submitReferrerTypeForm = function (e) {
      e.preventDefault();
      const form = e.target;
      const afterClose = () => app.modal.firstFocusable?.focus();
      console.log('app.submitReferrerTypeForm(), start...', form);
      app.showBusy();
      Ajax.submit( form, { extraData: { action: 'saveReferrerType' } } )
      .then(function (resp) {
        if (!resp.success) return app.handleAjaxError(resp, 'submit', { afterClose });
        app.removeBusy();
        app.modal.close({src:'submit.success'});
        console.log('submit.success:', resp);
        app.redirect(resp.goto);
      })
      .catch((err) => app.handleAjaxError(err, 'submit', { afterClose }));
    };
    
    
    /* top nav */
    Utils.removeFrom(app.el.toolbar, '.tool');
    
    tippy('[data-tippy-content]', { allowHTML: true });
    
    });
    