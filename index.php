<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Driving Experience App</title>

<style>
  * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }

  body {
    min-height: 100vh;
    background-image: url('background1.avif');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;

    display: flex;
    justify-content: center;
    align-items: center;
    padding: 18px;
    color: white;
  }

  .glass-box {
    background: rgba(28, 7, 57, 0.75);
    backdrop-filter: blur(10px);
    padding: 44px 34px;
    border-radius: 18px;
    width: 100%;
    max-width: 460px;
    text-align: center;
    box-shadow: 0 8px 20px rgba(0,0,0,0.5);
    animation: fadeIn 0.9s ease;
  }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(25px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  h1 {
    font-size: 32px;
    margin-bottom: 22px;
    font-weight: 700;
    text-shadow: 0 0 12px rgba(255,255,255,0.35);
  }

  .btn {
    display: block;
    width: 100%;
    padding: 16px;
    margin: 12px 0;
    border-radius: 12px;
    font-size: 18px;
    font-weight: bold;
    color: white;
    text-decoration: none;
    background: linear-gradient(45deg, #c6abe0, #3e123e);
    transition: 0.25s ease;
  }

  .btn:hover {
    transform: translateY(-1px) scale(1.03);
    box-shadow: 0 0 14px rgba(255,255,255,0.3);
    background: linear-gradient(45deg, #3e123e, #c6abe0);
  }

  footer {
    margin-top: 18px;
    font-size: 14px;
    opacity: 0.85;
  }

  @media (max-width: 420px) {
    .glass-box { padding: 34px 18px; border-radius: 16px; }
    h1 { font-size: 26px; margin-bottom: 18px; }
    .btn { font-size: 16px; padding: 14px; border-radius: 11px; }
  }
</style>
</head>

<body>
  <div class="glass-box">
    <h1>Driving Experience</h1>

    <a href="form.php" class="btn">➕ Add Driving Experience</a>
    <a href="dashboard.php" class="btn">📊 Open Dashboard</a>

    <footer>© 2025 Adila Jafarova</footer>
  </div>
</body>
</html>
