<?php
$pageTitle = 'Contact';
require_once __DIR__ . '/../includes/header.php';

$formData = [
    'pseudo' => $_SESSION['user_pseudo'] ?? '',
    'email' => $_SESSION['user_email'] ?? '',
    'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['pseudo'] = cleanInput($_POST['pseudo'] ?? '');
    $formData['email'] = cleanInput($_POST['email'] ?? '');
    $formData['message'] = cleanInput($_POST['message'] ?? '');

    if (!verifyPostCsrf()) {
        setFlash('Jeton de securite invalide. Veuillez reessayer.', 'danger');
    } elseif ($formData['email'] === '' || $formData['message'] === '' || !filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        setFlash('Veuillez remplir correctement les champs obligatoires.', 'danger');
    } else {
        $subject = 'Nouveau message de contact Esportify';
        $body = "Pseudo / nom : " . ($formData['pseudo'] !== '' ? $formData['pseudo'] : 'Non renseigne') . "\n";
        $body .= "Email : {$formData['email']}\n";
        $body .= "------------------------\n";
        $body .= $formData['message'] . "\n";

        sendAppEmail('contact@esportify.fr', $subject, $body, $formData['email']);
        setFlash('Votre message a bien ete envoye. Nous vous repondrons dans les plus brefs delais.', 'success');
        $formData['message'] = '';
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
                        <label for="pseudo" class="form-label">Pseudo ou nom</label>
                        <input type="text" class="form-control" id="pseudo" name="pseudo" value="<?= e($formData['pseudo']) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required value="<?= e($formData['email']) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message *</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required><?= e($formData['message']) ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Envoyer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
