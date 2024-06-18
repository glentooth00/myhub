<?php /* User Logout Controller */

$app->security = use_security();

$app->security->logout();

redirect( 'user/login?u=logout' );
