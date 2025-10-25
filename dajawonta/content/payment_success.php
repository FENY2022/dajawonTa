<?php
ob_start();
session_start();
require '../db.php'; // Database connection file

// === CONFIGURATION ===
define('PAYMONGO_SECRET_KEY', 'sk_test_96buPr5S5wCEGUSjiEizVgMx'); // ✅ Use SECRET key

// Enable detailed error display for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * Function to retrieve a PayMongo checkout session and check its payment status.
 *
 * @param string $checkout_id The PayMongo Checkout Session ID (e.g., "cs_...")
 * @return array ['status' => 'paid'|'pending'|'failed', 'payment_intent_id' => 'pi_...', 'error' => '...']
 */
function verify_paymongo_payment($checkout_id)
{
    $ch = curl_init('https://api.paymongo.com/v1/checkout_sessions/' . $checkout_id);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => PAYMONGO_SECRET_KEY . ':',
        CURLOPT_SSL_VERIFYPEER => false // ✅ Use false for localhost only
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        error_log("❌ CURL Error verifying payment: $curl_error");
        return ['status' => 'failed', 'error' => $curl_error];
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("❌ JSON Decode Error: " . json_last_error_msg());
        return ['status' => 'failed', 'error' => 'Invalid JSON response from PayMongo.'];
    }

    if ($http_code !== 200 || isset($data['errors'])) {
        $error_detail = $data['errors'][0]['detail'] ?? 'Unknown API error.';
        error_log("⚠️ PayMongo API Error: " . $error_detail);
        return ['status' => 'failed', 'error' => $error_detail];
    }

    // --- This is the most important part ---
    // We check the status of the associated 'payment_intent'.
    $payment_intent = $data['data']['attributes']['payment_intent'] ?? null;
    $payment_intent_id = $payment_intent['id'] ?? null;
    $payment_status = $payment_intent['attributes']['status'] ?? 'pending';

    // Check if the payment intent was successful
    if ($payment_status === 'succeeded') {
        // Payment is confirmed!
        return [
            'status' => 'paid', 
            'payment_intent_id' => $payment_intent_id
        ];
    } elseif ($payment_status === 'awaiting_payment_method' || $payment_status === 'processing') {
        // Payment is still pending (e.g., waiting for bank transfer)
        return [
            'status' => 'pending', 
            'payment_intent_id' => $payment_intent_id
        ];
    } else {
        // Payment failed or was canceled
        return [
            'status' => 'failed', 
            'payment_intent_id' => $payment_intent_id, 
            'error' => 'Payment was not successful (Status: ' . $payment_status . ')'
        ];
    }
}

// ---------------------------------------------------------------------
// === CORE LOGIC STARTS HERE ===
// ---------------------------------------------------------------------

// === 1. GET BOOKING ID ===
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    die("Invalid request. No booking ID provided.");
}
$booking_id = intval($_GET['booking_id']);

// === 2. FETCH BOOKING FROM DB ===
// We need the 'payment_id' (which is the checkout_id) to verify with PayMongo
$stmt = $conn->prepare("SELECT payment_id, payment_status FROM bookings WHERE id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Booking not found.");
}
$booking = $result->fetch_assoc();
$stmt->close();

$checkout_id = $booking['payment_id']; // This is the PayMongo Checkout Session ID
$current_payment_status = $booking['payment_status'];

// === 3. CHECK IF ALREADY MARKED AS PAID ===
// This prevents running the verification multiple times
if ($current_payment_status === 'paid') {
    // Already verified. Just redirect.
    $_SESSION['toast_message'] = "Your payment is confirmed.";
    $_SESSION['toast_type'] = "success";
    header("Location: customer_booking_details.php?booking_id=$booking_id");
    exit;
}

if (empty($checkout_id)) {
    // This shouldn't happen, but good to check
    $_SESSION['toast_message'] = "Error: Payment ID not found for this booking.";
    $_SESSION['toast_type'] = "danger";
    header("Location: customer_booking_details.php?booking_id=$booking_id");
    exit;
}

// === 4. VERIFY PAYMENT WITH PAYMONGO ===
$verification = verify_paymongo_payment($checkout_id);
$payment_intent_id = $verification['payment_intent_id'] ?? null;

if ($verification['status'] === 'paid') {
    // --- SUCCESS ---
    // Update the database to mark as 'paid'
    $update = $conn->prepare("UPDATE bookings SET payment_status = 'paid', paymongo_payment_intent_id = ? WHERE id = ?");
    $update->bind_param("si", $payment_intent_id, $booking_id);
    $update->execute();
    $update->close();

    $_SESSION['toast_message'] = "Payment successful! Your booking is confirmed.";
    $_SESSION['toast_type'] = "success";

} elseif ($verification['status'] === 'pending') {
    // --- PENDING ---
    // This can happen with bank transfers, etc.
    $_SESSION['toast_message'] = "Your payment is still processing. We will update your booking status shortly.";
    $_SESSION['toast_type'] = "warning";
    
} else {
    // --- FAILED ---
    $_SESSION['toast_message'] = "Payment verification failed. Please try again or contact support.";
    $_SESSION['toast_type'] = "danger";
    error_log("Failed payment verification for Booking #$booking_id: " . $verification['error']);
}

$conn->close();

// === 5. REDIRECT BACK TO DETAILS PAGE ===
// The customer will now see the "Payment received!" message
header("Location: customer_booking_details.php?booking_id=$booking_id");
exit;

ob_end_flush();
?>