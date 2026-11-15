// ─────────────────────────────────────────────
//  cart.js — gestion du panier (sans compte requis)
//  Le panier est stocké dans le navigateur du client (localStorage)
// ─────────────────────────────────────────────
const CART_KEY = 'zayna_cart';

function getCart() {
    try {
        return JSON.parse(localStorage.getItem(CART_KEY)) || [];
    } catch (e) {
        return [];
    }
}

function saveCart(cart) {
    localStorage.setItem(CART_KEY, JSON.stringify(cart));
    updateCartBadge();
}

// item attendu : {product_id, name, image, size, unit_price, quantity}
function addToCart(item) {
    const cart = getCart();
    const existing = cart.find(c => c.product_id === item.product_id && c.size === item.size);
    if (existing) {
        existing.quantity += item.quantity;
    } else {
        cart.push(item);
    }
    saveCart(cart);
}

function removeFromCartAt(index) {
    const cart = getCart();
    cart.splice(index, 1);
    saveCart(cart);
}

function updateQuantityAt(index, qty) {
    const cart = getCart();
    if (cart[index]) {
        cart[index].quantity = Math.max(1, parseInt(qty) || 1);
        saveCart(cart);
    }
}

function clearCart() {
    localStorage.removeItem(CART_KEY);
    updateCartBadge();
}

function cartCount() {
    return getCart().reduce((sum, i) => sum + i.quantity, 0);
}

function cartTotal() {
    return getCart().reduce((sum, i) => sum + (i.unit_price * i.quantity), 0);
}

function updateCartBadge() {
    const badge = document.getElementById('cartBadge');
    if (!badge) return;
    const count = cartCount();
    badge.textContent = count;
    badge.style.display = count > 0 ? 'flex' : 'none';
}

document.addEventListener('DOMContentLoaded', updateCartBadge);
