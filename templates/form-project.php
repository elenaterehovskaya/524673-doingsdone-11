<section class="content__side">
    <h2 class="content__side-heading">Проекты</h2>

    <nav class="main-navigation">
        <ul class="main-navigation__list">
            <?php foreach ($projects as $item): ?>

                <?php $classname = isset($item["id"]) && isset($_GET["id"]) && $item["id"] === intval($_GET["id"]) ?
                    "main-navigation__list-item--active" : ""; ?>
                <li class="main-navigation__list-item <?= $classname; ?>">

                    <a class="main-navigation__list-item-link" href="/?id=<?= $item["id"]; ?>">
                        <?php if (isset($item["name"])): ?>
                            <?= htmlspecialchars($item["name"]); ?>
                        <?php endif; ?>
                    </a>
                    <span class="main-navigation__list-item-count">
                        <?= getCountTasksProject($all_tasks, $item); ?>
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <a class="button button--transparent button--plus content__side-button">Добавить проект</a>
</section>

<main class="content__main">
    <h2 class="content__main-heading">Добавление проекта</h2>

    <form class="form" action="/add-project.php" method="post" autocomplete="off">
        <!-- Название -->
        <div class="form__row">
            <label class="form__label" for="project_name">Название <sup>*</sup></label>

            <?php $classname = isset($errors["name"]) ? "form__input--error" : ""; ?>
            <input class="form__input <?= $classname; ?>" type="text" name="name" id="project_name"
                   value="<?= getPostVal("name"); ?>" placeholder="Введите название проекта">

            <?php if (isset($errors["name"])): ?>
                <p class="form__message"><?= $errors["name"]; ?></p>
            <?php endif; ?>
        </div>

        <div class="form__row form__row--controls">
            <?php if (!empty($errors)): ?>
                <p class="error-message">Пожалуйста, исправьте ошибку в форме</p>
            <?php endif; ?>
            <input class="button" type="submit" name="" value="Добавить">
        </div>
    </form>
</main>
