<div class="space-y-6 text-sm text-neutral-200">
  <h2 class="text-xl font-semibold text-white">Order #<?= htmlspecialchars($order['transaction_id']) ?></h2>

  <div class="rounded-xl border border-neutral-900 bg-neutral-900/40 p-5 space-y-2">
    <p><strong>Buyer:</strong> <?= htmlspecialchars($order['buyer_name']) ?></p>
    <p><strong>Phone:</strong> <?= htmlspecialchars($order['buyer_phone']) ?></p>
    <p><strong>Telegram:</strong> <?= htmlspecialchars($order['buyer_telegram'] ?? '-') ?></p>
    <p><strong>Amount:</strong> $<?= number_format((float)$order['total_amount'], 2) ?></p>
    <p><strong>Date:</strong> <?= htmlspecialchars($order['created_at'] ?? '') ?></p>
    <button onclick="navigator.clipboard.writeText(`<?= htmlspecialchars(json_encode($order)) ?>`)"
            class="mt-2 rounded-md bg-blue-600 hover:bg-blue-500 px-3 py-1 text-xs">Copy Buyer Info</button>
  </div>

  <?php foreach ($items as $it): ?>
    <div class="rounded-xl border border-neutral-900 bg-neutral-900/40 p-5 space-y-4" data-item-block>
      <h3 class="text-lg font-semibold text-white">
        Product: <?= htmlspecialchars($it['product_name'] ?? '') ?>
      </h3>

      <div>
        <p><strong>License Key:</strong>
          <span class="text-emerald-400"><?= htmlspecialchars($it['license_key'] ?? '-') ?></span>
          <?php if (!empty($it['license_key'])): ?>
            <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($it['license_key']) ?>')"
                    class="ml-2 text-xs text-blue-400 underline">Copy</button>
          <?php endif; ?>
        </p>
      </div>

      <div>
        <form method="post" action="/admin/order-items/<?= (int)$it['id'] ?>/status">
          <label class="text-neutral-400 text-xs block mb-1">Delivery Status</label>
          <select name="status" class="rounded-md bg-neutral-900 border border-neutral-800 px-2 py-1 text-sm"
                  onchange="this.form.submit()">
            <option value="preparing" <?= ($it['delivery_status'] ?? 'preparing')==='preparing'?'selected':'' ?>>preparing</option>
            <option value="completed" <?= ($it['delivery_status'] ?? '')==='completed'?'selected':'' ?>>completed</option>
          </select>
        </form>
      </div>

      <div class="space-y-2">
        <label class="text-neutral-400 text-xs block">Proof Receipt Image</label>

        <div class="space-y-2" data-proof-wrapper>
          <?php if (!empty($it['proof_image_base64'])): ?>
            <img data-proof-img src="<?= htmlspecialchars($it['proof_image_base64']) ?>"
                 class="h-48 w-auto rounded border border-neutral-800 object-contain shadow" alt="Proof Image" />
            <a data-proof-download href="<?= htmlspecialchars($it['proof_image_base64']) ?>"
               download="proof_<?= (int)$it['id'] ?>.jpg"
               class="inline-block rounded-md bg-neutral-800 hover:bg-neutral-700 px-3 py-1 text-xs text-blue-400">
               Download Proof
            </a>
          <?php else: ?>
            <img data-proof-img src="" class="hidden h-48 w-auto rounded border border-neutral-800 object-contain shadow" alt="Proof Image" />
            <a data-proof-download href="#" class="hidden"></a>
          <?php endif; ?>
        </div>

        <form method="post"
              action="/admin/order-items/<?= (int)$it['id'] ?>/proof"
              enctype="multipart/form-data"
              data-upload-proof>
          <input type="file" name="proof" accept="image/*"
                 class="text-xs file:mr-2 file:bg-neutral-800 file:px-2 file:py-1" required>
          <button class="rounded-md bg-blue-600 hover:bg-blue-500 px-2 py-1 text-xs">Upload / Replace Proof</button>
        </form>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<script>
// This script runs inside the modal (for sale_detail.php only)
document.querySelectorAll('[data-upload-proof]').forEach(form => {
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const btn = form.querySelector('button[type="submit"]');
    if (btn) {
      btn.disabled = true;
      btn.textContent = 'Uploading...';
    }

    try {
      const res = await fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { 'X-Requested-With': 'fetch' }
      });
      const json = await res.json();
      if (json.ok && json.base64) {
        const wrapper = form.closest('[data-item-block]');
        const img = wrapper.querySelector('[data-proof-img]');
        const download = wrapper.querySelector('[data-proof-download]');
        img.src = json.base64;
        img.classList.remove('hidden');
        download.href = json.base64;
        download.classList.remove('hidden');
        alert('✅ Proof uploaded successfully!');
      } else {
        alert('❌ Upload failed: ' + (json.error || 'Unknown error'));
      }
    } catch (err) {
      alert('❌ Upload failed: ' + err.message);
    } finally {
      if (btn) {
        btn.disabled = false;
        btn.textContent = 'Upload / Replace Proof';
      }
    }
  });
});
</script>
