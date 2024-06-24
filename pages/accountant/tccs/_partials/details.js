F1.deferred.push(function initTccsDetailsPageView(app) {

  console.log('initTccsDetailsPageView()');

  const Ajax = F1.lib.Ajax;
  const Popup = F1.lib.Popup;
  const Utils = F1.lib.Utils;

  const deletedAt = Utils.getEl('deletedAt');
  const status = Utils.getEl('status');

  function spin(el) { if (el) el.classList.add('spin'); }
  function spinOff(el) { if (el) el.classList.remove('spin'); }


  app.undeleteTcc = function() {
    console.log('unSoftDeleteTcc()');
    const pinNo = document.getElementById('pinNo').innerText.trim() || status.innerText.trim();
    if ( ! confirm(`Undelete TCC Pin: ${pinNo}. Are you sure?`) ) return;

    app.showBusy(app.el.content);

    console.log('Undelete TCC start...');

    Ajax.post( location.href, { action: 'unSoftDeleteTcc' } )
      .then(function (resp) {
        app.removeBusy(app.el.content);
        if (!resp.success) return app.handleAjaxError(resp, 'unsoftdelete.tcc');
        app.redirect(resp.goto, 'unsoftdelete.tcc.success:', resp);
      })
      .catch((err) => app.handleAjaxError(err, 'unsoftdelete.tcc'));
  };


  app.deleteTcc = function() { 
    console.log('deleteTcc()');

    const deleteType = trashTip;
    const pinNo = document.getElementById('pinNo').innerText.trim() || status.innerText.trim();
    if ( ! confirm(`${deleteType} TCC Pin: ${pinNo}. Are you sure?`) ) return;

    app.showBusy(app.el.content);

    console.log(deleteType + ' TCC start...');

    Ajax.post( location.href, { action: 'deleteTcc', deleteType } )
      .then(function (resp) {
        app.removeBusy(app.el.content);
        if (!resp.success) return app.handleAjaxError(resp, 'delete.tcc');
        app.redirect(resp.goto, 'delete.tcc.success:', resp);
      })
      .catch((err) => app.handleAjaxError(err, 'delete.tcc'));
  };


  app.sendApprovedNotice = function() {
    console.log('sendApprovedNotice()');

    const pinNo = document.getElementById('pinNo').innerText.trim() || status.innerText.trim();
    if ( ! confirm(`Send Approval Notice for TCC Pin: ${pinNo}. Are you sure?`) ) return;

    const actionButton = event.currentTarget;
    const title = 'Approval Notice';
    spin(actionButton);

    const syncPopup = new Popup({
      title,
      content: 'Sending email. Please wait...',
      position: 'center',
      theme: 'success',
      size: 'small',
    });    
    syncPopup.show();

    console.log('Send Approval Notice start...');

    Ajax.post( location.href, { action: 'sendApprovedNotice' } )
      .then(function (resp) {
        syncPopup.close();
        spinOff(actionButton);
        if (!resp.success) return app.handleAjaxError(resp, 'tcc.sendApprovedNotice');
        console.log('tcc.sendApprovedNotice.success', resp);
        app.toast({ message: resp.message });
      })
      .catch(function (resp) {
        syncPopup.close();
        spinOff(actionButton);
        return app.handleAjaxError(resp, 'tcc.sendApprovedNotice');
      });
  };


  /* top nav */
  if (deletedAt) status.classList.add('deleted');
  const trashTip = deletedAt ? 'Permanently Delete' : 'Soft Delete';
  const trashClass = 'icon btn-round tool' + ( deletedAt ? ' permanent-delete' : '' );
  const trashIcon = Utils.newEl('button', trashClass, { type: 'button' });
  trashIcon.innerHTML = `<i class="fa fa-trash-o fa-lg"></i>`;
  trashIcon.setAttribute('data-tippy-content', trashTip);
  trashIcon.onclick = app.deleteTcc;

  Utils.removeFrom(app.el.toolbar, '.tool');
  app.el.toolbar.prepend(trashIcon);


  tippy('[data-tippy-content]', { allowHTML: true });

});