<?php
/** Admin layout — end. $useEditor / $useIconPicker: load optional helper script. */
$loadHelpers = (!empty($useEditor) || !empty($useIconPicker)) ? true : false;
?>
    </main>
    <footer class="admin-foot">پنل مدیریت دکتر فاطمه حیدری</footer>
  </div>
</div>
<script src="../assets/admin/admin.js?v=<?= filemtime(dirname(__DIR__, 2) . '/assets/admin/admin.js') ?>"></script>
<?php if ($loadHelpers): ?>
<script src="../assets/admin/admin-editor.js?v=<?= filemtime(dirname(__DIR__, 2) . '/assets/admin/admin-editor.js') ?>"></script>
<?php endif; ?>
</body>
</html>
