-- Database: lost_and_found_db

CREATE DATABASE IF NOT EXISTS lost_and_found_db;
USE lost_and_found_db;

-- Table users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    contact VARCHAR(255),
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table reports
CREATE TABLE IF NOT EXISTS reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('lost', 'found') NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255) NOT NULL,
    event_date DATE NOT NULL,
    image_url VARCHAR(255),
    status ENUM('open', 'resolved') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Optional: Insert initial admin data (password: admin123)
-- Hash: $2y$10$koo8hP2FegUHE17.WejGy.dMNu.2Zz4dsm84M9Udf9cpdbUpb7Xmm
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@lostfound.com', '$2y$10$koo8hP2FegUHE17.WejGy.dMNu.2Zz4dsm84M9Udf9cpdbUpb7Xmm', 'admin');
