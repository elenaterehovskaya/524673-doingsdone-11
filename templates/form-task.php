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

    <a class="button button--transparent button--plus content__side-button" href="/add-project.php">Добавить проект</a>
</section>

<main class="content__main">
    <h2 class="content__main-heading">Добавление задачи</h2>

    <form class="form" action="/add-task.php" method="post" enctype="multipart/form-data" autocomplete="off">
        <!-- Название -->
        <div class="form__row">
            <label class="form__label" for="name">Название <sup>*</sup></label>

            <?php $classname = isset($errors["title"]) ? "form__input--error" : ""; ?>
            <input class="form__input <?= $classname; ?>" type="text" name="title" id="name"
                   value="<?= getPostVal("title"); ?>" placeholder="Введите название">

            <?php if (isset($errors["title"])): ?>
                <p class="form__message"><?= $errors["title"]; ?></p>
            <?php endif; ?>
        </div>

        <!-- Проект -->
        <div class="form__row">
            <label class="form__label" for="project">Проект <sup>*</sup></label>

            <?php $classname = isset($errors["project_id"]) ? "form__input--error" : ""; ?>
            <select class="form__input form__input--select <?= $classname; ?>" name="project_id" id="project">
                <option>Выберите проект</option>

                <?php foreach ($projects as $item): ?>
                    <?php if (isset($item["id"])): ?>
                        <option value="<?= $item["id"]; ?>"
                            <?php if ($item["id"] == getPostVal("project_id")): ?>
                                selected
                            <?php endif; ?>
                        >
                            <?= $item["name"]; ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>

            <?php if (isset($errors["project_id"])): ?>
                <p class="form__message"><?= $errors["project_id"]; ?></p>
            <?php endif; ?>
        </div>

        <!-- Дата выполнения -->
        <div class="form__row">
            <label class="form__label" for="date">Дата выполнения</label>

            <?php $classname = isset($errors["deadline"]) ? "form__input--error" : ""; ?>
            <input class="form__input form__input--date <?= $classname; ?>" type="text" name="deadline" id="date"
                   value="<?= getPostVal("deadline"); ?>" placeholder="Введите дату в формате ГГГГ-ММ-ДД">

            <?php if (isset($errors["deadline"])): ?>
                <p class="form__message"><?= $errors["deadline"]; ?></p>
            <?php endif; ?>
        </div>

        <!-- Файл -->
        <div class="form__row">
            <label class="form__label" for="file">Файл</label>

            <?php $classname = isset($errors["file"]) ? "form__input--error" : ""; ?>
            <div class="form__input-file <?= $classname; ?>">
                <input class="visually-hidden" type="file" name="file" id="file" value="">
                <label class="button button--transparent" for="file">
                    <span>Выберите файл</span>
                </label>

                <?php if (isset($errors["file"])): ?>
                    <p class="form__message"><?= $errors["file"]; ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="form__row form__row--controls">
            <?php if (isset($errors)): ?>
                <p class="error-message">Пожалуйста, исправьте ошибки в форме</p>
            <?php endif; ?>
            <input class="button" type="submit" name="" value="Добавить">
        </div>
    </form>
</main>
