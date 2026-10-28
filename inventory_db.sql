-- ============================================================
-- Inventory Management System — database schema + sample data
-- Import this file via phpMyAdmin (or `mysql -u root < inventory_db.sql`)
-- Creates: inventory_db  →  tables: products, stock_movements
-- ============================================================

CREATE DATABASE IF NOT EXISTS inventory_db
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE inventory_db;

-- ------------------------------------------------------------
-- Table: products
-- ------------------------------------------------------------
DROP TABLE IF EXISTS stock_movements;
DROP TABLE IF EXISTS products;

CREATE TABLE products (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    description     TEXT NULL,
    price           DECIMAL(10,2) NOT NULL CHECK (price > 0),
    stock_quantity  INT NOT NULL DEFAULT 0,
    alert_threshold INT NOT NULL DEFAULT 5,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Table: stock_movements  (every IN / OUT, for auditing)
-- ------------------------------------------------------------
CREATE TABLE stock_movements (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    type       ENUM('in','out') NOT NULL,
    quantity   INT NOT NULL CHECK (quantity > 0),
    note       VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_movements_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Helpful indexes for the history / dashboard queries
CREATE INDEX idx_movements_product ON stock_movements (product_id, created_at);
CREATE INDEX idx_products_stock    ON products (stock_quantity, alert_threshold);

-- ============================================================
-- SAMPLE DATA (optional — delete this block for an empty system)
-- ============================================================
INSERT INTO products (name, description, price, stock_quantity, alert_threshold, created_at) VALUES
('Wireless Mouse',      '2.4GHz ergonomic mouse, black',        25.99, 40, 10, '2026-10-12 09:00:00'),
('Mechanical Keyboard', 'RGB switches, full size',              79.50,  8, 10, '2026-10-12 09:05:00'),
('USB-C Cable 2m',      'Braided, fast charge 100W',             9.99, 25, 15, '2026-10-13 10:20:00'),
('27" LED Monitor',     'QHD 75Hz, HDMI + DisplayPort',        189.00,  4,  5, '2026-10-14 11:40:00'),
('Laptop Stand',        'Aluminium, adjustable height',          34.75, 12,  6, '2026-10-15 14:10:00'),
('Webcam HD',           '1080p with privacy shutter',           45.00, 18,  8, '2026-10-16 15:30:00');

-- Movement history (quantities reconcile with stock_quantity above)
INSERT INTO stock_movements (product_id, type, quantity, note, created_at) VALUES
(1, 'in',  50, 'Initial delivery',                 '2026-10-12 09:30:00'),
(1, 'out', 10, 'Walk-in customer sales',           '2026-10-18 10:15:00'),
(2, 'in',  20, 'Supplier shipment #4821',          '2026-10-12 09:45:00'),
(2, 'out', 12, 'Online orders shipped',            '2026-10-20 13:05:00'),
(3, 'in',  30, 'Restocked from warehouse',         '2026-10-13 10:35:00'),
(3, 'out',  5, 'Damaged units replaced',           '2026-10-19 09:50:00'),
(4, 'in',   6, 'New batch received',               '2026-10-14 12:00:00'),
(4, 'out',  2, 'Display unit assigned',            '2026-10-21 16:20:00'),
(6, 'in',  25, 'Restock before campaign',          '2026-10-16 15:45:00'),
(6, 'out',  7, 'Orders shipped',                   '2026-10-22 11:10:00');
-- Product 5 (Laptop Stand) intentionally has NO movements,
-- so you can test the "delete only products without history" rule.
