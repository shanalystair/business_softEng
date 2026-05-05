<?php
$host = 'localhost'; $db = 'collectibles_biz'; $user = 'root'; $pass = ''; $charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // --- INSERT ---
        if (isset($_POST['add_order'])) {
            $stmt = $pdo->prepare("INSERT INTO customers (full_name, email, phone) VALUES (?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE full_name = VALUES(full_name), phone = VALUES(phone)");
            $stmt->execute([$_POST['full_name'], $_POST['email'], $_POST['phone']]);
            
            $stmt = $pdo->prepare("SELECT customer_id FROM customers WHERE email = ?");
            $stmt->execute([$_POST['email']]);
            $custId = $stmt->fetchColumn();

            $stmt = $pdo->prepare("INSERT INTO orders (customer_id) VALUES (?)");
            $stmt->execute([$custId]);
            $orderId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO items (item_name, price, order_id) VALUES (?, ?, ?)");
            $stmt->execute([$_POST['item_name'], $_POST['price'], $orderId]);
        }

        // --- UPDATE STATUS ---
        if (isset($_POST['update_status'])) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
            $stmt->execute([$_POST['status'], $_POST['order_id']]);
        }

        // --- CANCEL (SOFT DELETE) ---
        if (isset($_POST['cancel_order'])) {
            $stmt = $pdo->prepare("UPDATE orders SET is_cancelled = 1 WHERE order_id = ?");
            $stmt->execute([$_POST['order_id']]);
        }

        // --- DELETE (PERMANENT REMOVAL) ---
        if (isset($_POST['delete_order'])) {
            // We delete from 'items' first because of the foreign key relationship
            $stmt = $pdo->prepare("DELETE FROM items WHERE order_id = ?");
            $stmt->execute([$_POST['order_id']]);

            $stmt = $pdo->prepare("DELETE FROM orders WHERE order_id = ?");
            $stmt->execute([$_POST['order_id']]);
        }

        header("Location: " . $_SERVER['PHP_SELF']); exit;
    }

    // FETCH: Only show orders where is_cancelled = 0
    $query = "SELECT o.order_id, c.full_name, c.phone, i.item_name, i.price, o.status 
              FROM orders o 
              JOIN customers c ON o.customer_id = c.customer_id 
              JOIN items i ON o.order_id = i.order_id
              WHERE o.is_cancelled = 0";
    $records = $pdo->query($query)->fetchAll();

} catch (PDOException $e) { die("Error: " . $e->getMessage()); }
?>

<!DOCTYPE html>
<html>
<head>
    <title>Collectibles Shop</title>
    <style>
        body { font-family: sans-serif; background: #f0f2f5; padding: 20px; }
        .box { max-width: 1150px; margin: auto; background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        input, select, button { padding: 10px; margin: 5px; border: 1px solid #ccc; border-radius: 5px; }
        button { background: #1a73e8; color: white; border: none; cursor: pointer; font-weight: bold; }
        .cancel-btn { background: #ff9800; }
        .delete-btn { background: #d93025; }
        .delete-btn:hover { background: #b71c1c; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        .actions { white-space: nowrap; }
    </style>
</head>
<body>

<div class="box">
    <h2>Collectibles Business Manager</h2>
    
    <form method="POST">
        <input type="text" name="full_name" placeholder="Name" required>
        <input type="email" name="email" placeholder="Email (Unique)" required>
        <input type="text" name="phone" placeholder="Phone Number" required>
        <input type="text" name="item_name" placeholder="Item Name" required>
        <input type="number" step="0.01" name="price" placeholder="Price" required>
        <button type="submit" name="add_order">Place Order</button>
    </form>

    <table>
        <tr>
            <th>Order #</th>
            <th>Customer</th>
            <th>Phone</th>
            <th>Item</th>
            <th>Price</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php foreach ($records as $r): ?>
        <tr>
            <td><?= $r['order_id'] ?></td>
            <td><?= htmlspecialchars($r['full_name']) ?></td>
            <td><?= htmlspecialchars($r['phone']) ?></td>
            <td><strong><?= htmlspecialchars($r['item_name']) ?></strong></td>
            <td>$<?= number_format($r['price'], 2) ?></td>
            <td>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="order_id" value="<?= $r['order_id'] ?>">
                    <select name="status" onchange="this.form.submit()">
                        <option <?= $r['status']=='Pending'?'selected':'' ?>>Pending</option>
                        <option <?= $r['status']=='Shipped'?'selected':'' ?>>Shipped</option>
                        <option <?= $r['status']=='Delivered'?'selected':'' ?>>Delivered</option>
                    </select>
                    <input type="hidden" name="update_status" value="1">
                </form>
            </td>
            <td class="actions">
                <!-- Cancel Button -->
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="order_id" value="<?= $r['order_id'] ?>">
                    <button type="submit" name="cancel_order" class="cancel-btn">Cancel</button>
                </form>

                <!-- Delete Button -->
                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to PERMANENTLY delete this order?');">
                    <input type="hidden" name="order_id" value="<?= $r['order_id'] ?>">
                    <button type="submit" name="delete_order" class="delete-btn">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>
