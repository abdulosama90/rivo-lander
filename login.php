<?php
require_once __DIR__ . '/lib/rivo.php';

// Force HTTPS — we handle credentials on this page.
if (empty($_SERVER['HTTPS'])) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
    exit;
}

session_set_cookie_params(['secure' => true, 'httponly' => true, 'samesite' => 'Lax']);
session_start();

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $error = 'الصفحة قعدت مفتوحة كتير. حدّثها وجرّب تاني.';
    } elseif (!rivo_throttle()) {
        $error = 'محاولات كتير. استنى ٥ دقايق وجرّب تاني.';
    } elseif ($username === '' || $password === '') {
        $error = 'اكتب اسم المستخدم وكلمة السر.';
    } elseif (!rivo_cfg('oauth_client_key') || !rivo_cfg('general_api_key')) {
        $error = 'الدخول مش متاح دلوقتي. جرّب بعد شوية.';
    } else {
        rivo_note_attempt();
        $token = rivo_authenticate($username, $password);
        if ($token === null) {
            $error = 'اسم المستخدم أو كلمة السر غلط.';
        } else {
            $url = rivo_sso_url($token['access_token']);
            unset($token, $password);
            if ($url) {
                session_regenerate_id(true);
                unset($_SESSION['rivo_attempts']);
                header('Location: ' . $url, true, 302);
                exit;
            }
            $error = 'دخلت صح، بس في مشكلة في فتح اللوحة. جرّب تاني.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>دخول العملاء — ريفو</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Lalezar&family=Marhey:wght@400;500;600;700&family=Noto+Kufi+Arabic:wght@400;500;600;700&family=Aref+Ruqaa:wght@400;700&family=Baloo+2:wght@600;700;800&display=swap" rel="stylesheet">
<style>
  :root {
    --ink:#161513; --pill:#EFEFEA; --pill-deep:#D8DCD3;
    --teal:#3FBCAE; --teal-deep:#27998C; --stamp:#C0503C; --sun:#E2BC5F;
    --f-logo:'Lalezar','Baloo 2',sans-serif;
    --f-loud:'Marhey',sans-serif;
    --f-body:'Aref Ruqaa','Noto Kufi Arabic',serif;
    --border:3px solid var(--ink);
  }
  *{margin:0;padding:0;box-sizing:border-box}
  body{
    font-family:var(--f-body); background-color:var(--teal); color:var(--ink);
    background-image:
      repeating-linear-gradient(0deg,rgba(255,255,255,.07) 0 1px,transparent 1px 3px),
      repeating-linear-gradient(90deg,rgba(255,255,255,.09) 0 1px,transparent 1px 3px);
    background-size:3px 3px;
    min-height:100vh; display:flex; flex-direction:column;
  }
  .grain{position:fixed;inset:0;z-index:60;pointer-events:none;
    background-image:radial-gradient(rgba(22,21,19,.5) .6px,transparent 1.1px),
                     radial-gradient(rgba(255,255,255,.55) .6px,transparent 1.1px);
    background-size:5px 7px,9px 11px;background-position:0 0,3px 5px;opacity:.16;mix-blend-mode:overlay}
  .perf{position:fixed;top:0;bottom:0;width:34px;z-index:50;
    background:repeating-linear-gradient(to bottom,var(--ink) 0 22px,transparent 22px 44px);
    background-color:var(--teal);border-left:var(--border)}
  .perf.left{left:0;border-left:none;border-right:var(--border)}
  .perf.right{right:0}

  main{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 54px}
  .card{
    width:min(440px,100%); background:var(--pill);
    border:var(--border); border-radius:20px; box-shadow:8px 8px 0 var(--ink);
    padding:38px 34px 34px; position:relative;
    background-image:repeating-linear-gradient(0deg,rgba(22,21,19,.025) 0 1px,transparent 1px 4px);
  }
  .stamp{
    position:absolute; top:-19px; right:26px;
    background:var(--stamp); color:#fff;
    font-family:var(--f-loud); font-weight:700; font-size:14px;
    padding:7px 20px; border-radius:999px; border:2.5px solid var(--ink);
    transform:rotate(-2deg); box-shadow:-4px 4px 0 var(--sun),3px 3px 0 var(--ink);
  }
  .logo{display:block;height:52px;width:auto;margin:0 auto 22px;transform:rotate(-3deg)}
  h1{
    font-family:var(--f-logo); font-size:38px; line-height:1.25; text-align:center; margin-bottom:6px;
    color:var(--ink);
    text-shadow:
      4px 0 0 var(--pill),-4px 0 0 var(--pill),0 4px 0 var(--pill),0 -4px 0 var(--pill),
      3px 3px 0 var(--pill),-3px 3px 0 var(--pill),3px -3px 0 var(--pill),-3px -3px 0 var(--pill);
  }
  .sub{text-align:center;font-size:16px;color:#4a4d45;margin-bottom:26px}
  label{display:block;font-family:var(--f-loud);font-weight:600;font-size:15px;margin:0 0 7px 2px}
  input[type=text],input[type=password]{
    width:100%; font-family:var(--f-body); font-size:17px;
    padding:13px 16px; margin-bottom:18px;
    border:2.5px solid var(--ink); border-radius:12px;
    background:#fff; color:var(--ink); outline:none;
  }
  input:focus{box-shadow:3px 3px 0 var(--teal)}
  .btn{
    width:100%; cursor:pointer; font-family:var(--f-loud); font-weight:700; font-size:19px;
    background:var(--ink); color:var(--pill);
    border:var(--border); border-radius:999px; padding:13px 26px;
    box-shadow:4px 4px 0 rgba(22,21,19,.3); transition:transform .1s,box-shadow .1s;
  }
  .btn:active{transform:translate(4px,4px);box-shadow:0 0 0}
  .err{
    background:var(--stamp); color:#fff; font-family:var(--f-loud); font-size:15px;
    border:2.5px solid var(--ink); border-radius:12px; padding:11px 15px; margin-bottom:20px;
    box-shadow:-4px 4px 0 var(--sun),3px 3px 0 var(--ink);
  }
  .foot{text-align:center;margin-top:22px;font-size:14.5px;line-height:2}
  .foot a{color:var(--teal-deep);font-weight:700}
  .back{display:block;text-align:center;margin-top:20px;font-family:var(--f-loud);font-size:15px;color:var(--ink)}
  @media(max-width:600px){
    .perf{width:12px;border-width:2px}
    main{padding:26px 20px}
    .card{padding:32px 22px 28px}
    h1{font-size:31px}
  }
</style>
</head>
<body>
<div class="grain"></div>
<div class="perf left"></div>
<div class="perf right"></div>

<main>
  <form class="card" method="post" autocomplete="on" novalidate>
    <span class="stamp">دخول العملاء</span>
    <img class="logo" src="assets/rivo-logo-ar.png" alt="ريفو">
    <h1>أهلاً بيك تاني</h1>
    <p class="sub">ادخل على لوحة التحكم بتاعتك</p>

    <?php if ($error): ?><div class="err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf'], ENT_QUOTES, 'UTF-8') ?>">

    <label for="username">الإيميل أو اسم المستخدم</label>
    <input type="text" id="username" name="username" autocomplete="username" spellcheck="false" dir="ltr"
           value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

    <label for="password">كلمة السر</label>
    <input type="password" id="password" name="password" autocomplete="current-password" dir="ltr">

    <button class="btn" type="submit">ادخل على اللوحة ←</button>

    <div class="foot">
      نسيت كلمة السر؟ <a href="https://app.rivo.host/login/forgotten-password" target="_blank" rel="noopener">اعملها ريست</a><br>
      لسه معندكش حساب؟ <a href="/#pricing">شوف الباقات</a>
    </div>
    <a class="back" href="/">→ رجوع للصفحة الرئيسية</a>
  </form>
</main>
</body>
</html>
