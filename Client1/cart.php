<?php
// Start session for user authentication
session_start();

// Database connection
$host = 'localhost';
$dbname = 'user_authentication'; // Updated to match your table creation
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // Sanitize and validate input
    $firstName = filter_input(INPUT_POST, 'firstName', FILTER_SANITIZE_STRING);
    $lastName = filter_input(INPUT_POST, 'lastName', FILTER_SANITIZE_STRING);
    $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
    $city = filter_input(INPUT_POST, 'city', FILTER_SANITIZE_STRING);
    $state = filter_input(INPUT_POST, 'state', FILTER_SANITIZE_STRING);
    $zipCode = filter_input(INPUT_POST, 'zipCode', FILTER_SANITIZE_STRING);
    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null; // Get user_id from session if logged in

    // Basic validation
    if (empty($firstName) || empty($lastName) || empty($address) || empty($city) || empty($state) || empty($zipCode) || empty($phone) || empty($email)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } else {
        try {
            // Insert data into delivery_info table
            $stmt = $pdo->prepare("
                INSERT INTO delivery_info (user_id, first_name, last_name, address, city, state, zip_code, phone, email)
                VALUES (:user_id, :first_name, :last_name, :address, :city, :state, :zip_code, :phone, :email)
            ");
            $stmt->execute([
                ':user_id' => $userId,
                ':first_name' => $firstName,
                ':last_name' => $lastName,
                ':address' => $address,
                ':city' => $city,
                ':state' => $state,
                ':zip_code' => $zipCode,
                ':phone' => $phone,
                ':email' => $email
            ]);

            // Optionally clear the cart (client-side via JS or server-side if stored in session)
            // For now, redirect to confirmation page
            header("Location: order-confirmation.php");
            exit();
        } catch (PDOException $e) {
            $error = "Error saving delivery information: " . $e->getMessage();
        }
    }
}
?>

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Your Cart - QuickCart</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            /* Animation Styles */
            
            .animate-fade-in {
                animation: fadeIn 0.3s ease-out;
            }
            
            .animate-fade-out {
                animation: fadeOut 0.3s ease-out;
            }
            
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            @keyframes fadeOut {
                from {
                    opacity: 1;
                    transform: translateY(0);
                }
                to {
                    opacity: 0;
                    transform: translateY(10px);
                }
            }
            /* Cart Styles */
            
            .cart-counter {
                position: absolute;
                top: -5px;
                right: -5px;
                background-color: #dc2626;
                color: white;
                border-radius: 50%;
                width: 18px;
                height: 18px;
                font-size: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .quantity-btn {
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                border: 1px solid #d1d5db;
                background-color: #f9fafb;
                cursor: pointer;
            }
            
            .quantity-input {
                width: 40px;
                text-align: center;
                border-top: 1px solid #d1d5db;
                border-bottom: 1px solid #d1d5db;
                border-left: none;
                border-right: none;
            }
            /* Payment Method Styles */
            
            .payment-method {
                transition: all 0.3s ease;
                border: 2px solid #e5e7eb;
            }
            
            .payment-method:hover {
                transform: translateY(-3px);
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            
            .payment-method.selected {
                border-color: #16a34a;
                background-color: #f0fdf4;
            }
            
            .payment-details {
                display: none;
            }
            
            .payment-details.active {
                display: block;
            }
        </style>
    </head>

    <body class="bg-gray-50">
        <!-- Navigation -->
        <nav class="bg-white shadow-md">
            <div class="container mx-auto px-4 py-3 flex justify-between items-center">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-shopping-basket text-2xl text-green-600"></i>
                    <a href="index.php" class="text-2xl font-bold text-gray-800">QuickCart</a>
                </div>
                <div class="flex items-center space-x-6">
                    <a href="cart.php" class="text-gray-600 hover:text-green-600 relative">
                        <i class="fas fa-shopping-cart"></i>
                        <span id="cartCounter" class="cart-counter hidden">0</span>
                    </a>
                    <?php
                // Dynamic login/logout
                if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
                    echo '<a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-md font-medium hover:bg-red-700">Logout</a>';
                } else {
                    echo '<a href="signup.php" class="bg-blue-500 text-white px-4 py-2 rounded-md font-medium hover:bg-blue-700">Sign Up</a>';
                    echo '<a href="login.php" class="bg-green-600 text-white px-4 py-2 rounded-md font-medium hover:bg-green-700">Login</a>';
                }
                ?>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="container mx-auto px-4 py-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Your Shopping Cart</h1>

            <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Cart Items Section -->
                <div class="lg:w-2/3">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="bg-gray-50 px-6 py-4 border-b flex justify-between items-center">
                            <h2 class="font-semibold text-gray-800">Items in your cart</h2>
                            <span id="itemCount" class="text-sm text-gray-600">0 items</span>
                        </div>
                        <div id="cartItems" class="divide-y">
                            <div class="p-6 text-center text-gray-500">Your cart is empty</div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary Section -->
                <div class="lg:w-1/3">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden sticky top-4">
                        <div class="bg-gray-50 px-6 py-4 border-b">
                            <h2 class="font-semibold text-gray-800">Order Summary</h2>
                        </div>
                        <div class="p-6">
                            <div id="orderItems" class="mb-6 max-h-64 overflow-y-auto"></div>
                            <div class="border-t pt-4">
                                <div class="flex justify-between mb-2">
                                    <span class="text-gray-600">Subtotal</span>
                                    <span id="orderSubtotal" class="font-medium">₹0</span>
                                </div>
                                <div class="flex justify-between mb-2">
                                    <span class="text-gray-600">Delivery</span>
                                    <span class="font-medium text-green-600">FREE</span>
                                </div>
                                <div class="flex justify-between mb-4">
                                    <span class="text-gray-600">Taxes</span>
                                    <span id="orderTaxes" class="font-medium">₹0</span>
                                </div>
                                <div class="border-t pt-4">
                                    <div class="flex justify-between mb-6">
                                        <span class="font-semibold text-lg">Total</span>
                                        <span id="orderTotal" class="font-bold text-lg">₹0</span>
                                    </div>
                                    <button id="showCheckoutFormBtn" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-md font-medium transition-colors mb-4">
                                    Proceed to Checkout
                                </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Checkout Form Section (Initially Hidden) -->
            <div id="checkoutFormContainer" class="hidden mt-8">
                <!-- Delivery Information -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6">
                    <div class="bg-gray-50 px-6 py-4 border-b">
                        <h2 class="font-semibold text-gray-800">Delivery Information</h2>
                    </div>
                    <div class="p-6">
                        <form id="deliveryForm" method="POST" action="cart.php">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="firstName" class="block text-sm font-medium text-gray-700 mb-1">First Name*</label>
                                    <input type="text" id="firstName" name="firstName" value="<?php echo isset($firstName) ? htmlspecialchars($firstName) : ''; ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-600">
                                </div>
                                <div>
                                    <label for="lastName" class="block text-sm font-medium text-gray-700 mb-1">Last Name*</label>
                                    <input type="text" id="lastName" name="lastName" value="<?php echo isset($lastName) ? htmlspecialchars($lastName) : ''; ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-600">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address*</label>
                                <input type="text" id="address" name="address" value="<?php echo isset($address) ? htmlspecialchars($address) : ''; ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-600">
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City*</label>
                                    <input type="text" id="city" name="city" value="<?php echo isset($city) ? htmlspecialchars($city) : ''; ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-600">
                                </div>
                                <div>
                                    <label for="state" class="block text-sm font-medium text-gray-700 mb-1">State*</label>
                                    <input type="text" id="state" name="state" value="<?php echo isset($state) ? htmlspecialchars($state) : ''; ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-600">
                                </div>
                                <div>
                                    <label for="zipCode" class="block text-sm font-medium text-gray-700 mb-1">ZIP Code*</label>
                                    <input type="text" id="zipCode" name="zipCode" value="<?php echo isset($zipCode) ? htmlspecialchars($zipCode) : ''; ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-600">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number*</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-600">
                            </div>
                            <div class="mb-4">
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address*</label>
                                <input type="email" id="email" name="email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-600">
                            </div>
                            <!-- Hidden input to indicate form submission -->
                            <input type="hidden" name="place_order" value="1">
                        </form>
                    </div>
                </div>

                <!-- Payment Methods -->
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="bg-gray-50 px-6 py-4 border-b">
                        <h2 class="font-semibold text-gray-800">Payment Method</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <!-- Credit Card -->
                            <div class="payment-method selected" data-method="credit-card">
                                <div class="flex items-center p-4">
                                    <input type="radio" name="paymentMethod" id="creditCard" class="mr-3" checked>
                                    <label for="creditCard" class="flex items-center cursor-pointer">
                                    <i class="fas fa-credit-card text-2xl text-gray-700 mr-2"></i>
                                    <span class="font-medium">Credit/Debit Card</span>
                                </label>
                                </div>
                                <div id="creditCardDetails" class="payment-details active px-4 pb-4">
                                    <div class="mb-3">
                                        <label for="cardNumber" class="block text-sm font-medium text-gray-700 mb-1">Card Number*</label>
                                        <input type="text" id="cardNumber" name="cardNumber" placeholder="1234 5678 9012 3456" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-600">
                                    </div>
                                    <div class="grid grid-cols-2 gap-3 mb-3">
                                        <div>
                                            <label for="expiryDate" class="block text-sm font-medium text-gray-700 mb-1">Expiry Date*</label>
                                            <input type="text" id="expiryDate" name="expiryDate" placeholder="MM/YY" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-600">
                                        </div>
                                        <div>
                                            <label for="cvv" class="block text-sm font-medium text-gray-700 mb-1">CVV*</label>
                                            <input type="text" id="cvv" name="cvv" placeholder="123" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-600">
                                        </div>
                                    </div>
                                    <div>
                                        <label for="cardName" class="block text-sm font-medium text-gray-700 mb-1">Name on Card*</label>
                                        <input type="text" id="cardName" name="cardName" placeholder="John Doe" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-600">
                                    </div>
                                </div>
                            </div>
                            <!-- UPI -->
                            <div class="payment-method" data-method="upi">
                                <div class="flex items-center p-4">
                                    <input type="radio" name="paymentMethod" id="upi" class="mr-3">
                                    <label for="upi" class="flex items-center cursor-pointer">
                                    <i class="fas fa-mobile-alt text-2xl text-gray-700 mr-2"></i>
                                    <span class="font-medium">UPI Payment</span>
                                </label>
                                </div>
                                <div id="upiDetails" class="payment-details px-4 pb-4">
                                    <div class="mb-3">
                                        <label for="upiId" class="block text-sm font-medium text-gray-700 mb-1">UPI ID*</label>
                                        <input type="text" id="upiId" name="upiId" placeholder="yourname@upi" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-600">
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        You'll be redirected to your UPI app for payment
                                    </div>
                                </div>
                            </div>
                            <!-- Net Banking -->
                            <div class="payment-method" data-method="netbanking">
                                <div class="flex items-center p-4">
                                    <input type="radio" name="paymentMethod" id="netbanking" class="mr-3">
                                    <label for="netbanking" class="flex items-center cursor-pointer">
                                    <i class="fas fa-university text-2xl text-gray-700 mr-2"></i>
                                    <span class="font-medium">Net Banking</span>
                                </label>
                                </div>
                                <div id="netbankingDetails" class="payment-details px-4 pb-4">
                                    <div class="mb-3">
                                        <label for="bankSelect" class="block text-sm font-medium text-gray-700 mb-1">Select Bank*</label>
                                        <select id="bankSelect" name="bankSelect" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-600">
                                        <option value="">Select your bank</option>
                                        <option value="sbi">State Bank of India</option>
                                        <option value="hdfc">HDFC Bank</option>
                                        <option value="icici">ICICI Bank</option>
                                        <option value="axis">Axis Bank</option>
                                        <option value="kotak">Kotak Mahindra Bank</option>
                                    </select>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        You'll be redirected to your bank's secure payment page
                                    </div>
                                </div>
                            </div>
                            <!-- Cash on Delivery -->
                            <div class="payment-method" data-method="cod">
                                <div class="flex items-center p-4">
                                    <input type="radio" name="paymentMethod" id="cod" class="mr-3">
                                    <label for="cod" class="flex items-center cursor-pointer">
                                    <i class="fas fa-money-bill-wave text-2xl text-gray-700 mr-2"></i>
                                    <span class="font-medium">Cash on Delivery</span>
                                </label>
                                </div>
                                <div id="codDetails" class="payment-details px-4 pb-4">
                                    <div class="text-sm text-gray-500">
                                        Pay in cash when your order is delivered
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center mb-4">
                            <input type="checkbox" id="termsAgree" name="termsAgree" class="mr-2" required>
                            <label for="termsAgree" class="text-sm text-gray-700">
                            I agree to the <a href="#" class="text-green-600 hover:underline">Terms & Conditions</a> and <a href="#" class="text-green-600 hover:underline">Privacy Policy</a>*
                        </label>
                        </div>
                        <button type="submit" id="placeOrderBtn" class="w-full bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-md font-medium transition-colors">
                        Place Order
                    </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize cart if not exists
                if (!localStorage.getItem('cart')) {
                    localStorage.setItem('cart', JSON.stringify([]));
                }

                // DOM Elements
                const cartItemsContainer = document.getElementById('cartItems');
                const orderItemsContainer = document.getElementById('orderItems');
                const itemCountElement = document.getElementById('itemCount');
                const orderSubtotalElement = document.getElementById('orderSubtotal');
                const orderTaxesElement = document.getElementById('orderTaxes');
                const orderTotalElement = document.getElementById('orderTotal');
                const cartCounter = document.getElementById('cartCounter');
                const showCheckoutFormBtn = document.getElementById('showCheckoutFormBtn');
                const checkoutFormContainer = document.getElementById('checkoutFormContainer');
                const placeOrderBtn = document.getElementById('placeOrderBtn');

                // Load cart data
                let cart = JSON.parse(localStorage.getItem('cart')) || [];

                // Initialize the page
                updateCartCounter();
                renderCartItems();
                renderOrderSummary();

                // Event Delegation for dynamic elements
                document.addEventListener('click', function(e) {
                    if (e.target.classList.contains('decrease') || e.target.parentElement.classList.contains('decrease')) {
                        const button = e.target.classList.contains('decrease') ? e.target : e.target.parentElement;
                        const id = button.getAttribute('data-id');
                        updateQuantity(id, -1);
                    }
                    if (e.target.classList.contains('increase') || e.target.parentElement.classList.contains('increase')) {
                        const button = e.target.classList.contains('increase') ? e.target : e.target.parentElement;
                        const id = button.getAttribute('data-id');
                        updateQuantity(id, 1);
                    }
                    if (e.target.classList.contains('remove-item') || e.target.parentElement.classList.contains('remove-item')) {
                        const button = e.target.classList.contains('remove-item') ? e.target : e.target.parentElement;
                        const id = button.getAttribute('data-id');
                        removeItem(id);
                    }
                    if (e.target.closest('.payment-method')) {
                        const methodElement = e.target.closest('.payment-method');
                        selectPaymentMethod(methodElement);
                    }
                });

                document.addEventListener('change', function(e) {
                    if (e.target.classList.contains('quantity-input')) {
                        const input = e.target;
                        const id = input.getAttribute('data-id');
                        const newQuantity = parseInt(input.value);
                        if (newQuantity > 0) {
                            updateQuantity(id, 0, newQuantity);
                        } else {
                            input.value = 1;
                        }
                    }
                });

                showCheckoutFormBtn.addEventListener('click', function() {
                    checkoutFormContainer.classList.toggle('hidden');
                    this.textContent = checkoutFormContainer.classList.contains('hidden') ? 'Proceed to Checkout' : 'Hide Checkout Form';
                    if (!checkoutFormContainer.classList.contains('hidden')) {
                        checkoutFormContainer.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                });

                // Place order - Submit form after validation
                placeOrderBtn.addEventListener('click', function(e) {
                    if (validateCheckoutForm()) {
                        document.getElementById('deliveryForm').submit();
                    }
                });

                function renderCartItems() {
                    if (cart.length === 0) {
                        cartItemsContainer.innerHTML = `
                        <div class="p-6 text-center text-gray-500">
                            Your cart is empty
                        </div>
                    `;
                        itemCountElement.textContent = '0 items';
                        showCheckoutFormBtn.disabled = true;
                        showCheckoutFormBtn.classList.add('opacity-50', 'cursor-not-allowed');
                        return;
                    }

                    let html = '';
                    cart.forEach(item => {
                        html += `
                        <div class="p-4 flex flex-col sm:flex-row gap-4" data-id="${item.id}">
                            <div class="flex-shrink-0">
                                <img src="${item.image}" alt="${item.name}" class="w-20 h-20 object-cover rounded-md">
                            </div>
                            <div class="flex-grow">
                                <h3 class="font-medium text-gray-800">${item.name}</h3>
                                <p class="text-sm text-gray-500">${item.weight}</p>
                                <p class="text-green-600 font-medium mt-1">${item.price}</p>
                            </div>
                            <div class="flex items-center">
                                <div class="flex items-center border rounded-md">
                                    <button class="quantity-btn decrease" data-id="${item.id}">
                                        <i class="fas fa-minus text-xs"></i>
                                    </button>
                                    <input type="number" min="1" value="${item.quantity}" class="quantity-input" data-id="${item.id}">
                                    <button class="quantity-btn increase" data-id="${item.id}">
                                        <i class="fas fa-plus text-xs"></i>
                                    </button>
                                </div>
                                <button class="ml-4 text-red-500 hover:text-red-700 remove-item" data-id="${item.id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    });

                    cartItemsContainer.innerHTML = html;
                    itemCountElement.textContent = `${cart.reduce((total, item) => total + item.quantity, 0)} items`;
                    showCheckoutFormBtn.disabled = false;
                    showCheckoutFormBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                }

                function renderOrderSummary() {
                    if (cart.length === 0) {
                        orderItemsContainer.innerHTML = '<p class="text-gray-500">Your cart is empty</p>';
                        orderSubtotalElement.textContent = '₹0';
                        orderTaxesElement.textContent = '₹0';
                        orderTotalElement.textContent = '₹0';
                        return;
                    }

                    let subtotal = 0;
                    let html = '';
                    cart.forEach(item => {
                        const itemPrice = parseFloat(item.price.replace('₹', ''));
                        const itemTotal = itemPrice * item.quantity;
                        subtotal += itemTotal;
                        html += `
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center">
                                <span class="bg-gray-100 rounded-md px-2 py-1 text-sm mr-3">${item.quantity}</span>
                                <div>
                                    <h4 class="font-medium text-gray-800">${item.name}</h4>
                                    <p class="text-sm text-gray-500">${item.weight}</p>
                                </div>
                            </div>
                            <span class="font-medium">₹${itemTotal.toFixed(2)}</span>
                        </div>
                    `;
                    });

                    orderItemsContainer.innerHTML = html;
                    const taxes = subtotal * 0.05; // 5% tax
                    const total = subtotal + taxes;

                    orderSubtotalElement.textContent = `₹${subtotal.toFixed(2)}`;
                    orderTaxesElement.textContent = `₹${taxes.toFixed(2)}`;
                    orderTotalElement.textContent = `₹${total.toFixed(2)}`;
                }

                function updateQuantity(id, change, newQuantity = null) {
                    const itemIndex = cart.findIndex(item => item.id === id);
                    if (itemIndex >= 0) {
                        if (newQuantity !== null) {
                            cart[itemIndex].quantity = newQuantity;
                        } else {
                            cart[itemIndex].quantity += change;
                        }
                        if (cart[itemIndex].quantity <= 0) {
                            cart.splice(itemIndex, 1);
                        }
                        saveCart();
                    }
                }

                function removeItem(id) {
                    cart = cart.filter(item => item.id !== id);
                    saveCart();
                }

                function saveCart() {
                    localStorage.setItem('cart', JSON.stringify(cart));
                    updateCartCounter();
                    renderCartItems();
                    renderOrderSummary();
                }

                function updateCartCounter() {
                    const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
                    cartCounter.textContent = totalItems;
                    cartCounter.classList.toggle('hidden', totalItems === 0);
                }

                function selectPaymentMethod(methodElement) {
                    document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
                    methodElement.classList.add('selected');
                    const radio = methodElement.querySelector('input[type="radio"]');
                    radio.checked = true;
                    document.querySelectorAll('.payment-details').forEach(d => d.classList.remove('active'));
                    const methodName = methodElement.getAttribute('data-method');
                    document.getElementById(`${methodName}Details`).classList.add('active');
                }

                function validateCheckoutForm() {
                    const form = document.getElementById('deliveryForm');
                    const requiredFields = form.querySelectorAll('[required]');
                    let isValid = true;

                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            field.classList.add('border-red-500');
                            isValid = false;
                        } else {
                            field.classList.remove('border-red-500');
                        }
                    });

                    if (!isValid) {
                        showNotification('Please fill in all required delivery information', 'error');
                        return false;
                    }

                    const selectedPayment = document.querySelector('input[name="paymentMethod"]:checked');
                    if (!selectedPayment) {
                        showNotification('Please select a payment method', 'error');
                        return false;
                    }

                    const method = selectedPayment.id;
                    if (method === 'credit-card') {
                        const cardNumber = document.getElementById('cardNumber').value;
                        const expiryDate = document.getElementById('expiryDate').value;
                        const cvv = document.getElementById('cvv').value;
                        const cardName = document.getElementById('cardName').value;
                        if (!cardNumber || !expiryDate || !cvv || !cardName) {
                            showNotification('Please complete all credit card details', 'error');
                            return false;
                        }
                    } else if (method === 'upi') {
                        const upiId = document.getElementById('upiId').value;
                        if (!upiId) {
                            showNotification('Please enter your UPI ID', 'error');
                            return false;
                        }
                    } else if (method === 'netbanking') {
                        const bankSelect = document.getElementById('bankSelect').value;
                        if (!bankSelect) {
                            showNotification('Please select your bank', 'error');
                            return false;
                        }
                    }

                    if (!document.getElementById('termsAgree').checked) {
                        showNotification('Please agree to the Terms & Conditions', 'error');
                        return false;
                    }

                    return true;
                }

                function showNotification(message, type = 'success') {
                    document.querySelectorAll('.notification').forEach(n => n.remove());
                    const notification = document.createElement('div');
                    notification.className = `notification fixed bottom-4 right-4 px-4 py-2 rounded-md shadow-lg flex items-center animate-fade-in ${type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'}`;
                    notification.innerHTML = `
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
                    ${message}
                    <button class="ml-4 hover:opacity-75" onclick="this.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                    document.body.appendChild(notification);
                    setTimeout(() => {
                        if (notification.parentNode) {
                            notification.classList.remove('animate-fade-in');
                            notification.classList.add('animate-fade-out');
                            setTimeout(() => notification.remove(), 500);
                        }
                    }, 5000);
                }
            });
        </script>
    </body>

    </html>