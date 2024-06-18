<style>
/* Page Specific CSS */

.container {
  margin: auto;
  padding: 1rem;
  max-width: 768px;
}

#settings-nav button {
  margin: 1rem 0.67em;
  width: 100px;
}

#settings-nav i {
  margin: 0.34em 0.67em;
}

</style>
<div class="container">
  <header>
    <hr>
    <h2>Welcome to Settings</h2>
    <hr>
  </header>
  <nav id="settings-nav">
    <button class="btn-primary" type="button" onclick="F1.app.navigateTo(
        {url: 'admin/settings?view=roles&back=settings', view: 'roles', title: 'Roles'})">
      <i class="fa fa-user fa-lg"></i><br>
      <span>Roles</span>
    </button>  
    <button class="btn-primary" type="button" onclick="F1.app.navigateTo(
        {url: 'admin/settings?view=banks&back=settings', view: 'banks', title: 'Banks'})">
      <i class="fa fa-university fa-lg"></i><br>
      <span>Banks</span>
    </button>  
    <button class="btn-primary" type="button" onclick="F1.app.navigateTo(
        {url: 'admin/settings?view=ncrs&back=settings', view: 'ncrs', title: 'NCRS'})">
      <i class="fa fa-money fa-lg"></i><br>
      <span>NCRS</span>
    </button>    
    <br>
    <button class="btn-primary" type="button" onclick="F1.app.navigateTo(
        {url: 'admin/settings?view=countries&back=settings', view: 'countries', title: 'Countries'})">
      <i class="fa fa-globe fa-lg"></i><br>
      <span>Countries</span>
    </button>
    <button class="btn-primary" type="button" onclick="F1.app.navigateTo(
        {url: 'admin/settings?view=provinces&back=settings', view: 'provinces', title: 'Provinces'})">
      <i class="fa fa-globe fa-lg"></i><br>
      <span>Provinces</span>
    </button>
    <button class="btn-primary" type="button" onclick="F1.app.navigateTo(
        {url: 'admin/settings?view=cities&back=settings', view: 'cities', title: 'Cities'})">
      <i class="fa fa-building fa-lg"></i><br>
      <span>Cities</span>
    </button>
    <br>
    <button class="btn-primary" type="button" onclick="F1.app.navigateTo(
      {url: 'admin/settings?view=suburbs&back=settings', view: 'suburbs', title: 'Suburbs'})">
    <i class="fa fa-map fa-lg"></i><br>
    <span>Suburbs</span>
    </button>
    <button class="btn-primary" type="button" onclick="F1.app.navigateTo(
      {url: 'admin/settings?view=accountants&back=settings', view: 'accountants', title: 'Accountants'})">
    <i class="fa fa-calculator fa-lg"></i><br>
    <span>Accountants</span>
  </button>


  </nav>
</div>
<!-- Compiled: 2024-06-17 16:03:33 -->