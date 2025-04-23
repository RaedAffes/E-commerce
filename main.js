// Back-to-top button functionality
const backToTopButton = document.getElementById('back-to-top');
window.addEventListener('scroll', () => {
  if (window.scrollY > 300) {
    backToTopButton.style.display = 'block';
  } else {
    backToTopButton.style.display = 'none';
  }
});

backToTopButton.addEventListener('click', () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
});

// Mobile menu toggle
const mobileMenuButton = document.querySelector('.mobile-menu-btn');
const mobileMenu = document.querySelector('.mobile-menu');

mobileMenuButton.addEventListener('click', () => {
  mobileMenu.classList.toggle('open');
});

// Update cart count dynamically (example)
const cartCountElement = document.querySelector('.cart-count');
let cartCount = 0;

function addToCart() {
  cartCount++;
  cartCountElement.textContent = cartCount;
}

const addToCartButtons = document.querySelectorAll('.btn-primary.btn-full');
addToCartButtons.forEach((button) => {
  button.addEventListener('click', () => {
    addToCart();
    alert('Produit ajouté au panier !');
  });
});

// Wishlist button functionality
const wishlistButtons = document.querySelectorAll('.wishlist-btn');
wishlistButtons.forEach((button) => {
  button.addEventListener('click', () => {
    button.classList.toggle('active');
    alert('Produit ajouté à la liste de souhaits !');
  });
});
