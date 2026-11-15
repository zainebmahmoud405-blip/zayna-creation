-- ============================================================
--  Zayna Creation — Mise à jour de la base de données
--  À exécuter APRÈS setup_users.sql (dans phpMyAdmin > Onglet SQL)
--  Ajoute : les produits (gérés par l'admin) et un vrai panier
--  multi-articles (order_items) relié aux commandes existantes.
-- ============================================================

-- 1. Table des produits (gérée depuis l'espace admin)
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    image VARCHAR(255) NOT NULL,
    category ENUM('standard','laptop','autre') DEFAULT 'standard',
    base_price DECIMAL(6,2) DEFAULT NULL,       -- prix unique (ex: pochette laptop / autre)
    price_s DECIMAL(6,2) DEFAULT NULL,          -- prix taille S
    price_m DECIMAL(6,2) DEFAULT NULL,          -- prix taille M
    price_l DECIMAL(6,2) DEFAULT NULL,          -- prix taille L
    price_pack_sm DECIMAL(6,2) DEFAULT NULL,    -- pack S+M
    price_pack_sml DECIMAL(6,2) DEFAULT NULL,   -- pack S+M+L
    in_stock TINYINT(1) DEFAULT 1,              -- 1 = disponible, 0 = épuisé
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Table des articles de commande (un panier peut contenir plusieurs articles)
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NULL,
    product_name VARCHAR(150) NOT NULL,
    size VARCHAR(30) DEFAULT NULL,
    quantity INT DEFAULT 1,
    unit_price DECIMAL(6,2) DEFAULT 0,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- 3. Migration : on récupère les anciennes commandes (1 produit par commande)
--    vers order_items pour ne rien perdre de l'historique.
INSERT INTO order_items (order_id, product_name, size, quantity, unit_price)
SELECT id, product_name, size, quantity, 0
FROM orders o
WHERE o.product_name IS NOT NULL
  AND NOT EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id);

-- 4. Les commandes ne stockent plus un seul produit : ces colonnes deviennent
--    optionnelles (on ne les supprime pas, par sécurité, mais elles ne sont
--    plus utilisées par le nouveau code).
ALTER TABLE orders MODIFY product_name VARCHAR(150) NULL;
ALTER TABLE orders MODIFY size VARCHAR(30) NULL;
ALTER TABLE orders MODIFY quantity INT NULL;

-- 5. On ajoute vos 7 articles actuels dans la table produits pour qu'ils
--    apparaissent tout de suite dans la nouvelle boutique dynamique.
--    (Ne les insère que si la table est vide, pour ne pas dupliquer si vous relancez ce script)
INSERT INTO products (name, description, image, category, price_s, price_m, price_l, price_pack_sm, price_pack_sml, in_stock, sort_order)
SELECT * FROM (SELECT
    'Golden Bloom Pouch' AS name,
    'Une pochette florale délicate aux reflets dorés, parfaite pour vos essentiels au quotidien. Tissu matelassé pour une texture douce et luxueuse.' AS description,
    'images/golden-bloom.png' AS image, 'standard' AS category, 10 AS price_s, 12 AS price_m, 15 AS price_l, 20 AS price_pack_sm, 30 AS price_pack_sml, 1 AS in_stock, 1 AS sort_order
) t
WHERE NOT EXISTS (SELECT 1 FROM products);

INSERT INTO products (name, description, image, category, price_s, price_m, price_l, price_pack_sm, price_pack_sml, in_stock, sort_order)
SELECT * FROM (SELECT 'Heart', 'Pochette à motif cœur pleine de charme. Idéale comme cadeau ou accessoire du quotidien.', 'images/heart.png', 'standard', 10, 12, 15, 20, 30, 1, 2) t
WHERE (SELECT COUNT(*) FROM products) = 1;

INSERT INTO products (name, description, image, category, price_s, price_m, price_l, price_pack_sm, price_pack_sml, in_stock, sort_order)
SELECT * FROM (SELECT 'Pinky', 'Tons roses doux pour un look féminin et élégant. Tissu matelassé fin et fermeture éclair fluide.', 'images/pinky.png', 'standard', 10, 12, 15, 20, 30, 1, 3) t
WHERE (SELECT COUNT(*) FROM products) = 2;

INSERT INTO products (name, description, image, category, price_s, price_m, price_l, price_pack_sm, price_pack_sml, in_stock, sort_order)
SELECT * FROM (SELECT 'Lavina', 'Inspirée des champs de lavande, cette pochette apporte une esthétique pastel et rêveuse.', 'images/lavina.png', 'standard', 10, 12, 15, 20, 30, 1, 4) t
WHERE (SELECT COUNT(*) FROM products) = 3;

INSERT INTO products (name, description, image, category, price_s, price_m, price_l, price_pack_sm, price_pack_sml, in_stock, sort_order)
SELECT * FROM (SELECT 'Cloudy', 'Légère comme un nuage, cette pochette a des textures douces et des couleurs aériennes pour un style minimaliste et mignon.', 'images/cloudy.png', 'standard', 10, 12, 15, 20, 30, 1, 5) t
WHERE (SELECT COUNT(*) FROM products) = 4;

INSERT INTO products (name, description, image, category, price_s, price_m, price_l, price_pack_sm, price_pack_sml, in_stock, sort_order)
SELECT * FROM (SELECT 'Flora', 'Un jardin de fleurs brodé avec soin. Flora apporte la beauté de la nature à votre quotidien.', 'images/flora.png', 'standard', 10, 12, 15, 20, 30, 1, 6) t
WHERE (SELECT COUNT(*) FROM products) = 5;

INSERT INTO products (name, description, image, category, base_price, in_stock, sort_order)
SELECT * FROM (SELECT 'Laptop Pouch', 'Une pochette laptop élégante et protectrice, fabriquée à la main pour garder votre appareil en sécurité avec style.', 'images/laptop.png', 'laptop', 20, 1, 7) t
WHERE (SELECT COUNT(*) FROM products) = 6;
