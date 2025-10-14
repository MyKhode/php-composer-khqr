<?php require __DIR__.'/partials/header.php'; ?>

<section class="max-w-7xl mx-auto px-4 py-10">
  <div class="flex flex-wrap items-end justify-between gap-4">
    <div>
      <h1 class="text-2xl sm:text-3xl font-semibold">License Key Store</h1>
      <p class="text-neutral-400 mt-1">Instant digital keys. Fast & secure payment via KHQR.</p>
    </div>

    <!-- Filter + Search -->
    <form method="get" class="flex flex-wrap gap-2">
      <select name="category"
              class="rounded-md bg-neutral-900 border border-neutral-800 px-3 py-2 text-sm text-white">
        <option value="">All Categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= htmlspecialchars($c) ?>" <?= ($c === $category) ? 'selected' : '' ?>>
            <?= htmlspecialchars($c) ?>
          </option>
        <?php endforeach; ?>
      </select>

      <input type="text" name="q"
             placeholder="Search product..."
             value="<?= htmlspecialchars($search ?? '') ?>"
             class="rounded-md bg-neutral-900 border border-neutral-800 px-3 py-2 text-sm text-white w-48 sm:w-60">

      <button class="rounded-md bg-blue-600 hover:bg-blue-500 text-white px-3 py-2 text-sm">Search</button>
    </form>
  </div>

  <div class="grid gap-6 mt-8 sm:grid-cols-2 lg:grid-cols-3">
    <?php foreach ($products as $p): ?>
      <a href="/product/<?= (int)$p['id'] ?>"
         class="group relative rounded-2xl border border-neutral-900 bg-neutral-900/40 hover:bg-neutral-900/70 transition">
        <?php if ((int)$p['count_same_name'] > 1): ?>
          <span class="absolute top-2 right-2 bg-neutral-800 text-neutral-300 text-xs rounded-full px-2 py-0.5">
            <?= (int)$p['count_same_name'] ?> variants
          </span>
        <?php endif; ?>

        <div class="aspect-square overflow-hidden rounded-t-2xl">
          <img class="size-full object-cover group-hover:scale-105 transition"
               src="<?= htmlspecialchars($p['photo_base64'] ?? '') ?>"
               alt="<?= htmlspecialchars($p['name'] ?? '') ?>" />
        </div>

        <div class="p-4">
          <div class="text-sm text-blue-300/80"><?= htmlspecialchars($p['category'] ?? '') ?></div>
          <h3 class="mt-1 font-semibold"><?= htmlspecialchars($p['name'] ?? '') ?></h3>
          <div class="mt-3 flex items-center justify-between">
            <span class="text-lg font-bold">
              $<?= number_format((float)($p['price'] ?? 0), 2) ?>
              <?php if ($p['max_price'] > $p['price']): ?>
                – $<?= number_format((float)$p['max_price'], 2) ?>
              <?php endif; ?>
            </span>
            <span class="text-neutral-400 text-xs">View details →</span>
          </div>
        </div>
      </a>
    <?php endforeach; ?>

    <?php if (empty($products)): ?>
      <div class="col-span-full text-neutral-400">No products found.</div>
    <?php endif; ?>
  </div>

  <?php if (($pages ?? 1) > 1): ?>
    <?php
      $currPage = (int)($page ?? 1);
      $totalPages = (int)($pages ?? 1);
      $query = $_GET ?? [];
      unset($query['page']);
      $base = '/';
      $qs = http_build_query($query);
      $baseQs = $base . ($qs ? ('?' . htmlspecialchars($qs)) : '');
      $sep = $qs ? '&' : '?';
      $window = 2;
      $start = max(1, $currPage - $window);
      $end = min($totalPages, $currPage + $window);
    ?>
    <div class="mt-8 flex items-center justify-between">
      <div class="text-xs text-neutral-400">
        Page <?= $currPage ?> of <?= $totalPages ?> · <?= (int)($total ?? 0) ?> products
      </div>
      <div class="flex items-center gap-1">
        <?php if ($currPage > 1): ?>
          <a class="px-3 py-1.5 rounded-md border border-neutral-800 bg-neutral-900 hover:bg-neutral-800 text-sm"
             href="<?= $baseQs . $sep . 'page=' . ($currPage - 1) ?>">Prev</a>
        <?php else: ?>
          <span class="px-3 py-1.5 rounded-md border border-neutral-900 bg-neutral-950 text-sm text-neutral-600">Prev</span>
        <?php endif; ?>

        <?php if ($start > 1): ?>
          <a class="px-3 py-1.5 rounded-md border border-neutral-800 bg-neutral-900 hover:bg-neutral-800 text-sm" href="<?= $baseQs . $sep . 'page=1' ?>">1</a>
          <?php if ($start > 2): ?><span class="px-2 text-neutral-500">…</span><?php endif; ?>
        <?php endif; ?>

        <?php for ($i = $start; $i <= $end; $i++): ?>
          <?php if ($i === $currPage): ?>
            <span class="px-3 py-1.5 rounded-md border border-blue-700 bg-blue-900/40 text-blue-300 text-sm"><?= $i ?></span>
          <?php else: ?>
            <a class="px-3 py-1.5 rounded-md border border-neutral-800 bg-neutral-900 hover:bg-neutral-800 text-sm" href="<?= $baseQs . $sep . 'page=' . $i ?>"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>

        <?php if ($end < $totalPages): ?>
          <?php if ($end < $totalPages - 1): ?><span class="px-2 text-neutral-500">…</span><?php endif; ?>
          <a class="px-3 py-1.5 rounded-md border border-neutral-800 bg-neutral-900 hover:bg-neutral-800 text-sm" href="<?= $baseQs . $sep . 'page=' . $totalPages ?>"><?= $totalPages ?></a>
        <?php endif; ?>

        <?php if ($currPage < $totalPages): ?>
          <a class="px-3 py-1.5 rounded-md border border-neutral-800 bg-neutral-900 hover:bg-neutral-800 text-sm"
             href="<?= $baseQs . $sep . 'page=' . ($currPage + 1) ?>">Next</a>
        <?php else: ?>
          <span class="px-3 py-1.5 rounded-md border border-neutral-900 bg-neutral-950 text-sm text-neutral-600">Next</span>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__.'/partials/footer.php'; ?>
