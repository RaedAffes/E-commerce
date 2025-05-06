<?php
require_once 'config.php';

$category = filter_input(INPUT_GET, 'categorie', FILTER_SANITIZE_STRING);
$max_price = filter_input(INPUT_GET, 'max_price', FILTER_VALIDATE_FLOAT) ?: 2000;
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);
$brands = filter_input(INPUT_GET, 'brands', FILTER_SANITIZE_STRING) ? explode(',', filter_input(INPUT_GET, 'brands', FILTER_SANITIZE_STRING)) : [];
$sort = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING) ?: 'featured';

$where_clauses = ['price <= ?'];
$params = [$max_price];

if ($category) {
  $where_clauses[] = 'category = ?';
  $params[] = $category;
}

if ($search) {
  $where_clauses[] = '(name LIKE ? OR description LIKE ?)';
  $params[] = "%$search%";
  $params[] = "%$search%";
}

if ($brands) {
  $placeholders = implode(',', array_fill(0, count($brands), '?'));
  $where_clauses[] = "brand IN ($placeholders)";
  $params = array_merge($params, $brands);
}

$where = $where_clauses ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$order_by = '';
switch ($sort) {
  case 'price-asc':
    $order_by = 'ORDER BY price ASC';
    break;
  case 'price-desc':
    $order_by = 'ORDER BY price DESC';
    break;
  case 'newest':
    $order_by = 'ORDER BY id DESC';
    break;
  case 'featured':
  default:
    $order_by = 'ORDER BY rating DESC';
}

try {
  $stmt = $pdo->prepare("SELECT id, name, category, price, image, rating, brand, stock FROM products $where $order_by");
  $stmt->execute($params);
  $products = $stmt->fetchAll();
  $product_count = count($products);

  $stmt = $pdo->query('SELECT DISTINCT brand FROM products WHERE brand IS NOT NULL');
  $available_brands = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
  $error = "Query failed: " . $e->getMessage();
  $products = [];
  $product_count = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Produits - ENSITEK</title>
  <meta name="description" content="Découvrez notre gamme complète de produits informatiques">
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
              <li><a href="produits.php" class="active">Produits</a></li>
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
    <main>
      <div class="container py-5">
        <h1 class="section-title">Nos produits</h1>
        <?php if (isset($error)): ?>
          <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <div class="products-container">
          <div class="filters-toggle-mobile">
            <button class="btn btn-outline filters-btn">
              <i class="fas fa-sliders-h"></i> Filtres
            </button>
          </div>
          <div class="products-layout">
            <aside class="filters-sidebar">
              <div class="filters-header-mobile">
                <h3>Filtres</h3>
                <button class="close-filters-btn"><i class="fas fa-times"></i></button>
              </div>
              <div class="search-desktop">
                <form class="search-filtre-form" action="produits.php" method="GET">
                  <input type="text" name="search" placeholder="Rechercher..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                  <button type="submit"><i class="fas fa-search"></i></button>
                </form>
              </div>
              <div class="filter-group">
                <h3 class="filter-title">Prix</h3>
                <div class="price-slider">
                  <div class="price-range">
                    <input type="range" min="0" max="2000" value="<?php echo $max_price; ?>" class="range-input" id="max-price">
                  </div>
                  <div class "price-inputs">
                    <span>0 DT</span>
                    <span id="price-value"><?php echo $max_price; ?> DT</span>
                  </div>
                </div>
              </div>
              <div class="filter-group">
                <h3 class="filter-title">Catégories</h3>
                <div class="filter-options">
                  <div class="filter-option">
                    <input type="checkbox" id="cat-laptops" name="categorie" value="Ordinateurs portables" <?php echo $category === 'Ordinateurs portables' ? 'checked' : ''; ?>>
                    <label for="cat-laptops">Ordinateurs portables</label>
                  </div>
                  <div class="filter-option">
                    <input type="checkbox" id="cat-desktops" name="categorie" value="Ordinateurs de bureau" <?php echo $category === 'Ordinateurs de bureau' ? 'checked' : ''; ?>>
                    <label for="cat-desktops">Ordinateurs de bureau</label>
                  </div>
                  <div class="filter-option">
                    <input type="checkbox" id="cat-components" name="categorie" value="Composants" <?php echo $category === 'Composants' ? 'checked' : ''; ?>>
                    <label for="cat-components">Composants</label>
                  </div>
                  <div class="filter-option">
                    <input type="checkbox" id="cat-peripherals" name="categorie" value="Périphériques" <?php echo $category === 'Périphériques' ? 'checked' : ''; ?>>
                    <label for="cat-peripherals">Périphériques</label>
                  </div>
                  <div class="filter-option">
                    <input type="checkbox" id="cat-accessories" name="categorie" value="Accessoires" <?php echo $category === 'Accessoires' ? 'checked' : ''; ?>>
                    <label for="cat-accessories">Accessoires</label>
                  </div>
                </div>
              </div>
              <div class="filter-group">
                <h3 class="filter-title">Marques</h3>
                <div class="filter-options">
                  <?php foreach ($available_brands as $brand): ?>
                    <div class="filter-option">
                      <input type="checkbox" id="brand-<?php echo htmlspecialchars($brand); ?>" name="brands[]" value="<?php echo htmlspecialchars($brand); ?>" <?php echo in_array($brand, $brands) ? 'checked' : ''; ?>>
                      <label for="brand-<?php echo htmlspecialchars($brand); ?>"><?php echo htmlspecialchars($brand); ?></label>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>
              <button class="btn btn-outline btn-full reset-filters-btn">Réinitialiser les filtres</button>
            </aside>
            <div class="products-content">
              <div class="products-header">
                <span class="products-count"><?php echo $product_count; ?> produits trouvés</span>
                <div class="products-sort">
                  <select name="sort" id="sort-select">
                    <option value="featured" <?php echo $sort === 'featured' ? 'selected' : ''; ?>>Les plus populaires</option>
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Les plus récents</option>
                    <option value="price-asc" <?php echo $sort === 'price-asc' ? 'selected' : ''; ?>>Prix croissant</option>
                    <option value="price-desc" <?php echo $sort === 'price-desc' ? 'selected' : ''; ?>>Prix décroissant</option>
                  </select>
                </div>
              </div>
              <div class="product-grid">
                <?php foreach ($products as $product): ?>
                  <div class="product-card">
                    <div class="product-image">
                      <img src="<?php echo htmlspecialchars($product['image'] ?: 'https://via.placeholder.com/500'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                      <button class="wishlist-btn"><i class="far fa-heart"></i></button>
                    </div>
                    <div class="product-info">
                      <div class="product-category"><?php echo htmlspecialchars($product['category']); ?></div>
                      <h3 class="product-name"><a href="produit-detail.php?id=<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></a></h3>
                      <div class="product-details">
                        <div class="product-rating">
                          <div class="stars">
                            <?php
                            $rating = floatval($product['rating']);
                            $full_stars = floor($rating);
                            $half_star = ($rating - $full_stars) >= 0.5 ? 1 : 0;
                            $empty_stars = 5 - $full_stars - $half_star;
                            for ($i = 0; $i < $full_stars; $i++) {
                              echo '<i class="fas fa-star"></i>';
                            }
                            if ($half_star) {
                              echo '<i class="fas fa-star-half-alt"></i>';
                            }
                            for ($i = 0; $i < $empty_stars; $i++) {
                              echo '<i class="far fa-star"></i>';
                            }
                            ?>
                          </div>
                          <span><?php echo number_format($product['rating'], 1); ?></span>
                        </div>
                        <div class="product-price"><?php echo number_format($product['price'], 2); ?> DT</div>
                      </div>
                      <button class="btn btn-primary btn-full add-to-cart" data-product-id="<?php echo $product['id']; ?>">Ajouter au panier</button>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
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
    // Update price display on slider change
    const priceSlider = document.getElementById('max-price');
    const priceValue = document.getElementById('price-value');
    priceSlider.addEventListener('input', () => {
      priceValue.textContent = priceSlider.value + ' DT';
    });

    // Handle filter form submission
    const filterForm = document.querySelector('.search-filtre-form');
    filterForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const searchInput = filterForm.querySelector('input[name="search"]').value;
      const url = new URL(window.location);
      url.searchParams.set('search', searchInput);
      window.location = url;
    });

    // Handle category checkbox (single selection)
    document.querySelectorAll('input[name="categorie"]').forEach(checkbox => {
      checkbox.addEventListener('change', () => {
        document.querySelectorAll('input[name="categorie"]').forEach(cb => {
          if (cb !== checkbox) cb.checked = false;
        });
        const url = new URL(window.location);
        url.searchParams.set('categorie', checkbox.checked ? checkbox.value : '');
        window.location = url;
      });
    });

    // Handle brand checkboxes
    document.querySelectorAll('input[name="brands[]"]').forEach(checkbox => {
      checkbox.addEventListener('change', () => {
        const selectedBrands = Array.from(document.querySelectorAll('input[name="brands[]"]:checked')).map(cb => cb.value);
        const url = new URL(window.location);
        url.searchParams.set('brands', selectedBrands.join(','));
        window.location = url;
      });
    });

    // Handle sort selection
    document.getElementById('sort-select').addEventListener('change', (e) => {
      const url = new URL(window.location);
      url.searchParams.set('sort', e.target.value);
      window.location = url;
    });

    // Reset filters
    document.querySelector('.reset-filters-btn').addEventListener('click', () => {
      window.location = 'produits.php';
    });

    // Add to cart (handled in main.js, ensure button has correct data attribute)
    document.querySelectorAll('.add-to-cart').forEach(button => {
      button.addEventListener('click', () => {
        const productId = button.getAttribute('data-product-id');
        // Assuming main.js handles the cart logic
        addToCart(productId);
      });
    });
  </script>
</body>

</html>