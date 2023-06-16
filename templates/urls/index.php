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
          <?php if (isset($urlsData) and isset($router)) : ?>
                <?php foreach ($urlsData as $url) : ?>
                <tr>
                  <td><?= $url['id'] ?></td>
                  <td><a href="<?= $router->urlFor('urls.show', ['id' => $url['id']]) ?>"><?= htmlspecialchars($url['name']) ?></a></td>
                    <?php if (isset($url['check_status_code']) and isset($url['check_created_at'])) : ?>
                      <td><?= $url['check_created_at'] ?></td>
                      <td><?= $url['check_status_code'] ?></td>
                    <?php else : ?>
                    <td></td>
                    <td></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
