<?php
session_start();
require_once 'db.php';

// حماية الصفحة
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$message = "";
$message_type = "";

// 1. عملية الإضافة والتعديل
if (isset($_POST['save_product'])) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = trim($_POST['name']);
    $quantity = intval($_POST['quantity']);
    $price = floatval($_POST['price']);
    
    // معالجة رفع الصورة
    $image_name = "default.jpg";
    if (isset($_POST['current_image'])) {
        $image_name = $_POST['current_image'];
    }
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            $image_name = time() . '_' . uniqid() . '.' . $ext;
            if (!is_dir('uploads')) {
                mkdir('uploads', 0777, true);
            }
            move_uploaded_file($_FILES['image']['tmp_transform'] ?? $_FILES['image']['tmp_name'], 'uploads/' . $image_name);
        }
    }

    if (!empty($name)) {
        if ($id == 0) {
            // إضافة منتج جديد
            $stmt = $conn->prepare("INSERT INTO products (name, quantity, price, image) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $quantity, $price, $image_name]);
            $message = "تم إضافة المنتج بنجاح الذهب!";
            $message_type = "success";
        } else {
            // تعديل المنتج
            $stmt = $conn->prepare("UPDATE products SET name = ?, quantity = ?, price = ?, image = ? WHERE id = ?");
            $stmt->execute([$name, $quantity, $price, $image_name, $id]);
            $message = "تم تحديث المنتج بنجاح!";
            $message_type = "success";
        }
    } else {
        $message = "اسم المنتج مطلوب!";
        $message_type = "danger";
    }
}

// 2. عملية الحذف
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    // جلب اسم الصورة لحذفها من السيرفر (اختياري)
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->execute([$delete_id]);
    $p = $stmt->fetch();
    if ($p && $p['image'] != 'default.jpg' && file_exists('uploads/' . $p['image'])) {
        unlink('uploads/' . $p['image']);
    }
    
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$delete_id]);
    $message = "تم حذف المنتج بنجاح.";
    $message_type = "success";
}

// 3. جلب بيانات منتج معين للتعديل (AJAX / Form fill)
$edit_product = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_product = $stmt->fetch();
}

// 4. محرك البحث والاستعلام العام
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE ? ORDER BY id DESC");
    $stmt->execute(["%$search%"]);
} else {
    $stmt = $conn->query("SELECT * FROM products ORDER BY id DESC");
}
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المتجر الفاخر</title>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #d4af37;
            --primary-hover: #b8922f;
            --bg-dark: #0a0c10;
            --card-dark: #12161f;
            --table-row-alt: #171c27;
            --text-light: #f3f4f6;
            --text-muted: #9ca3af;
            --accent-blue: #3b82f6;
            --accent-red: #ef4444;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Cairo', sans-serif; }
        body { background-color: var(--bg-dark); color: var(--text-light); min-height: 100vh; padding-bottom: 3rem; }
        
        /* Navbar */
        nav { background: var(--card-dark); border-bottom: 1px solid rgba(212, 175, 55, 0.15); padding: 1rem 5%; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
        .nav-brand { display: flex; align-items: center; gap: 10px; }
        .nav-brand i { color: var(--primary); font-size: 1.8rem; text-shadow: 0 0 10px rgba(212,175,55,0.3); }
        .nav-brand h1 { font-size: 1.4rem; font-weight: 700; background: linear-gradient(45deg, #fff, var(--primary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .nav-user { display: flex; align-items: center; gap: 15px; }
        .logout-btn { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); color: #f87171; padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; font-size: 0.9rem; transition: 0.3s; }
        .logout-btn:hover { background: var(--accent-red); color: #fff; }

        /* Container */
        .container { max-width: 1200px; margin: 2rem auto; padding: 0 1rem; display: grid; grid-template-columns: 1fr; gap: 2rem; }
        @media(min-width: 992px) { .container { grid-template-columns: 350px 1fr; } }

        /* General Card Design */
        .luxury-card { background: var(--card-dark); border: 1px solid rgba(212, 175, 55, 0.1); border-radius: 12px; padding: 1.5rem; box-shadow: 0 10px 30px rgba(0,0,0,0.2); height: fit-content; }
        .card-title { font-size: 1.2rem; margin-bottom: 1.5rem; border-bottom: 2px solid rgba(212,175,55,0.2); padding-bottom: 0.5rem; display: flex; align-items: center; gap: 8px; color: var(--primary); }

        /* Alerts */
        .alert { padding: 0.75rem 1rem; border-radius: 8px; margin-bottom: 1rem; font-size: 0.9rem; border-right: 4px solid transparent; }
        .alert-success { background: rgba(16, 185, 129, 0.1); color: #34d399; border-right-color: #10b981; }
        .alert-danger { background: rgba(239, 68, 68, 0.1); color: #f87171; border-right-color: #ef4444; }

        /* Form Controls */
        .form-group { margin-bottom: 1.2rem; }
        .form-group label { display: block; margin-bottom: 0.4rem; color: var(--text-muted); font-size: 0.85rem; }
        .form-control { width: 100%; background: #0a0c10; border: 1px solid #232a36; padding: 0.75rem; border-radius: 8px; color: #fff; font-size: 0.95rem; transition: 0.3s; }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 5px rgba(212,175,55,0.2); }
        .btn-luxury { width: 100%; background: linear-gradient(45deg, var(--primary-hover), var(--primary)); border: none; padding: 0.75rem; color: #000; font-weight: 700; border-radius: 8px; cursor: pointer; transition: 0.3s; box-shadow: 0 4px 15px rgba(212,175,55,0.15); }
        .btn-luxury:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(212,175,55,0.3); }
        .btn-cancel { display: block; text-align: center; margin-top: 0.5rem; color: var(--text-muted); font-size: 0.85rem; text-decoration: none; }

        /* Search Area */
        .search-wrapper { display: flex; gap: 10px; margin-bottom: 1.5rem; }
        .search-wrapper .form-control { width: auto; flex-grow: 1; }
        .btn-search { background: #1c2331; border: 1px solid rgba(212,175,55,0.2); color: var(--primary); padding: 0.75rem 1.2rem; border-radius: 8px; cursor: pointer; transition: 0.3s; }
        .btn-search:hover { background: var(--primary); color: #000; }

        /* Luxury Table View */
        .table-responsive { overflow-x: auto; width: 100%; }
        .luxury-table { width: 100%; border-collapse: separate; border-spacing: 0 8px; margin-top: -8px; }
        .luxury-table th { padding: 1rem; color: var(--text-muted); font-weight: 600; text-align: right; font-size: 0.9rem; letter-spacing: 0.5px; text-transform: uppercase; background: #0f131a; }
        .luxury-table th:first-child { border-top-right-radius: 8px; border-bottom-right-radius: 8px; }
        .luxury-table th:last-child { border-top-left-radius: 8px; border-bottom-left-radius: 8px; }
        
        .luxury-table tr.product-row { background: var(--card-dark); transition: transform 0.2s, box-shadow 0.2s; }
        .luxury-table tr.product-row:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.3); background: #161b26; }
        .luxury-table td { padding: 1rem; vertical-align: middle; border-top: 1px solid rgba(212,175,55,0.05); border-bottom: 1px solid rgba(212,175,55,0.05); }
        .luxury-table td:first-child { border-right: 1px solid rgba(212,175,55,0.05); border-top-right-radius: 8px; border-bottom-right-radius: 8px; }
        .luxury-table td:last-child { border-left: 1px solid rgba(212,175,55,0.05); border-top-left-radius: 8px; border-bottom-left-radius: 8px; }

        .product-img { width: 55px; height: 55px; object-fit: cover; border-radius: 8px; border: 1px solid rgba(212,175,55,0.2); background: #000; }
        .badge-qty { background: rgba(59,130,246,0.15); color: #60a5fa; padding: 0.25rem 0.6rem; border-radius: 6px; font-size: 0.85rem; font-weight: 600; }
        .badge-qty.zero { background: rgba(239,68,68,0.15); color: #f87171; }
        .price-text { color: var(--primary); font-weight: 700; font-size: 1.05rem; }
        
        /* Action Buttons */
        .actions-cell { display: flex; gap: 8px; }
        .btn-action { width: 34px; height: 34px; display: flex; align-items: center; justify-content: center; border-radius: 6px; border: none; text-decoration: none; cursor: pointer; transition: 0.2s; font-size: 0.9rem; }
        .btn-edit { background: rgba(212,175,55,0.15); color: var(--primary); }
        .btn-edit:hover { background: var(--primary); color: #000; }
        .btn-delete { background: rgba(239,68,68,0.15); color: #f87171; }
        .btn-delete:hover { background: var(--accent-red); color: #fff; }
        
        .no-data { text-align: center !important; color: var(--text-muted); padding: 3rem !important; font-style: italic; }
    </style>
</head>
<body>

    <!-- الهيدر والناف بار -->
    <nav>
        <div class="nav-brand">
            <i class="fa-solid fa-gem"></i>
            <h1>الملك للمنتجات الفاخرة</h1>
        </div>
        <div class="nav-user">
            <span>مرحباً، <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
            <a href="logout.php" class="logout-btn"><i class="fa-solid fa-power-off"></i> تسجيل الخروج</a>
        </div>
    </nav>

    <!-- المحتوى الرئيسي -->
    <div class="container">
        
        <!-- قسم إضافة / تعديل المنتج -->
        <div class="luxury-card">
            <h2 class="card-title">
                <i class="fa-solid <?php echo $edit_product ? 'fa-pen-to-square' : 'fa-plus-circle'; ?>"></i>
                <?php echo $edit_product ? 'تعديل هذا المنتج الفاخر' : 'إضافة منتج ملكي جديد'; ?>
            </h2>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $edit_product ? $edit_product['id'] : 0; ?>">
                <input type="hidden" name="current_image" value="<?php echo $edit_product ? $edit_product['image'] : 'default.jpg'; ?>">

                <div class="form-group">
                    <label>اسم المنتج الفخم</label>
                    <input type="text" name="name" class="form-control" required placeholder="مثال: ساعة رولكس ذهبية" value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label>الكمية المتوفرة</label>
                    <input type="number" name="quantity" class="form-control" required min="0" placeholder="0" value="<?php echo $edit_product ? $edit_product['quantity'] : ''; ?>">
                </div>

                <div class="form-group">
                    <label>السعر (دولار)</label>
                    <input type="number" name="price" class="form-control" required step="0.01" min="0" placeholder="0.00" value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>">
                </div>

                <div class="form-group">
                    <label>صورة المنتج</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                    <?php if ($edit_product && $edit_product['image'] != 'default.jpg'): ?>
                        <small style="color: var(--primary); display:block; margin-top:5px;">هناك صورة مرفوعة بالفعل.</small>
                    <?php endif; ?>
                </div>

                <button type="submit" name="save_product" class="btn-luxury">
                    <?php echo $edit_product ? 'تحديث التعديلات الملكية' : 'حفظ المنتج الفاخر'; ?>
                </button>
                
                <?php if ($edit_product): ?>
                    <a href="index.php" class="btn-cancel">إلغاء التعديل</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- قسم عرض الجدول والبحث -->
        <div class="luxury-card">
            <!-- خانة البحث الفاخرة -->
            <form method="GET" action="index.php" class="search-wrapper">
                <input type="text" name="search" class="form-control" placeholder="ابحث عن منتج بالاسم الفخم..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn-search"><i class="fa-solid fa-magnifying-glass"></i></button>
                <?php if (!empty($search)): ?>
                    <a href="index.php" class="btn-search" style="display:flex; align-items:center; background:#ef4444; color:#fff; border:none;"><i class="fa-solid fa-xmark"></i></a>
                <?php endif; ?>
            </form>

            <!-- الجدول -->
            <div class="table-responsive">
                <table class="luxury-table">
                    <thead>
                        <tr>
                            <th>الصورة</th>
                            <th>اسم المنتج</th>
                            <th>الكمية</th>
                            <th>السعر</th>
                            <th>التحكم</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($products) > 0): ?>
                            <?php foreach ($products as $row): ?>
                                <tr class="product-row">
                                    <td>
                                        <?php 
                                        $img_src = 'uploads/' . $row['image'];
                                        if ($row['image'] == 'default.jpg' || !file_exists($img_src)) {
                                            // صورة افتراضية فخمة عبر CSS إن لم تكن متوفرة
                                            echo '<div class="product-img" style="display:flex; align-items:center; justify-content:center; background:#1c2331;"><i class="fa-solid fa-box-open" style="color:var(--primary)"></i></div>';
                                        } else {
                                            echo '<img src="'.$img_src.'" class="product-img" alt="product">';
                                        }
                                        ?>
                                    </td>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td>
                                        <span class="badge-qty <?php echo $row['quantity'] == 0 ? 'zero' : ''; ?>">
                                            <?php echo $row['quantity']; ?> وحدة
                                        </span>
                                    </td>
                                    <td><span class="price-text">$<?php echo number_format($row['price'], 2); ?></span></td>
                                    <td>
                                        <div class="actions-cell">
                                            <a href="index.php?edit=<?php echo $row['id']; ?>" class="btn-action btn-edit" title="تعديل"><i class="fa-solid fa-pen"></i></a>
                                            <a href="index.php?delete=<?php echo $row['id']; ?>" class="btn-action btn-delete" title="حذف" onclick="return confirm('هل أنت متأكد من رغبتك في حذف هذا المنتج الفاخر نهائياً؟')"><i class="fa-solid fa-trash"></i></a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="no-data">
                                    <i class="fa-solid fa-receipt" style="font-size:2rem; display:block; margin-bottom:10px;"></i>
                                    لا توجد منتجات فاخرة مطابقة حالياً.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>