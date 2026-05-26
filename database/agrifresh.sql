-- ============================================================
-- AgriFresh Nepal – Database Schema
-- Run in phpMyAdmin: Import → agrifresh.sql → Go
-- ============================================================

CREATE DATABASE IF NOT EXISTS agrifresh
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE agrifresh;

-- ─────────────────────────────────────────
-- USERS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
  id         INT          NOT NULL AUTO_INCREMENT,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL,
  password   VARCHAR(255) NOT NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- PRODUCTS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS products (
  id          INT             NOT NULL AUTO_INCREMENT,
  name        VARCHAR(100)    NOT NULL,
  category    VARCHAR(50)     NOT NULL DEFAULT 'General',
  price       DECIMAL(10,2)   NOT NULL,
  farmer      VARCHAR(150)    NOT NULL DEFAULT '',
  description TEXT,
  rating      DECIMAL(3,1)    NOT NULL DEFAULT 0.0,
  reviews     INT             NOT NULL DEFAULT 0,
  image       VARCHAR(255)    NOT NULL DEFAULT '',
  created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- ORDERS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
  id             INT           NOT NULL AUTO_INCREMENT,
  user_id        INT               NULL DEFAULT NULL,
  customer_name  VARCHAR(200)  NOT NULL DEFAULT 'Guest',
  total          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status         VARCHAR(50)   NOT NULL DEFAULT 'Confirmed',
  payment_method VARCHAR(50)   NOT NULL DEFAULT 'cod',
  address        TEXT          NOT NULL,
  created_at     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_orders_user (user_id),
  CONSTRAINT fk_orders_user
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- ORDER ITEMS
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS order_items (
  id           INT           NOT NULL AUTO_INCREMENT,
  order_id     INT           NOT NULL,
  product_id   INT               NULL DEFAULT NULL,
  product_name VARCHAR(100)  NOT NULL,
  price        DECIMAL(10,2) NOT NULL,
  quantity     INT           NOT NULL DEFAULT 1,
  image        VARCHAR(255)  NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  KEY idx_order_items_order (order_id),
  CONSTRAINT fk_items_order
    FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- CONTACT MESSAGES
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS contact_messages (
  id         INT          NOT NULL AUTO_INCREMENT,
  name       VARCHAR(100) NOT NULL,
  email      VARCHAR(150) NOT NULL,
  reason     VARCHAR(100) NOT NULL DEFAULT '',
  subject    VARCHAR(200) NOT NULL DEFAULT '',
  message    TEXT         NOT NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────
-- SAMPLE PRODUCTS
-- Image paths are root-relative: "uploads/filename.jpg"
-- resolveImage() in app.js prepends basePath at runtime
-- ─────────────────────────────────────────
INSERT INTO products (id, name, category, price, farmer, description, rating, reviews, image) VALUES
(1,  'Organic Tomatoes', 'Vegetables', 120,  'Ram Bahadur Farm, Chitwan',     'Freshly picked organic tomatoes grown without pesticides.',  4.5, 128, 'uploads/tomato.jpg'),
(2,  'Fresh Spinach',    'Vegetables',  80,  'Sita Devi Organics, Bhaktapur', 'Tender baby spinach leaves packed with nutrients.',          4.2,  87, 'uploads/spinach.jpg'),
(3,  'Potatoes',         'Vegetables',  60,  'Himalaya Agro Farm',            'Fresh hill potatoes rich in taste.',                         4.1,  54, 'uploads/potato.jpg'),
(4,  'Carrots',          'Vegetables',  90,  'Organic Valley Nepal',          'Crunchy sweet carrots freshly harvested.',                   4.3,  66, 'uploads/carrot.jpg'),
(5,  'Cabbage',          'Vegetables',  50,  'Green Hill Farmers',            'Fresh green cabbage grown in hills.',                        4.0,  40, 'uploads/cabbage.jpg'),
(6,  'Onions',           'Vegetables',  70,  'Terai Fresh Farm',              'Red onions with strong flavor.',                             4.2,  52, 'uploads/onion.jpg'),
(7,  'Apples',           'Fruits',     200,  'Mustang Apple Farm',            'Sweet and juicy apples from Mustang.',                       4.7, 190, 'uploads/apple.jpg'),
(8,  'Bananas',          'Fruits',     100,  'Terai Fruit Valley',            'Fresh ripe bananas full of energy.',                         4.3, 112, 'uploads/banana.jpg'),
(9,  'Mangoes',          'Fruits',     250,  'Nepal Mango Garden',            'Sweet juicy mangoes seasonal harvest.',                      4.8, 210, 'uploads/mango.jpg'),
(10, 'Milk',             'Dairy',       70,  'Happy Cow Dairy',               'Pure fresh cow milk delivered daily.',                       4.4,  95, 'uploads/milk.jpg'),
(11, 'Eggs',             'Dairy',      150,  'Nepal Poultry Farm',            'Farm fresh white eggs rich in protein.',                     4.3,  88, 'uploads/eggs.jpg'),
(12, 'Garlic',           'Vegetables', 110,  'Himalayan Spice Farm',          'Strong aromatic garlic for cooking.',                        4.2,  61, 'uploads/garlic.jpg');



ALTER TABLE orders 
ADD COLUMN transaction_uuid VARCHAR(100) NULL AFTER id,
ADD COLUMN payment_status VARCHAR(50) DEFAULT 'Unpaid' AFTER payment_method;