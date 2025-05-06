document.addEventListener('DOMContentLoaded', () => {
    // Authentication Management
    const auth = {
        isLoggedIn() {
            return !!localStorage.getItem('userToken');
        },
        isAdmin() {
            const user = this.getCurrentUser();
            return user && user.is_admin === 1;
        },
        getCurrentUser() {
            return JSON.parse(localStorage.getItem('currentUser')) || {};
        },
        logout() {
            localStorage.removeItem('userToken');
            localStorage.removeItem('currentUser');
            window.location.href = 'compte.php';
        }
    };

    // Update Navigation for Auth Status
    const userIcons = document.querySelectorAll('.nav-actions .icon-btn i.fa-user');
    const navLinks = document.querySelectorAll('.nav-links');
    if (auth.isLoggedIn()) {
        userIcons.forEach(icon => {
            icon.classList.remove('fa-user');
            icon.classList.add('fa-sign-out-alt');
            icon.parentElement.title = 'Déconnexion';
            icon.parentElement.addEventListener('click', (e) => {
                e.preventDefault();
                auth.logout();
            });
        });
        if (auth.isAdmin()) {
            navLinks.forEach(nav => {
                const adminItem = document.createElement('li');
                adminItem.innerHTML = '<a href="admin.php">Admin</a>';
                nav.appendChild(adminItem);
            });
        }
    }

    // Cart Management
    const cart = {
        items: JSON.parse(localStorage.getItem('cart')) || [],
        addItem(item) {
            if (!auth.isLoggedIn()) {
                alert('Veuillez vous connecter pour ajouter au panier.');
                window.location.href = 'compte.php';
                return;
            }
            const existingItem = this.items.find(i => i.id === item.id);
            if (existingItem) {
                existingItem.quantity += item.quantity;
            } else {
                this.items.push(item);
            }
            this.save();
            this.updateCartCount();
        },
        removeItem(itemId) {
            if (!auth.isLoggedIn()) return;
            this.items = this.items.filter(item => item.id !== itemId);
            this.save();
            this.updateCartCount();
        },
        updateQuantity(itemId, quantity) {
            if (!auth.isLoggedIn()) return;
            const item = this.items.find(i => i.id === itemId);
            if (item) {
                item.quantity = quantity;
                if (item.quantity <= 0) {
                    this.removeItem(itemId);
                }
            }
            this.save();
            this.updateCartCount();
        },
        getTotal() {
            return this.items.reduce((total, item) => total + item.price * item.quantity, 0);
        },
        save() {
            localStorage.setItem('cart', JSON.stringify(this.items));
        },
        updateCartCount() {
            const cartCountElements = document.querySelectorAll('.cart-count');
            const totalItems = this.items.reduce((sum, item) => sum + item.quantity, 0);
            cartCountElements.forEach(el => el.textContent = totalItems);
        }
    };

    // Mobile Menu Toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const mobileMenu = document.querySelector('.mobile-menu');
    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('active');
        });
    }

    // Back to Top Button
    const backToTopBtn = document.querySelector('#back-to-top');
    if (backToTopBtn) {
        window.addEventListener('scroll', () => {
            backToTopBtn.style.display = window.scrollY > 300 ? 'block' : 'none';
        });
        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // Product Detail Page - Image Gallery
    const thumbnails = document.querySelectorAll('.thumbnail');
    const mainImage = document.querySelector('#main-product-image');
    if (thumbnails && mainImage) {
        thumbnails.forEach(thumbnail => {
            thumbnail.addEventListener('click', () => {
                thumbnails.forEach(t => t.classList.remove('active'));
                thumbnail.classList.add('active');
                mainImage.src = thumbnail.dataset.image;
            });
        });
    }

    // Product Detail Page - Quantity Selector
    const quantityInput = document.querySelector('.quantity-input');
    const decreaseBtn = document.querySelector('.decrease-btn');
    const increaseBtn = document.querySelector('.increase-btn');
    if (quantityInput && decreaseBtn && increaseBtn) {
        decreaseBtn.addEventListener('click', () => {
            let value = parseInt(quantityInput.value);
            if (value > 1) quantityInput.value = value - 1;
        });
        increaseBtn.addEventListener('click', () => {
            let value = parseInt(quantityInput.value);
            quantityInput.value = value + 1;
        });
    }

    // Product Detail Page - Add to Cart
    const addToCartBtn = document.querySelector('.add-to-cart-btn');
    if (addToCartBtn) {
        addToCartBtn.addEventListener('click', () => {
            if (!auth.isLoggedIn()) {
                alert('Veuillez vous connecter pour ajouter au panier.');
                window.location.href = 'compte.php';
                return;
            }
            const quantity = parseInt(quantityInput.value);
            const item = {
                id: 'laptop-pro-x',
                name: 'Laptop Pro X',
                price: 1299.99,
                quantity: quantity,
                image: 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?&w=800&h=600&fit=crop',
                category: 'Ordinateurs portables',
                rating: 4.8
            };
            cart.addItem(item);
            alert('Produit ajouté au panier !');
        });
    }

    // Product Cards - Add to Cart
    const productAddToCartButtons = document.querySelectorAll('.product-card .btn-primary');
    productAddToCartButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            if (!auth.isLoggedIn()) {
                alert('Veuillez vous connecter pour ajouter au panier.');
                window.location.href = 'compte.php';
                return;
            }
            const productCard = e.target.closest('.product-card');
            const item = {
                id: productCard.querySelector('.product-name a').href.split('/').pop(),
                name: productCard.querySelector('.product-name').textContent,
                price: parseFloat(productCard.querySelector('.product-price').textContent.replace(' DT', '')),
                quantity: 1,
                image: productCard.querySelector('.product-image img').src,
                category: productCard.querySelector('.product-category').textContent,
                rating: parseFloat(productCard.querySelector('.product-rating span').textContent)
            };
            cart.addItem(item);
            alert('Produit ajouté au panier !');
        });
    });

    // Product Tabs
    const tabButtons = document.querySelectorAll('.tab-btn');
    const tabPanels = document.querySelectorAll('.tab-panel');
    if (tabButtons && tabPanels) {
        tabButtons.forEach(button => {
            button.addEventListener('click', () => {
                tabButtons.forEach(btn => btn.classList.remove('active'));
                tabPanels.forEach(panel => panel.classList.remove('active'));
                button.classList.add('active');
                document.getElementById(`${button.dataset.tab}-panel`).classList.add('active');
            });
        });
    }

    // Products Page - Filters
    const filtersBtn = document.querySelector('.filters-btn');
    const filtersSidebar = document.querySelector('.filters-sidebar');
    const closeFiltersBtn = document.querySelector('.close-filters-btn');
    if (filtersBtn && filtersSidebar && closeFiltersBtn) {
        filtersBtn.addEventListener('click', () => {
            filtersSidebar.classList.add('active');
        });
        closeFiltersBtn.addEventListener('click', () => {
            filtersSidebar.classList.remove('active');
        });
    }

    // Products Page - Price Filter
    const priceRange = document.getElementById('max-price');
    const priceValue = document.getElementById('price-value');
    if (priceRange && priceValue) {
        priceRange.addEventListener('input', () => {
            priceValue.textContent = priceRange.value + ' DT';
            filterProducts();
        });
    }

    // Products Page - Category and Brand Filters
    const filterCheckboxes = document.querySelectorAll('.filter-option input[type="checkbox"]');
    filterCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', filterProducts);
    });

    // Products Page - Reset Filters
    const resetFiltersBtn = document.querySelector('.reset-filters-btn');
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', () => {
            filterCheckboxes.forEach(checkbox => checkbox.checked = false);
            priceRange.value = 2000;
            priceValue.textContent = '2000 DT';
            filterProducts();
        });
    }

    // Products Page - Sort
    const sortSelect = document.querySelector('.products-sort select');
    if (sortSelect) {
        sortSelect.addEventListener('change', () => {
            sortProducts(sortSelect.value);
        });
    }

    // Filter Products Function
    function filterProducts() {
        const maxPrice = parseInt(priceRange.value);
        const selectedCategories = Array.from(document.querySelectorAll('.filter-group:nth-child(2) input:checked'))
            .map(cb => cb.id.replace('cat-', ''));
        const selectedBrands = Array.from(document.querySelectorAll('.filter-group:nth-child(3) input:checked'))
            .map(cb => cb.id.replace('brand-', ''));

        const productCards = document.querySelectorAll('.product-card');
        let visibleCount = 0;

        productCards.forEach(card => {
            const price = parseFloat(card.querySelector('.product-price').textContent.replace(' DT', ''));
            const category = card.querySelector('.product-category').textContent.toLowerCase();
            const brand = card.querySelector('.product-name').textContent.toLowerCase();

            const priceMatch = price <= maxPrice;
            const categoryMatch = selectedCategories.length === 0 || selectedCategories.some(cat => category.includes(cat));
            const brandMatch = selectedBrands.length === 0 || selectedBrands.some(brandId => brand.includes(brandId));

            if (priceMatch && categoryMatch && brandMatch) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        document.querySelector('.products-count').textContent = `${visibleCount} produits trouvés`;
    }

    // Sort Products Function
    function sortProducts(criteria) {
        const productGrid = document.querySelector('.product-grid');
        const productCards = Array.from(document.querySelectorAll('.product-card'));

        productCards.sort((a, b) => {
            const priceA = parseFloat(a.querySelector('.product-price').textContent.replace(' DT', ''));
            const priceB = parseFloat(b.querySelector('.product-price').textContent.replace(' DT', ''));
            const ratingA = parseFloat(a.querySelector('.product-rating span').textContent);
            const ratingB = parseFloat(a.querySelector('.product-rating span').textContent);

            switch (criteria) {
                case 'price-asc':
                    return priceA - priceB;
                case 'price-desc':
                    return priceB - priceA;
                case 'newest':
                    return 0;
                case 'featured':
                default:
                    return ratingB - ratingA;
            }
        });

        productGrid.innerHTML = '';
        productCards.forEach(card => productGrid.appendChild(card));
    }

    // Initialize Cart Count
    cart.updateCartCount();

    // Panier Page - Render Cart
    if (window.location.pathname.includes('panier.php')) {
        const cartContainer = document.createElement('main');
        cartContainer.className = 'container py-8';
        cartContainer.innerHTML = `
            <h1 class="section-title">Votre panier</h1>
            <div class="product-grid cart-items"></div>
            <div class="cart-summary">
                <h3>Total: <span id="cart-total">0.00 DT</span></h3>
                <button class="btn btn-primary btn-full checkout-btn">Passer la commande</button>
            </div>
        `;
        document.querySelector('#root').insertBefore(cartContainer, document.querySelector('footer'));

        function renderCart() {
            const cartItemsContainer = document.querySelector('.cart-items');
            cartItemsContainer.innerHTML = '';

            if (cart.items.length === 0) {
                cartItemsContainer.innerHTML = '<p>Votre panier est vide.</p>';
                return;
            }

            cart.items.forEach(item => {
                const cartItem = document.createElement('div');
                cartItem.className = 'product-card';
                const stars = generateStarIcons(item.rating);
                cartItem.innerHTML = `
                    <div class="product-image">
                        <img src="${item.image}" alt="${item.name}">
                        <button class="wishlist-btn"><i class="far fa-heart"></i></button>
                    </div>
                    <div class="product-info">
                        <div class="product-category">${item.category}</div>
                        <h3 class="product-name"><a href="produit-detail.html">${item.name}</a></h3>
                        <div class="product-details">
                            <div class="product-rating">
                                <div class="stars">${stars}</div>
                                <span>${item.rating}</span>
                            </div>
                            <div class="product-price">${item.price.toFixed(2)} DT</div>
                        </div>
                        <div class="quantity-selector">
                            <button class="quantity-btn decrease-btn"><i class="fas fa-minus"></i></button>
                            <input type="number" min="1" value="${item.quantity}" class="quantity-input">
                            <button class="quantity-btn increase-btn"><i class="fas fa-plus"></i></button>
                        </div>
                        <button class="btn btn-outline btn-full remove-btn">Supprimer</button>
                    </div>
                `;
                cartItemsContainer.appendChild(cartItem);

                const quantityInput = cartItem.querySelector('.quantity-input');
                const decreaseBtn = cartItem.querySelector('.decrease-btn');
                const increaseBtn = cartItem.querySelector('.increase-btn');
                const removeBtn = cartItem.querySelector('.remove-btn');

                decreaseBtn.addEventListener('click', () => {
                    if (!auth.isLoggedIn()) return;
                    let value = parseInt(quantityInput.value);
                    if (value > 1) {
                        quantityInput.value = value - 1;
                        cart.updateQuantity(item.id, value - 1);
                        updateCartTotal();
                    }
                });

                increaseBtn.addEventListener('click', () => {
                    if (!auth.isLoggedIn()) return;
                    let value = parseInt(quantityInput.value);
                    quantityInput.value = value + 1;
                    cart.updateQuantity(item.id, value + 1);
                    updateCartTotal();
                });

                quantityInput.addEventListener('change', () => {
                    if (!auth.isLoggedIn()) return;
                    let value = parseInt(quantityInput.value);
                    if (value < 1) value = 1;
                    quantityInput.value = value;
                    cart.updateQuantity(item.id, value);
                    updateCartTotal();
                });

                removeBtn.addEventListener('click', () => {
                    if (!auth.isLoggedIn()) return;
                    cart.removeItem(item.id);
                    renderCart();
                });
            });

            updateCartTotal();
        }

        function generateStarIcons(rating) {
            const fullStars = Math.floor(rating);
            const halfStar = rating % 1 >= 0.5 ? 1 : 0;
            const emptyStars = 5 - fullStars - halfStar;
            let stars = '';
            for (let i = 0; i < fullStars; i++) stars += '<i class="fas fa-star"></i>';
            if (halfStar) stars += '<i class="fas fa-star-half-alt"></i>';
            for (let i = 0; i < emptyStars; i++) stars += '<i class="far fa-star"></i>';
            return stars;
        }

        function updateCartTotal() {
            const totalElement = document.querySelector('#cart-total');
            totalElement.textContent = `${cart.getTotal().toFixed(2)} DT`;
        }

        renderCart();

        const checkoutBtn = document.querySelector('.checkout-btn');
        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', () => {
                if (!auth.isLoggedIn()) {
                    alert('Veuillez vous connecter pour passer la commande.');
                    window.location.href = 'compte.php';
                    return;
                }
                alert('Commande passée avec succès !');
                cart.items = [];
                cart.save();
                cart.updateCartCount();
                renderCart();
            });
        }
    }
});