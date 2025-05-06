<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id']) || !$_SESSION['is_admin']) {
    header('Location: compte.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function uploadImage($file)
{
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    $target_file = $target_dir . uniqid() . '_' . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif']) || $file['size'] > 5000000) {
        return '';
    }
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return $target_file;
    }
    return '';
}

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    if (isset($_POST['add_user'])) {
        $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('INSERT INTO users (nom, email, phone, password, is_admin) VALUES (?, ?, ?, ?, 0)');
        $stmt->execute([$nom, $email, $phone, $password]);
    } elseif (isset($_POST['edit_user'])) {
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;

        if ($password) {
            $stmt = $pdo->prepare('UPDATE users SET nom = ?, email = ?, phone = ?, password = ? WHERE id = ? AND is_admin = 0');
            $stmt->execute([$nom, $email, $phone, $password, $user_id]);
        } else {
            $stmt = $pdo->prepare('UPDATE users SET nom = ?, email = ?, phone = ? WHERE id = ? AND is_admin = 0');
            $stmt->execute([$nom, $email, $phone, $user_id]);
        }
    } elseif (isset($_POST['delete_user'])) {
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        if ($user_id) {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ? AND is_admin = 0');
            $stmt->execute([$user_id]);
        }
    }
}


// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    if (isset($_POST['add_product'])) {
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
        $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $brand = filter_input(INPUT_POST, 'brand', FILTER_SANITIZE_STRING);
        $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
        $image = $_FILES['image']['name'] ? uploadImage($_FILES['image']) : '';

        $stmt = $pdo->prepare('INSERT INTO products (name, price, category, description, brand, stock, image) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $price, $category, $description, $brand, $stock, $image]);
    } elseif (isset($_POST['delete_product'])) {
        $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        if ($product_id) {
            $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
            $stmt->execute([$product_id]);
        }
    } elseif (isset($_POST['edit_product'])) {
        $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
        $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
        $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
        $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        $brand = filter_input(INPUT_POST, 'brand', FILTER_SANITIZE_STRING);
        $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
        $image = $_FILES['image']['name'] ? uploadImage($_FILES['image']) : $_POST['existing_image'];

        $stmt = $pdo->prepare('UPDATE products SET name = ?, price = ?, category = ?, description = ?, brand = ?, stock = ?, image = ? WHERE id = ?');
        $stmt->execute([$name, $price, $category, $description, $brand, $stock, $image, $product_id]);
    }
}

$users = $pdo->query('SELECT id, nom, email, phone FROM users WHERE is_admin = 0')->fetchAll();
$products = $pdo->query('SELECT * FROM products')->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Admin - ENSITEK</title>
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
                            <li><a href="admin.php" class="active">Admin</a></li>
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
                        <li><a href="admin.php" class="active">Admin</a></li>
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
            <h1 class="page-title">Tableau de bord Admin</h1>

            <section class="mb-5">
                <h2>Gestion des utilisateurs</h2>
                <form method="POST" class="mb-4" user-form>
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="user_id" id="user_id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nom" class="form-label">Nom</label><br>
                            <input type="text" class="form-control" id="nom" name="nom" required><br>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email</label><br>
                            <input type="email" class="form-control" id="email" name="email" required><br>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="phone" class="form-label">Téléphone</label><br>
                            <input type="text" class="form-control" id="phone" name="phone"><br>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Mot de passe</label><br>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Laissez vide pour ne pas modifier"><br>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" name="add_user" id="submit_user" class="btn btn-primary">Ajouter l'utilisateur</button>
                        </div>
                    </div>
                </form>

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID </th>
                            <th>Nom </th>
                            <th>Email </th>
                            <th>Téléphone </th>
                            <th>Actions </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['id']); ?></td>
                                <td><?php echo htmlspecialchars($user['nom']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <button class="btn btn-warning btn-sm edit-user" data-user='<?php echo json_encode($user); ?>'>Modifier</button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="submit" name="delete_user" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer cet utilisateur ?');">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <section>
                <h2>Gestion des produits</h2>
                <form method="POST" enctype="multipart/form-data" class="mb-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="product_id" id="product_id">
                    <input type="hidden" name="existing_image" id="existing_image">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label">Nom du produit</label><br>
                            <input type="text" class="form-control" id="name" name="name" required><br>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Prix (DT)</label><br>
                            <input type="number" step="0.01" class="form-control" id="price" name="price" required><br>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label">Catégorie</label><br>
                            <select class="form-select" id="category" name="category" required><br>
                                <option value="Ordinateurs portables">Ordinateurs portables</option>
                                <option value="Ordinateurs de bureau">Ordinateurs de bureau</option>
                                <option value="Composants">Composants</option>
                                <option value="Périphériques">Périphériques</option>
                                <option value="Accessoires">Accessoires</option>
                            </select><br>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="brand" class="form-label">Marque</label><br>
                            <input type="text" class="form-control" id="brand" name="brand"><br>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="stock" class="form-label">Stock</label><br>
                            <input type="number" class="form-control" id="stock" name="stock" required><br>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="image" class="form-label">Image</label><br>
                            <input type="file" class="form-control" id="image" name="image" accept="image/*"><br>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="description" class="form-label">Description</label><br>
                            <textarea class="form-control" id="description" name="description" rows="4"></textarea><br>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" name="add_product" id="submit_product" class="btn btn-primary">Ajouter le produit</button>
                        </div>
                    </div>
                </form>

                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Nom </th>
                            <th>Prix </th>
                            <th>Catégorie </th>
                            <th>Marque </th>
                            <th>Stock </th>
                            <th>Image </th>
                            <th>Actions </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo number_format($product['price'], 2); ?> DT</td>
                                <td><?php echo htmlspecialchars($product['category']); ?></td>
                                <td><?php echo htmlspecialchars($product['brand'] ?? 'N/A'); ?></td>
                                <td><?php echo $product['stock']; ?></td>
                                <td><img src="<?php echo htmlspecialchars($product['image'] ?: 'https://via.placeholder.com/50'); ?>" alt="Product" style="width:50px;"></td>
                                <td>
                                    <button class="btn btn-warning btn-sm edit-product" data-product='<?php echo json_encode($product); ?>'>Modifier</button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <button type="submit" name="delete_product" class="btn btn-danger btn-sm" onclick="return confirm('Supprimer ce produit ?');">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
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
    <script>
        document.querySelectorAll('.edit-product').forEach(button => {
            button.addEventListener('click', () => {
                const product = JSON.parse(button.dataset.product);
                document.getElementById('product_id').value = product.id;
                document.getElementById('name').value = product.name;
                document.getElementById('price').value = product.price;
                document.getElementById('category').value = product.category;
                document.getElementById('brand').value = product.brand || '';
                document.getElementById('stock').value = product.stock;
                document.getElementById('description').value = product.description || '';
                document.getElementById('existing_image').value = product.image || '';
                document.getElementById('submit_product').name = 'edit_product';
                document.getElementById('submit_product').textContent = 'Modifier le produit';
            });
        });

        document.querySelector('form').addEventListener('submit', () => {
            setTimeout(() => {
                document.getElementById('product_id').value = '';
                document.getElementById('name').value = '';
                document.getElementById('price').value = '';
                document.getElementById('category').value = 'Ordinateurs portables';
                document.getElementById('brand').value = '';
                document.getElementById('stock').value = '';
                document.getElementById('description').value = '';
                document.getElementById('image').value = '';
                document.getElementById('existing_image').value = '';
                document.getElementById('submit_product').name = 'add_product';
                document.getElementById('submit_product').textContent = 'Ajouter le produit';
            }, 100);
        });
    </script>
</body>

</html>