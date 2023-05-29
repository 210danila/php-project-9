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
    <?php include(__DIR__ . '/../header.php'); ?>

    <?php if (array_key_exists('success', $flash)) : ?>
      <div class="alert alert-success"><?= $flash['success'][0] ?></div>
    <?php elseif (array_key_exists('error', $flash)) : ?>
      <div class="alert alert-danger"><?= $flash['error'][0] ?></div>
    <?php endif; ?>
    
    <main class="flex-grow-1">
      <div class="container-lg mt-3">
        <h1>Сайт: <?= htmlspecialchars($url['name']) ?></h1>
        <div class="table-responsive">
          <table class="table table-bordered table-hover text-nowrap" data-test="url">
            <tbody>
              <tr>
                <td>ID</td>
                <td><?= $url['id'] ?></td>
              </tr>
              <tr>
                <td>Имя</td>
                <td><?= htmlspecialchars($url['name']) ?></td>
              </tr>
              <tr>
                <td>Дата создания</td>
                <td><?= $url['created_at'] ?></td>
              </tr>
            </tbody>
          </table>
        </div>
        <h2 class="mt-5 mb-3">Проверки</h2>
        <form method="post" action="<?= $router->urlFor('urls.checks.store', ['id' => $url['id']]) ?>" style="margin-bottom: 1em;">
          <input type="submit" class="btn btn-primary" value="Запустить проверку">
        </form>
        <table class="table table-bordered table-hover" data-test="checks">
          <tbody>
            <tr>
              <th>ID</th>
              <th>Код ответа</th>
              <th>h1</th>
              <th>title</th>
              <th>description</th>
              <th>Дата создания</th>
            </tr>
            <?php foreach ($urlChecks as $check) : ?>
              <tr>
                <td><?= $check['id'] ?></td>
                <td><?= $check['status_code'] ?></td>
                <td><?= $check['h1'] ?></td>
                <td><?= $check['title'] ?></td>
                <td><?= $check['description'] ?></td>
                <td><?= $check['created_at'] ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>

    <?php include(__DIR__ . '/../footer.php'); ?>
  </body>
</html>