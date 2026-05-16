<?php
require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    redirect('index.php?page=home');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = cleanInput($_POST['pseudo'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirm'] ?? '';

    if (!verifyPostCsrf()) {
        $errors[] = 'Jeton de securite invalide.';
    }
    if (strlen($pseudo) < 3 || strlen($pseudo) > 50) {
        $errors[] = 'Le pseudo doit contenir entre 3 et 50 caracteres.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'adresse email n\'est pas valide.';
    }
    if (!validateStrongPassword($password)) {
        $errors[] = 'Le mot de passe doit contenir au moins 8 caracteres, une majuscule, une minuscule, un chiffre et un caractere special.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Les mots de passe ne correspondent pas.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email OR pseudo = :pseudo LIMIT 1");
        $stmt->execute([':email' => $email, ':pseudo' => $pseudo]);
        if ($stmt->fetch()) {
            $errors[] = 'Un compte existe deja avec cet email ou ce pseudo.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (pseudo, email, password_hash, role) VALUES (:pseudo, :email, :hash, 'joueur')");
            $stmt->execute([':pseudo' => $pseudo, ':email' => $email, ':hash' => $hash]);
            setFlash('Inscription reussie ! Vous pouvez maintenant vous connecter.', 'success');
            redirect('index.php?page=login');
        }
    }
}

$pageTitle = 'Inscription';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow">
            <div class="card-body">
                <h2 class="card-title fw-bold mb-4">Inscription</h2>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <div class="mb-3">
                        <label for="pseudo" class="form-label">Pseudo</label>
                        <input type="text" class="form-control" id="pseudo" name="pseudo" required minlength="3" maxlength="50" value="<?= e($_POST['pseudo'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Adresse email</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password" name="password" required minlength="8">
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password', this)"><i class="bi bi-eye"></i></button>
                        </div>
                        <div class="form-text">Minimum 8 caracteres, avec majuscule, minuscule, chiffre et caractere special.</div>
                    </div>
                    <div class="mb-3">
                        <label for="password_confirm" class="form-label">Confirmer le mot de passe</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="togglePassword('password_confirm', this)"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Creer mon compte</button>
                </form>
                <hr>
                <p class="text-center mb-0">Deja inscrit ? <a href="index.php?page=login">Se connecter</a></p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
