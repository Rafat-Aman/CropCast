<?php
/**
 * Fields — main page (User area)
 * Refactor:
 *  - Use the new shared sidebar partial (/Dashboard/partials/sidebar.php)
 *  - Keep existing Fields CRUD logic intact inside the "PAGE CONTENT" block
 */
session_start();
header('Content-Type: text/html; charset=utf-8');

// 1) App bootstrap (DB connection etc.)
require_once __DIR__ . '/../../main.php';  // provides $conn (mysqli)

// 2) Session guard
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo "<!doctype html><html><body><p>Unauthorized</p></body></html>";
  exit;
}

// Optional: flash messaging (use these from your existing handlers)
$flash = $flash ?? null;
$flash_class = $flash_class ?? 'ok';

?>
<!doctype html>
<html lang="en">
<head>
  <!-- ========== HEAD: meta + styles ========== -->
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Fields</title>

  <!-- Fields page styles (sidebar styles removed; light theme kept) -->
  <link rel="stylesheet" href="fields.css?v=<?php echo time(); ?>">
</head>
<body>

<?php
  /**
   * 3) Include the shared, collapsible sidebar (absolute include)
   *    - Handles its own CSS/JS and body margin shift.
   *    - Make sure the file exists at: /Dashboard/partials/sidebar.php
   */
  //include $_SERVER['DOCUMENT_ROOT'] . '/Dashboard/partials/partials.php';
  include __DIR__ . '../../partials/partials.php';


?>

<!-- ========== PAGE CONTENT WRAPPER ========== -->
<div class="container">
  <!-- 4) Page header -->
  <header class="header">
    <h1>Fields</h1>
    <p class="subtitle">Manage your fields, sizes, and details.</p>
  </header>

  <!-- 5) Flash messages (optional) -->
  <?php if (!empty($flash)): ?>
    <div class="panel">
      <strong class="<?php echo htmlspecialchars($flash_class); ?>">
        <?php echo htmlspecialchars($flash); ?>
      </strong>
    </div>
  <?php endif; ?>

  <!-- 6) PANELS: your existing forms/tables go here.
          Keep your current PHP handlers/queries; only the sidebar changed. -->

  <!-- Example: Create / Update Field panel (keep/replace with your real form) -->
  <section class="panel">
    <h2>Add / Update Field</h2>
    <form class="form" method="post" action="">
      <!--
        NOTE: Replace inputs with your real field names.
        Keep your existing POST handlers above this template.
      -->
      <div class="form-row">
        <label for="field_name">Field Name</label>
        <input id="field_name" name="field_name" type="text" placeholder="e.g., North Plot" required>
      </div>
      <div class="form-row">
        <label for="field_size">Size (acres)</label>
        <input id="field_size" name="field_size" type="number" step="0.01" placeholder="e.g., 2.50" required>
      </div>
      <div class="form-row">
        <label for="field_notes">Notes (optional)</label>
        <input id="field_notes" name="field_notes" type="text" placeholder="Irrigation schedule, soil type…">
      </div>

      <div style="display:flex; gap:8px; flex-wrap:wrap">
        <button type="submit" name="create" class="btn">Save Field</button>
        <!-- Optional destructive action -->
        <!-- <button type="submit" name="delete" class="btn btn-danger">Delete Selected</button> -->
      </div>
    </form>
    <p class="msg">Tip: You can edit existing fields below.</p>
  </section>

  <!-- Example: Your Fields list (replace with your live table/results) -->
  <section class="panel">
    <h2>Your Fields</h2>

    <?php
      /**
       * 7) DATA LIST:
       *    If you already have the SELECT query + loop, keep it.
       *    Below is a harmless, placeholder example you can remove.
       */
      $example = []; // Replace with your fetched rows
    ?>

    <?php if (empty($example)): ?>
      <div class="empty">No fields yet. Add your first field above.</div>
    <?php else: ?>
      <div class="list">
        <?php foreach ($example as $row): ?>
          <div class="card">
            <div class="card-main">
              <div><strong><?php echo htmlspecialchars($row['name']); ?></strong></div>
              <div>Size: <?php echo htmlspecialchars($row['size']); ?> acres</div>
              <div class="muted"><?php echo htmlspecialchars($row['notes'] ?? ''); ?></div>
            </div>
            <div class="card-actions">
              <form method="post" action="">
                <input type="hidden" name="field_id" value="<?php echo (int)$row['id']; ?>">
                <button class="btn" name="edit">Edit</button>
              </form>
              <form method="post" action="" onsubmit="return confirm('Delete this field?');">
                <input type="hidden" name="field_id" value="<?php echo (int)$row['id']; ?>">
                <button class="btn btn-danger" name="delete">Delete</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>
<!-- /container -->

</body>
</html>
