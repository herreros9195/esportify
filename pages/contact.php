<?php
$pageTitle = 'Contact';
require_once __DIR__ . '/../includes/header.php';

$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!empty($name) && filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($message)) {
        // Simulation d'envoi (dans un vrai projet, utiliser mail() ou PHPMailer)
        setFlash('Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.', 'success');
        $sent = true;
    } else {
        setFlash('Veuillez remplir correctement tous les champs.', 'danger');
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <h2 class="fw-bold mb-4">Contactez-nous</h2>
        <div class="card shadow">
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Envoyer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
