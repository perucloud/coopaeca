<?php
$headerLogo = !empty($settings['header_logo_path']) ? url('/' . $settings['header_logo_path']) : asset('img/logo-ccopaeca.png');
$footerLogo = !empty($settings['footer_logo_path']) ? url('/' . $settings['footer_logo_path']) : asset('img/logowhite.png');
$activeLandingNav = 'publicaciones';
$fecha = $post['published_at'] ?? $post['created_at'];
$shareUrl = absolute_url(lurl('/publicacion?slug=' . $post['slug']));
$postTitle = localized_value($post, 'title');
$postContent = localized_value($post, 'content');
$postCategory = localized_value($post, 'category');
$shareText = $postTitle . ' - ' . ($settings['cooperative_name'] ?? 'COOPAECA');
$formatPostDate = static function (?string $value): string {
    if (!$value) return '';
    $timestamp = strtotime($value);
    if ($timestamp === false) return '';
    $months = landing_lang() === 'en'
        ? [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']
        : [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    $month = $months[(int)date('n', $timestamp)];
    return landing_lang() === 'en'
        ? $month . ' ' . date('j, Y', $timestamp)
        : date('j', $timestamp) . ' de ' . $month . ' de ' . date('Y', $timestamp);
};
?>

<?php require __DIR__ . '/partials/header.php'; ?>

<style>
    .post-breadcrumb { padding: 18px 0; font-size: 13px; color: #78715f; }
    .post-breadcrumb a { color: #78715f; text-decoration: none; }
    .post-breadcrumb a:hover { color: #2f6b3f; }
    .post-hero-img { width: 100%; max-height: 420px; object-fit: cover; border-radius: 16px; margin-bottom: 28px; }
    .post-hero-placeholder { width: 100%; height: 260px; border-radius: 16px; margin-bottom: 28px; background: linear-gradient(135deg, #e7e0cf, #cdd9c2); display: flex; align-items: center; justify-content: center; color: #6b7d5f; }
    .post-meta-row { display: flex; align-items: center; gap: 14px; margin-bottom: 12px; flex-wrap: wrap; }
    .post-category-tag { background: #eef3e6; color: #2f6b3f; font-weight: 700; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; padding: 6px 14px; border-radius: 999px; }
    .post-date { color: #78715f; font-size: 13px; }
    .post-title { font-size: clamp(28px, 4vw, 42px); margin: 0 0 20px; color: #26301f; }
    .post-body { max-width: 780px; margin: 0 auto; padding: 20px 0 60px; }
    .post-content { font-size: 17px; line-height: 1.8; color: #3d3527; }
    .post-content img { max-width: 100%; border-radius: 10px; margin: 16px 0; }
    .post-related-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    @media (max-width: 900px) { .post-related-grid { grid-template-columns: 1fr; } }

    .post-share { display: flex; align-items: center; gap: 10px; margin: 24px 0 32px; flex-wrap: wrap; }
    .post-share-label { font-size: 13px; font-weight: 700; color: #6b7d5f; text-transform: uppercase; letter-spacing: .04em; }
    .post-share-btn {
        display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px;
        border-radius: 50%; border: none; cursor: pointer; color: #fff; transition: transform .15s ease, box-shadow .15s ease;
        box-shadow: 0 4px 10px rgba(0,0,0,.12);
    }
    .post-share-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 18px rgba(0,0,0,.18); }
    .post-share-btn svg { width: 19px; height: 19px; fill: currentColor; }
    .share-whatsapp { background: #25D366; }
    .share-facebook { background: #1877F2; }
    .share-copy { background: #3d3527; }
    .post-copy-feedback {
        font-size: 13px; color: #2f6b3f; font-weight: 600; opacity: 0; transition: opacity .25s ease;
    }
    .post-copy-feedback.is-visible { opacity: 1; }
</style>

<div class="post-breadcrumb">
    <div class="lp-container">
        <a href="<?= e(lurl('/')) ?>"><?= e(t('breadcrumb.home')) ?></a>
        <span>/</span>
        <a href="<?= e(lurl('/#publicaciones')) ?>"><?= e(t('nav.posts')) ?></a>
        <span>/</span>
        <strong><?= e($postTitle) ?></strong>
    </div>
</div>

<main class="post-body">
    <div class="lp-container">
        <?php if (!empty($post['image_path'])): ?>
            <img class="post-hero-img" src="<?= e(url('/' . $post['image_path'])) ?>" alt="<?= e($postTitle) ?>">
        <?php else: ?>
            <div class="post-hero-placeholder"><?= icon('edit') ?></div>
        <?php endif; ?>

        <div class="post-meta-row">
            <span class="post-category-tag"><?= e($postCategory !== '' ? $postCategory : t('post.general')) ?></span>
            <span class="post-date"><?= e($formatPostDate($fecha)) ?></span>
        </div>

        <h1 class="post-title"><?= e($postTitle) ?></h1>

        <div class="post-share">
            <span class="post-share-label"><?= e(t('post.share')) ?></span>
            <a class="post-share-btn share-whatsapp" target="_blank" rel="noopener" aria-label="<?= e(t('post.share_whatsapp')) ?>"
               href="<?= e(whatsapp_link('', $shareText . ' ' . $shareUrl)) ?>">
                <svg viewBox="0 0 24 24"><path d="M17.5 14.4c-.3-.1-1.7-.8-2-.9-.3-.1-.5-.1-.7.1-.2.3-.8.9-.9 1.1-.2.2-.3.2-.6.1-.3-.1-1.2-.4-2.3-1.4-.9-.8-1.4-1.7-1.6-2-.2-.3 0-.5.1-.6.1-.1.3-.3.4-.5.1-.2.2-.3.3-.5.1-.2 0-.4 0-.5C10 9 9.6 8 9.4 7.6c-.2-.4-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.2.3-.9.9-.9 2.1 0 1.2.9 2.4 1 2.6.1.2 1.8 2.8 4.4 3.8.6.3 1.1.4 1.5.6.6.2 1.2.2 1.6.1.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.3-.2-.6-.3zM12 2C6.5 2 2 6.5 2 12c0 1.9.5 3.7 1.5 5.2L2 22l4.9-1.4C8.4 21.5 10.2 22 12 22c5.5 0 10-4.5 10-10S17.5 2 12 2zm0 18c-1.7 0-3.3-.5-4.6-1.3l-.3-.2-3 .8.8-2.9-.2-.3C3.9 15 3.5 13.5 3.5 12c0-4.7 3.8-8.5 8.5-8.5s8.5 3.8 8.5 8.5-3.8 8.5-8.5 8.5z"/></svg>
            </a>
            <a class="post-share-btn share-facebook" target="_blank" rel="noopener" aria-label="<?= e(t('post.share_facebook')) ?>"
               href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($shareUrl) ?>">
                <svg viewBox="0 0 24 24"><path d="M22 12a10 10 0 1 0-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.7-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.4v7A10 10 0 0 0 22 12z"/></svg>
            </a>
            <button type="button" class="post-share-btn share-copy" id="postCopyLink" aria-label="<?= e(t('post.copy_link')) ?>" data-url="<?= e($shareUrl) ?>">
                <svg viewBox="0 0 24 24"><path d="M17 7h-4a5 5 0 0 0 0 10h4a5 5 0 0 0 0-10zm-10 5a5 5 0 0 1 5-5h1v2H9a3 3 0 1 0 0 6h1v2H8a5 5 0 0 1-5-5zm10 3h-1v-2h1a3 3 0 1 0 0-6h-1V5h1a5 5 0 0 1 0 10z"/></svg>
            </button>
            <span class="post-copy-feedback" id="postCopyFeedback"><?= e(t('post.copied')) ?></span>
        </div>

        <article class="post-content">
            <?= $postContent /* contenido cargado por un usuario del dashboard via TinyMCE, no input publico */ ?>
        </article>
    </div>
</main>

<?php if (!empty($related)): ?>
<section class="lp-section lp-section-alt">
    <div class="lp-container">
        <div class="lp-section-head reveal">
            <span class="lp-tag"><?= e(t('posts.tag')) ?></span>
            <h2><?= e(t('post.more')) ?></h2>
        </div>
        <div class="post-related-grid">
            <?php foreach ($related as $rp): ?>
            <?php
                $rpTitle = localized_value($rp, 'title');
                $rpExcerpt = localized_value($rp, 'excerpt');
                $rpCategory = localized_value($rp, 'category');
            ?>
            <a href="<?= e(lurl('/publicacion?slug=' . $rp['slug'])) ?>" class="lp-news-card reveal">
                <?php if (!empty($rp['image_path'])): ?>
                    <div class="lp-news-image" style="background-image:url('<?= e(url('/' . $rp['image_path'])) ?>')"></div>
                <?php else: ?>
                    <div class="lp-news-image lp-gallery-placeholder lp-gallery-placeholder-<?= ((int)$rp['id'] % 6) + 1 ?>"></div>
                <?php endif; ?>
                <div class="lp-news-body">
                    <span><?= e($rpCategory !== '' ? $rpCategory : t('post.general')) ?></span>
                    <h3><?= e($rpTitle) ?></h3>
                    <p><?= e($rpExcerpt) ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>

<script>
(function () {
    const btn = document.getElementById('postCopyLink');
    const feedback = document.getElementById('postCopyFeedback');
    if (!btn) return;
    btn.addEventListener('click', function () {
        const url = btn.dataset.url;
        const mostrarFeedback = function () {
            feedback.classList.add('is-visible');
            setTimeout(function () { feedback.classList.remove('is-visible'); }, 2000);
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(mostrarFeedback);
        } else {
            const temp = document.createElement('textarea');
            temp.value = url;
            temp.style.position = 'fixed';
            temp.style.opacity = '0';
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
            mostrarFeedback();
        }
    });
})();
</script>
