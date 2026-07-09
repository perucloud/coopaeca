<?php
$headerLogo = !empty($settings['header_logo_path']) ? url('/' . $settings['header_logo_path']) : asset('img/logo-ccopaeca.png');
$footerLogo = !empty($settings['footer_logo_path']) ? url('/' . $settings['footer_logo_path']) : asset('img/logowhite.png');
$activeLandingNav = 'nosotros';

// Aplana todas las imagenes con su album, para el filtro por album en el cliente
$todasLasImagenes = [];
foreach ($galerias as $g) {
    foreach ($imagenesPorAlbum[(int)$g['id']] ?? [] as $img) {
        $todasLasImagenes[] = ['album' => $g['title'], 'album_slug' => $g['slug']] + $img;
    }
}
?>

<?php require __DIR__ . '/partials/header.php'; ?>

<style>
    /* Paleta pastel del modulo Galeria (independiente del verde oscuro del resto del sitio) */
    .gal-page {
        --gal-sage-bg: #eaf3e6; --gal-sage: #7fa88c; --gal-sage-dark: #5c8a6c;
        --gal-peach-bg: #fdece2; --gal-peach: #eab692; --gal-peach-dark: #d99270;
        --gal-sand-bg: #f9f0dc; --gal-sand: #cba86a; --gal-sand-dark: #b6904e;
        --gal-sky-bg: #e6eff6; --gal-sky: #8bafc9; --gal-sky-dark: #6c96b3;
        --gal-rose-bg: #f8e8ec; --gal-rose: #cf9aad; --gal-rose-dark: #bd7d94;
    }
    .gal-hero {
        padding: 68px 0 28px; text-align: center;
        background: linear-gradient(135deg, #f3f7ee 0%, #fdf3ec 55%, #f7eee3 100%);
    }
    .gal-hero h1 { color: #3d3527; }
    .gal-hero p { color: #7a7160; max-width: 520px; margin-left: auto; margin-right: auto; }
    .gal-filters { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; margin: 28px 0 8px; }
    .gal-filter-btn {
        border: 1px solid transparent; background: #fff; color: #6b6355; padding: 10px 22px; border-radius: 999px;
        font-weight: 700; font-size: 13.5px; cursor: pointer; transition: all .2s ease; box-shadow: 0 2px 8px rgba(60,50,30,.06);
    }
    .gal-filter-btn:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(60,50,30,.12); }
    .gal-filter-btn[data-filter="todos"] { background: #3d3527; color: #fff; }
    .gal-filter-btn[data-filter="todos"].is-active,
    .gal-filter-btn[data-filter="todos"]:hover { background: #2a251b; color: #fff; }

    /* Cada album rota por una de 5 combinaciones pastel, para que el filtro se sienta variado y prolijo */
    .gal-filter-btn:nth-of-type(5n+2) { background: var(--gal-sage-bg); color: var(--gal-sage-dark); }
    .gal-filter-btn:nth-of-type(5n+2).is-active { background: var(--gal-sage); color: #fff; }
    .gal-filter-btn:nth-of-type(5n+3) { background: var(--gal-peach-bg); color: var(--gal-peach-dark); }
    .gal-filter-btn:nth-of-type(5n+3).is-active { background: var(--gal-peach); color: #fff; }
    .gal-filter-btn:nth-of-type(5n+4) { background: var(--gal-sand-bg); color: var(--gal-sand-dark); }
    .gal-filter-btn:nth-of-type(5n+4).is-active { background: var(--gal-sand); color: #fff; }
    .gal-filter-btn:nth-of-type(5n+5) { background: var(--gal-sky-bg); color: var(--gal-sky-dark); }
    .gal-filter-btn:nth-of-type(5n+5).is-active { background: var(--gal-sky); color: #fff; }
    .gal-filter-btn:nth-of-type(5n+6) { background: var(--gal-rose-bg); color: var(--gal-rose-dark); }
    .gal-filter-btn:nth-of-type(5n+6).is-active { background: var(--gal-rose); color: #fff; }

    .gal-masonry { columns: 4 220px; column-gap: 18px; padding: 32px 0 64px; }
    .gal-item {
        break-inside: avoid; margin-bottom: 18px; position: relative; border-radius: 16px; overflow: hidden;
        cursor: zoom-in; padding: 7px; background: var(--gal-sage-bg);
        box-shadow: 0 10px 26px rgba(60,50,30,.09); transition: transform .25s ease, box-shadow .25s ease;
    }
    .gal-item:nth-of-type(5n+2) { background: var(--gal-peach-bg); }
    .gal-item:nth-of-type(5n+3) { background: var(--gal-sand-bg); }
    .gal-item:nth-of-type(5n+4) { background: var(--gal-sky-bg); }
    .gal-item:nth-of-type(5n+5) { background: var(--gal-rose-bg); }
    .gal-item:hover { transform: translateY(-4px); box-shadow: 0 16px 34px rgba(60,50,30,.16); }
    .gal-item img { width: 100%; display: block; border-radius: 10px; }
    .gal-item .gal-caption {
        position: absolute; left: 7px; right: 7px; bottom: 7px; padding: 14px 12px 10px; border-radius: 0 0 10px 10px;
        background: linear-gradient(0deg, rgba(20,15,10,.72), transparent);
        color: #fff; font-size: 13px; font-weight: 500; opacity: 0; transition: opacity .2s ease;
    }
    .gal-item:hover .gal-caption { opacity: 1; }
    .gal-item.is-hidden { display: none; }
    .gal-empty { text-align: center; padding: 60px 0; color: #9a9384; }

    .gal-lightbox {
        position: fixed; inset: 0; z-index: 400; background: rgba(20,18,14,.94);
        display: none; align-items: center; justify-content: center; padding: 24px;
    }
    .gal-lightbox.is-open { display: flex; }
    .gal-lightbox img { max-width: 92vw; max-height: 82vh; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,.5); }
    .gal-lightbox-caption { position: absolute; bottom: 28px; left: 0; right: 0; text-align: center; color: #f2efe6; font-size: 14px; }
    .gal-lightbox-close, .gal-lightbox-nav {
        position: absolute; border: none; background: rgba(255,255,255,.14); color: #fff; cursor: pointer;
        width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
        font-size: 20px; transition: background .2s ease;
    }
    .gal-lightbox-close:hover, .gal-lightbox-nav:hover { background: rgba(255,255,255,.28); }
    .gal-lightbox-close { top: 20px; right: 20px; }
    .gal-lightbox-prev { left: 20px; top: 50%; transform: translateY(-50%); }
    .gal-lightbox-next { right: 20px; top: 50%; transform: translateY(-50%); }

    @media (max-width: 900px) { .gal-masonry { columns: 2 180px; } }
    @media (max-width: 520px) { .gal-masonry { columns: 1; } }
</style>

<main class="gal-page">
    <section class="gal-hero lp-section">
        <div class="lp-container">
            <span class="lp-tag"><?= e(t('gallery.tag')) ?></span>
            <h1><?= e(t('gallery.title')) ?></h1>
            <p><?= e(t('gallery.text')) ?></p>

            <?php if ($galerias): ?>
            <div class="gal-filters" id="galFilters">
                <button type="button" class="gal-filter-btn is-active" data-filter="todos"><?= e(t('gallery.all')) ?></button>
                <?php foreach ($galerias as $g): ?>
                <button type="button" class="gal-filter-btn" data-filter="<?= e($g['slug']) ?>"><?= e($g['title']) ?></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="lp-container">
        <?php if ($todasLasImagenes): ?>
        <div class="gal-masonry" id="galMasonry">
            <?php foreach ($todasLasImagenes as $i => $img): ?>
            <figure class="gal-item" data-album="<?= e($img['album_slug']) ?>"
                    data-src="<?= e(url('/' . $img['disk_path'])) ?>"
                    data-caption="<?= e($img['caption'] ?: $img['album']) ?>" data-index="<?= (int)$i ?>">
                <img src="<?= e(url('/' . $img['disk_path'])) ?>" alt="<?= e($img['caption'] ?: $img['original_name']) ?>" loading="lazy">
                <figcaption class="gal-caption"><?= e($img['caption'] ?: $img['album']) ?></figcaption>
            </figure>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="gal-empty">
            <p><?= e(t('gallery.empty')) ?></p>
        </div>
        <?php endif; ?>
    </section>
</main>

<div class="gal-lightbox" id="galLightbox">
    <button type="button" class="gal-lightbox-close" id="galLbClose">&times;</button>
    <button type="button" class="gal-lightbox-nav gal-lightbox-prev" id="galLbPrev">&#8249;</button>
    <img src="" alt="" id="galLbImg">
    <button type="button" class="gal-lightbox-nav gal-lightbox-next" id="galLbNext">&#8250;</button>
    <div class="gal-lightbox-caption" id="galLbCaption"></div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>

<script>
(function () {
    const filtros = document.querySelectorAll('.gal-filter-btn');
    const items = Array.from(document.querySelectorAll('.gal-item'));
    const empty = document.querySelector('.gal-empty');

    filtros.forEach(function (btn) {
        btn.addEventListener('click', function () {
            filtros.forEach(b => b.classList.remove('is-active'));
            btn.classList.add('is-active');
            const filtro = btn.dataset.filter;
            let visibles = 0;
            items.forEach(function (item) {
                const mostrar = filtro === 'todos' || item.dataset.album === filtro;
                item.classList.toggle('is-hidden', !mostrar);
                if (mostrar) visibles++;
            });
        });
    });

    // Lightbox
    const lightbox = document.getElementById('galLightbox');
    const lbImg = document.getElementById('galLbImg');
    const lbCaption = document.getElementById('galLbCaption');
    let visiblesActuales = [];
    let indiceActual = 0;

    function itemsVisibles() {
        return items.filter(i => !i.classList.contains('is-hidden'));
    }

    function abrir(item) {
        visiblesActuales = itemsVisibles();
        indiceActual = visiblesActuales.indexOf(item);
        mostrarActual();
        lightbox.classList.add('is-open');
    }

    function mostrarActual() {
        const item = visiblesActuales[indiceActual];
        if (!item) return;
        lbImg.src = item.dataset.src;
        lbCaption.textContent = item.dataset.caption || '';
    }

    items.forEach(function (item) {
        item.addEventListener('click', function () { abrir(item); });
    });

    document.getElementById('galLbClose').addEventListener('click', () => lightbox.classList.remove('is-open'));
    lightbox.addEventListener('click', function (e) { if (e.target === lightbox) lightbox.classList.remove('is-open'); });
    document.getElementById('galLbPrev').addEventListener('click', function () {
        indiceActual = (indiceActual - 1 + visiblesActuales.length) % visiblesActuales.length;
        mostrarActual();
    });
    document.getElementById('galLbNext').addEventListener('click', function () {
        indiceActual = (indiceActual + 1) % visiblesActuales.length;
        mostrarActual();
    });
    document.addEventListener('keydown', function (e) {
        if (!lightbox.classList.contains('is-open')) return;
        if (e.key === 'Escape') lightbox.classList.remove('is-open');
        if (e.key === 'ArrowLeft') document.getElementById('galLbPrev').click();
        if (e.key === 'ArrowRight') document.getElementById('galLbNext').click();
    });
})();
</script>
