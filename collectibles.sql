-- 1. Create and Use the Database
CREATE DATABASE collectibles_biz;
USE collectibles_biz;

-- 2. Create Customers Table
CREATE TABLE customers (
    customer_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20) NOT NULL
);

-- 3. Create Orders Table
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending', 'Shipped', 'Delivered') DEFAULT 'Pending',
    is_cancelled TINYINT(1) DEFAULT 0,
    customer_id INT,
    CONSTRAINT fk_customer FOREIGN KEY (customer_id) 
        REFERENCES customers(customer_id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
);

-- 4. Create Items Table
CREATE TABLE items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(150) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    order_id INT,
    CONSTRAINT fk_order_items FOREIGN KEY (order_id) 
        REFERENCES orders(order_id) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE
);
