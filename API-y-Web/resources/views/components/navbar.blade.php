<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container">
    <a class="navbar-brand" href="/">
      <img src="{{ asset('logo.png') }}" alt="Helpdesk" height="40" onerror="this.style.display='none'">
      <strong>HELPDESK</strong>
    </a>

    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ml-auto">
        <li class="nav-item">
          <a class="nav-link {{ Request::is('/') ? 'active' : '' }}" href="/">
            <i class="fas fa-home mr-1"></i> Inicio
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('register*') ? 'active' : '' }}" href="/register">
            <i class="fas fa-user-plus mr-1"></i> Registro
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link {{ Request::is('login*') ? 'active' : '' }}" href="/login">
            <i class="fas fa-sign-in-alt mr-1"></i> Login
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>
