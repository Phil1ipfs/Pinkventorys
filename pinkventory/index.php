<?php
require_once 'bootstrap.php';

// Old links pointed at index.php?action=signin|signup|logout; send them to the right place.
$action = $_GET['action'] ?? null;
if ($action === 'logout') {
    redirect(logout_url());
}
if ($action === 'signup') {
    redirect('login.php?action=signup');
}
if ($action !== null) {
    redirect('login.php');
}

$signedIn = is_user() || is_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pinkventory · Inventory management made simple</title>
    <meta name="description" content="Pinkventory is a simple inventory and checkout system for small shops: track stock in real time, get low-stock alerts and sell with barcode checkout.">
    <link rel="icon" type="image/png" href="pinkventory-logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?php echo asset('landing.css'); ?>">
    <script src="<?php echo asset('landing.js'); ?>"></script>
</head>
<body>

<header class="site-nav" id="siteNav">
    <div class="wrap nav-inner">
        <a href="index.php" class="brand">
            <img src="pinkventory-logo.png" alt="">
            Pinkventory
        </a>

        <button class="menu-toggle" id="menuToggle" aria-label="Open menu" aria-expanded="false" aria-controls="siteNav">
            <?php echo icon('menu'); ?>
        </button>

        <nav class="nav-links" aria-label="Main">
            <a href="#features">Features</a>
            <a href="#how">How it works</a>
        </nav>

        <div class="nav-actions">
            <?php if ($signedIn): ?>
                <a href="<?php echo e(logout_url()); ?>" class="btn btn-ghost btn-sm">Sign out</a>
                <a href="<?php echo home_url(); ?>" class="btn btn-primary btn-sm">Open dashboard</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-ghost btn-sm">Sign in</a>
                <a href="login.php?action=signup" class="btn btn-primary btn-sm">Get started</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main>
    <!-- Hero -->
    <section class="hero">
        <div class="wrap hero-grid">
            <div class="hero-copy">
                <span class="eyebrow"><span class="dot"><?php echo icon('sparkles'); ?></span> Inventory management, made pretty</span>
                <h1>Know your stock. <span class="accent">Sell with confidence.</span></h1>
                <p>Pinkventory keeps your products, stock levels and checkout in one tidy place, so you always know what's on the shelf and what needs reordering.</p>

                <div class="hero-ctas">
                    <?php if ($signedIn): ?>
                        <a href="<?php echo home_url(); ?>" class="btn btn-primary">Open your dashboard <?php echo icon('arrow-right'); ?></a>
                    <?php else: ?>
                        <a href="login.php?action=signup" class="btn btn-primary">Create free account <?php echo icon('arrow-right'); ?></a>
                        <a href="login.php" class="btn btn-ghost">Sign in</a>
                    <?php endif; ?>
                </div>

                <ul class="hero-note">
                    <li><?php echo icon('check'); ?> Free to get started</li>
                    <li><?php echo icon('check'); ?> Works on any device</li>
                    <li><?php echo icon('check'); ?> Set up in minutes</li>
                </ul>
            </div>

            <div class="hero-visual" aria-hidden="true">
                <div class="mock">
                    <div class="mock-bar"><span></span><span></span><span></span><em>Pinkventory · Dashboard</em></div>
                    <div class="mock-body">
                        <div class="mock-stats">
                            <div class="mock-stat"><small>Active items</small><strong>1,248</strong><span class="up">+32 this week</span></div>
                            <div class="mock-stat"><small>Low stock</small><strong>6</strong><span class="down">Needs reorder</span></div>
                            <div class="mock-stat"><small>Today's sales</small><strong>$2,850</strong><span class="up">+20.1%</span></div>
                        </div>
                        <div class="mock-panels">
                            <div class="mock-card">
                                <h4>Weekly sales</h4>
                                <div class="bars">
                                    <span style="--h: 45%"></span>
                                    <span style="--h: 62%"></span>
                                    <span style="--h: 38%"></span>
                                    <span style="--h: 74%"></span>
                                    <span style="--h: 58%"></span>
                                    <span style="--h: 92%"></span>
                                    <span style="--h: 70%"></span>
                                </div>
                                <div class="bar-labels"><span>M</span><span>T</span><span>W</span><span>T</span><span>F</span><span>S</span><span>S</span></div>
                            </div>
                            <div class="mock-card">
                                <h4>Stock levels</h4>
                                <ul class="stock-list">
                                    <li>Pink Hoodie <span class="tag tag-ok">In stock</span></li>
                                    <li>Phone Charger <span class="tag tag-low">Low</span></li>
                                    <li>Wireless Earbuds <span class="tag tag-ok">In stock</span></li>
                                    <li>Denim Jacket <span class="tag tag-out">Out</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="float-card float-a">
                    <span class="ic"><?php echo icon('package-check'); ?></span>
                    <div><strong>Stock updated</strong><small>Pink Hoodie +24</small></div>
                </div>
                <div class="float-card float-b">
                    <span class="ic"><?php echo icon('bell-ring'); ?></span>
                    <div><strong>Low-stock alert</strong><small>Phone Charger · 6 left</small></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits strip -->
    <section class="strip" aria-label="Highlights">
        <div class="wrap">
            <ul>
                <li><?php echo icon('store'); ?> Built for small shops</li>
                <li><?php echo icon('smartphone'); ?> Desktop, tablet &amp; phone</li>
                <li><?php echo icon('lock'); ?> Secure accounts &amp; roles</li>
            </ul>
        </div>
    </section>

    <!-- Features -->
    <section class="section" id="features">
        <div class="wrap">
            <div class="section-head reveal">
                <span class="kicker">Features</span>
                <h2>Everything you need to run the stockroom</h2>
                <p>From the first product you add to the sale at the counter, Pinkventory keeps it all in sync.</p>
            </div>

            <div class="features">
                <article class="feature reveal">
                    <span class="ic"><?php echo icon('package'); ?></span>
                    <h3>Live inventory</h3>
                    <p>Add, edit and remove products with categories, prices and stock levels that update instantly.</p>
                </article>
                <article class="feature reveal">
                    <span class="ic"><?php echo icon('scan-barcode'); ?></span>
                    <h3>Barcode checkout</h3>
                    <p>Scan or type a barcode to build a cart, with subtotal, tax and total worked out for you.</p>
                </article>
                <article class="feature reveal">
                    <span class="ic"><?php echo icon('bell-ring'); ?></span>
                    <h3>Low-stock alerts</h3>
                    <p>See which items are running low on your dashboard before customers find empty shelves.</p>
                </article>
                <article class="feature reveal">
                    <span class="ic"><?php echo icon('bar-chart-3'); ?></span>
                    <h3>Sales dashboard</h3>
                    <p>Revenue, orders and average order value at a glance, with clear weekly trend charts.</p>
                </article>
                <article class="feature reveal">
                    <span class="ic"><?php echo icon('search'); ?></span>
                    <h3>Instant search</h3>
                    <p>Find any product in seconds with suggestions that appear as you type.</p>
                </article>
                <article class="feature reveal">
                    <span class="ic"><?php echo icon('shield-check'); ?></span>
                    <h3>Secure by design</h3>
                    <p>Separate user and admin roles, encrypted passwords and protection against common attacks.</p>
                </article>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section class="section how" id="how">
        <div class="wrap">
            <div class="section-head reveal">
                <span class="kicker">How it works</span>
                <h2>Up and running in three steps</h2>
                <p>No setup fees, no complicated configuration.</p>
            </div>

            <ol class="steps">
                <li class="step reveal">
                    <h3>Create your account</h3>
                    <p>Sign up with your email in under a minute and land straight on your dashboard.</p>
                </li>
                <li class="step reveal">
                    <h3>Add your products</h3>
                    <p>Enter your items with category, price and quantity. Status badges keep things readable.</p>
                </li>
                <li class="step reveal">
                    <h3>Sell and restock</h3>
                    <p>Ring up sales at checkout and use low-stock alerts to reorder at the right time.</p>
                </li>
            </ol>
        </div>
    </section>

    <!-- Call to action -->
    <section class="cta">
        <div class="wrap">
            <div class="cta-box reveal">
                <h2>Ready to get organised?</h2>
                <p>Join Pinkventory today and spend less time counting boxes and more time selling.</p>
                <?php if ($signedIn): ?>
                    <a href="<?php echo home_url(); ?>" class="btn btn-light">Open your dashboard <?php echo icon('arrow-right'); ?></a>
                <?php else: ?>
                    <a href="login.php?action=signup" class="btn btn-light">Create free account <?php echo icon('arrow-right'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<footer class="site-footer">
    <div class="wrap footer-inner">
        <a href="index.php" class="brand">
            <img src="pinkventory-logo.png" alt="">
            Pinkventory
        </a>
        <nav class="footer-links" aria-label="Footer">
            <a href="#features">Features</a>
            <a href="#how">How it works</a>
            <a href="login.php">Sign in</a>
            <a href="login.php?action=signup">Create account</a>
        </nav>
        <p class="copyright">© <?php echo date('Y'); ?> Pinkventory. All rights reserved.</p>
    </div>
</footer>

</body>
</html>
