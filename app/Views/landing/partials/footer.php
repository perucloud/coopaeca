<?php
$footerLogo = $footerLogo ?? asset('img/logowhite.png');
$socials = $socials ?? [];
$plataformasFooter = social_platforms();
?>

<style>
    .lp-footer-social { display: flex; gap: 12px; }
    .lp-footer-social a {
        width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        background: rgba(255,255,255,.1); color: #fff; transition: transform .18s ease, background .18s ease;
    }
    .lp-footer-social a svg { width: 17px; height: 17px; }
    .lp-footer-social a:hover { transform: translateY(-3px); background: var(--sn-color, rgba(255,255,255,.22)); }
</style>

<!-- FOOTER -->
<footer class="lp-footer">
    <div class="lp-container lp-footer-inner">
        <a href="<?= e(url('/')) ?>" class="lp-footer-logo" aria-label="COOPAECA">
            <img src="<?= e($footerLogo) ?>" alt="COOPAECA">
        </a>
        <?php if (!empty($socials)): ?>
            <div class="lp-footer-social">
                <?php foreach ($socials as $s):
                    $key = $s['platform_key'] ?? 'otro';
                    $color = $plataformasFooter[$key]['color'] ?? '#ffffff33';
                ?>
                    <a href="<?= e($s['url']) ?>" target="_blank" rel="noopener" title="<?= e($s['platform']) ?>"
                       style="--sn-color: <?= e($color) ?>">
                        <?= social_icon($key) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <p>&copy; <?= date('Y') ?> <?= e($settings['cooperative_name'] ?? 'COOPAECA') ?>. <?= e(t('footer.rights')) ?></p>
    </div>
</footer>
