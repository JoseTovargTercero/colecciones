<?php
require_once APP_ROOT . 'models/MenuModel.php';

$isLoggedIn = isset($_SESSION['user_id']);
$flatMenuItems = [];

if ($isLoggedIn) {
    $menuModel = new MenuModel();
    $flatMenuItems = $menuModel->listar(999, 0, false);
}

function buildMenuTree(array $flatItems): array
{
    $groupedByCategory = [];
    foreach ($flatItems as $item) {
        $category = $item['categoria'] ?? 'General';
        $groupedByCategory[$category][] = $item;
    }

    $menuConfig = [];
    foreach ($groupedByCategory as $categoryName => $items) {
        $menuConfig[] = ['is_title' => true, 'title' => $categoryName];
        foreach ($items as $item) {
            $menuConfig[] = [
                'title' => $item['nombre'],
                'icon' => $item['icono'] ?: 'bx bx-circle',
                'url' => BASE_URL . $item['url'],
            ];
        }
    }
    return $menuConfig;
}

$menuConfig = buildMenuTree($flatMenuItems);

$currentUrl = strtok($_SERVER['REQUEST_URI'], '?');
?>

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme" style="overflow-y:auto;overflow-x:visible;scrollbar-width:none;-ms-overflow-style:none">
    <style>#layout-menu::-webkit-scrollbar{display:none}</style>
    <div class="app-brand demo">
        <a href="<?= BASE_URL ?>" class="app-brand-link">
            <img src="<?= BASE_URL ?>/public/assets/images/logo-horizontal.png" alt="logo" height="90px">
        </a>
        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto d-block d-xl-none">
            <i class="bx bx-chevron-left bx-sm align-middle"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <?php foreach ($menuConfig as $item): ?>
            <?php if (isset($item['is_title']) && $item['is_title']): ?>
                <li class="menu-header small text-uppercase">
                    <span class="menu-header-text"><?= htmlspecialchars($item['title']) ?></span>
                </li>
            <?php else:
                $isActive = (strpos($currentUrl, $item['url']) !== false);
            ?>
                <li class="menu-item<?= $isActive ? ' active' : '' ?>">
                    <a href="<?= $item['url'] ?>" class="menu-link">
                        <i class="menu-icon tf-icons <?= htmlspecialchars($item['icon']) ?>"></i>
                        <div><?= htmlspecialchars($item['title']) ?></div>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</aside>