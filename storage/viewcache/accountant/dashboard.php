<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <base href="<?=$app->baseUri?>">
  <title><?=$app->view->get('title')?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="vendors/FAwesome/font-awesome.min.css">
  <link rel="stylesheet" href="vendors/JQDTables/jquery.dataTables.css">
  <link rel="stylesheet" href="vendors/GridStack/gridstack.css">
  <link rel="stylesheet" href="vendors/F1/css/sidebar.css?<?=$app->ver?>">
  <link rel="stylesheet" href="vendors/F1/css/select.css?<?=$app->ver?>">
  <link rel="stylesheet" href="vendors/F1/css/upload.css?<?=$app->ver?>">
  <link rel="stylesheet" href="vendors/F1/css/popup.css?<?=$app->ver?>">
  <link rel="stylesheet" href="assets/css/main.css?<?=$app->ver?>">
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <script>window.F1 = window.F1 || { DEBUG: '<?=__DEBUG__?>', lib: {}, deferred: [], pageRef: '<?=page_link()?>' }</script>
</head>
<body>
<nav id="top-bar" class="flex-row flex-gap space-between">
  <header class="px1 flex-row flex-gap align-center">
    <span id="nav-buttons" class="flex-row flex-gap align-center">
      <button class="icon btn-round hidden" id="back-arrow" type="button"
        data-tippy-content="Go Back" onclick="F1.app.onExit(event, history.back.bind(history))">
        <i class="fa fa-arrow-left fa-lg"></i>
      </button>
      <button class="icon btn-round" id="show-sidebar" type="button"
        data-tippy-content="Show Sidebar Menu" onclick="F1.app.showSidebar('left')">
        <i class="fa fa-bars fa-lg"></i>
      </button>
    </span>
    <h1 class="page-title" id="page-title"><?=$app->view->get('title')?></h1>
  </header>
  <section id="app-toolbar" class="px1 flex-row flex-gap align-center">
    <button class="icon btn-round" id="show-notifications" type="button"
      data-tippy-content="Show Notifications" onclick="F1.app.showNotifications()">
      <i class="fa fa-bell-o fa-lg"></i>
    </button>
  </section>
</nav>
<nav id="sidebar-left" class="sidebar off-screen-left" onclick="F1.app.maybeCloseSidebar(event, 'left')">
  <div class="sidebar-content">
    <header class="flex-row align-center">
      <img src="assets/img/brand/logo.png" alt="Logo">
      <h2><?=$app->name?></h2>
      <small><?=$app->ver . ' (' . (__ENV_PROD__ ? 'prod' : 'dev' ) . ')'?></small>
    </header>
    <section class="menu main-menu">
      <button type="button" onclick="F1.app.navigateTo({url:'accountant/dashboard', view:'show', title:'Dashboard'})">
        <i class="fa fa-dashboard fa-lg"></i><span>Dashboard</span>
      </button> 
      <button type="button" onclick="F1.app.navigateTo({url:'accountant/clients?back=dashboard', view:'list', title:'Clients'})">
        <i class="fa fa-users fa-lg"></i><span>Clients</span>
      </button>      
      <button type="button" onclick="F1.app.navigateTo({url:'accountant/trades?back=dashboard', view:'list', title:'Trades'})">
        <i class="fa fa-money fa-lg"></i><span>Trades</span>
      </button> 
      <button type="button" onclick="F1.app.navigateTo({url:'accountant/tccs?back=dashboard', view:'list', title:'FIA TAX Clearances'})">
        <i class="fa fa-star-o fa-lg"></i><span>TCC's</span>
      </button>
      <a class="button flex-row align-center" href="#help">
        <i class="fa fa-question-circle-o fa-lg"></i><span>Help</span>
      </a>
    </section>
    <section class="menu user-menu">
      <button type="button" onclick="F1.app.navigateTo({url:'accountant/profile?back=dashboard', view:'details', title:'My Profile'})">
        <i class="fa fa-user-circle-o fa-lg"></i><span><?=$app->user->username?></span>
      </button>
      <small class="user-role">Role: <span><?=$app->user->role?></span></small>
    </section>
    <footer class="flex-row space-between">
      <a class="button btn-outline logout" href="user/logout">Logout</a>
      <button class="btn-default btn-outline" onclick="F1.app.closeSidebar('left')">Close</button>
    </footer>
  </div>
</nav>
<main id="main">
  <div class="page" data-view="show" data-title="Dashboard">
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
  </div>
</main>
<aside id="sidebar-right" class="sidebar off-screen-right">
  <div class="sidebar-content">
    Filter options etc.
  </div>
</aside>
<nav id="bottom-bar" class="flex-row flex-gap space-around">
  <a href="accountant/dashboard" id="nav-item-dash" class="nav-item"
    onclick="F1.app.navigateTo({event, view:'show', title:'Dashboard'})">
    <i class="fa fa-dashboard fa-lg"></i>
    <span>Dashboard</span>
    <hr>
  </a>
  <a href="accountant/clients?back=dashboard" id="nav-item-clients" class="nav-item"
    onclick="F1.app.navigateTo({event, view:'list', title:'Clients'})">
    <i class="fa fa-users fa-lg"></i>
    <span>Clients</span>
    <hr>
  </a>
  <a href="accountant/trades?back=dashboard" id="nav-item-trades" class="nav-item"
    onclick="F1.app.navigateTo({event, view:'list', title:'Trades'})">
    <i class="fa fa-money fa-lg"></i>
    <span>Trades</span>
    <hr>
  </a>
  <a href="accountant/tccs?back=dashboard" id="nav-item-tccs" class="nav-item"
    onclick="F1.app.navigateTo({event, view:'list', title:'FIA TAX Clearances'})">
    <i class="fa fa-star-o fa-lg"></i>
    <span>TCC's</span>
    <hr>
  </a>
</nav>
<script src="vendors/JQuery/jquery-3.6.0.js"></script>
<script src="vendors/JQDTables/jquery.dataTables.js"></script>
<script src="vendors/GridStack/gridstack-all.js"></script>
<script src="vendors/Tippy/popper_core.min.js"></script>
<script src="vendors/Tippy/tippy.min.js"></script>
<script src="vendors/F1/js/formfield.js?<?=$app->ver?>"></script>
<script src="vendors/F1/js/formfields.js?<?=$app->ver?>"></script>
<script src="vendors/F1/js/upload.js?<?=$app->ver?>"></script>
<script src="vendors/F1/js/select.js?<?=$app->ver?>"></script>
<script src="vendors/F1/js/popup.js?<?=$app->ver?>"></script>
<script src="vendors/F1/js/utils.js?<?=$app->ver?>"></script>
<script src="vendors/F1/js/form.js?<?=$app->ver?>"></script>
<script src="vendors/F1/js/ajax.js?<?=$app->ver?>"></script>
<script src="assets/js/main.js?<?=$app->ver?>"></script>
</body>
</html>
<!-- Compiled: 2024-06-17 16:03:44 -->