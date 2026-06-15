<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/base.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/auth.css">
</head>

<body>
    <div class="login-wrapper">
        <div class="login-box">
            <div class="login-logo">
                <img src="<?= BASE_URL ?>/images/logo.png" alt="<?= APP_NAME ?>">
            </div>
            <h1>Login</h1>
            <p>Asset Management System</p>
            <?php if (isset($_SESSION['flash'])): ?>
                <div class="alert alert-<?= $_SESSION['flash']['type'] ?>">
                    <?= $_SESSION['flash']['message'] ?>
                </div>
                <?php unset($_SESSION['flash']); ?>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>/auth/processLogin" method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autocomplete="username">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>

                <button type="submit" class="btn-login">Login</button>
            </form>
        </div>
    </div>
</body>

</html>