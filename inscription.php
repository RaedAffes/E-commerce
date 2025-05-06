<?php
session_start();
require_once 'config.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['motdepasse'];
    $confirm_password = $_POST['confirm_motdepasse'];
    $phone = filter_input(INPUT_POST, 'Num_Tel', FILTER_SANITIZE_STRING);
    $adresse = filter_input(INPUT_POST, 'adresse', FILTER_SANITIZE_STRING);
    $code_postal = filter_input(INPUT_POST, 'code_postal', FILTER_SANITIZE_STRING);
    $ville = filter_input(INPUT_POST, 'ville', FILTER_SANITIZE_STRING);
    $pays = filter_input(INPUT_POST, 'pays', FILTER_SANITIZE_STRING);

    // Validation
    if (!$nom || strlen($nom) < 2) {
        $errors[] = 'Le nom doit contenir au moins 2 caractères.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email invalide.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Les mots de passe ne correspondent pas.';
    }
    if (!preg_match('/^[0-9+]{8,15}$/', $phone)) {
        $errors[] = 'Numéro de téléphone invalide.';
    }
    if (!$adresse || !$code_postal || !$ville || !$pays) {
        $errors[] = 'Tous les champs d\'adresse sont requis.';
    }

    // Check email existence
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        $errors[] = 'Cet email est déjà utilisé.';
    }

    // Handle profile image
    $profil_img = '';
    if (isset($_FILES['profil_img']) && $_FILES['profil_img']['name']) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        $target_file = $target_dir . uniqid() . '_' . basename($_FILES['profil_img']['name']);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if (in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif']) && $_FILES['profil_img']['size'] <= 5000000) {
            if (move_uploaded_file($_FILES['profil_img']['tmp_name'], $target_file)) {
                $profil_img = $target_file;
            } else {
                $errors[] = 'Erreur lors du téléchargement de l\'image.';
            }
        } else {
            $errors[] = 'Image invalide (format ou taille).';
        }
    }

    // Insert user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (nom, email, password, phone, adresse, code_postal, ville, pays, profil_img) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        try {
            $stmt->execute([$nom, $email, $hashed_password, $phone, $adresse, $code_postal, $ville, $pays, $profil_img]);
            $success = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
        } catch (PDOException $e) {
            $errors[] = 'Erreur lors de la création du compte : ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - ENSITEK</title>
    <meta name="description" content="Créez votre compte sur ENSITEK pour acheter du matériel informatique et bénéficier de nos services.">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div id="root">
        <header class="navbar">
            <div class="container">
                <div class="navbar-content">
                    <a href="index.html" class="logo">ENSITEK</a>
                    <nav class="nav-desktop">
                        <ul class="nav-links">
                            <li><a href="index.html">Accueil</a></li>
                            <li><a href="produits.php">Produits</a></li>
                            <li><a href="contact.html">Contact</a></li>
                            <li><a href="about.html">À propos</a></li>
                        </ul>
                    </nav>
                    <div class="nav-actions">
                        <form class="search-form">
                            <div class="search-box">
                                <input type="text" placeholder="Rechercher..." aria-label="Search">
                                <button type="submit" class="icon-btn" aria-label="Rechercher"><i class="fas fa-search"></i></button>
                            </div>
                        </form>
                        <a href="compte.php" class="icon-btn" aria-label="Compte"><i class="fas fa-user"></i></a>
                        <a href="panier.php" class="icon-btn cart-icon" aria-label="Panier">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-count">0</span>
                        </a>
                    </div>
                    <button class="mobile-menu-btn" aria-label="Menu mobile">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
                <div class="mobile-menu">
                    <ul class="mobile-nav-links">
                        <li><a href="index.html">Accueil</a></li>
                        <li><a href="produits.php">Produits</a></li>
                        <li><a href="contact.html">Contact</a></li>
                        <li><a href="about.html">À propos</a></li>
                    </ul>
                    <div class="mobile-actions">
                        <form class="search-form">
                            <div class="search-box">
                                <input type="text" placeholder="Rechercher..." aria-label="Search">
                                <button type="submit" class="icon-btn" aria-label="Rechercher"><i class="fas fa-search"></i></button>
                            </div>
                        </form>
                        <a href="compte.php" class="icon-btn" aria-label="Compte"><i class="fas fa-user"></i></a>
                        <a href="panier.php" class="icon-btn" aria-label="Panier"><i class="fas fa-shopping-cart"></i></a>
                    </div>
                </div>
            </div>
        </header>

        <main class="container py-5">
            <h1 class="page-title">Créer un compte</h1>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <form class="form" method="POST" enctype="multipart/form-data" id="inscription-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nom" class="form-label">Nom et Prénom</label><br>
                        <input type="text" class="form-control" id="nom" name="nom" required><br>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="profil_img" class="form-label">Photo de profil</label><br>
                        <input type="file" class="form-control" id="profil_img" name="profil_img" accept="image/*"><br>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="Num_Tel" class="form-label">Numéro de téléphone</label><br>
                        <input type="tel" class="form-control" id="Num_Tel" name="Num_Tel" required><br>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email</label><br>
                        <input type="email" class="form-control" id="email" name="email" required><br>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="motdepasse" class="form-label">Mot de passe</label><br>
                        <input type="password" class="form-control" id="motdepasse" name="motdepasse" required><br>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="confirm_motdepasse" class="form-label">Confirmer le mot de passe</label><br>
                        <input type="password" class="form-control" id="confirm_motdepasse" name="confirm_motdepasse" required><br>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="adresse" class="form-label">Adresse</label><br>
                        <input type="text" class="form-control" id="adresse" name="adresse" required><br>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="code_postal" class="form-label">Code postal</label><br>
                        <input type="text" class="form-control" id="code_postal" name="code_postal" required><br>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="ville" class="form-label">Ville</label><br>
                        <input type="text" class="form-control" id="ville" name="ville" required><br>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="pays" class="form-label">Pays</label><br>
                        <input type="text" class="form-control" id="pays" name="pays" required><br>
                    </div><br>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Créer un compte</button><br>
                        <p class="form-footer mt-3">Déjà un compte ? <a href="compte.php">Connectez-vous ici</a></p><br>
                    </div>
                </div>
            </form>
        </main> <br>

        <footer class="footer">
            <div class="container">
                <div class="footer-grid">
                    <div class="footer-col">
                        <h3 class="footer-title">ENSITEK</h3>
                        <p class="footer-description">Votre partenaire pour tous vos besoins en matériel informatique et services IT.</p>
                        <div class="footer-contact">
                            <div class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <span><a href="mailto:ensitek.sg@gmail.com">ensitek.sg@gmail.com</a></span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-phone"></i>
                                <span>+33 1 23 45 67 89</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>123 ENSI, Campus Universitaire, Manouba, Tunisie</span>
                            </div>
                        </div>
                    </div>
                    <div class="footer-col">
                        <h3 class="footer-title">Catégories</h3>
                        <ul class="footer-links">
                            <li><a href="produits.php?categorie=ordinateurs-portables">Ordinateurs portables</a></li>
                            <li><a href="produits.php?categorie=ordinateurs-bureau">Ordinateurs de bureau</a></li>
                            <li><a href="produits.php?categorie=composants">Composants</a></li>
                            <li><a href="produits.php?categorie=peripheriques">Périphériques</a></li>
                            <li><a href="produits.php?categorie=accessoires">Accessoires</a></li>
                        </ul>
                    </div>
                    <div class="footer-col">
                        <h3 class="footer-title">Service client</h3>
                        <ul class="footer-links">
                            <li><a href="contact.html">Contact</a></li>
                            <li><a href="about.html">À propos de nous</a></li>
                            <li><a href="faq.html">FAQ</a></li>
                            <li><a href="livraison.html">Livraison</a></li>
                            <li><a href="retours.html">Retours et remboursements</a></li>
                        </ul>
                    </div>
                    <div class="footer-col">
                        <h3 class="footer-title">Newsletter</h3>
                        <p class="footer-description">Abonnez-vous à notre newsletter pour recevoir les dernières offres et promotions.</p>
                        <form class="newsletter-form">
                            <input type="email" placeholder="Votre email" required>
                            <button type="submit" class="btn btn-primary">S'abonner</button>
                        </form>
                    </div>
                </div>
                <div class="footer-bottom">
                    <p>© 2025 ENSITEK. Tous droits réservés.</p>
                </div>
            </div>
        </footer>
    </div>
    <script src="main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('inscription-form').addEventListener('submit', function(e) {
            const password = document.getElementById('motdepasse').value;
            const confirmPassword = document.getElementById('confirm_motdepasse').value;
            const email = document.getElementById('email').value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
                return;
            }
            if (password.length < 8) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 8 caractères.');
                return;
            }
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Veuillez entrer un email valide.');
                return;
            }
        });
    </script>
</body>

</html>