<?php
/**
 * classes.php
 * Contains the Menu and Order OOP classes.
 * All database operations use MySQLi prepared statements for security.
 */

require_once 'config.php';

// ============================================================
//  CLASS: Menu
//  Responsibility: Read operations on the `menu` table.
// ============================================================
class Menu {
    private mysqli $db;

    public function __construct() {
        // Get the shared mysqli connection from the Database singleton
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Fetch all menu items (both pure and mix).
     *
     * @return array  Array of associative arrays representing each row.
     */
    public function getAll(): array {
        $result = $this->db->query("SELECT * FROM menu ORDER BY jenis, nama_kopi");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Fetch menu items filtered by type.
     *
     * @param  string $type  Either 'pure' or 'mix'.
     * @return array
     */
    public function getByType(string $type): array {
        $stmt = $this->db->prepare("SELECT * FROM menu WHERE jenis = ? ORDER BY nama_kopi");
        $stmt->bind_param('s', $type);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    /**
     * Fetch a single menu item by its ID.
     *
     * @param  int $id
     * @return array|null
     */
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM menu WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_assoc() ?: null;
    }
}


// ============================================================
//  CLASS: Order
//  Responsibility: Full CRUD on the `pesanan` table.
// ============================================================
class Order {
    private mysqli $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ----------------------------------------------------------
    //  CREATE: Insert a new order.
    //  Price is looked up from `menu` and total is calculated here.
    // ----------------------------------------------------------
    /**
     * @param  int    $id_menu
     * @param  string $nama_pelanggan
     * @param  string $suhu           'Panas' | 'Dingin'
     * @param  int    $jumlah
     * @return bool   True on success, false on failure.
     */
    public function create(int $id_menu, string $nama_pelanggan, string $suhu, int $jumlah): bool {
        // Step 1: Look up the unit price from the menu table
        $menuClass = new Menu();
        $menuItem  = $menuClass->getById($id_menu);

        if (!$menuItem) return false; // Guard: menu item must exist

        // Step 2: Calculate total price on the backend (cannot be tampered by client)
        $total_harga = $menuItem['harga'] * $jumlah;

        // Step 3: Insert the order using a prepared statement
        $stmt = $this->db->prepare(
            "INSERT INTO pesanan (id_menu, nama_pelanggan, suhu, jumlah, total_harga)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('issis', $id_menu, $nama_pelanggan, $suhu, $jumlah, $total_harga);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    // ----------------------------------------------------------
    //  READ: Fetch all orders joined with menu names.
    // ----------------------------------------------------------
    /**
     * Returns all orders with the coffee name included via INNER JOIN.
     *
     * @return array
     */
    public function readAll(): array {
        $sql = "SELECT p.id_pesanan, p.nama_pelanggan, m.nama_kopi, m.jenis,
                       p.suhu, p.jumlah, p.total_harga, p.waktu_pesan, p.id_menu
                FROM pesanan p
                INNER JOIN menu m ON p.id_menu = m.id
                ORDER BY p.waktu_pesan DESC";
        $result = $this->db->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // ----------------------------------------------------------
    //  READ: Fetch a single order by its ID (for pre-filling edit form).
    // ----------------------------------------------------------
    /**
     * @param  int $id_pesanan
     * @return array|null
     */
    public function readById(int $id_pesanan): ?array {
        $stmt = $this->db->prepare(
            "SELECT p.*, m.nama_kopi FROM pesanan p
             INNER JOIN menu m ON p.id_menu = m.id
             WHERE p.id_pesanan = ?"
        );
        $stmt->bind_param('i', $id_pesanan);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        return $result->fetch_assoc() ?: null;
    }

    // ----------------------------------------------------------
    //  UPDATE: Edit an existing order and recalculate total price.
    // ----------------------------------------------------------
    /**
     * @param  int    $id_pesanan
     * @param  int    $id_menu
     * @param  string $nama_pelanggan
     * @param  string $suhu
     * @param  int    $jumlah
     * @return bool
     */
    public function update(int $id_pesanan, int $id_menu, string $nama_pelanggan, string $suhu, int $jumlah): bool {
        // Recalculate price based on possibly-changed menu selection
        $menuClass   = new Menu();
        $menuItem    = $menuClass->getById($id_menu);
        if (!$menuItem) return false;

        $total_harga = $menuItem['harga'] * $jumlah;

        $stmt = $this->db->prepare(
            "UPDATE pesanan
             SET id_menu = ?, nama_pelanggan = ?, suhu = ?, jumlah = ?, total_harga = ?
             WHERE id_pesanan = ?"
        );
        $stmt->bind_param('issiii', $id_menu, $nama_pelanggan, $suhu, $jumlah, $total_harga, $id_pesanan);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    // ----------------------------------------------------------
    //  DELETE: Remove an order by ID.
    // ----------------------------------------------------------
    /**
     * @param  int $id_pesanan
     * @return bool
     */
    public function delete(int $id_pesanan): bool {
        $stmt = $this->db->prepare("DELETE FROM pesanan WHERE id_pesanan = ?");
        $stmt->bind_param('i', $id_pesanan);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    // ----------------------------------------------------------
    //  ANALYTICS: Aggregate data for the dashboard widgets.
    // ----------------------------------------------------------
    /**
     * Returns total revenue (sum of total_harga) and total cups sold (sum of jumlah).
     *
     * @return array ['total_revenue' => int, 'total_cups' => int]
     */
    public function getAnalytics(): array {
        $result = $this->db->query(
            "SELECT COALESCE(SUM(total_harga), 0) AS total_revenue,
                    COALESCE(SUM(jumlah), 0)      AS total_cups
             FROM pesanan"
        );
        return $result ? $result->fetch_assoc() : ['total_revenue' => 0, 'total_cups' => 0];
    }
}