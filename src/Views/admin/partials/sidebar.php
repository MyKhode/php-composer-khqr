<!-- Mobile Header -->
<header class="lg:hidden flex items-center justify-between px-4 py-3 border-b border-neutral-900 bg-neutral-950">
  <div class="flex items-center gap-2">
    <span class="inline-flex size-9 items-center justify-center rounded-lg bg-blue-600 font-semibold">A</span>
    <div>
      <div class="text-sm font-semibold">Admin</div>
      <div class="text-xs text-neutral-400">License Store</div>
    </div>
  </div>
  <button id="mobileMenuBtn"
          class="rounded-md border border-neutral-800 bg-neutral-900 p-2 hover:bg-neutral-800"
          aria-label="Open menu">
    <!-- simple hamburger icon -->
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
    </svg>
  </button>
</header>

<!-- Sidebar -->
<aside id="sidebar"
       class="fixed lg:static inset-y-0 left-0 z-50 hidden lg:flex flex-col gap-2 w-64 min-h-screen border-r border-neutral-900 bg-neutral-950 p-4 transition-transform duration-200 transform lg:translate-x-0">
  <div class="flex items-center justify-between">
    <div class="flex items-center gap-2">
      <span class="inline-flex size-9 items-center justify-center rounded-lg bg-blue-600 font-semibold">A</span>
      <div>
        <div class="text-sm font-semibold">Admin</div>
        <div class="text-xs text-neutral-400">License Store</div>
      </div>
    </div>
    <!-- Close button for mobile -->
    <button id="closeSidebarBtn"
            class="lg:hidden text-neutral-400 hover:text-white"
            aria-label="Close menu">✖</button>
  </div>

  <nav class="mt-6 grid gap-1 text-sm">
    <a class="rounded-md px-3 py-2 <?= str_contains($_SERVER['REQUEST_URI'], '/admin$') ? 'bg-neutral-800 text-blue-400' : 'text-neutral-300 hover:bg-neutral-900 hover:text-white' ?>" href="/admin">Dashboard</a>
    <a class="rounded-md px-3 py-2 <?= str_contains($_SERVER['REQUEST_URI'], '/admin/products') ? 'bg-neutral-800 text-blue-400' : 'text-neutral-300 hover:bg-neutral-900 hover:text-white' ?>" href="/admin/products">Products & Keys</a>
    <a class="rounded-md px-3 py-2 <?= str_contains($_SERVER['REQUEST_URI'], '/admin/sales') ? 'bg-neutral-800 text-blue-400' : 'text-neutral-300 hover:bg-neutral-900 hover:text-white' ?>" href="/admin/sales">Sales Record</a>
    <a class="rounded-md px-3 py-2 text-neutral-300 hover:bg-neutral-900 hover:text-white" href="/admin/logout">Logout</a>
  </nav>
</aside>

<!-- Dark overlay for mobile -->
<div id="overlay"
     class="hidden fixed inset-0 z-40 bg-black/70 lg:hidden"
     aria-hidden="true"></div>

<script>
const sidebar = document.getElementById('sidebar');
const overlay = document.getElementById('overlay');
const openBtn = document.getElementById('mobileMenuBtn');
const closeBtn = document.getElementById('closeSidebarBtn');

if (openBtn && sidebar && overlay) {
  openBtn.addEventListener('click', () => {
    sidebar.classList.remove('hidden');
    overlay.classList.remove('hidden');
    sidebar.classList.add('translate-x-0');
  });
}

if (closeBtn && sidebar && overlay) {
  closeBtn.addEventListener('click', () => {
    sidebar.classList.add('hidden');
    overlay.classList.add('hidden');
  });
}

if (overlay) {
  overlay.addEventListener('click', () => {
    sidebar.classList.add('hidden');
    overlay.classList.add('hidden');
  });
}
</script>
