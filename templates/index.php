<main class="flex-grow-1">
  <div class="container-lg mt-3">
    <h1></h1>
    <div class="row">
      <div class="col-12 col-md-10 col-lg-8 mx-auto border rounded-3 bg-light p-5">
        <h1 class="display-3">Анализатор страниц</h1>
        <p class="lead">Бесплатно проверяйте сайты на SEO пригодность</p>

        <form action="<?= $router->urlFor('urls.store') ?>" method="post" class="row" required="">
          <div class="col-8">

            <input type="text" name="url[name]" value="<?= !isset($urlName) || empty($urlName) ? "" : htmlspecialchars($urlName) ?>" class="form-control form-control-lg <?=empty($errors) ? "" : "is-invalid"?>" placeholder="https://www.example.com">
            <?php if (isset($errors) && !empty($errors)) : ?>
              <div class="invalid-feedback"><?= $errors[0] ?></div>
            <?php endif; ?>
          </div>
          <div class="col-2">
            <input type="submit" class="btn btn-primary btn-lg ms-3 px-5 text-uppercase mx-3" value="Проверить">
          </div>
        </form>
      </div>
    </div>
  </div>
</main>
