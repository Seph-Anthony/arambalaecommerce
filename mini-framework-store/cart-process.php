<?php
// cart-process.php (Now handling direct form submission and redirecting)

require_once __DIR__ . '/vendor/autoload.php';
include 'helpers/functions.php'; // Include your functions.php for isLoggedIn()

if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Ensure session is started for cart operations
}

use Aries\MiniFrameworkStore\Models\Product;

// Initialize a default redirect URL. If something goes wrong, we go back to index.
$redirectUrl = $_SERVER['HTTP_REFERER'] ?? 'index.php';

// Check if the user is logged in
if (!isLoggedIn()) {
    // If not logged in, redirect them to the login page
    header('Location: login.php');
    exit;
}

if (isset($_POST['product_id']) && isset($_POST['quantity'])) {
    $productId = (int)$_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1; // Default to 1 if not provided

    // Validate product ID and quantity (basic validation)
    if ($productId <= 0 || $quantity <= 0) {
        // Log error and redirect back, perhaps with an error message in session
        error_log("Invalid product ID or quantity received in cart-process.php. ID: " . $productId . ", QTY: " . $quantity);
        $_SESSION['message'] = 'Error: Invalid product ID or quantity.';
        header('Location: ' . $redirectUrl);
        exit;
    }

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $productModel = new Product(); // Changed variable name to avoid conflict with $productDetails
    $productDetails = $productModel->getById($productId); // Use $productId after intval()

    // Check if product exists
    if (!$productDetails) {
        error_log("Product not found in DB for ID: " . $productId . " in cart-process.php.");
        $_SESSION['message'] = 'Error: Product not found.';
        header('Location: ' . $redirectUrl);
        exit;
    }

    // Add or update product in cart
    // We are now storing the full product details needed for the cart page,
    // rather than just product_id and quantity, as your cart.php expects them.
    if (isset($_SESSION['cart'][$productId])) {
        // If product already in cart, increment quantity
        $_SESSION['cart'][$productId]['quantity'] += $quantity;
        $_SESSION['cart'][$productId]['total'] = $_SESSION['cart'][$productId]['price'] * $_SESSION['cart'][$productId]['quantity'];
        $_SESSION['message'] = 'Product quantity updated in cart.';
    } else {
        // If new product, add it
        $_SESSION['cart'][$productId] = [
            'id' => $productId, // Ensure 'id' key is set, your cart.php uses 'id'
            'name' => $productDetails['name'],
            'price' => $productDetails['price'],
            'image_path' => $productDetails['image_path'],
            'quantity' => $quantity,
            'total' => $productDetails['price'] * $quantity
        ];
        $_SESSION['message'] = 'Product added to cart!';
    }
} else {
    // If no product_id or quantity, log and redirect
    error_log("No product_id or quantity received in POST for cart-process.php.");
    $_SESSION['message'] = 'Error: No product data received.';
}

// Redirect back to the previous page or to the cart page
// You might want to redirect directly to cart.php for confirmation, or back to index.php
// Let's redirect to the cart page for a clear confirmation
header('Location: cart.php'); // Redirect to cart page after processing
exit;
?>