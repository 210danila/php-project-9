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

    <main class="flex-grow-1">
      <div class="container-lg mt-3">
        <h1>Сайты</h1>
        <div class="table-responsive">
          <table class="table table-bordered table-hover text-nowrap" data-test="urls">
            <tbody>
              <tr>
                <th>ID</th>
                <th>Имя</th>
                <th>Последняя проверка</th>
                <th>Код ответа</th>
              </tr>
              <?php foreach ($urlsData as $url) : ?>
                <tr>
                  <td><?= htmlspecialchars($url['name']) ?></td>
                  <td><a href="/urls/<?= $url['id'] ?>"><?= htmlspecialchars($url['name']) ?></a></td>
                    <?php if (!empty($url['check'])) : ?>
                      <td><?= $url['check']['created_at'] ?></td>
                      <td><?= $url['check']['status_code'] ?></td>
                    <?php else : ?>
                      <td></td>
                      <td></td>
                    <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <?php include(__DIR__ . '/../footer.php'); ?>
  </body>
</html>
