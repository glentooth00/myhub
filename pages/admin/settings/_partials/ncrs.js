/* global F1 */

F1.deferred.push(function initAdminSettingsNcrsView(app) {

    console.log('initAdminSettingsNcrsView()');
    
    const Ajax = F1.lib.Ajax;
    const Popup = F1.lib.Popup;
    const Utils = F1.lib.Utils;
    
    
    app.el.form = Utils.getEl('NcrForm');
    
    
    app.showNcrForm = function(data = null) {
        app.el.form.reset();
        if (!data) {
          console.log('showNcrForm(), new bank');
          app.el.form.querySelector('h4').textContent = 'New Ncr';
          app.el.form.elements[0].value = 'new'; // data.id
        } else {
          console.log('showNcrForm(), edit bank:', data);
          app.el.form.querySelector('h4').textContent = 'Edit Ncr';
          app.el.form.elements[0].value = data.id;
          app.el.form.elements[1].value = data.name;
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
    
    
    app.editNcr = function(id) {
        const url = new URL(window.location.href);
        url.searchParams.set('id', id);
        Ajax.fetch(url.toString())
        .then(resp => {
            console.log(resp);
            this.showNcrForm(resp.data);
        })
        .catch((err) => app.handleAjaxError(err, 'delete'));  
    };
    
    
    app.deleteNcr = function(id,name) {
        if (!confirm('Are you sure you want to delete: ' + name)) return;
        const url = new URL(window.location.href);
        url.searchParams.set('id', id);
        app.showBusy();
        Ajax.post(url.toString(), { id, action: 'deleteNcr' })
        .then(resp => {
            console.log(resp);
            if (!resp.success) return app.handleAjaxError(resp, 'delete');
            app.removeBusy();
            console.log('delete.success:', resp);
            app.redirect(resp.goto);
        })
        .catch((err) => app.handleAjaxError(err, 'delete'));
    };
    
    
    app.submitNcrForm = function (e) {
        e.preventDefault();
        const form = e.target;
        const afterClose = () => app.modal.firstFocusable?.focus();
        console.log('app.submitNcrForm(), start...', form);
        app.showBusy();
        Ajax.submit( form, { extraData: { action: 'saveNcr' } } )
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
    