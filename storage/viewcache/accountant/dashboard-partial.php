<div class="grid-stack" hidden>
  <div class="grid-stack-item" gs-x="0" gs-y="0" gs-w="3" gs-h="9">
    <div class="grid-stack-item-content">
      <div class="widget">
        <h3>Welcome</h3>
        <div class="card">
          <figure>
            <img src="assets/img/splash.jpg" alt="calm-water-scene">
            <figcaption><?=$app->user->username?></figcaption>
          </figure>
          <div class="card-body">
            <p>Hi, <?=$app->user->first_name?></p>
            <p>Welcome to the Accountant Portal. This is your dashboard. From here you can access your clients and TCC's.</p>
          </div>
          <button class="btn btn-primary" type="button" onclick="F1.app.navigateTo(
              {url: 'accountant/profile?id=<?=$app->user->id?>&back=dashboard', view: 'details', title: 'My Profile'})">
              <i class="fa fa-vcard fa-lg"></i>&nbsp;
            <span>Goto Profile</span>
          </button>           
        </div>
      </div>
    </div> <!-- .grid-stack-item-content -->
  </div> <!-- .grid-stack-item -->  
  <div class="grid-stack-item" gs-x="3" gs-y="0" gs-w="4" gs-h="9">
    <div class="grid-stack-item-content">
      <div class="widget">
        <h3>Clients</h3>
        <button class="btn btn-primary" type="button" onclick="F1.app.navigateTo(
            {url: 'accountant/clients?back=dashboard', view: 'list', title: 'Clients'})">
          <i class="fa fa-user-circle fa-lg"></i>&nbsp;
          <span>Goto Clients</span>
        </button>        
      </div>
    </div> <!-- .grid-stack-item-content -->
  </div> <!-- .grid-stack-item -->  
  <div class="grid-stack-item" gs-x="7" gs-y="0" gs-w="5" gs-h="9">
    <div class="grid-stack-item-content">
      <div class="widget">
        <h3>FIA Tracking</h3>
        <button class="btn btn-primary" type="button" onclick="F1.app.navigateTo(
            {url: 'accountant/trades?back=dashboard', view: 'list', title: 'Trades'})">
          <i class="fa fa-money fa-lg"></i>&nbsp;
          <span>Goto Trades</span>
        </button>
        <br><br>
        <button class="btn btn-primary" type="button" onclick="F1.app.navigateTo(
            {url: 'accountant/tccs?back=dashboard', view: 'list', title: 'FIA TAX Clearances'})">
          <i class="fa fa-star-o fa-lg"></i>&nbsp;
          <span>Goto TCC's</span>
        </button>       
      </div>
    </div> <!-- .grid-stack-item-content -->
  </div> <!-- .grid-stack-item -->
</div> <!-- .grid-stack -->
<script>
  /* global F1 */

  F1.deferred.push(function initAccountantDashView(app) {

    console.log('initAccountantDashView()');

    const Utils = F1.lib.Utils;

    /* grid */
    let staticGrid = true;
    const gridStack = GridStack.init({ id: 'dashboard', staticGrid, cellHeight: 57 });
    const gridIcon = Utils.newEl('button', 'icon icon-grid tool', { type: 'button', title: 'Edit Dashboard' });
    gridIcon.onclick = () => { gridStack.setStatic( staticGrid =! staticGrid ); };
    gridStack.el.hidden = false; // Show when ready.

    /* bottom nav */
    Utils.removeClassFrom(app.el.bottomBar, 'active');
    Utils.getEl('nav-item-dash').classList.add('active');

    /* top nav */
    Utils.removeFrom(app.el.toolbar, '.tool');
    app.el.toolbar.prepend(gridIcon);

    tippy('[data-tippy-content]', { allowHTML: true });

  });  
</script>
<!-- Compiled: 2024-06-17 16:04:00 -->