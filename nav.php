<style>
  .navbar {
    width: 100%;
    padding: 12px 18px;
    background: rgba(28, 7, 57, 0.75);
    backdrop-filter: blur(6px);
    display: flex;
    justify-content: center;
    gap: 14px;
    flex-wrap: wrap;
    position: sticky;
    top: 0;
    z-index: 1000;
    box-shadow: 0 3px 12px rgba(0,0,0,0.4);
  }

  .navbar a {
    color: #ffffff;
    text-decoration: none;
    font-size: 16px;
    font-weight: 600;
    padding: 10px 14px;
    border-radius: 10px;
    background: linear-gradient(45deg,#c6abe0, #3e123e);
    transition: 0.25s ease;
    white-space: nowrap;
  }

  .navbar a:hover {
    transform: translateY(-1px) scale(1.03);
    box-shadow: 0 0 12px rgba(255,255,255,0.35);
  }

  @media (max-width: 420px) {
    .navbar { padding: 10px 12px; gap: 10px; }
    .navbar a { font-size: 14px; padding: 9px 12px; border-radius: 9px; }
  }
</style>

<div class="navbar">
  <a href="index.php">🏠 Home</a>
  <a href="form.php">➕ Add Experience</a>
  <a href="dashboard.php">📊 Dashboard</a>
</div>
