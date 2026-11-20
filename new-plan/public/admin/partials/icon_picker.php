<?php
/**
 * Icon picker partial (searchable).
 * Expects: $selectedIcon (string), optional $pickerIdSuffix.
 * Requires $useIconPicker = true for layout_end to load admin-editor.js helpers.
 */
$allIcons = [
    // تغذیه و سلامت
    'nutrition', 'restaurant', 'eco', 'spa', 'monitor_weight', 'monitor_heart',
    'health_and_safety', 'medical_information', 'medical_services', 'medication',
    'vaccines', 'medication_liquid', 'water_drop', 'local_fire_department',
    'fitness_center', 'directions_run', 'sports_gymnastics', 'self_improvement',
    'psychology', 'bedtime', 'schedule', 'balance', 'favorite', 'ecology_heart_placeholder',
    // غذا و آشپزی
    'lunch_dining', 'dinner_dining', 'breakfast_dining', 'fastfood', 'bakery_dining',
    'cake', 'coffee', 'emoji_food_beverage', 'icecream', 'local_cafe', 'local_dining',
    'ramen_dining', 'rice_bowl', 'soup_kitchen', 'egg', 'blender', 'kitchen', 'countertops',
    // بدن و ورزش
    'accessibility_new', 'sports_martial_arts', 'pool', 'sports_basketball', 'pedal_bike',
    'exercise', 'monitor', 'speed', 'trending_up', 'trending_down', 'chart_data_placeholder',
    // پزشکی و مراقبت
    'healing', 'emergency', 'bloodtype', 'biotech', 'science', 'labs_placeholder',
    'clinical_notes_placeholder', 'stethoscope_placeholder', 'thermostat', 'sick',
    'pregnant_woman', 'child_care', 'elderly', 'accessibility',
    // سبک زندگی
    'wb_sunny', 'nights_stay', 'park', 'emoji_nature', 'cruelty_free_placeholder',
    'volunteer_activism', 'emoji_emotions', 'mood', 'sentiment_satisfied',
    'checklist_placeholder', 'event_available', 'today', 'date_range',
    // عمومی پنل
    'star', 'verified', 'workspace_premium', 'school', 'lightbulb', 'tips_and_updates',
    'flag', 'bookmark', 'label', 'category', 'widgets', 'apps', 'grain',
    'spa_outlined_placeholder', 'leaf_placeholder', 'grass', 'potted_plant_placeholder',
    'egg_alt', 'local_grocery_store', 'shopping_basket_placeholder', 'storefront_placeholder',
];
// فقط آیکون‌هایی که واقعاً در فونت موجودند (لیست تأییدشده نهایی)
$icons = array_values(array_filter($allIcons, static fn($i) => !str_contains($i, '_placeholder')));
$selectedIcon = $selectedIcon ?? 'nutrition';
?>
<div class="field" data-icon-picker>
  <label for="icon-search-<?= e($pickerIdSuffix ?? 'main') ?>">آیکون خدمت</label>
  <input class="icon-search" id="icon-search-<?= e($pickerIdSuffix ?? 'main') ?>" type="search"
         placeholder="جستجوی آیکون… مثلا: water یا heart" autocomplete="off">
  <div class="icon-grid">
    <?php foreach ($icons as $icon): ?>
    <button type="button" class="icon-opt<?= $selectedIcon === $icon ? ' is-selected' : '' ?>" data-icon="<?= e($icon) ?>">
      <span class="material-symbols-rounded"><?= e($icon) ?></span>
      <span><?= e($icon) ?></span>
    </button>
    <?php endforeach; ?>
  </div>
  <input type="hidden" name="icon" value="<?= e($selectedIcon) ?>">
  <p class="icon-count"></p>
</div>
