<?php /* Admin Module - TCCs SPA - TCCs List Sub Controller */

use App\Models\Tcc as TccModel;
use App\Models\User as UserModel;
use App\Models\UserSettings as UserSettingsModel;



// ----------
// -- POST --
// ----------

if ($app->request->isPost) {
  exit;
}




// ---------
// -- GET --
// ---------

function get_cert_url($tcc)
{
  global $app;
  return $app->uploadsRef . '/' . $tcc->client_name . '_' . $tcc->client_id2 . '/' .
    $tcc->tcc_pin . '.pdf?' . time();
}

function get_cert_link($tcc)
{
  if (empty($tcc->tcc_pin) or empty($tcc->tax_cert_pdf))
    return '';
  return '<a href="' . get_cert_url($tcc) . '" target="_blank" title="View Certificate PDF"' .
    ' onclick="event.stopPropagation()"><i class="fa fa-file-pdf-o"></i></a>';
}



// /* settings */
$settings = new UserSettingsModel($app);


// /* request */

$accountantId = $_GET['accountant'] ?? $settings->getSettingValue('tccs_accountant', 'All');
$category = $_GET['category'] ?? $settings->getSettingValue('tccs_category', 'All');
$days = $_GET['days'] ?? $settings->getSettingValue('tccs_days', 'this-week');

$settings->saveIfChanged('tccs_accountant', $accountantId);
$settings->saveIfChanged('tccs_category', $category);
$settings->saveIfChanged('tccs_days', $days);



// /* lists */
$accountantName = null;
$tccModel = new TccModel($app);
$userModel = new UserModel($app);
$accountantIds = $app->user->id; // i used the logged in user ID instead of the $accountantId
// echo '<b>'. $accountantId .'</b>'; when i used this it just returns "All"


$accountants = $userModel->getUsersByRole('accountant');
$accountants[] = (object) ['id' => 'Personal', 'name' => 'Personal'];

foreach ($accountants as $ac) {
  if (empty($ac->name))
    $ac->name = trim($ac->first_name . ' ' . $ac->last_name);
  if ($ac->id == $accountantIds)
    ($accountantName = $ac->name);
}

$tccs = $tccModel->getAllByAccountant($accountantName, [
  'days' => $days,
  'category' => $category,
]);

