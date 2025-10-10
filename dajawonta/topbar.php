

<header class="bg-white shadow-md w-full fixed top-0 left-0 z-50">
  <div class="container mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between h-16">
      <div class="flex-shrink-0">
        <a href="index.php" class="flex items-center">
          <img src="logo/dajawonta.png" alt="DajawonTa Logo" class="h-8 w-8 object-contain mr-2" />
          <span class="ml-1 text-2xl font-bold text-gray-800 hidden sm:block" style="color: var(--primary);">DajawonTa</span>
        </a>
      </div>

      <nav class="hidden md:flex md:items-center md:space-x-8">
        <a href="index.php" class="text-gray-600 hover:text-indigo-600 font-medium transition duration-150 ease-in-out">Home</a>
        <a href="#services" class="text-gray-600 hover:text-indigo-600 font-medium transition duration-150 ease-in-out">Services</a>
        <a href="#about" class="text-gray-600 hover:text-indigo-600 font-medium transition duration-150 ease-in-out">About</a>
        <a href="#contact" class="text-gray-600 hover:text-indigo-600 font-medium transition duration-150 ease-in-out">Contact</a>
      </nav>

      <div class="hidden md:flex items-center space-x-2">
        <a href="login.php" class="px-4 py-2 text-sm font-semibold text-indigo-600 border border-indigo-600 rounded-lg hover:bg-indigo-50 transition duration-150 ease-in-out">
          Log In
        </a>
        <a href="register.php" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition duration-150 ease-in-out">
          Register
        </a>
      </div>

      <div class="md:hidden flex items-center">
        <button id="mobile-menu-button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500">
          <i class="fas fa-bars fa-lg"></i>
        </button>
      </div>

    </div>
  </div>

  <div id="mobile-menu" class="md:hidden hidden">
    <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
      <a href="index.php" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Home</a>
      <a href="#services" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Services</a>
      <a href="#about" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">About</a>
      <a href="#contact" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Contact</a>
    </div>
    <div class="px-4 pb-4 space-y-2">
       <a href="login.php" class="block w-full text-center px-4 py-2 text-base font-semibold text-indigo-600 border border-indigo-600 rounded-lg hover:bg-indigo-50">Log In</a>
       <a href="register.php" class="block w-full text-center px-4 py-2 text-base font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Register</a>
    </div>
  </div>
</header>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const menuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    menuButton.addEventListener('click', function () {
      mobileMenu.classList.toggle('hidden');
    });
  });
</script>

<style>
  body {
    padding-top: 64px; /* Height of the header */
  }
   :root {
      --primary: #3a86ff;
  }
</style>

