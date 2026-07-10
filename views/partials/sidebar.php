<?php
require_once APP_ROOT . 'models/MenuModel.php';

$isLoggedIn = isset($_SESSION['user_id']);
$flatMenuItems = [];

if ($isLoggedIn) {
    $menuModel = new MenuModel();
    $flatMenuItems = $menuModel->listar(999, 0, false);

    $userId = $_SESSION['user_id'] ?? null;
    if ($userId) {
        $userModel = new SystemUserModel();
        $user = $userModel->obtenerPorId($userId);
        if ($user && ($user['tipo'] ?? '') === 'vendedor') {
            $hiddenIds = [
                '4ee12e53-38a1-8337-d4f5-17f796e011e5',
                '6aa34a75-50c3-0559-f6f7-39f9b8f233f7',
                '3dd01d42-2790-7226-c3e4-06f685df00d4',
                'b308d04e-032a-4fd1-97a8-0e15cf6d850e',
                '5ff23f64-49b2-9448-e5e6-28f8a7f122f6'
            ];
            $flatMenuItems = array_values(array_filter($flatMenuItems, function ($item) use ($hiddenIds) {
                return !in_array($item['menu_id'], $hiddenIds);
            }));
        }
    }
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
    <style>
        #layout-menu::-webkit-scrollbar {
            display: none
        }
    </style>
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