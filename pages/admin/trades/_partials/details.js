F1.deferred.push(function initTradesDetailsPageView(app) {

  console.log('initTradesDetailsPageView()');

  const Ajax = F1.lib.Ajax;
  const Utils = F1.lib.Utils;

  const deletedAt = Utils.getEl('deletedAt');


  app.undeleteTrade = function() {
    console.log('unSoftDeleteTrade()');
    const tradeId = document.getElementById('tradeId').innerText.trim();
    if ( ! confirm(`Undelete Trade ID = ${tradeId}. Are you sure?`) ) return;

    app.showBusy(app.el.content);

    console.log('Undelete Trade start...');

    Ajax.post( location.href, { action: 'unSoftDeleteTrade' } )
      .then(function (resp) {
        app.removeBusy(app.el.content);
        if (!resp.success) return app.handleAjaxError(resp, 'unsoftdelete.trade');
        app.redirect(resp.goto, 'unsoftdelete.trade.success:', resp);
      })
      .catch((err) => app.handleAjaxError(err, 'unsoftdelete.trade'));
  };


  app.deleteTrade = function() { 
    console.log('deleteTrade()');

    const deleteType = trashTip;
    const tradeId = document.getElementById('tradeId').innerText.trim();
    if ( ! confirm(`${deleteType} Trade ID = ${tradeId}. Are you sure?`) ) return;

    app.showBusy(app.el.content);

    console.log(deleteType + ' Trade start...');

    Ajax.post( location.href, { action: 'deleteTrade', deleteType } )
      .then(function (resp) {
        app.removeBusy(app.el.content);
        if (!resp.success) return app.handleAjaxError(resp, 'delete.trade');
        app.redirect(resp.goto, 'delete.trade.success:', resp);
      })
      .catch((err) => app.handleAjaxError(err, 'delete.trade'));
  };


  /* top nav */
  const trashTip = deletedAt ? 'Permanently Delete' : 'Soft Delete';
  const trashClass = 'icon btn-round tool' + ( deletedAt ? ' permanent-delete' : '' );
  const trashIcon = Utils.newEl('button', trashClass, { type: 'button' });
  trashIcon.innerHTML = `<i class="fa fa-trash-o fa-lg"></i>`;
  trashIcon.setAttribute('data-tippy-content', trashTip);
  trashIcon.onclick = app.deleteTrade;

  Utils.removeFrom(app.el.toolbar, '.tool');
  app.el.toolbar.prepend(trashIcon);


  tippy('[data-tippy-content]', { allowHTML: true });

});