CREATE DATABASE IF NOT EXISTS `coffeeshop`;
USE `coffeeshop`;

CREATE TABLE IF NOT EXISTS `menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kopi` varchar(100) NOT NULL,
  `jenis` enum('pure','mix') NOT NULL,
  `harga` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `menu` (`nama_kopi`, `jenis`, `harga`) VALUES
('Espresso', 'pure', 15000),
('Americano', 'pure', 18000),
('Long Black', 'pure', 18000),
('V60 Manual Brew', 'pure', 22000),
('Cold Brew', 'pure', 20000),
('Caffe Latte', 'mix', 24000),
('Cappuccino', 'mix', 24000),
('Caramel Macchiato', 'mix', 28000),
('Kopi Susu Gula Aren', 'mix', 20000),
('Mocha Latte', 'mix', 26000);

CREATE TABLE IF NOT EXISTS `pesanan` (
  `id_pesanan` int(11) NOT NULL AUTO_INCREMENT,
  `id_menu` int(11) NOT NULL,
  `nama_pelanggan` varchar(100) NOT NULL,
  `suhu` enum('Panas','Dingin') NOT NULL,
  `jumlah` int(11) NOT NULL,
  `total_harga` int(11) NOT NULL,
  `waktu_pesan` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id_pesanan`),
  FOREIGN KEY (`id_menu`) REFERENCES `menu`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;