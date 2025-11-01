-- Original : Oracle SQL Developer Data Modeler
-- prepis: do MySQL
-- zmeny: int auto_increment, varchar, decimal, datetime
-- CHECK -> nahradeny ENUM 

CREATE TABLE boxes (
    box_id INT AUTO_INCREMENT PRIMARY KEY,
    box_name VARCHAR(10) NOT NULL,
    size ENUM('S','M','L') NOT NULL,
    location VARCHAR(50) NOT NULL,
    price_per_day DECIMAL(10,2) NOT NULL,
    status VARCHAR(10) NOT NULL,
    description VARCHAR(255)
);

#  opraveny cas, nastaveny na default
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reservations (
    reservation_id INT AUTO_INCREMENT PRIMARY KEY,
    users_user_id INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    reservation_date DATETIME NOT NULL,
    status ENUM('pending', 'confirmed','cancelled','completed') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (users_user_id) REFERENCES users(user_id)
);

-- NOVÁ tabuľka pre M:N vzťah medzi rezerváciami a boxmi
CREATE TABLE reservation_boxes (
    reservation_id INT NOT NULL,
    box_id INT NOT NULL,
    PRIMARY KEY (reservation_id, box_id),
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id)
        ON DELETE CASCADE,
    FOREIGN KEY (box_id) REFERENCES boxes(box_id)
        ON DELETE CASCADE
);

CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATETIME NOT NULL,
    payment_method ENUM('transfer', 'card') NOT NULL,
    payment_status ENUM('pending','paid','cancelled') NOT NULL,
    FOREIGN KEY (reservation_id) REFERENCES reservations(reservation_id)
);

CREATE TABLE  messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(30),
    email VARCHAR(50),
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);