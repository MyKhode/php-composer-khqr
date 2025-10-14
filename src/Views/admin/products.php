<div class="min-h-screen grid grid-cols-1 lg:grid-cols-[256px_1fr]">
  <?php require __DIR__.'/partials/sidebar.php'; ?>

  <main class="p-6 space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-3">
      <div>
        <h1 class="text-2xl font-semibold">Products & Keys</h1>
        <p class="text-neutral-400 text-sm mt-1">Manage products and license key inventory.</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <form method="get" class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
          <input name="q" value="<?= htmlspecialchars($q ?? '') ?>"
                class="w-full sm:w-40 md:w-56 rounded-md bg-neutral-900 border border-neutral-800 px-3 py-2 text-sm text-white"
                placeholder="Search name/category">
          <button class="rounded-md bg-neutral-800 hover:bg-neutral-700 text-white px-3 py-2 text-sm whitespace-nowrap">
            Search
          </button>
        </form>
        <a href="/admin/products/new"
          class="rounded-md bg-blue-600 hover:bg-blue-500 text-white px-4 py-2 text-sm font-medium whitespace-nowrap">
          Add
        </a>
      </div>

    </div>

    <!-- Mobile horizontal scroll -->
    <div class="overflow-x-auto rounded-xl border border-neutral-900 bg-neutral-900/40">
      <table class="w-full min-w-[1080px] text-sm">
        <thead class="bg-neutral-900/60 border-b border-neutral-900">
          <tr>
            <th class="px-4 py-3 text-left text-neutral-300">ID</th>
            <th class="px-4 py-3 text-left text-neutral-300">Image</th>
            <th class="px-4 py-3 text-left text-neutral-300">Name</th>
            <th class="px-4 py-3 text-left text-neutral-300">Category</th>
            <th class="px-4 py-3 text-left text-neutral-300">Delivery</th>
            <th class="px-4 py-3 text-right text-neutral-300">Price</th>
            <th class="px-4 py-3 text-left text-neutral-300">Keys (in stock)</th>
            <th class="px-4 py-3 text-left text-neutral-300">Status</th>
            <th class="px-4 py-3 text-left text-neutral-300">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-neutral-900">
          <?php foreach ($products as $p): ?>
            <?php
              $db  = App\Db::pdo();
              $cnt = $db->query('SELECT COUNT(*) c FROM license_keys WHERE product_id='.(int)$p['id'].' AND is_sold=0')->fetch()['c'];
              $sold = !empty($p['is_sold_out']);
            ?>
            <tr class="hover:bg-neutral-900/30 <?= $sold ? 'opacity-70' : '' ?>">
              <td class="px-4 py-3"><?= (int)$p['id'] ?></td>
              <td class="px-4 py-3">
                <img class="h-14 w-20 object-cover rounded-md border border-neutral-800"
                     src="<?= htmlspecialchars($p['photo_base64'] ?? '') ?>" alt="thumb" />
              </td>
              <td class="px-4 py-3 font-medium"><?= htmlspecialchars($p['name'] ?? '') ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($p['category'] ?? '') ?></td>
              <td class="px-4 py-3">
                <?php $dt = strtolower(trim($p['delivery_type'] ?? '')); ?>
                <?php if ($dt === 'instant'): ?>
                  <span class="px-2 py-1 text-xs rounded-md bg-emerald-700 text-white">Instant</span>
                <?php elseif ($dt === 'waiting'): ?>
                  <span class="px-2 py-1 text-xs rounded-md bg-amber-700 text-white">Waiting</span>
                <?php else: ?>
                  <span class="px-2 py-1 text-xs rounded-md bg-neutral-700 text-white">—</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-right">$<?= number_format((float)($p['price'] ?? 0),2) ?></td>
              <td class="px-4 py-3"><?= (int)$cnt ?></td>
              <td class="px-4 py-3">
                <?php if ($sold): ?>
                  <span class="px-2 py-1 text-xs rounded-md bg-green-700 text-white">✅ Sold</span>
                <?php else: ?>
                  <span class="px-2 py-1 text-xs rounded-md bg-yellow-700 text-white">Available</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <a class="rounded-md bg-neutral-800 hover:bg-neutral-700 px-3 py-1.5" href="/admin/products/<?= (int)$p['id'] ?>/edit">Edit</a>
                  <form method="post" action="/admin/products/<?= (int)$p['id'] ?>/delete" onsubmit="return confirm('Hide (mark sold) this product?')">
                    <button class="rounded-md bg-red-600 hover:bg-red-500 px-3 py-1.5">Delete</button>
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($products)): ?>
            <tr><td class="px-4 py-6 text-neutral-400" colspan="8">No products yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
