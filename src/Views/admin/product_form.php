<div class="min-h-screen grid grid-cols-1 lg:grid-cols-[256px_1fr]">
  <?php require __DIR__.'/partials/sidebar.php'; ?>

  <main class="p-6">
    <h1 class="text-2xl font-semibold mb-4"><?= $product ? 'Edit' : 'New' ?> Product</h1>

    <?php if (!empty($error)): ?>
      <div class="mb-4 rounded-md border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-300">
        <?= htmlspecialchars($error ?? '') ?>
      </div>
    <?php endif; ?>

    <form method="post" action="/admin/products/save" enctype="multipart/form-data"
          class="rounded-xl border border-neutral-900 bg-neutral-900/40 p-5 space-y-5">
      <input type="hidden" name="id" value="<?= htmlspecialchars($product['id'] ?? '') ?>" />

      <div class="grid gap-4 sm:grid-cols-2">
        <label class="text-sm text-neutral-300">Category
          <input class="mt-1 w-full rounded-md bg-neutral-900 border border-neutral-800 px-3 py-2"
                 name="category" value="<?= htmlspecialchars($product['category'] ?? '') ?>" required>
        </label>
        <label class="text-sm text-neutral-300">Name
          <input class="mt-1 w-full rounded-md bg-neutral-900 border border-neutral-800 px-3 py-2"
                 name="name" value="<?= htmlspecialchars($product['name'] ?? '') ?>" required>
        </label>
      </div>

      <label class="text-sm text-neutral-300">Price
        <input class="mt-1 w-full rounded-md bg-neutral-900 border border-neutral-800 px-3 py-2"
               type="number" step="0.01" name="price" value="<?= htmlspecialchars($product['price'] ?? '') ?>" required>
      </label>

      <label class="text-sm text-neutral-300">Description
        <textarea class="mt-1 w-full rounded-md bg-neutral-900 border border-neutral-800 px-3 py-2 h-28"
                  name="description"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
      </label>

      <div class="grid gap-4 sm:grid-cols-2">
        <label class="text-sm text-neutral-300">Link (optional)
          <input class="mt-1 w-full rounded-md bg-neutral-900 border border-neutral-800 px-3 py-2"
                 name="link" value="<?= htmlspecialchars($product['link'] ?? '') ?>">
        </label>
        <label class="text-sm text-neutral-300">Delivery Type
          <?php $dt = strtolower(trim($product['delivery_type'] ?? 'instant')); ?>
          <select class="mt-1 w-full rounded-md bg-neutral-900 border border-neutral-800 px-3 py-2"
                  name="delivery_type">
            <option value="instant" <?= $dt === 'instant' ? 'selected' : '' ?>>Instant</option>
            <option value="waiting" <?= $dt === 'waiting' ? 'selected' : '' ?>>Waiting</option>
          </select>
        </label>
      </div>

      <label class="text-sm text-neutral-300">Image
        <input class="mt-1 w-full rounded-md bg-neutral-900 border border-neutral-800 px-3 py-2 file:mr-3 file:rounded-md file:border-0 file:bg-neutral-800 file:px-3 file:py-2"
               type="file" name="photo" accept="image/*" <?= $product? '' : 'required' ?>>
      </label>

      <div>
        <h3 class="text-neutral-200 font-medium mb-2">Bulk License Keys (comma or newline)</h3>
        <textarea class="mt-1 w-full rounded-md bg-neutral-900 border border-neutral-800 px-3 py-2 h-32"
                  name="license_keys"
                  placeholder="ABC-123-XYZ, DEF-456-UVW"><?=
            !empty($existingKeys) ? htmlspecialchars(implode(',', $existingKeys)) : '' ?></textarea>
        <p class="mt-1 text-xs text-neutral-400">Tip: Paste keys separated by commas or new lines. Only unsold keys are shown here.</p>
      </div>

      <div class="flex items-center gap-3">
        <button class="rounded-md bg-blue-600 hover:bg-blue-500 text-white px-4 py-2">Save</button>
        <a href="/admin/products" class="rounded-md bg-neutral-800 hover:bg-neutral-700 px-4 py-2">Cancel</a>
      </div>
    </form>
  </main>
</div>
