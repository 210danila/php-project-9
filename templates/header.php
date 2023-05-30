<header class="flex-shrink-0">
    <nav class="navbar navbar-expand-md navbar-dark bg-dark px-3">
        <?php if (isset($router) && isset($activeLink)) : ?>
            <a class="navbar-brand" href="<?= $router->urlFor('root') ?>">Анализатор страниц</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <?php if ($activeLink === 'Главная') : ?>
                        <li class="nav-item">
                            <a class="nav-link active" href="<?= $router->urlFor('root') ?>">Главная</a>
                        </li>
                    <?php else : ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $router->urlFor('root') ?>">Главная</a>
                        </li>
                    <?php endif; ?>
                    <?php if ($activeLink === 'Сайты') : ?>
                        <li class="nav-item">
                            <a class="nav-link active" href="<?= $router->urlFor('urls.index') ?>">Сайты</a>
                        </li>
                    <?php else : ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= $router->urlFor('urls.index') ?>">Сайты</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>
    </nav>
</header>
