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
          <?php if (isset($urls) && isset($urlChecks)) : ?>
                <?php foreach ($urls as $url) : ?>
                <tr>
                  <td><?= $url['id'] ?></td>
                  <?php if (isset($router)) : ?>
                      <td><a href="<?= $router->urlFor('urls.show', ['id' => $url['id']]) ?>"><?= htmlspecialchars($url['name']) ?></a></td>
                  <?php endif; ?>
                  <td><?= $urlChecks[$url['id']]['created_at'] ?? "" ?></td>
                  <td><?= $urlChecks[$url['id']]['status_code'] ?? "" ?></td>
                </tr>
                <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
