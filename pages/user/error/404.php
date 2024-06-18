<?php if ( ! $app->request->isAjax ): ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <title>404 Not Found</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { background: #f3f3f3; }
  </style>
</head>
<body>
<?php endif; ?>  
<style>
  .container { display: flex; background: #f3f3f3; justify-content: center; 
    align-items: center; min-height: 80vh; font-family: 'Arial', sans-serif; }
  .message { text-align: center; padding: 15px; }
  .message h1 { font-size: 4em; color: #333; }
  .message p { color: #666; font-size: 1.5em; margin-top: 15px; }
  .message a { display: inline-block; margin-top: 30px; padding: 10px 20px; background: #333; 
    color: #fff; text-decoration: none; border-radius: 5px; transition: background 0.3s; }
  .message a:hover { background: #555; }
</style>
<div class="container">
  <div class="message">
    <h1>404</h1>
    <p>Oops! The page you're looking for doesn't exist.</p>
    <a href="<?=$app->baseUri?>" onclick="if (history.length > 1) { 
      history.back(); event.preventDefault(); }">Go Back</a>
  </div>
</div>
<?php if ( ! $app->request->isAjax ): ?>
</body>
</html>
<?php endif; ?> 