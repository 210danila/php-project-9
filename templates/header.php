<header class="flex-shrink-0">
    <nav class="navbar navbar-expand-md navbar-dark bg-dark px-3">
        <?php if (isset($router) && isset($activeLink)) : ?>
            <a class="navbar-brand" href="<?= $router->urlFor('root') ?>">Анализатор страниц</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link<?= $activeLink === 'Главная' ? ' active' : '' ?>" href="<?= $router->urlFor('root') ?>">Главная</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= $activeLink === 'Сайты' ? ' active' : '' ?>" href="<?= $router->urlFor('urls.index') ?>">Сайты</a>
                    </li>
                </ul>
            </div>
        <?php endif; ?>
    </nav>
</header>
