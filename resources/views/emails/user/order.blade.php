<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Delivery Order - Action Required</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .order-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
            font-size: 14px;
            font-weight: bold;
        }

        .content {
            padding: 30px 20px;
        }

        .alert-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
        }

        .alert-box h3 {
            color: #856404;
            margin-bottom: 5px;
        }

        .alert-box p {
            color: #856404;
            margin: 0;
        }

        .order-details {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e9ecef;
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #495057;
            flex: 1;
        }

        .detail-value {
            flex: 2;
            text-align: right;
            color: #212529;
        }

        .priority-high {
            color: #dc3545;
            font-weight: bold;
        }

        .priority-medium {
            color: #fd7e14;
            font-weight: bold;
        }

        .priority-low {
            color: #28a745;
            font-weight: bold;
        }

        .address-section {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .address-title {
            color: #1565c0;
            font-weight: bold;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .address-title::before {
            content: "📍";
            margin-right: 8px;
        }

        .address-text {
            color: #424242;
            line-height: 1.5;
        }

        .customer-info {
            background: #f3e5f5;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .customer-title {
            color: #7b1fa2;
            font-weight: bold;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .customer-title::before {
            content: "👤";
            margin-right: 8px;
        }

        .action-buttons {
            text-align: center;
            margin: 30px 0;
        }

        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 0 10px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .footer {
            background: #343a40;
            color: #adb5bd;
            text-align: center;
            padding: 20px;
            font-size: 14px;
        }

        .footer a {
            color: #fff;
            text-decoration: none;
        }

        @media (max-width: 600px) {
            .container {
                margin: 10px;
                border-radius: 0;
            }

            .detail-row {
                flex-direction: column;
            }

            .detail-value {
                text-align: left;
                margin-top: 5px;
            }

            .btn {
                display: block;
                margin: 10px 0;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🚚 New Delivery Order</h1>
            <div class="order-badge">Order {{ $order_id }}</div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Alert Box -->
            <div class="alert-box">
                <h3>⚡ Action Required</h3>
                <p>You have been assigned a new delivery order. Please review the details below and confirm acceptance.
                </p>
            </div>

                <!-- Pickup Address -->
                <div class="address-section">
                    <div class="address-title">Pickup Address</div>
                    <div class="address-text">
                        <strong>{{ $pickup_location }}</strong><br>
                
                        <!-- <strong>Contact:</strong> (555) 987-6543 -->
                    </div>
                </div>
                
                <!-- Delivery Address -->
                <div class="address-section">
                    <div class="address-title">Delivery Address</div>
                    <div class="address-text">
                
                        <strong>{{ $delivery_address }}</strong>
                    </div>
                </div>


            <!-- Customer Information -->
            <div class="customer-info">
                <div class="customer-title">Customer Information</div>
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value">{{ $customer_name }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value">{{ $customer_phone }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value">{{ $customer_email }}</span>
                </div>
            
            </div>

            <!-- Order Details -->
            <div class="order-details">
                <h3 style="margin-bottom: 15px; color: #495057;">📋 Order Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Order ID:</span>
                    <span class="detail-value">{{ $order_id }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Order Date:</span>
                    <span class="detail-value">{{ $order_date }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Estimated Time:</span>
                    <span class="detail-value">25 minutes</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Food to pick up:</span>
                    <span class="detail-value">25 minutes</span>
                </div>
                <!-- <div class="detail-row">
                    <span class="detail-label">Delivery Fee:</span>
                    <span class="detail-value">$12.50</span>
                </div> -->
            </div>
        <!-- Order Items -->
        <div class="order-items">
<h3 style="margin-bottom: 15px; color: #495057;">🍽️ Order Items</h3>
@foreach($orderItems as $item)
<div class="item-card">
    <div class="item-image">
        <!-- <img src="{{ $item['image'] }}" alt="{{ $item['menu_name'] }}" /> -->
    </div>
    <div class="item-details">
        <div class="item-name">{{ $item['menu_name'] }}</div>
        <div class="item-info">
            <span class="item-quantity">Qty: {{ $item['quantity'] }}</span>
            <span class="item-price">₦{{ number_format($item['price']) }}</span>
        </div>
        <div class="item-total">
            Total: ₦{{ number_format($item['price'] * $item['quantity']) }}
        </div>
    </div>
</div>
@endforeach

<div class="order-summary">
    <div class="summary-row">
        <span class="summary-label">Total Items:</span>
        <span class="summary-value">{{ array_sum(array_column($orderItems, 'quantity')) }}</span>
    </div>
    <div class="summary-row total-row">
        <span class="summary-label">Order Total:</span>
        <span class="summary-value">₦{{ number_format(array_sum(array_map(function ($item) {
    return $item['price'] * $item['quantity']; }, $orderItems))) }}</span>
    </div>
</div>
</div>

        <!-- Order Items -->
        <div class="order-items">
            <h3 style="margin-bottom: 15px; color: #495057;">🍽️ Order Items</h3>
            @foreach($orderItems as $item)
                <div class="item-card">
                    <div class="item-image">
                        <!-- <img width="200" src="{{ $item['image'] }}" alt="{{ $item['menu_name'] }}" /> -->
                    </div>
                    <div class="item-details">
                        <div class="item-name">{{ $item['menu_name'] }}</div>
                        <div class="item-info">
                            <span class="item-quantity">Qty: {{ $item['quantity'] }}</span>
                            <span class="item-price">₦{{ number_format($item['price']) }}</span>
                        </div>
                        <div class="item-total">
                            Total: ₦{{ number_format($item['price'] * $item['quantity']) }}
                        </div>
                    </div>
                </div>
            @endforeach
        
            <div class="order-summary">
                <div class="summary-row">
                    <span class="summary-label">Total Items:</span>
                    <span class="summary-value">{{ array_sum(array_column($orderItems, 'quantity')) }}</span>
                </div>
                <div class="summary-row total-row">
                    <span class="summary-label">Order Total:</span>
                    <span
                        class="summary-value">₦{{ number_format(array_sum(array_map(function ($item) {
    return $item['price'] * $item['quantity']; }, $orderItems))) }}</span>
                </div>
            </div>
        </div>

          

     

            <!-- Action Buttons -->
            <!-- <div class="action-buttons">
                <a href="#" class="btn btn-primary">✅ Accept Order</a>
                <a href="#" class="btn btn-secondary">❌ Decline Order</a>
            </div> -->

            <!-- Additional Information -->
            <div style="background: #fff3e0; padding: 15px; border-radius: 8px; margin-top: 20px;">
                <h4 style="color: #ef6c00; margin-bottom: 10px;">📝 Important Notes:</h4>
                <ul style="color: #bf360c; padding-left: 20px;">
                    <li>Order must be delivered within 30 minutes</li>
                </ul>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>© 2025 Order Food and Delivery Service- Order</p>
            <p>Need help? Contact support: <a href="mailto:bhuorder@gmail.com">bhuorder@gmail.com</a> | <a
                    href="tel:+2349032497799">+234 903 249-7799</a></p>
        </div>
    </div>
</body>

</html>