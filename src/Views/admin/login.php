<section class="min-h-screen grid place-items-center bg-neutral-950">
  <form method="post" class="w-[90%] max-w-md rounded-2xl border border-neutral-900 bg-neutral-900/40 p-6">
    <h1 class="text-xl font-semibold text-white text-center">Admin Login</h1>

    <?php if (!empty($error)): ?>
      <div class="mt-4 rounded-md border border-red-500/30 bg-red-500/10 px-3 py-2 text-sm text-red-300">
        <?= htmlspecialchars($error ?? '') ?>
      </div>
    <?php endif; ?>

    <div class="mt-6 grid gap-4">
      <label class="text-sm text-neutral-300">Username
        <input class="mt-1 w-full rounded-md bg-neutral-900 border border-neutral-800 px-4 py-3" name="username" required />
      </label>
      <label class="text-sm text-neutral-300">Password
        <input class="mt-1 w-full rounded-md bg-neutral-900 border border-neutral-800 px-4 py-3" type="password" name="password" required />
      </label>
      <button class="mt-2 w-full rounded-md bg-blue-600 hover:bg-blue-500 px-5 py-3 font-medium">Sign in</button>
    </div>
  </form>
</section>
