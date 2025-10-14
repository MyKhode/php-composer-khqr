<div class="min-h-screen grid grid-cols-1 lg:grid-cols-[256px_1fr]">
  <?php require __DIR__.'/partials/sidebar.php'; ?>

  <main class="p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-2xl font-semibold">Dashboard</h1>
        <p class="text-neutral-400 text-sm mt-1">Overview of products, inventory, and revenue.</p>
      </div>
    </div>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <div class="rounded-xl border border-neutral-900 bg-neutral-900/40 p-4">
        <div class="text-neutral-400 text-sm">Products</div>
        <div class="mt-2 text-2xl font-semibold"><?= (int)($stats['products'] ?? 0) ?></div>
      </div>

      <div class="rounded-xl border border-neutral-900 bg-neutral-900/40 p-4">
        <div class="text-neutral-400 text-sm">Keys In Stock</div>
        <div class="mt-2 text-2xl font-semibold"><?= (int)($stats['keys_instock'] ?? 0) ?></div>
      </div>

      <div class="rounded-xl border border-neutral-900 bg-neutral-900/40 p-4">
        <div class="text-neutral-400 text-sm">Orders Paid</div>
        <div class="mt-2 text-2xl font-semibold"><?= (int)($stats['orders_paid'] ?? 0) ?></div>
      </div>

      <div class="rounded-xl border border-neutral-900 bg-neutral-900/40 p-4">
        <div class="text-neutral-400 text-sm">Total Revenue</div>
        <div class="mt-2 text-2xl font-semibold">$<?= number_format((float)($stats['revenue'] ?? 0), 2) ?></div>
      </div>
    </section>

    <section class="mt-8">
      <div class="rounded-xl border border-neutral-900 bg-neutral-900/40 p-4">
        <h2 class="text-lg font-semibold">Quick Actions</h2>
        <div class="mt-4 flex flex-wrap gap-3">
          <a href="/admin/products/new" class="rounded-md bg-blue-600 hover:bg-blue-500 px-4 py-2 text-sm font-medium">Add Product</a>
          <a href="/admin/products" class="rounded-md bg-neutral-800 hover:bg-neutral-700 px-4 py-2 text-sm font-medium">Manage Products</a>
          <a href="/admin/sales" class="rounded-md bg-neutral-800 hover:bg-neutral-700 px-4 py-2 text-sm font-medium">Sales</a>
        </div>
      </div>
    </section>
  </main>
</div>
