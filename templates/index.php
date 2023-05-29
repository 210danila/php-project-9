<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Анализатор страниц</title>
    <!--<link rel="stylesheet" href="style.css">-->

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
  </head>
  <body class="min-vh-100 d-flex flex-column">
    <?php include(__DIR__ . '/header.php'); ?>

    <main class="flex-grow-1">
      <div class="container-lg mt-3">
        <h1></h1>
        <div class="row">
          <div class="col-12 col-md-10 col-lg-8 mx-auto border rounded-3 bg-light p-5">
            <h1 class="display-3">Анализатор страниц</h1>
            <p class="lead">Бесплатно проверяйте сайты на SEO пригодность</p>

            <form action="<?= $router->urlFor('urls.index') ?>" method="post" class="row" required="">
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
          </div>
        </div>
      </div>
    </main>

    <?php include(__DIR__ . '/footer.php'); ?>
  </body>
</html>