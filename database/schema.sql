-- =========================================================
-- Manga Trading System - Database Schema
-- Engine: MySQL 5.7+ / MariaDB 10.3+
-- =========================================================

CREATE DATABASE IF NOT EXISTS manga_stock CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE manga_stock;

-- ---------------------------------------------------------
-- 0. users : ผู้ใช้งานระบบ (admin / user)
--    - admin : ใช้งานได้ทุกฟีเจอร์ (เพิ่ม/แก้ไข/ลบ)
--    - user  : ดูข้อมูลได้อย่างเดียว (read-only)
-- ---------------------------------------------------------
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    role ENUM('admin','user') NOT NULL DEFAULT 'user',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME DEFAULT NULL,
    INDEX idx_users_role (role)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 1. purchases : บิลซื้อเข้า 1 บิล อาจมีหลายรายการ (purchase_items)
-- ---------------------------------------------------------
CREATE TABLE purchases (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_code VARCHAR(30) NOT NULL,
    purchase_date DATE NOT NULL,
    total_shipping_cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_book_count INT UNSIGNED NOT NULL DEFAULT 0,   -- จำนวนเล่มทั้งหมดในบิล (ใช้ปันส่วนค่าส่ง)
    note VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 2. purchase_items : รายการในบิล (1 แถว = 1 เรื่องในบิล)
--    เช่น "ดันดาดัน ราคารวม 1500 บาท" ซึ่งอาจมีหลายช่วงเล่ม (purchase_item_volumes)
-- ---------------------------------------------------------
CREATE TABLE purchase_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    item_price DECIMAL(10,2) NOT NULL,       -- ราคาซื้อรวมของเรื่องนี้ในบิล (ไม่รวมค่าส่ง)
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pi_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 3. purchase_item_volumes : ช่วงเล่มของรายการ (1 เรื่องในบิล อาจมีหลายช่วง เช่น 1-10, 15-20, 22)
--    ใช้สำหรับกรณีซื้อแบบขาดเล่ม เช่น ดันดาดัน เล่ม 1-10, 15-20, 22
-- ---------------------------------------------------------
CREATE TABLE purchase_item_volumes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_item_id INT UNSIGNED NOT NULL,
    volume_start INT UNSIGNED NOT NULL,
    volume_end INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,          -- จำนวนเล่มของช่วงนี้ (volume_end - volume_start + 1)
    CONSTRAINT fk_piv_purchase_item FOREIGN KEY (purchase_item_id) REFERENCES purchase_items(id) ON DELETE CASCADE,
    INDEX idx_piv_item (purchase_item_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 4. inventory_books : สต็อกแยกรายเล่ม (แตกออกจาก purchase_item_volumes)
--    unit_cost = (item_price / item_qty) + (total_shipping_cost / total_book_count)
--    โดย item_qty = SUM(quantity) ของทุก volume range ในเรื่องเดียวกัน
-- ---------------------------------------------------------
CREATE TABLE inventory_books (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_item_volume_id INT UNSIGNED NOT NULL,
    bundle_id INT UNSIGNED DEFAULT NULL,
    title VARCHAR(150) NOT NULL,
    volume INT UNSIGNED NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL,
    status ENUM('available','in_bundle','sold') NOT NULL DEFAULT 'available',
    purchase_date DATE NOT NULL,             -- คัดลอกมาจากบิลซื้อ ใช้เรียง FIFO
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ib_purchase_item_volume FOREIGN KEY (purchase_item_volume_id) REFERENCES purchase_item_volumes(id) ON DELETE CASCADE,
    INDEX idx_title_status (title, status),
    INDEX idx_fifo (title, volume, purchase_date)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 5. bundles : ชุดที่จับรวมจากหลายเล่ม (เช่น เล่ม 1-15)
-- ---------------------------------------------------------
CREATE TABLE bundles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    volume_start INT UNSIGNED NOT NULL,
    volume_end INT UNSIGNED NOT NULL,
    book_count INT UNSIGNED NOT NULL,
    total_cost DECIMAL(10,2) NOT NULL,       -- ผลรวม unit_cost ของเล่มที่นำมารวม
    cover_image VARCHAR(255) DEFAULT NULL,   -- path รูปภาพปกชุด (relative เช่น uploads/bundles/xxx.jpg) — ไม่บังคับ
    status ENUM('available','sold') NOT NULL DEFAULT 'available',
    group_id INT UNSIGNED DEFAULT NULL,      -- ถ้าอยู่ในกลุ่มขายรวมหลายเรื่อง จะอ้างถึง bundle_groups.id
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_bundle_group (group_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 5.1 bundle_groups : กลุ่มขายรวมหลายเรื่อง (atomic sale)
--     ตัวอย่าง: ขาย "ดันดาดัน เล่ม 1-15" + "โดจิมิ เล่ม 1-10" พร้อมกัน
--     bundle ที่อยู่ในกลุ่มจะ group_id = bundle_groups.id
-- ---------------------------------------------------------
CREATE TABLE bundle_groups (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(150) DEFAULT NULL,         -- ชื่อกลุ่ม (optional) เช่น "เซ็ตลดราคาส่งท้ายปี"
    book_count INT UNSIGNED NOT NULL,         -- ผลรวมจำนวนเล่มทุก bundle ในกลุ่ม
    total_cost DECIMAL(10,2) NOT NULL,        -- ผลรวม total_cost ทุก bundle ในกลุ่ม
    bundle_count INT UNSIGNED NOT NULL,       -- จำนวน bundle ย่อยในกลุ่ม
    status ENUM('available','sold') NOT NULL DEFAULT 'available',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

ALTER TABLE bundles
    ADD CONSTRAINT fk_bundle_group FOREIGN KEY (group_id) REFERENCES bundle_groups(id) ON DELETE SET NULL;

ALTER TABLE inventory_books
    ADD CONSTRAINT fk_ib_bundle FOREIGN KEY (bundle_id) REFERENCES bundles(id) ON DELETE SET NULL;

-- ---------------------------------------------------------
-- 6. purchase_images : รูปภาพแนบการซื้อเข้า (บิล/รูปหนังสือ)
--    1 บิลซื้อมีได้หลายรูป ใช้อ้างอิงต้นทางของสต็อกในหน้า Stock
-- ---------------------------------------------------------
CREATE TABLE purchase_images (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_id INT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,        -- path relative เช่น uploads/purchases/PO-xxx_abc.jpg
    original_name VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_pimg_purchase FOREIGN KEY (purchase_id) REFERENCES purchases(id) ON DELETE CASCADE,
    INDEX idx_pimg_purchase (purchase_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- 7. sales : บันทึกการขาย (ขายได้ทั้งเล่มเดี่ยว หรือ bundle)
--    ref_type + ref_id เป็นแบบ polymorphic reference
-- ---------------------------------------------------------
CREATE TABLE sales (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ref_type ENUM('book','bundle','bundle_group') NOT NULL,
    ref_id INT UNSIGNED NOT NULL,            -- inventory_books.id / bundles.id / bundle_groups.id ตาม ref_type
    title_snapshot VARCHAR(150) NOT NULL,
    channel ENUM('shopee','facebook') NOT NULL,
    fee_percent DECIMAL(5,2) NOT NULL DEFAULT 0,
    sale_price DECIMAL(10,2) NOT NULL,
    fee_amount DECIMAL(10,2) NOT NULL,
    net_revenue DECIMAL(10,2) NOT NULL,      -- sale_price - fee_amount
    cost_amount DECIMAL(10,2) NOT NULL,      -- ต้นทุนรวมของสินค้าที่ขาย
    profit_amount DECIMAL(10,2) NOT NULL,    -- net_revenue - cost_amount
    sale_date DATE NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ref (ref_type, ref_id)
) ENGINE=InnoDB;