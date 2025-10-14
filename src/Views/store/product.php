<?php require __DIR__.'/partials/header.php'; ?>

<section class="max-w-7xl mx-auto px-4 py-8">
  <a class="text-sm text-neutral-400 hover:text-neutral-200" href="/">← Back</a>

  <div class="grid gap-8 mt-5 lg:grid-cols-12">
    <div class="lg:col-span-6 rounded-2xl border border-neutral-900 overflow-hidden aspect-square">
      <img class="w-full h-full object-cover" src="<?= htmlspecialchars($product['photo_base64'] ?? '') ?>"
           alt="<?= htmlspecialchars($product['name'] ?? '') ?>" />
    </div>

    <div class="lg:col-span-6 space-y-5">
      <div>
        <h1 class="text-3xl font-semibold"><?= htmlspecialchars($product['name'] ?? '') ?></h1>
        <p class="text-neutral-400 mt-1">Category: <?= htmlspecialchars($product['category'] ?? '') ?></p>
      </div>

      <div class="prose prose-invert max-w-none text-neutral-200">
        <?= nl2br(htmlspecialchars($product['description'] ?? '')) ?>
      </div>

      <div class="rounded-xl border border-neutral-900 bg-neutral-900/40 p-4 space-y-2">
        <h2 class="text-neutral-200 font-medium mb-2">Product Info</h2>
        <p><strong>Category:</strong> <?= htmlspecialchars($product['category'] ?? '-') ?></p>
        <p><strong>Delivery Type:</strong>
          <?= htmlspecialchars($product['delivery_type'] ?? 'N/A') ?>
        </p>
        <p><strong>Link:</strong>
          <?php if (!empty($product['link'])): ?>
            <a href="<?= htmlspecialchars($product['link']) ?>" target="_blank" class="text-blue-400 underline">
              <?= htmlspecialchars($product['link']) ?>
            </a>
          <?php else: ?>
            N/A
          <?php endif; ?>
        </p>
        <p><strong>Description:</strong></p>
        <div class="text-neutral-300 text-sm leading-relaxed">
          <?= nl2br(htmlspecialchars($product['description'] ?? 'No description available.')) ?>
        </div>
      </div>


      <div class="rounded-xl border border-neutral-900 bg-neutral-900/40 p-4 space-y-3">
        <div class="flex items-center justify-between">
          <span class="text-2xl font-bold">$<?= number_format((float)($product['price'] ?? 0), 2) ?></span>
        </div>
        <div class="grid sm:grid-cols-2 gap-3">
          <input id="buyerName" class="w-full rounded-md bg-neutral-900 border border-neutral-800 px-3 py-2 text-sm" placeholder="Your name (optional)" />
          <input id="buyerPhone" class="w-full rounded-md bg-neutral-900 border border-neutral-800 px-3 py-2 text-sm" placeholder="Phone (optional)" />
        </div>
        <button
          id="buyBtn"
          data-id="<?= (int)($product['id'] ?? 0) ?>"
          class="w-full inline-flex items-center justify-center gap-2 rounded-md bg-blue-600 hover:bg-blue-500 px-4 py-2 font-medium">
          Purchase License Key Now
        </button>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__.'/partials/footer.php'; ?>

<!-- KHQR Modal (QR only) -->
<div class="fixed inset-0 z-50 hidden items-center justify-center bg-black/60" id="qrModal">
  <div class="relative w-[90%] max-w-md rounded-2xl border border-neutral-800 bg-neutral-900 p-6 text-center">
    <button class="absolute right-3 top-3 text-neutral-400 hover:text-white" data-close>✖</button>
    <h2 class="text-lg font-semibold">Scan to Pay</h2>
    <img id="qrImage" alt="KHQR" class="mx-auto mt-4 size-56 rounded-lg border border-neutral-800 object-contain bg-neutral-950" />
    <p id="qrStatus" class="mt-3 text-sm text-neutral-400">Generating QR...</p>
    <div class="mt-4 flex justify-center gap-2">
      <button id="downloadQR" class="rounded-md border border-neutral-800 bg-neutral-900 px-3 py-2">Download QR</button>
      <button class="rounded-md bg-neutral-800 px-3 py-2" data-close>Close</button>
    </div>
  </div>
  <input type="hidden" id="currentTxId" value="">
</div>

<!-- Success Modal -->
<div class="fixed inset-0 z-[55] hidden items-center justify-center bg-black/60" id="successModal">
  <div class="relative w-[90%] max-w-md rounded-2xl border border-neutral-800 bg-neutral-900 p-6 text-left">
    <button class="absolute right-3 top-3 text-neutral-400 hover:text-white" id="successCloseBtn">✖</button>
    <h2 class="text-lg font-semibold text-white">Payment Successful 🎉</h2>
    <p class="mt-2 text-sm text-neutral-300">Transaction ID:</p>
    <div class="flex items-center gap-2 mt-1">
      <input id="successTxId" class="flex-1 rounded-md bg-neutral-900 border border-neutral-800 p-1 text-xs text-blue-400" readonly />
      <button id="copyTxBtn" class="text-xs text-blue-400 underline">Copy</button>
    </div>

    <p class="mt-3 text-sm text-neutral-300">Your license key(s):</p>
    <textarea id="successLicenseText" class="mt-2 w-full rounded-md bg-neutral-900 border border-neutral-800 p-2 text-green-400 text-sm" rows="5" readonly></textarea>
    <div class="mt-4 flex flex-wrap gap-2">
      <button id="copyKeyBtn" class="rounded-md bg-blue-600 hover:bg-blue-500 px-3 py-2 text-sm">Copy Key(s)</button>
      <button id="downloadKeyBtnSuccess" class="rounded-md bg-emerald-600 hover:bg-emerald-500 px-3 py-2 text-sm">Download .txt</button>
    </div>
  </div>
</div>

<!-- Close Confirmation Modal (instead of window.confirm) -->
<div class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/60" id="closeConfirmModal" aria-hidden="true">
  <div class="w-[90%] max-w-md rounded-2xl border border-neutral-800 bg-neutral-900 p-6 text-center">
    <h3 class="text-lg font-semibold text-white">Have you saved your license key?</h3>
    <p class="mt-2 text-sm text-neutral-300">
      Once you close this window, you won’t be able to view your key again. Please copy and download it now.
    </p>
    <div class="mt-5 flex flex-wrap justify-center gap-3">
      <button id="confirmCloseBtn" class="rounded-md bg-red-600 hover:bg-red-500 px-4 py-2">Yes, I saved it — Close</button>
      <button id="cancelCloseBtn" class="rounded-md bg-neutral-800 hover:bg-neutral-700 px-4 py-2">Go back</button>
    </div>
  </div>
</div>
