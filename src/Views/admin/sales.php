<div class="min-h-screen grid grid-cols-1 lg:grid-cols-[256px_1fr]">
  <?php require __DIR__ . '/partials/sidebar.php'; ?>

  <main class="p-6 space-y-6 relative">
    <div class="flex items-center justify-between flex-wrap gap-2">
      <div>
        <h1 class="text-2xl font-semibold">Sales</h1>
        <p class="text-neutral-400 text-sm mt-1">
          Showing paid orders <?= (isset($tab) && $tab === 'preparing') ? '(Not delivered / Preparing)' : '' ?>
        </p>
      </div>

      <!-- 🔍 Search + Date Filter -->
      <form method="get" class="flex flex-wrap gap-2 items-center">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab ?? '') ?>">

        <input type="text" name="q"
               value="<?= htmlspecialchars($search ?? '') ?>"
               placeholder="Search by TX ID, buyer, or phone"
               class="rounded-md bg-neutral-900 border border-neutral-800 px-3 py-2 text-sm text-white w-48 sm:w-60">

        <input type="date" name="from"
               value="<?= htmlspecialchars($_GET['from'] ?? '') ?>"
               class="rounded-md bg-neutral-900 border border-neutral-800 px-3 py-2 text-sm text-white">
        <input type="date" name="to"
               value="<?= htmlspecialchars($_GET['to'] ?? '') ?>"
               class="rounded-md bg-neutral-900 border border-neutral-800 px-3 py-2 text-sm text-white">

        <button class="rounded-md bg-blue-600 hover:bg-blue-500 text-white px-3 py-2 text-sm">Apply</button>

        <?php if (!empty($search) || !empty($_GET['from']) || !empty($_GET['to'])): ?>
          <a href="/admin/sales" class="rounded-md bg-neutral-800 hover:bg-neutral-700 text-white px-3 py-2 text-sm">Reset</a>
        <?php endif; ?>
      </form>
    </div>

    <!-- 📊 Sales Table -->
    <div class="overflow-x-auto rounded-xl border border-neutral-900 bg-neutral-900/40">
      <table class="w-full min-w-[1100px] text-sm">
        <thead class="bg-neutral-900/60 border-b border-neutral-900">
          <tr>
            <th class="px-4 py-3 text-left">Txn</th>
            <th class="px-4 py-3 text-left">Buyer</th>
            <th class="px-4 py-3 text-left">Product / Key</th>
            <th class="px-4 py-3 text-right">Total</th>
            <th class="px-4 py-3 text-left">Delivery</th>
            <th class="px-4 py-3 text-left">Proof / Detail</th>
            <th class="px-4 py-3 text-left">Date</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-neutral-900">
          <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $o): ?>
              <?php if (empty($o['items'])) continue; ?>
              <?php foreach ($o['items'] as $it): ?>
                <tr class="hover:bg-neutral-900/40">
                  <!-- Txn -->
                  <td class="px-4 py-3 align-top"><?= htmlspecialchars($o['transaction_id'] ?? '-') ?></td>

                  <!-- Buyer -->
                  <td class="px-4 py-3 align-top">
                    <div class="font-medium"><?= htmlspecialchars($o['buyer_name'] ?? 'Guest') ?></div>
                    <div class="text-xs text-neutral-400">
                      <?= htmlspecialchars($o['buyer_phone'] ?? '') ?>
                      <?php if (!empty($o['buyer_phone']) && !empty($o['buyer_telegram'])): ?> · <?php endif; ?>
                      <?= htmlspecialchars($o['buyer_telegram'] ?? '') ?>
                    </div>
                  </td>

                  <!-- Product -->
                  <td class="px-4 py-3 align-top">
                    <div class="font-medium"><?= htmlspecialchars($it['product_name_current'] ?? '') ?></div>
                    <div class="text-xs text-neutral-400">
                      Key: <?= htmlspecialchars($it['license_key'] ?? '-') ?>
                    </div>
                  </td>

                  <!-- Total -->
                  <td class="px-4 py-3 align-top text-right">
                    $<?= number_format((float)($o['total_amount'] ?? 0), 2) ?>
                  </td>

                  <!-- Delivery -->
                  <td class="px-4 py-3 align-top">
                    <?php $dstat = $it['delivery_status'] ?? 'preparing'; $isPreparing = ($dstat === 'preparing'); ?>
                    <form method="post" action="/admin/order-items/<?= (int)($it['item_id'] ?? $it['id'] ?? 0) ?>/status">
                      <select name="status"
                              class="rounded-md px-2 py-1 text-sm border transition
                                     <?= $isPreparing
                                        ? 'bg-amber-900/60 border-amber-600 text-amber-100'
                                        : 'bg-emerald-900/50 border-emerald-700 text-emerald-100' ?>"
                              onchange="this.form.submit()">
                        <option class="bg-amber-900 text-amber-100" value="preparing" <?= $isPreparing ? 'selected' : '' ?>>Preparing</option>
                        <option class="bg-emerald-900 text-emerald-100" value="completed" <?= !$isPreparing ? 'selected' : '' ?>>Completed</option>
                      </select>
                    </form>
                  </td>

                  <!-- Proof / Detail -->
                  <td class="px-4 py-3 align-top">
                    <button type="button"
                            class="rounded-md bg-neutral-800 hover:bg-neutral-700 px-2 py-1 text-xs"
                            onclick="openSaleDetail(<?= (int)$o['id'] ?>)">
                      View Detail
                    </button>
                  </td>

                  <!-- Date -->
                  <td class="px-4 py-3 align-top text-xs"><?= htmlspecialchars($o['created_at'] ?? '-') ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="7" class="px-4 py-6 text-center text-neutral-400">No sales found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if (($pages ?? 1) > 1): ?>
      <?php
        $currPage = (int)($page ?? 1);
        $totalPages = (int)($pages ?? 1);
        $query = $_GET ?? [];
        unset($query['page']);
        $base = '/admin/sales';
        $qs = http_build_query($query);
        $baseQs = $base . ($qs ? ('?' . htmlspecialchars($qs)) : '');
        $sep = $qs ? '&' : '?';

        $window = 2; // how many pages around current
        $start = max(1, $currPage - $window);
        $end = min($totalPages, $currPage + $window);
      ?>
      <div class="flex items-center justify-between mt-4">
        <div class="text-xs text-neutral-400">
          Page <?= $currPage ?> of <?= $totalPages ?> · <?= (int)($total ?? 0) ?> orders
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

    <!-- Modal overlay -->
    <div id="saleDetailModal"
         class="hidden fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4">
      <div class="relative w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-2xl border border-neutral-800 bg-neutral-950 p-6">
        <button onclick="closeSaleDetail()"
                class="absolute right-3 top-3 text-neutral-400 hover:text-white text-xl">✖</button>
        <div id="saleDetailContent" class="text-sm text-neutral-200">Loading...</div>
      </div>
    </div>
  </main>
</div>

<script>
async function openSaleDetail(orderId) {
  const modal = document.getElementById('saleDetailModal');
  const content = document.getElementById('saleDetailContent');
  modal.classList.remove('hidden');
  content.innerHTML = '<div class="text-center py-6 text-neutral-400">Loading order details...</div>';
  try {
    const res = await fetch(`/admin/sales/detail/${orderId}`, { headers: { 'X-Requested-With': 'fetch' } });
    const html = await res.text();
    content.innerHTML = html;
  } catch (err) {
    content.innerHTML = '<div class="text-center py-6 text-red-400">Failed to load details.</div>';
  }
}

function closeSaleDetail() {
  document.getElementById('saleDetailModal').classList.add('hidden');
}

// Handle proof upload inside modal (AJAX)
document.addEventListener('submit', async (e) => {
  const form = e.target;
  if (!form.matches('[data-upload-proof]')) return;
  e.preventDefault();

  const btn = form.querySelector('button[type="submit"]');
  if (btn) { btn.disabled = true; btn.textContent = 'Uploading...'; }

  try {
    const res = await fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      headers: { 'X-Requested-With': 'fetch' }
    });
    const json = await res.json();
    if (json.ok && json.base64) {
      const wrap = form.closest('[data-item-block]');
      const img = wrap?.querySelector('[data-proof-img]');
      const dl = wrap?.querySelector('[data-proof-download]');
      if (img) img.src = json.base64;
      if (dl) dl.href = json.base64;
    } else {
      alert(json.error || 'Upload failed');
    }
  } catch (err) {
    alert('Upload failed');
  } finally {
    if (btn) { btn.disabled = false; btn.textContent = 'Upload / Replace Proof'; }
  }
});
</script>
