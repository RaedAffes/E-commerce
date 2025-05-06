<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (!$email || !$password) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $stmt = $pdo->prepare('SELECT id, nom, email, password, is_admin FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nom'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['is_admin'] = $user['is_admin'];
            echo "<script>
                localStorage.setItem('userToken', '" . htmlspecialchars($user['email'], ENT_QUOTES) . "');
                localStorage.setItem('currentUser', JSON.stringify(" . json_encode($user) . "));
                window.location.href = 'index.html';
            </script>";
            exit;
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace client - Connexion</title>
    <meta name="description" content="Connectez-vous à votre compte ENSITEK">
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
                                <button type="submit" class="icon-btn"><i class="fas fa-search"></i></button>
                            </div>
                        </form>
                        <a href="compte.php" class="icon-btn"><i class="fas fa-user"></i></a>
                        <a href="panier.php" class="icon-btn cart-icon">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-count">0</span>
                        </a>
                    </div>
                    <button class="mobile-menu-btn">
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
                                <button type="submit" class="icon-btn"><i class="fas fa-search"></i></button>
                            </div>
                        </form>
                        <a href="compte.php" class="icon-btn"><i class="fas fa-user"></i></a>
                        <a href="panier.php" class="icon-btn"><i class="fas fa-shopping-cart"></i></a>
                    </div>
                </div>
            </div>
        </header>

        <main class="container py-5">
            <h1 class="page-title">Connectez-vous</h1>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form class="form" method="POST">
                <div class="form-group mb-3">
                    <label for="email">Email</label> <br>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="form-group mb-3">
                    <label for="password">Mot de passe</label> <br>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary">Se connecter</button>
                <p class="form-footer mt-3">Pas encore de compte ? <a href="inscription.php">Inscrivez-vous ici</a></p>
            </form>
        </main><br>

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
</body>

</html>