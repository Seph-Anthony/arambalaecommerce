<?php
// update-cart-quantity.php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/helpers/functions.php'; // Make sure this contains formatCurrencyFallback if needed

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set header to indicate JSON response
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unexpected error occurred.']; // Default error response

// Ensure the request is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit();
}

// Check if product_id and quantity are provided
if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
    $response['message'] = 'Invalid product ID or quantity provided.';
    echo json_encode($response);
    exit();
}

$productId = (int)$_POST['product_id'];
$newQuantity = (int)$_POST['quantity'];

// Validate quantity
if ($newQuantity < 0) { // Quantity cannot be negative
    $response['message'] = 'Quantity cannot be negative.';
    echo json_encode($response);
    exit();
}

// Get cart from session
$cartItems = $_SESSION['cart'] ?? [];
$itemFound = false;

// Initialize formatter outside the loop if using it
$pesoFormatter = null;
if (extension_loaded('intl')) {
    $amounLocale = 'en_PH';
    $pesoFormatter = new NumberFormatter($amounLocale, NumberFormatter::CURRENCY);
    $pesoFormatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 2);
    $pesoFormatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 2);
    $pesoFormatter->setSymbol(NumberFormatter::CURRENCY_SYMBOL, '₱');
}


// Iterate through cart items to find and update the specific product
foreach ($cartItems as $index => &$item) { // Use & to get a reference to the item for modification
    if ($item['id'] == $productId) {
        if ($newQuantity === 0) {
            // If new quantity is 0, remove the item from the cart
            unset($cartItems[$index]);
            // Re-index array to avoid issues with numeric keys if needed, though not strictly necessary for foreach
            $cartItems = array_values($cartItems);
            $response['message'] = 'Product removed from cart.';
        } else {
            // Update the quantity
            $item['quantity'] = $newQuantity;
            $response['message'] = 'Cart quantity updated successfully!';
        }
        $itemFound = true;
        break; // Product found and updated/removed, exit loop
    }
}
unset($item); // Break the reference to the last element

if ($itemFound) {
    $_SESSION['cart'] = $cartItems; // Save the updated cart back to session

    // Recalculate subtotal for the updated item (if not removed)
    $updatedItemSubtotal = 0;
    if (isset($_SESSION['cart'][$productId])) { // Check if the item still exists in cart
        $updatedItemSubtotal = ($_SESSION['cart'][$productId]['price'] ?? 0) * ($_SESSION['cart'][$productId]['quantity'] ?? 0);
    }

    // Recalculate overall cart total
    $superTotal = 0;
    foreach ($_SESSION['cart'] as $item) {
        $superTotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
    }

    $response['status'] = 'success';
    $response['new_subtotal_formatted'] = $pesoFormatter ? $pesoFormatter->formatCurrency($updatedItemSubtotal, 'PHP') : formatCurrencyFallback($updatedItemSubtotal);
    $response['cart_total_formatted'] = $pesoFormatter ? $pesoFormatter->formatCurrency($superTotal, 'PHP') : formatCurrencyFallback($superTotal);
    $response['cart_item_count'] = count($_SESSION['cart']); // For updating the cart badge
    $response['product_id'] = $productId; // Send back the product ID for JS to target
    if ($newQuantity === 0) {
        $response['item_removed'] = true; // Flag for JS to remove the row
    }

} else {
    $response['message'] = 'Product not found in cart.';
}

echo json_encode($response);
exit();
?>