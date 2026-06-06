<?php
/**
 * index.php
 * Main single-page application.
 * Acts as the controller: handles CRUD POST/GET actions, then renders the full UI.
 */

require_once 'config.php';
require_once 'classes.php';

$orderHandler = new Order();
$menuHandler  = new Menu();
$message      = '';
$messageType  = '';

// ---------- CREATE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $id_menu        = (int)($_POST['id_menu'] ?? 0);
    $nama_pelanggan = trim($_POST['nama_pelanggan'] ?? '');
    $suhu           = trim($_POST['suhu'] ?? '');
    $jumlah         = (int)($_POST['jumlah'] ?? 1);
    if ($id_menu && $nama_pelanggan && in_array($suhu, ['Panas', 'Dingin']) && $jumlah > 0) {
        if ($orderHandler->create($id_menu, $nama_pelanggan, $suhu, $jumlah)) {
            $message = '✓ Order berhasil ditambahkan!'; $messageType = 'success';
        } else { $message = '✗ Gagal menambah order.'; $messageType = 'error'; }
    } else { $message = '✗ Semua field wajib diisi.'; $messageType = 'error'; }
}

// ---------- UPDATE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id_pesanan     = (int)($_POST['id_pesanan'] ?? 0);
    $id_menu        = (int)($_POST['id_menu'] ?? 0);
    $nama_pelanggan = trim($_POST['nama_pelanggan'] ?? '');
    $suhu           = trim($_POST['suhu'] ?? '');
    $jumlah         = (int)($_POST['jumlah'] ?? 1);
    if ($id_pesanan && $id_menu && $nama_pelanggan && in_array($suhu, ['Panas', 'Dingin']) && $jumlah > 0) {
        if ($orderHandler->update($id_pesanan, $id_menu, $nama_pelanggan, $suhu, $jumlah)) {
            $message = '✓ Order berhasil diperbarui!'; $messageType = 'success';
        } else { $message = '✗ Gagal memperbarui order.'; $messageType = 'error'; }
    } else { $message = '✗ Data tidak valid.'; $messageType = 'error'; }
}

// ---------- DELETE ----------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id_pesanan = (int)($_GET['id'] ?? 0);
    if ($id_pesanan) {
        if ($orderHandler->delete($id_pesanan)) {
            $message = '✓ Order berhasil dihapus.'; $messageType = 'success';
        } else { $message = '✗ Gagal menghapus order.'; $messageType = 'error'; }
    }
}

$allOrders  = $orderHandler->readAll();
$allMenus   = $menuHandler->getAll();
$pureMenus  = $menuHandler->getByType('pure');
$mixMenus   = $menuHandler->getByType('mix');
$analytics  = $orderHandler->getAnalytics();

$editOrder = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editOrder = $orderHandler->readById((int)$_GET['id']);
}

$bestSellers = ['Espresso', 'Caramel Macchiato'];

function formatRupiah(int $amount): string {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kopi Nusantara — Coffee Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brew':   { 50:'#fdf8f0',100:'#faefd9',200:'#f4d9a8',300:'#ecbc6e',400:'#e39d3a',500:'#d4821d',600:'#b96515',700:'#984f14',800:'#7c4016',900:'#673616' },
                        'roast':  { 50:'#faf5f0',100:'#f2e4d4',200:'#e5c9a8',300:'#d4a67a',400:'#c07f4d',500:'#a86030',600:'#8f4b24',700:'#763b1e',800:'#5f2f1a',900:'#4a2416' },
                        'cream':  '#fdf6ec',
                        'parchment': '#f5e9d3',
                    },
                    fontFamily: {
                        'display': ['"Playfair Display"','Georgia','serif'],
                        'body':    ['"DM Sans"','system-ui','sans-serif'],
                    },
                    boxShadow: {
                        'warm': '0 4px 24px 0 rgba(161,90,30,0.15)',
                        'card': '0 2px 16px 0 rgba(100,50,10,0.10)',
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,500&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'DM Sans', sans-serif; background-color: #fdf6ec; }
        h1,h2,h3,.font-display { font-family: 'Playfair Display', serif; }

        /* FIXED: grain overlay sangat tipis agar tidak menggelapkan halaman */
        body::before {
            content: '';
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
            opacity: 0.03;
        }

        .nav-glass {
            background: rgba(253,246,236,0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(212,130,29,0.15);
        }
        .menu-card { transition: transform 0.25s ease, box-shadow 0.25s ease; }
        .menu-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(161,90,30,0.18); }
        .tab-btn { transition: all 0.2s ease; }
        .tab-btn.active {
            background: linear-gradient(135deg, #b96515, #d4821d);
            color: #fdf6ec;
            box-shadow: 0 4px 12px rgba(185,101,21,0.4);
        }
        .modal-backdrop {
            background: rgba(61,28,8,0.55);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
        }
        .modal-box { transition: transform 0.3s cubic-bezier(0.34,1.56,0.64,1), opacity 0.3s ease; }
        .modal-hidden .modal-box { transform: scale(0.85) translateY(20px); opacity: 0; pointer-events: none; }
        .modal-visible .modal-box { transform: scale(1) translateY(0); opacity: 1; }
        .modal-hidden { display: none !important; }
        .analytics-card-revenue { background: linear-gradient(135deg,#7c4016 0%,#b96515 60%,#e39d3a 100%); }
        .analytics-card-cups    { background: linear-gradient(135deg,#4a2416 0%,#8f4b24 60%,#c07f4d 100%); }
        @keyframes badgePulse {
            0%,100% { box-shadow: 0 0 0 0 rgba(212,130,29,0.5); }
            50%      { box-shadow: 0 0 0 6px rgba(212,130,29,0); }
        }
        .badge-pulse { animation: badgePulse 2s infinite; }
        .order-row { transition: background 0.15s ease; }
        .order-row:hover { background-color: #faefd9; }
        #toast { transition: transform 0.4s cubic-bezier(0.34,1.56,0.64,1), opacity 0.4s ease; }
        input:focus, select:focus { outline: none; box-shadow: 0 0 0 3px rgba(212,130,29,0.25); border-color: #d4821d; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #f5e9d3; }
        ::-webkit-scrollbar-thumb { background: #d4a67a; border-radius: 3px; }
    </style>
</head>
<body class="min-h-screen">

<!-- NAVBAR -->
<nav class="nav-glass sticky top-0 z-50 px-6 py-3">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-brew-700 to-brew-500 flex items-center justify-center shadow-warm">
                <span class="text-xl">☕</span>
            </div>
            <div>
                <h1 class="font-display text-brew-900 text-lg leading-tight">Kopi Nusantara</h1>
                <p class="text-brew-600 text-xs">Coffee Shop Management</p>
            </div>
        </div>
        <div class="hidden md:flex items-center gap-6 text-sm font-medium text-brew-700">
            <a href="#menu" class="hover:text-brew-500 transition-colors">Menu</a>
            <a href="#orders" class="hover:text-brew-500 transition-colors">Pesanan</a>
            <button onclick="openOrderModal()" class="bg-gradient-to-r from-brew-700 to-brew-500 text-white px-5 py-2 rounded-full text-sm font-semibold shadow-warm hover:scale-105 transition-all">
                + Order Baru
            </button>
        </div>
    </div>
</nav>

<!-- TOAST NOTIFICATION -->
<?php if ($message): ?>
<div id="toast" class="fixed top-6 right-6 z-[100] flex items-center gap-3 px-5 py-3 rounded-2xl shadow-xl text-sm font-semibold max-w-sm
    <?= $messageType === 'success' ? 'bg-emerald-700 text-white' : 'bg-red-700 text-white' ?>">
    <span class="text-lg"><?= $messageType === 'success' ? '✓' : '✗' ?></span>
    <?= htmlspecialchars($message) ?>
    <button onclick="this.parentElement.remove()" class="ml-auto opacity-70 hover:opacity-100 text-lg">×</button>
</div>
<script>setTimeout(() => { const t=document.getElementById('toast'); if(t){t.style.opacity='0';setTimeout(()=>t.remove(),400);} }, 4000);</script>
<?php endif; ?>

<!-- HERO -->
<header class="py-14 px-6 text-center relative overflow-hidden">
    <div class="absolute -top-20 -right-20 w-80 h-80 rounded-full bg-brew-200 opacity-20 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-16 -left-16 w-64 h-64 rounded-full bg-roast-200 opacity-15 blur-2xl pointer-events-none"></div>
    <div class="relative max-w-2xl mx-auto">
        <p class="text-brew-500 text-xs font-bold tracking-widest uppercase mb-3">— Selamat Datang —</p>
        <h2 class="font-display text-5xl md:text-6xl text-brew-900 mb-4 leading-tight">
            Tempat Kopi<br><em class="text-brew-600">Terbaik</em> Anda
        </h2>
        <p class="text-brew-700 text-base leading-relaxed">
            Nikmati racikan kopi pilihan dari biji arabika terbaik Nusantara.
        </p>
    </div>
</header>

<!-- ANALYTICS -->
<section class="max-w-7xl mx-auto px-6 mb-12">
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="analytics-card-revenue col-span-2 rounded-2xl p-6 text-white shadow-warm relative overflow-hidden">
            <div class="absolute top-0 right-0 w-40 h-40 rounded-full bg-white opacity-5 -translate-y-12 translate-x-12"></div>
            <p class="text-amber-200 text-xs font-bold tracking-widest uppercase mb-1">Total Pendapatan</p>
            <p class="font-display text-3xl font-bold mb-1"><?= formatRupiah((int)$analytics['total_revenue']) ?></p>
            <p class="text-amber-300 text-sm">Dari seluruh transaksi aktif</p>
            <div class="absolute bottom-4 right-5 text-4xl opacity-20">💰</div>
        </div>
        <div class="analytics-card-cups col-span-2 rounded-2xl p-6 text-white shadow-warm relative overflow-hidden">
            <div class="absolute top-0 right-0 w-40 h-40 rounded-full bg-white opacity-5 -translate-y-12 translate-x-12"></div>
            <p class="text-orange-200 text-xs font-bold tracking-widest uppercase mb-1">Total Cangkir Terjual</p>
            <p class="font-display text-3xl font-bold mb-1"><?= number_format((int)$analytics['total_cups']) ?> <span class="text-xl">Cangkir</span></p>
            <p class="text-orange-300 text-sm">Kopi yang telah dipesan</p>
            <div class="absolute bottom-4 right-5 text-4xl opacity-20">☕</div>
        </div>
        <div class="bg-white border border-parchment rounded-2xl p-5 shadow-card">
            <p class="text-brew-500 text-xs font-bold tracking-widest uppercase mb-2">Varian Menu</p>
            <p class="font-display text-3xl text-brew-900"><?= count($allMenus) ?></p>
            <p class="text-brew-600 text-xs mt-1">Item tersedia</p>
        </div>
        <div class="bg-white border border-parchment rounded-2xl p-5 shadow-card">
            <p class="text-brew-500 text-xs font-bold tracking-widest uppercase mb-2">Pesanan Aktif</p>
            <p class="font-display text-3xl text-brew-900"><?= count($allOrders) ?></p>
            <p class="text-brew-600 text-xs mt-1">Transaksi tercatat</p>
        </div>
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 shadow-card">
            <p class="text-amber-700 text-xs font-bold tracking-widest uppercase mb-2">Pure Coffee</p>
            <p class="font-display text-3xl text-brew-900"><?= count($pureMenus) ?></p>
            <p class="text-amber-600 text-xs mt-1">Kopi murni</p>
        </div>
        <div class="bg-orange-50 border border-orange-200 rounded-2xl p-5 shadow-card">
            <p class="text-orange-700 text-xs font-bold tracking-widest uppercase mb-2">Mix Coffee</p>
            <p class="font-display text-3xl text-brew-900"><?= count($mixMenus) ?></p>
            <p class="text-orange-600 text-xs mt-1">Kopi campuran</p>
        </div>
    </div>
</section>

<!-- MENU SECTION -->
<section id="menu" class="max-w-7xl mx-auto px-6 mb-16">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-4">
        <div>
            <h2 class="font-display text-3xl text-brew-900">Menu Kopi</h2>
            <p class="text-brew-600 text-sm mt-1">Pilih kopi favorit dan pesan sekarang</p>
        </div>
        <div class="flex gap-2 bg-parchment rounded-full p-1 border border-amber-200">
            <button onclick="switchTab('pure')" id="tab-pure" class="tab-btn active text-white text-sm font-semibold px-5 py-2 rounded-full">
                ☕ Pure Coffee
            </button>
            <button onclick="switchTab('mix')" id="tab-mix" class="tab-btn text-brew-700 text-sm font-semibold px-5 py-2 rounded-full hover:bg-white">
                🥛 Mix Coffee
            </button>
        </div>
    </div>

    <!-- Pure -->
    <div id="grid-pure" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <?php foreach ($pureMenus as $item): $isBest = in_array($item['nama_kopi'], $bestSellers); ?>
        <div class="menu-card bg-white border border-parchment rounded-2xl overflow-hidden shadow-card relative flex flex-col">
            <?php if ($isBest): ?>
            <span class="badge-pulse absolute top-3 right-3 bg-amber-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full z-10">★ Best</span>
            <?php endif; ?>
            <div class="h-24 bg-gradient-to-br from-brew-900 via-roast-700 to-brew-500 flex items-center justify-center">
                <span class="text-4xl">☕</span>
            </div>
            <div class="p-3 flex flex-col flex-1">
                <p class="text-[10px] font-bold text-amber-600 uppercase tracking-wider mb-1">Pure</p>
                <h3 class="font-display text-brew-900 text-sm font-semibold leading-snug mb-2"><?= htmlspecialchars($item['nama_kopi']) ?></h3>
                <p class="text-brew-600 text-xs font-semibold mt-auto mb-2"><?= formatRupiah($item['harga']) ?></p>
                <button onclick="openOrderModal(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['nama_kopi'])) ?>')"
                    class="w-full bg-gradient-to-r from-brew-700 to-brew-500 text-white text-xs font-bold py-1.5 rounded-xl hover:scale-105 transition-all">
                    Order Now
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Mix -->
    <div id="grid-mix" class="hidden grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <?php foreach ($mixMenus as $item): $isBest = in_array($item['nama_kopi'], $bestSellers); ?>
        <div class="menu-card bg-white border border-parchment rounded-2xl overflow-hidden shadow-card relative flex flex-col">
            <?php if ($isBest): ?>
            <span class="badge-pulse absolute top-3 right-3 bg-amber-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full z-10">★ Best</span>
            <?php endif; ?>
            <div class="h-24 bg-gradient-to-br from-roast-800 via-roast-500 to-amber-400 flex items-center justify-center">
                <span class="text-4xl">🥛</span>
            </div>
            <div class="p-3 flex flex-col flex-1">
                <p class="text-[10px] font-bold text-orange-500 uppercase tracking-wider mb-1">Mix</p>
                <h3 class="font-display text-brew-900 text-sm font-semibold leading-snug mb-2"><?= htmlspecialchars($item['nama_kopi']) ?></h3>
                <p class="text-brew-600 text-xs font-semibold mt-auto mb-2"><?= formatRupiah($item['harga']) ?></p>
                <button onclick="openOrderModal(<?= $item['id'] ?>, '<?= htmlspecialchars(addslashes($item['nama_kopi'])) ?>')"
                    class="w-full bg-gradient-to-r from-roast-700 to-roast-500 text-white text-xs font-bold py-1.5 rounded-xl hover:scale-105 transition-all">
                    Order Now
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ORDER TABLE -->
<section id="orders" class="max-w-7xl mx-auto px-6 mb-20">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-4">
        <div>
            <h2 class="font-display text-3xl text-brew-900">Manajemen Pesanan</h2>
            <p class="text-brew-600 text-sm mt-1"><?= count($allOrders) ?> pesanan aktif</p>
        </div>
        <div class="relative">
            <input type="text" id="search-orders" placeholder="Cari nama pelanggan…" onkeyup="filterOrders()"
                class="pl-9 pr-4 py-2 rounded-xl border border-amber-200 bg-white text-sm text-brew-800 w-56 shadow-sm">
            <span class="absolute left-3 top-2.5 text-brew-400 text-sm">🔍</span>
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-card border border-parchment overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gradient-to-r from-brew-900 to-brew-700 text-white text-xs uppercase tracking-wider">
                        <th class="px-4 py-3 text-left">#</th>
                        <th class="px-4 py-3 text-left">Pelanggan</th>
                        <th class="px-4 py-3 text-left">Kopi</th>
                        <th class="px-4 py-3 text-left">Jenis</th>
                        <th class="px-4 py-3 text-left">Suhu</th>
                        <th class="px-4 py-3 text-left">Jml</th>
                        <th class="px-4 py-3 text-left">Total</th>
                        <th class="px-4 py-3 text-left">Waktu</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody id="order-tbody">
                    <?php if (empty($allOrders)): ?>
                    <tr><td colspan="9" class="text-center py-16 text-brew-400">
                        <div class="flex flex-col items-center gap-2">
                            <span class="text-5xl">☕</span>
                            <p class="font-display text-lg text-brew-600">Belum ada pesanan</p>
                            <p class="text-sm">Mulai dengan klik "Order Baru"</p>
                        </div>
                    </td></tr>
                    <?php else: ?>
                    <?php foreach ($allOrders as $order): ?>
                    <tr class="order-row border-t border-parchment" data-name="<?= strtolower(htmlspecialchars($order['nama_pelanggan'])) ?>">
                        <td class="px-4 py-3 text-brew-500 font-mono text-xs"><?= $order['id_pesanan'] ?></td>
                        <td class="px-4 py-3 font-semibold text-brew-900"><?= htmlspecialchars($order['nama_pelanggan']) ?></td>
                        <td class="px-4 py-3 text-brew-700"><?= htmlspecialchars($order['nama_kopi']) ?></td>
                        <td class="px-4 py-3">
                            <?php if ($order['jenis'] === 'pure'): ?>
                                <span class="bg-amber-100 text-amber-700 text-[10px] font-bold px-2 py-0.5 rounded-full">PURE</span>
                            <?php else: ?>
                                <span class="bg-orange-100 text-orange-700 text-[10px] font-bold px-2 py-0.5 rounded-full">MIX</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3">
                            <?php if ($order['suhu'] === 'Panas'): ?>
                                <span class="bg-red-100 text-red-600 text-[10px] font-bold px-2 py-1 rounded-full">🔥 Panas</span>
                            <?php else: ?>
                                <span class="bg-blue-100 text-blue-600 text-[10px] font-bold px-2 py-1 rounded-full">❄️ Dingin</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-center font-semibold text-brew-800"><?= $order['jumlah'] ?></td>
                        <td class="px-4 py-3 font-semibold text-brew-700"><?= formatRupiah($order['total_harga']) ?></td>
                        <td class="px-4 py-3 text-brew-500 text-xs whitespace-nowrap"><?= date('d/m H:i', strtotime($order['waktu_pesan'])) ?></td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="openEditModal(<?= $order['id_pesanan'] ?>, <?= $order['id_menu'] ?>, '<?= htmlspecialchars(addslashes($order['nama_pelanggan'])) ?>', '<?= $order['suhu'] ?>', <?= $order['jumlah'] ?>)"
                                    class="bg-amber-100 text-amber-700 hover:bg-amber-200 text-xs font-bold px-3 py-1.5 rounded-lg transition-colors">
                                    ✏️ Edit
                                </button>
                                <button onclick="confirmDelete(<?= $order['id_pesanan'] ?>, '<?= htmlspecialchars(addslashes($order['nama_pelanggan'])) ?>')"
                                    class="bg-red-100 text-red-600 hover:bg-red-200 text-xs font-bold px-3 py-1.5 rounded-lg transition-colors">
                                    🗑️ Hapus
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<footer class="border-t border-amber-200 py-6 text-center text-brew-500 text-xs">
    <p>☕ <strong class="font-display text-brew-700">Kopi Nusantara</strong> — PHP OOP + MySQLi + Tailwind CSS</p>
</footer>

<!-- MODAL CREATE -->
<div id="modal-create" class="fixed inset-0 z-[200] flex items-center justify-center modal-backdrop modal-hidden">
    <div class="modal-box bg-white rounded-3xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-brew-900 to-brew-700 px-6 py-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-display text-white text-xl">Order Kopi Baru</h3>
                    <p id="modal-coffee-name" class="text-amber-300 text-sm mt-0.5">Pilih kopi dari menu</p>
                </div>
                <button onclick="closeOrderModal()" class="text-amber-300 hover:text-white text-3xl leading-none">×</button>
            </div>
        </div>
        <form method="POST" action="#orders" class="p-6 space-y-4">
            <input type="hidden" name="action" value="create">
            <div>
                <label class="block text-brew-800 text-xs font-bold uppercase tracking-wider mb-1.5">Pilih Kopi</label>
                <select name="id_menu" id="create-id-menu" required class="w-full border border-amber-200 rounded-xl px-4 py-2.5 bg-white text-brew-800 text-sm">
                    <option value="">-- Pilih Menu --</option>
                    <optgroup label="☕ Pure Coffee">
                        <?php foreach ($pureMenus as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama_kopi']) ?> — <?= formatRupiah($m['harga']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="🥛 Mix Coffee">
                        <?php foreach ($mixMenus as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama_kopi']) ?> — <?= formatRupiah($m['harga']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
            <div>
                <label class="block text-brew-800 text-xs font-bold uppercase tracking-wider mb-1.5">Nama Pelanggan</label>
                <input type="text" name="nama_pelanggan" id="create-nama" required placeholder="Masukkan nama pelanggan…"
                    class="w-full border border-amber-200 rounded-xl px-4 py-2.5 bg-white text-brew-800 text-sm">
            </div>
            <div>
                <label class="block text-brew-800 text-xs font-bold uppercase tracking-wider mb-2">Suhu</label>
                <div class="flex gap-3">
                    <label class="flex-1 flex items-center gap-2 border-2 border-red-200 rounded-xl px-4 py-2.5 cursor-pointer hover:border-red-400 transition-colors">
                        <input type="radio" name="suhu" value="Panas" class="accent-red-500">
                        <span class="text-sm font-medium text-red-700">🔥 Panas</span>
                    </label>
                    <label class="flex-1 flex items-center gap-2 border-2 border-blue-200 rounded-xl px-4 py-2.5 cursor-pointer hover:border-blue-400 transition-colors">
                        <input type="radio" name="suhu" value="Dingin" class="accent-blue-500">
                        <span class="text-sm font-medium text-blue-700">❄️ Dingin</span>
                    </label>
                </div>
            </div>
            <div>
                <label class="block text-brew-800 text-xs font-bold uppercase tracking-wider mb-1.5">Jumlah Cangkir</label>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="changeQty('create-qty',-1)" class="w-9 h-9 rounded-full bg-amber-100 text-brew-700 font-bold text-lg hover:bg-amber-200 transition-colors flex items-center justify-center">−</button>
                    <input type="number" name="jumlah" id="create-qty" value="1" min="1" max="20" required class="flex-1 text-center border border-amber-200 rounded-xl py-2.5 bg-white text-brew-800 font-semibold text-sm">
                    <button type="button" onclick="changeQty('create-qty',1)" class="w-9 h-9 rounded-full bg-amber-100 text-brew-700 font-bold text-lg hover:bg-amber-200 transition-colors flex items-center justify-center">+</button>
                </div>
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-brew-800 to-brew-500 text-white font-bold py-3 rounded-2xl hover:scale-[1.02] transition-all text-sm">
                ☕ Konfirmasi Order
            </button>
        </form>
    </div>
</div>

<!-- MODAL EDIT -->
<div id="modal-edit" class="fixed inset-0 z-[200] flex items-center justify-center modal-backdrop modal-hidden">
    <div class="modal-box bg-white rounded-3xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-roast-800 to-roast-600 px-6 py-5">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-display text-white text-xl">Edit Pesanan</h3>
                    <p class="text-orange-300 text-sm mt-0.5">Perbarui detail pesanan</p>
                </div>
                <button onclick="closeEditModal()" class="text-orange-300 hover:text-white text-3xl leading-none">×</button>
            </div>
        </div>
        <form method="POST" action="#orders" class="p-6 space-y-4">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id_pesanan" id="edit-id-pesanan">
            <div>
                <label class="block text-brew-800 text-xs font-bold uppercase tracking-wider mb-1.5">Pilih Kopi</label>
                <select name="id_menu" id="edit-id-menu" required class="w-full border border-amber-200 rounded-xl px-4 py-2.5 bg-white text-brew-800 text-sm">
                    <optgroup label="☕ Pure Coffee">
                        <?php foreach ($pureMenus as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama_kopi']) ?> — <?= formatRupiah($m['harga']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                    <optgroup label="🥛 Mix Coffee">
                        <?php foreach ($mixMenus as $m): ?>
                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama_kopi']) ?> — <?= formatRupiah($m['harga']) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                </select>
            </div>
            <div>
                <label class="block text-brew-800 text-xs font-bold uppercase tracking-wider mb-1.5">Nama Pelanggan</label>
                <input type="text" name="nama_pelanggan" id="edit-nama" required class="w-full border border-amber-200 rounded-xl px-4 py-2.5 bg-white text-brew-800 text-sm">
            </div>
            <div>
                <label class="block text-brew-800 text-xs font-bold uppercase tracking-wider mb-2">Suhu</label>
                <div class="flex gap-3">
                    <label class="flex-1 flex items-center gap-2 border-2 border-red-200 rounded-xl px-4 py-2.5 cursor-pointer hover:border-red-400 transition-colors">
                        <input type="radio" name="suhu" id="edit-suhu-panas" value="Panas" class="accent-red-500">
                        <span class="text-sm font-medium text-red-700">🔥 Panas</span>
                    </label>
                    <label class="flex-1 flex items-center gap-2 border-2 border-blue-200 rounded-xl px-4 py-2.5 cursor-pointer hover:border-blue-400 transition-colors">
                        <input type="radio" name="suhu" id="edit-suhu-dingin" value="Dingin" class="accent-blue-500">
                        <span class="text-sm font-medium text-blue-700">❄️ Dingin</span>
                    </label>
                </div>
            </div>
            <div>
                <label class="block text-brew-800 text-xs font-bold uppercase tracking-wider mb-1.5">Jumlah Cangkir</label>
                <div class="flex items-center gap-3">
                    <button type="button" onclick="changeQty('edit-qty',-1)" class="w-9 h-9 rounded-full bg-amber-100 text-brew-700 font-bold text-lg hover:bg-amber-200 transition-colors flex items-center justify-center">−</button>
                    <input type="number" name="jumlah" id="edit-qty" value="1" min="1" max="20" required class="flex-1 text-center border border-amber-200 rounded-xl py-2.5 bg-white text-brew-800 font-semibold text-sm">
                    <button type="button" onclick="changeQty('edit-qty',1)" class="w-9 h-9 rounded-full bg-amber-100 text-brew-700 font-bold text-lg hover:bg-amber-200 transition-colors flex items-center justify-center">+</button>
                </div>
            </div>
            <button type="submit" class="w-full bg-gradient-to-r from-roast-800 to-roast-500 text-white font-bold py-3 rounded-2xl hover:scale-[1.02] transition-all text-sm">
                💾 Simpan Perubahan
            </button>
        </form>
    </div>
</div>

<script>
    function switchTab(type) {
        document.getElementById('grid-pure').classList.toggle('hidden', type !== 'pure');
        document.getElementById('grid-mix').classList.toggle('hidden', type !== 'mix');
        document.getElementById('tab-pure').classList.toggle('active', type === 'pure');
        document.getElementById('tab-mix').classList.toggle('active', type === 'mix');
    }

    function openOrderModal(menuId=null, menuName=null) {
        const m = document.getElementById('modal-create');
        m.classList.remove('modal-hidden'); m.classList.add('modal-visible');
        document.body.style.overflow = 'hidden';
        if (menuId) { document.getElementById('create-id-menu').value = menuId; document.getElementById('modal-coffee-name').textContent = menuName; }
        else { document.getElementById('create-id-menu').value = ''; document.getElementById('modal-coffee-name').textContent = 'Pilih kopi dari menu'; }
        document.getElementById('create-qty').value = 1;
    }
    function closeOrderModal() {
        const m = document.getElementById('modal-create');
        m.classList.add('modal-hidden'); m.classList.remove('modal-visible');
        document.body.style.overflow = '';
    }

    function openEditModal(id, menuId, nama, suhu, jumlah) {
        const m = document.getElementById('modal-edit');
        m.classList.remove('modal-hidden'); m.classList.add('modal-visible');
        document.body.style.overflow = 'hidden';
        document.getElementById('edit-id-pesanan').value = id;
        document.getElementById('edit-id-menu').value    = menuId;
        document.getElementById('edit-nama').value       = nama;
        document.getElementById('edit-qty').value        = jumlah;
        document.getElementById('edit-suhu-panas').checked  = (suhu === 'Panas');
        document.getElementById('edit-suhu-dingin').checked = (suhu === 'Dingin');
    }
    function closeEditModal() {
        const m = document.getElementById('modal-edit');
        m.classList.add('modal-hidden'); m.classList.remove('modal-visible');
        document.body.style.overflow = '';
    }

    function confirmDelete(id, nama) {
        if (confirm(`Hapus pesanan dari "${nama}"?\nTindakan ini tidak dapat dibatalkan.`)) {
            window.location.href = `?action=delete&id=${id}`;
        }
    }

    function changeQty(inputId, delta) {
        const input = document.getElementById(inputId);
        input.value = Math.max(1, Math.min(20, (parseInt(input.value)||1) + delta));
    }

    function filterOrders() {
        const q = document.getElementById('search-orders').value.toLowerCase();
        document.querySelectorAll('#order-tbody tr[data-name]').forEach(row => {
            row.style.display = row.getAttribute('data-name').includes(q) ? '' : 'none';
        });
    }

    document.addEventListener('keydown', e => { if(e.key==='Escape'){closeOrderModal();closeEditModal();} });

    // Tutup modal jika klik backdrop
    document.getElementById('modal-create').addEventListener('click', function(e){ if(e.target===this) closeOrderModal(); });
    document.getElementById('modal-edit').addEventListener('click',   function(e){ if(e.target===this) closeEditModal(); });

    <?php if ($editOrder): ?>
    window.addEventListener('load', () => {
        openEditModal(<?= $editOrder['id_pesanan'] ?>, <?= $editOrder['id_menu'] ?>,
            '<?= htmlspecialchars(addslashes($editOrder['nama_pelanggan'])) ?>',
            '<?= $editOrder['suhu'] ?>', <?= $editOrder['jumlah'] ?>);
    });
    <?php endif; ?>
</script>
</body>
</html>