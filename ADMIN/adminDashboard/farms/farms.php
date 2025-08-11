<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin • Farms</title>

  <!-- shared admin styles -->
  <link rel="stylesheet" href="../../dashboard.css"/>
  <!-- page-specific styles -->
  <link rel="stylesheet" href="farms.css"/>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
</head>
<body>
<div class="admin-wrapper">
  <?php include $_SERVER['DOCUMENT_ROOT'] . '/ProjectFolder/admin/partials/sidebar.php'; ?>

  <main class="admin-main">
    <div class="page-head">
      <h1><i class="fa-solid fa-seedling"></i> Farms</h1>

      <!-- SEARCH (POST) -->
      <form class="search-bar" method="post" action="farms.php">
        <select name="by">
          <option value="farm_name" <?= $searchBy === 'farm_name' ? 'selected' : '' ?>>Farm Name</option>
          <option value="location" <?= $searchBy === 'location' ? 'selected' : '' ?>>Location</option>
        </select>
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search…"/>
        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
        <?php if ($q !== ''): ?>
          <button class="btn btn-muted" type="submit" name="clear" value="1">Clear</button>
        <?php endif; ?>
        <input type="hidden" name="page" value="1">
      </form>
    </div>

    <?php if (!empty($flash)): ?>
      <div class="flash <?= $flash['type'] === 'ok' ? 'ok' : 'err' ?>">
        <?= htmlspecialchars($flash['text']) ?>
      </div>
    <?php endif; ?>

    <div class="table-wrap">
      <table class="farms-table">
        <thead>
          <tr>
            <th>Farm Name</th>
            <th>Location</th>
            <th>Size</th>
            <th>Years Experience</th>
            <th>Owner</th>
            <th style="width:120px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="6">No farms found.</td></tr>
          <?php else: foreach ($rows as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['farm_name']) ?></td>
              <td><?= htmlspecialchars($r['location']) ?></td>
              <td><?= htmlspecialchars($r['farm_size']) ?></td>
              <td><?= htmlspecialchars($r['years_experience']) ?></td>
              <td><?= htmlspecialchars($r['owner_name']) ?></td>
              <td>
                <div class="actions">
                  <!-- DELETE FORM -->
                  <form method="post" action="farms.php" onsubmit="return confirm('Delete this farm?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="farm_id" value="<?= (int)$r['farmID'] ?>">
                    <input type="hidden" name="csrf" value="<?= $csrf ?>">
                    <!-- preserve current filters after delete -->
                    <input type="hidden" name="by" value="<?= htmlspecialchars($searchBy) ?>">
                    <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
                    <input type="hidden" name="page" value="<?= (int)$page ?>">
                    <button class="btn btn-danger" type="submit" title="Delete">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- PAGINATION (POST buttons) -->
    <?php if ($totalPages > 1): ?>
      <form class="pagination" method="post" action="farms.php">
        <input type="hidden" name="by" value="<?= htmlspecialchars($searchBy) ?>">
        <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
          <button class="<?= $p === $page ? 'active' : '' ?>" type="submit" name="page" value="<?= $p ?>">
            <?= $p ?>
          </button>
        <?php endfor; ?>
      </form>
    <?php endif; ?>

  </main>
</div>
</body>
</html>
