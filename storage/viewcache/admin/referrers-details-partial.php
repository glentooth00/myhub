<style>
/* Page Specific CSS */

.page {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.page button .icon {
  height: 1.5em;
  width: 1.5em;
}

.badge {
  padding: 2px 4px;
  border-radius: 4px;
  background-color: #eee;
  font-size: 1.1em;
}


/* Content */

.content-wrapper {
  background-color: white;
  box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
  width: min(1024px, 100%);
  overflow-y: auto;
  padding: 1rem;
  flex: 1;
}

section ul {
  list-style: none;
  padding: 0;
  margin: 0;
}

section ul li {
  padding: 10px 0;
}

section ul li label {
  display: block;
  font-size: 0.7em;
  color: #777;
  margin-bottom: 5px;
}


/* Actions */

.actions-bar {
  margin-top: 1rem;
  margin-bottom: 1rem;
}

.actions-bar .btn-round span {
  font-size: 1.34rem;
  user-select: none;
}

.inactive span {
  color: crimson;
}

.deleted span {
  text-decoration: line-through;
}

.undelete {
  position: relative;
  background-color: seagreen;
  color: whitesmoke;
}

.undelete i:before {
  font-size: 22px;
}

.undelete i:after {
  content: "X";
  position: absolute;
  font-family: sans-serif;
  font-size: 21px;
  color: seagreen;
  left: 14px;
  top: 15px;
}

.permanent-delete {
  color: orangered;
}

#reset-icon {
  background-color: darkorange;
}

#backup-link {
  display: inline-block;
  margin-left: auto;
  padding: 0.67rem;
}


/* Tables */

.table-responsive {
  overflow-x: scroll;
}

.related-items {
  border-top: 2px solid whitesmoke;
  border-bottom: 2px solid whitesmoke;
  margin-inline: -1rem;
  margin-block: 0.67rem;
  padding: 5px 0;
}

.related-items + .related-items {
  border-top: none;
}

.related-items header,
.related-items footer {
  padding: 5px 1rem;
}

.related-items table {
  border-collapse: collapse;
  min-width: 750px;
  font-size: 13px;
  width: 100%;
}

.related-items th {
  text-align: left;
  padding: 6px 10px;
  white-space: nowrap;
  font-size: 0.9em;
}

.related-items thead th {
  background-color: #f2f2f2;
  border-bottom: 1px solid #ddd;
}

.related-items td {
  padding: 6px 10px;
  border-bottom: 1px solid #ddd;
  white-space: nowrap;
}
</style>
<div class="content-wrapper">
  <header>
    <h1><i class="fa fa-handshake-o fa-lg"></i>&nbsp;<?=$referrer->name?></h1>
  </header>
  <br>
  <section>
    <ul>
      <li><label>Referrer UID:</label> <span><?=escape($referrer->referrer_id)?></span></li>
      <li><label>ID Number:</label> <span><?=escape($referrer->id_number)?></span></li>
      <li><label>Phone Number:</label> <span><?=escape($referrer->phone_number)?></span></li>
      <li><label>Email:</label> <span><?=escape($referrer->email)?></span></li>
      <li><label>Notes:</label> <span><?=escape($referrer->notes)?></span></li>

      <li class="related-items">
        <header>
          <div class="flex-row space-between">
            <div class="flex-col">
              <label class="flex-row flex-gap align-center">
                <span>Referred Clients:</span>
                <span class="badge"><?=count($clients)?></span>
              </label>
            </div>
          </div>
        </header>
        <div class="related-clients table-responsive">
          <table>
            <thead>
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>ID number</th>
              <th>Phone Number</th>
              <th>Personal Email</th>
            </tr>
            </thead>
            <tbody>
<?php foreach( $clients as $i => $client ): ?>
            <tr>
              <td><?=($i+1).'.'?></td>
              <td>
                <a href="<?=$app->request->module . '/clients?view=details&amp;id=' . $client->id?>" onclick="F1.app.navigateTo(
                  {event, view:'details', title:'Client Details'})"><?=escape($client->name)?> (<?=escape($client->client_id)?>)</a>
              </td>
              <td><?=escape($client->id_number)?></td>
              <td><?=escape($client->phone_number)?></td>
              <td><?=escape($client->personal_email)?></td>
            </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <br>
      </li> <!-- related-items -->

      <li><label>Referrer User ID:</label> <span><?=$referrer->user_id?></span></li>
      <li><label>Referrer Client ID:</label> <span><?=$referrer->client_id?></span></li>  
      <li><label>Updated At:</label> <span><?=$referrer->updated_at?></span></li>
      <li><label>Updated By:</label> <span><?=escape($referrer->updated_by)?></span></li>
      <li><label>Created At:</label> <span><?=$referrer->created_at?></span></li>
      <li><label>Created By:</label> <span><?=escape($referrer->created_by)?></span></li>
    </ul>
  </section>


</div>
<button class="fab btn-round btn-primary" type="button" data-tippy-content="Edit"
    onclick="F1.app.navigateTo({url:'<?=$app->request->module?>/referrers?view=edit&id=<?=$id?>', view:'edit', title:'Referrer Form'})">
  <i class="fa fa-edit fa-lg"></i>
</button>
<script>
/* global F1 */

F1.deferred.push(function initReferrersDetailsPageView(app) {

  console.log('initReferrerDetailsPageView()');

  tippy('[data-tippy-content]', { allowHTML: true });

}); // END: initReferrersDetailsPageView
</script>
<!-- Compiled: 2024-06-24 10:41:52 -->