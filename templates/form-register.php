<section class="content__side">
    <p class="content__side-info">Если у вас уже есть аккаунт, авторизуйтесь на сайте</p>
    <a class="button button--transparent content__side-button" href="/auth.php">Войти</a>
</section>

<main class="content__main">
    <h2 class="content__main-heading">Регистрация аккаунта</h2>

    <form class="form" action="/register.php" method="post" autocomplete="off">
        <!-- E-mail -->
        <div class="form__row">
            <label class="form__label" for="email">E-mail <sup>*</sup></label>

            <?php $className = isset($validErrors["email"]) ? "form__input--error" : ""; ?>
            <input class="form__input <?= $className; ?>" type="text" name="email" id="email"
                   value="<?= getPostVal("email"); ?>" placeholder="Введите e-mail">

            <?php if (isset($validErrors["email"])): ?>
                <p class="form__message"><?= $validErrors["email"]; ?></p>
            <?php endif; ?>
        </div>

        <!-- Пароль -->
        <div class="form__row">
            <label class="form__label" for="password">Пароль <sup>*</sup></label>

            <?php $className = isset($validErrors["password"]) ? "form__input--error" : ""; ?>
            <input class="form__input <?= $className; ?>" type="password" name="password" id="password"
                   value="" placeholder="Введите пароль">

            <?php if (isset($validErrors["password"])): ?>
                <p class="form__message"><?= $validErrors["password"]; ?></p>
            <?php endif; ?>
        </div>

        <!-- Имя -->
        <div class="form__row">
            <label class="form__label" for="name">Имя <sup>*</sup></label>

            <?php $className = isset($validErrors["name"]) ? "form__input--error" : ""; ?>
            <input class="form__input <?= $className; ?>" type="text" name="name" id="name"
                   value="<?= getPostVal("name"); ?>" placeholder="Введите имя">

            <?php if (isset($validErrors["name"])): ?>
                <p class="form__message"><?= $validErrors["name"]; ?></p>
            <?php endif; ?>
        </div>

        <div class="form__row form__row--controls">
            <?php if (!empty($validErrors)): ?>
                <p class="error-message">Пожалуйста, исправьте ошибки в форме</p>
            <?php endif; ?>
            <input class="button" type="submit" name="" value="Зарегистрироваться">
        </div>
    </form>
</main>