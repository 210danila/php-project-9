<main class="flex-grow-1">
  <div class="container-lg mt-3">
    <h1></h1>
    <div class="row">
      <div class="col-12 col-md-10 col-lg-8 mx-auto border rounded-3 bg-light p-5">
        <h1 class="display-3">Анализатор страниц</h1>
        <p class="lead">Бесплатно проверяйте сайты на SEO пригодность</p>

        <?php if (isset($router) && isset($urlName) && isset($errors)) : ?>
          <form action="<?= $router->urlFor('urls.store') ?>" method="post" class="row" required="">
            <div class="col-8">

              <?php if (empty($errors)) : ?>
                <input type="text" name="url[name]" value="" class="form-control form-control-lg" placeholder="https://www.example.com">
              <?php else : ?>
                  <?php if (empty($urlName)) : ?>
                  <input type="text" name="url[name]" value="" class="form-control form-control-lg is-invalid" placeholder="https://www.example.com">
                  <?php else : ?>
                  <input type="text" name="url[name]" value=<?= htmlspecialchars($urlName) ?> class="form-control form-control-lg is-invalid" placeholder="https://www.example.com">
                  <?php endif; ?>
                <div class="invalid-feedback"><?= $errors[0] ?></div>
              <?php endif; ?>

            </div>
            <div class="col-2">
              <input type="submit" class="btn btn-primary btn-lg ms-3 px-5 text-uppercase mx-3" value="Проверить">
            </div>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
</main>
