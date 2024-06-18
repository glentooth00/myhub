/* global F1 */

/* main.js */

(function(F1) {

  const Ajax = F1.lib.Ajax;
  const Popup = F1.lib.Popup;
  const Utils = F1.lib.Utils;

  function executeScript(deadScript) {
    console.log('main.executeScript()', deadScript);
    return new Promise((resolve, reject) => {
      const liveScript = document.createElement('script');
     if (deadScript.src) {
        liveScript.src = deadScript.src;
        liveScript.dataset.rel = 'page';
        liveScript.onload = resolve;
        liveScript.onerror = reject;
        document.head.appendChild(liveScript);      
      } else {
        liveScript.text = deadScript.text;
        deadScript.parentNode.replaceChild(liveScript, deadScript);
        resolve();
      }
    });
  }

  async function updatePageContent(elPage, newHtml) {
    try {
      elPage.innerHTML = newHtml;

      const scripts = elPage.querySelectorAll('script');
      const srcScripts = Array.from(scripts).filter(script => script.src);
      const inlineScripts = Array.from(scripts).filter(script => !script.src);

      document.head.querySelectorAll('script[data-rel="page"]').forEach(script => script.parentNode.removeChild(script));

      await Promise.all(srcScripts.map(executeScript));

      return Promise.all(inlineScripts.map(executeScript));
    }
    catch (err) {
      console.error('main.updatePageContent(), Something went wrong.', err);
    }
  }


  class AppPage {
    constructor(state) {
      Object.assign(this, { url: '', view: '', title: '', enterFrom: 'stage-right' }, state);
      if (this.el) return; this.el = Utils.newEl('div'); this.el.dataset.view = this.view;
      this.el.dataset.title = this.title; this.hideOffStage(this.enterFrom); }
    enterStage() { this.el.className = 'page'; }
    hideOffStage(side) { this.el.className = 'page' + (side ? ` ${side}` : ''); }
    getOtherSide(side) { return side === 'stage-right' ? 'stage-left' : 'stage-right';  }
    state() { return { url: this.url, view: this.view, title: this.title, enterFrom: this.enterFrom }; }
    findAll(selector) { return this.el.querySelectorAll(selector); }
    find(selector) { return this.el.querySelector(selector); }
  }

  class App {
    constructor() {
      this.el = {
        toolbar    : Utils.getEl('app-toolbar'),
        bottomBar  : Utils.getEl('bottom-bar'),
        backArrow  : Utils.getEl('back-arrow'),
        sidebarBtn : Utils.getEl('show-sidebar'),
        pageTitle  : Utils.getEl('page-title'),
        appMain    : Utils.getEl('main'),
        doc        : document.documentElement,
      };
      this.controllers = {};
      const el = this.el.appMain.querySelector('.page');
      const url = window.location.toString();
      console.log('app.construct(), url:', url);
      this.currentPage = new AppPage({ url, view: el.dataset.view, title: el.dataset.title, el });
      history.replaceState(this.currentPage.state(), null, url);
      this.pageHistory = [this.currentPage];
      this.previousPage = null;

      window.addEventListener('popstate', (e) => {
        const state = e.state; if (!state) return location.reload(); // if there is no state, then reload the page
        const lastPage = this.pageHistory[this.pageHistory.length - 2];
        let navDirection = lastPage && lastPage.url === state.url ? 'back' : 'fwd';
        if (navDirection === 'fwd' && this.currentPage.url.indexOf(state.url) === 0) navDirection = 'back';
        console.log('popstate', {navDirection, state, lastPage, hist:this.pageHistory});
        /* if the user navigated back to the previous page, then we need to enter the page from the opposite side */
        if (navDirection === 'back') { state.enterFrom = 'stage-left';  this.pageHistory.pop(); }
        this.navigateTo(state, navDirection, true);
      });

      window.addEventListener('beforeunload', (e) => {
        const view = this?.currentPage?.view;
        console.log('window.beforeunload(), page.view = ', view );
        if ( view === 'edit') {
          const formCtrl = this?.controllers?.form;
          if (formCtrl?.isModified()) {
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            return e.returnValue;
          }
        }
      });      

      console.log('app.construct(), referrer:', document.referrer);
      if (document.referrer.includes('login')) {
        const lastURL = sessionStorage.getItem('lastURL');
        if (lastURL) {
          const now = new Date();
          if (url !== lastURL && confirm('Do you want to return to the last page you were on?')) {
            sessionStorage.removeItem('lastURL');
            history.replaceState(null, "", lastURL);
            this.redirect(lastURL, 'Last URL');
          }
        }
      }

      this.afterNavigate();
    }
    showSidebar(side) { Utils.getEl('sidebar-' + side).classList.add('open'); }
    closeSidebar(side) { Utils.getEl('sidebar-' + side).classList.remove('open'); }
    maybeCloseSidebar(event, side) { if (event.target===event.currentTarget) this.closeSidebar(side); }
    showNotifications() { console.log('app.showNotifications(), This will eventually open a notifications dropdown.'); }
    fetchPage(elPage, url) { return Ajax.fetch(url, { responseType: 'html', headers: { 'F1-Request-Type' : 'page' } })
      .then(newHtml => updatePageContent(elPage, newHtml)); }      
    showBusy() { const busyOverlay = Utils.newEl('div', 'busy-overlay');
      busyOverlay.innerHTML = '<i class="spinner"></i>'; this.currentPage.el.appendChild(busyOverlay); }
    removeBusy() { const el = document.querySelector('.busy-overlay'); el && el.parentNode.removeChild(el); }
    pushState(newPage, isPopState) { if (this.pageHistory.length >= 10) { this.pageHistory.shift(); }
      this.pageHistory.push(newPage); !isPopState && history.pushState(newPage.state(), null, newPage.url); }
    replaceState(newPage) { this.pageHistory.pop(); this.pageHistory.push(newPage); 
      history.replaceState(newPage.state(), null, newPage.url); }
    afterNavigate() {
      const now = new Date();
      console.log('app.afterNavigate()', now);
      // Is this an entity page?  Check the url for "id="
      const isEntityPage = this.currentPage.url.indexOf('id=') > 0;
      const isSubPage = isEntityPage || this.currentPage.url.indexOf('back=') > 0;
      this.el.pageTitle.innerHTML = this.currentPage.title;
      this.el.sidebarBtn && this.el.sidebarBtn.classList.toggle('hidden', isSubPage);
      this.el.backArrow && this.el.backArrow.classList.toggle('hidden', !isSubPage);
      this.previousPage && this.previousPage.el.remove();
      requestAnimationFrame(() => {
        F1.deferred.forEach(fn => fn(F1.app));
        if (F1.DEBUG) console.log('app.afterNavigate(), After running deferred scripts. \nF1', F1); 
        sessionStorage.setItem('lastURL', this.currentPage.url);
        F1.deferred = [];
      });
    }
    handleAjaxError(error, actionId = '', options = {}) {
      console.error('app.handleAjaxError()', {actionId, error, options});
      this.removeBusy(); let message = error.errors || error.message || error || 'An error occurred.';
      if ( typeof message == 'object' ) message = Object.values(message).join('<br>');
      this.alert({ message, ...options });
    }
    /**
     * app.navigateTo({url: 'accountant/clients', view: 'list', title: 'Clients', el: elClientsPage, enterFrom: 'stage-right'})
     * app.navigateTo({url: 'accountant/tccs?page=details&id=' + id, view: 'details', title: 'TCC Details'}, 'back', true)
     * app.navigateTo({event, view:'details', title:'Trade Details'})
     */ 
    navigateTo(state, dir='fwd', isPopState=false, replaceState=false) {
      console.log('app.navigateTo()', { state, dir, isPopState, replaceState });
      if (state.event) {
        const elTarget = state.event.target;
        if (state.event.ctrlKey || state.event.metaKey) return false; // Allow default behaviour on "CTRL Click".
        if (elTarget.tagName === 'A') { state.url = state.event.currentTarget.href; }
        else if (elTarget?.parentElement.tagName === 'A') { state.url = elTarget.parentElement.href; }
        state.event.preventDefault();
      }
      const newPage = new AppPage(state), currentPage = this.currentPage;
      if (currentPage.url === state.url && !replaceState) return;
      this.showBusy();
      if (replaceState) { this.replaceState(newPage); return window.location.href = newPage.url; }      
      this.fetchPage(newPage.el, newPage.url).then(() => {
        if (dir === 'fwd') this.pushState(newPage, isPopState);
        this.el.appMain.append(newPage.el);
        requestAnimationFrame(() => { // Transition between pages         
          currentPage.hideOffStage(newPage.getOtherSide(newPage.enterFrom));
          requestAnimationFrame(() => { newPage.enterStage(); });
          // Update the page title with the new page title
          document.title = newPage.title;
          this.previousPage = currentPage;
          this.currentPage = newPage;
          this.afterNavigate();
          this.removeBusy();
        });
      })
      .catch((err) => this.handleAjaxError(err, 'navigateTo'));
    }
    redirect(url, message, data) {
      console.log('app.redirect()', { url, message, data });
      if (!url) window.location.reload();
      else if (url == 'back') history.back();
      else window.location.href = url;
    }
    alert(alertOptions) {
      const { message = '', title = '', theme = 'error' } = alertOptions;
      const options = {
        type: 'alert',
        content: message,
        title: title || Utils.titleCase(theme),
        buttons: [{ text: 'OK', className: 'btn--primary' }],
        size: message.length > 255 ? 'large' : message.length > 140 ? 'medium' : 'small',
        anchor: this.currentPage.el,
        escapeKeyClose: true,
        position: 'center',
        modal: true,
        theme,
      };
      console.log('app.alert()', { alertOptions, options });
      const popup = new Popup({ ...options, ...alertOptions });
      popup.show({src:'app.alert'}); return popup;
    }
    toast(toastOptions) {
      const { message = '', title = '', theme = 'success' } = toastOptions;
      const options = {
        type: 'toast',
        content: message,
        title: title || Utils.titleCase(theme),
        anchor: this.currentPage.el,
        timer: 5000,
        theme,
      };
      const popup = new Popup({ ...options, ...toastOptions });
      popup.show({src:'app.toast'}); return popup;
    }
    onExit(event, cb) {
      console.log('app.onExit(), event:', event, cb);
      event.preventDefault();
      const view = this?.currentPage?.view;
      console.log('app.onExit(), page.view = ', view );
      const formCtrl = this?.controllers?.form;
      if ( view !== 'edit' || ! formCtrl?.isModified() ) {
        console.log('app.onExit(), Not a changed form. Run OK Callback.' );
        return cb();
      }
      const controllers = this.controllers;
      const onContinue = () => { controllers.form = null; popup.close(); setTimeout( () => cb() ); };
      const onCancel = () => popup.close();
      const popup = new Popup({
        theme: 'warning',
        size: 'small',
        modal: true,
        escapeKeyClose: true,
        position: 'center',
        title: 'You have unsaved changes!',
        content: 'Discard changes?',
        buttons: [
          { text: 'Yes', className: 'btn--primary', onClick: onContinue },
          { text: 'No', className: 'btn--secondary', onClick: onCancel },
        ],
      });
      popup.show();
    }
  }

  F1.lib = F1.lib || {};
  F1.lib.App = App;
  F1.lib.AppPage = AppPage;

})(window.F1 = window.F1 || {});


document.addEventListener( 'DOMContentLoaded', function() {
  if ( F1.DEBUG ) console.log( 'main.init(), DOM content loaded.' );
  F1.app = new F1.lib.App();
});