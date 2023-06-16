<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Анализатор страниц</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
  </head>

  <body class="min-vh-100 d-flex flex-column">
    <?php if (isset($activeLink) && isset($this)) : ?>
        <?= $this->fetch('/header.php', ['activeLink' => $activeLink]) ?>
    <?php endif; ?>
    <?php if (isset($flash)) : ?>
        <?php if (array_key_exists('success', $flash)) : ?>
        <div class="alert alert-success"><?= $flash['success'][0] ?></div>
        <?php elseif (array_key_exists('error', $flash)) : ?>
        <div class="alert alert-danger"><?= $flash['error'][0] ?></div>
        <?php endif; ?>
    <?php endif; ?>
    <?php if (isset($content)) : ?>
        <?=$content?>
    <?php endif; ?>
    <?php if (isset($this)) : ?>
        <?= $this->fetch('/footer.php') ?>
    <?php endif; ?>
  <
  </body>
<html>