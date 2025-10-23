

--  Používatelia (registrácia, prihlasovanie)
CREATE TABLE `users` (
  `user_id` INT NOT NULL AUTO_INCREMENT,              -- Primárny kľúč
  `name` VARCHAR(50) NOT NULL,                        -- Meno používateľa
  `email` VARCHAR(50) UNIQUE NOT NULL,                -- Unikátny email (používa sa pri prihlásení)
  `password` VARCHAR(255) NOT NULL,                   -- Hash hesla
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,   -- Dátum registrácie
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--  Lokality (miesta, kde sú uložené boxy)
CREATE TABLE `locations` (
  `location_id` INT AUTO_INCREMENT PRIMARY KEY,       -- Primárny kľúč
  `address` VARCHAR(100) NOT NULL,                    -- Ulica a číslo
  `city` VARCHAR(50) NOT NULL,                        -- Mesto
  `post_code` VARCHAR(10) NOT NULL                    -- PSČ
) ENGINE=InnoDB;

--  Boxy (úložné skrinky)
CREATE TABLE `boxes` (
  `box_id` INT AUTO_INCREMENT PRIMARY KEY,            -- Primárny kľúč
  `location_id` INT,                                  -- Cudzí kľúč na lokalitu
  `size` ENUM('S','M','L'),                           -- Veľkosť boxu
  `box_number` VARCHAR(10),                           -- Označenie boxu (napr. B12)
  `amount` DECIMAL(10,2),                             -- Cena prenájmu
  `status` ENUM('available','reserved','occupied'),   -- Stav boxu
  FOREIGN KEY (`location_id`) REFERENCES `locations`(`location_id`)
) ENGINE=InnoDB;

--  Rezervácie (vytvorené používateľom)
CREATE TABLE `reservations` (
  `rental_id` INT AUTO_INCREMENT PRIMARY KEY,         -- Primárny kľúč
  `user_id` INT NOT NULL,                             -- Cudzí kľúč na používateľa
  `location_id` INT NOT NULL,                         -- Cudzí kľúč na lokalitu
  `start_time` DATETIME NOT NULL,                     -- Začiatok rezervácie
  `end_time` DATETIME DEFAULT NULL,                   -- Koniec rezervácie
  `total_price` DECIMAL(10,2) DEFAULT 0.00,           -- Celková suma
  `status` ENUM('active','reserved','completed','cancelled') DEFAULT 'reserved', -- Stav
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
  FOREIGN KEY (`location_id`) REFERENCES `locations`(`location_id`)
) ENGINE=InnoDB;

--  Spojovacia tabuľka (rezervácie ↔ boxy)
CREATE TABLE `reservation_boxes` (
  `rental_id` INT NOT NULL,                           -- FK na rezerváciu
  `box_id` INT NOT NULL,                              -- FK na box
  PRIMARY KEY (`rental_id`, `box_id`),                -- Kompozitný PK = jeden box môže byť v jednej rezervácii len raz
  FOREIGN KEY (`rental_id`) REFERENCES `reservations`(`rental_id`) ON DELETE CASCADE,
  FOREIGN KEY (`box_id`) REFERENCES `boxes`(`box_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

--  Platby (väzba 1:1 s rezerváciou)
CREATE TABLE `payments` (
  `payment_id` INT AUTO_INCREMENT PRIMARY KEY,        -- Primárny kľúč
  `rental_id` INT UNIQUE,                             -- FK na rezerváciu (unikátna väzba 1:1)
  `amount` DECIMAL(10,2) NOT NULL,                    -- Suma platby
  `method` ENUM('cash','card') NOT NULL,              -- Spôsob platby
  `status` ENUM('paid','unpaid') DEFAULT 'unpaid',    -- Stav platby
  FOREIGN KEY (`rental_id`) REFERENCES `reservations`(`rental_id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================
--  VYSVETLENIE
-- ============================
-- 🔹 users          → Ukladá registrovaných používateľov.
-- 🔹 locations      → Reprezentuje miesta, kde sú boxy umiestnené.
-- 🔹 boxes          → Každý box patrí jednej lokalite (1:N).
-- 🔹 reservations   → Používateľ si môže vytvoriť viac rezervácií (1:N).
-- 🔹 reservation_boxes → Spojovacia tabuľka M:N medzi rezerváciami a boxmi.
-- 🔹 payments       → Každá rezervácia má maximálne 1 platbu (1:1).

-- 🔹 ON DELETE CASCADE znamená:
--    Ak sa zmaže rezervácia → automaticky sa zmažú aj záznamy v reservation_boxes a payments.

-- 🔹 CHARSET=utf8mb4:
--    Umožňuje používať aj emoji a špeciálne znaky (moderný štandard pre weby).

-- 🔹 ENUM:
--    Slúži na obmedzenie hodnôt (napr. status môže byť len 'reserved' alebo 'occupied').

