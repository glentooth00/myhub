F1.deferred.push(function initTccsDetailsPageView(app) {

  console.log('initTccsDetailsPageView()');

  const Ajax = F1.lib.Ajax;
  const Utils = F1.lib.Utils;

  const deletedAt = Utils.getEl('deletedAt');
  const status = Utils.getEl('status');


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