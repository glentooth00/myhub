/* global F1 */

F1.deferred.push(function initAdminProcessClientTccsView(app) {

  console.log('initAdminProcessClientTccsView()');

  const utils = new F1.modules.Utils();
  const ajax = new F1.modules.Ajax();


  /* alert */
  app.alert = function (alertOptions) {
    const { message = '', title = '', theme = '' } = alertOptions;
    const capitalizeFirstChar = str => str.charAt(0).toUpperCase() + str.slice(1);
    const options = {
      type: 'alert',
      content: message,
      title: title || capitalizeFirstChar(theme),
      buttons: [{ text: 'OK', className: 'btn--primary' }],
      size: message.length > 255 ? 'large' : message.length > 140 ? 'medium' : 'small',
      position: 'center',
    };
    const popup = new F1.modules.Popup({ ...options, ...alertOptions });
    popup.show();
  };  

  /* api */
  F1.api = {};
  F1.api.processClients = function() {
  	console.log('F1.api.processClients()');
    utils.addClass(busyIcon, 'spin');
    const popup = new F1.modules.Popup({
      title: 'Processing Client Tccs...',
      content: 'Please wait...',
      position: 'center',
      theme: 'success',
      size: 'small',
    });
    popup.show();
    const jobId = utils.uid();
    ajax.post(location.href, { action: 'processClients', jobId })
    .then(function (response) {
      console.log('fetchData.success says Hi!');
      progressTimer && clearInterval(progressTimer);
      popup.close();
      utils.removeClass(busyIcon, 'spin');
      const toast = new F1.modules.Popup({
        type: 'toast',
        title: 'Success!',
        content: response.message,
        theme: 'success',
        timer: 2500,
      });
      toast.show();
    })
    .catch(function (error) {
      const message = error.message || 'Unknown error fetching data.';
      console.log('fetchData.fail says Hi!', { error, message });
      progressTimer && clearInterval(progressTimer);
      popup.close();
      utils.removeClass(busyIcon, 'spin');
      app.alert({ message, theme: 'error' });
    });

  }

  /* bottom nav */
  utils.removeClass('.nav-item.active', 'active');
  utils.addClass('#nav-item-dash', 'active');

  /* top nav */
  utils.removeFrom(app.el.toolbar, '.tool');

	const busyIcon = utils.getEl('busy-icon', app.el.appMain);

});
