<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: compte.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_cart'])) {
        $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
        if ($product_id && $quantity >= 0) {
            $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = ?');
            $stmt->execute([$product_id]);
            $stock = $stmt->fetchColumn();
            if ($quantity > $stock) {
                $error = 'Quantité demandée supérieure au stock disponible.';
            } else {
                if ($quantity == 0) {
                    $stmt = $pdo->prepare('DELETE FROM cart WHERE user_id = ? AND product_id = ?');
                    $stmt->execute([$_SESSION['user_id'], $product_id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?');
                    $stmt->execute([$quantity, $_SESSION['user_id'], $product_id]);
                }
            }
        }
    } elseif (isset($_POST['checkout'])) {
        $stmt = $pdo->prepare('SELECT c.product_id, c.quantity, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $cart_items = $stmt->fetchAll();
        foreach ($cart_items as $item) {
            if ($item['quantity'] > $item['stock']) {
                $error = 'Un ou plusieurs produits ont une quantité supérieure au stock disponible.';
                break;
            }
        }
        if (!isset($error)) {
            $stmt = $pdo->prepare('SELECT SUM(p.price * c.quantity) as total FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $total = $stmt->fetchColumn();

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('INSERT INTO orders (user_id, total) VALUES (?, ?)');
                $stmt->execute([$_SESSION['user_id'], $total]);
                $order_id = $pdo->lastInsertId();

                $stmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) SELECT ?, c.product_id, c.quantity, p.price FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?');
                $stmt->execute([$order_id, $_SESSION['user_id']]);

                foreach ($cart_items as $item) {
                    $stmt = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
                    $stmt->execute([$item['quantity'], $item['product_id']]);
                }

                $stmt = $pdo->prepare('DELETE FROM cart WHERE user_id = ?');
                $stmt->execute([$_SESSION['user_id']]);

                $pdo->commit();
                $success = 'Commande passée avec succès !';
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Erreur lors du passage de la commande : ' . $e->getMessage();
            }
        }
    }
}

$stmt = $pdo->prepare('SELECT c.product_id, c.quantity, p.name, p.price, p.image FROM cart c JOIN products p ON c.product_id = p.id WHERE c.user_id = ?');
$stmt->execute([$_SESSION['user_id']]);
$cart_items = $stmt->fetchAll();

$total = 0;
foreach ($cart_items as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier - ENSITEK</title>
    <meta name="description" content="Votre panier d'achats chez ENSITEK">
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
                            <span class="cart-count"><?php echo count($cart_items); ?></span>
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
            <h1 class="page-title">Votre Panier</h1>
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if (empty($cart_items)): ?>
                <p>Votre panier est vide. <a href="produits.php">Continuer vos achats</a></p>
            <?php else: ?>
                <div class="cart-items">
                    <?php foreach ($cart_items as $item): ?>
                        <div class="cart-item row mb-3">
                            <div class="col-md-2">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-fluid">
                            </div>
                            <div class="col-md-4">
                                <h5><?php echo htmlspecialchars($item['name']); ?></h5>
                            </div>
                            <div class="col-md-2">
                                <p><?php echo number_format($item['price'], 2); ?> DT</p>
                            </div>
                            <div class="col-md-2">
                                <form method="POST">
                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="0" class="form-control w-50 d-inline">
                                    <button type="submit" name="update_cart" class="btn btn-primary btn-sm">Mettre à jour</button>
                                </form>
                            </div>
                            <div class="col-md-2">
                                <p><?php echo number_format($item['price'] * $item['quantity'], 2); ?> DT</p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="cart-summary">
                    <h3>Total: <?php echo number_format($total, 2); ?> DT</h3>
                    <form method="POST">
                        <button type="submit" name="checkout" class="btn btn-primary">Passer la commande</button>
                    </form>
                </div>
            <?php endif; ?>
        </main>

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